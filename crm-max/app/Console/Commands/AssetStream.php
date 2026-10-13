<?php

namespace App\Console\Commands;

use App\Services\AssetService;
use App\Services\PriceWatch;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Ratchet\Client\Connector;
use Ratchet\Client\WebSocket;
use React\EventLoop\Loop;
use React\EventLoop\LoopInterface;

/**
 * Keeps one live connection to the price provider's stream and stores each price as it ticks,
 * through the same validator and compare-and-set write as the minute poll (AssetService).
 *
 * The provider speaks Socket.IO v4 over a WebSocket: it opens with "0{...}", we sign in with
 * "40{apiKey}", it confirms with "40", we send 42["subscribe:all"], and prices arrive as
 * 42["data:update", rows]. It pings with "2", answered "3". Runs under a process manager (a
 * Forge daemon), reconnects with backoff, and exits on `php artisan queue:restart` or after
 * --max-time so a fresh process picks up new code. While it is healthy, app:asset-updater
 * polls only once a minute; if it stops, that poll goes back to once a second.
 */
class AssetStream extends Command
{
    protected $signature = 'app:asset-stream {--max-time=3600 : Exit after this many seconds so the process manager starts a fresh one}';

    protected $description = 'Keep a live connection to the price provider and store each price as it ticks.';

    public const HEARTBEAT_KEY = 'price_stream:heartbeat';

    /** The stream counts as healthy while it delivered prices this recently. */
    public const HEALTHY_SECONDS = 15;

    /** Ticks are gathered and written together this often, latest per instrument. */
    private const FLUSH_SECONDS = 0.5;

    /** No packet at all for this long (the provider pings every 25 s) means a dead connection. */
    private const SILENCE_SECONDS = 60;

    /** @var array<string, array> */
    private array $buffer = [];

    private ?WebSocket $connection = null;

    private int $attempt = 0;

    private float $lastPacketAt = 0;

    private ?int $lastPricesAt = null;

    /** Set once the process is shutting down on purpose, so the close is not logged or retried. */
    private bool $stopping = false;

    public static function healthy(): bool
    {
        $heartbeat = Cache::get(self::HEARTBEAT_KEY);

        return $heartbeat !== null && (int) $heartbeat >= now()->getTimestamp() - self::HEALTHY_SECONDS;
    }

    public function handle(AssetService $assets): int
    {
        $loop = Loop::get();
        $restartSignal = Cache::get('illuminate:queue:restart');

        $this->connect($loop);

        $loop->addPeriodicTimer(self::FLUSH_SECONDS, fn () => $this->flush($assets));
        $loop->addPeriodicTimer(5, function () use ($loop, $restartSignal) {
            if (Cache::get('illuminate:queue:restart') !== $restartSignal) {
                $this->info('Restart signal received.');
                $loop->stop();

                return;
            }
            if ($this->connection && microtime(true) - $this->lastPacketAt > self::SILENCE_SECONDS) {
                Log::channel('price')->warning('price_stream.silent', ['seconds' => self::SILENCE_SECONDS]);
                $this->connection->close();
            }
        });
        $loop->addTimer((int) $this->option('max-time'), fn () => $loop->stop());

        $loop->run();

        $this->stopping = true;
        $this->flush($assets);
        $this->connection?->close();

        return self::SUCCESS;
    }

    /** Handles one Socket.IO packet; $send writes a packet back on the same connection. */
    public function handlePacket(string $packet, callable $send): void
    {
        $this->lastPacketAt = microtime(true);

        if ($packet === '2') {
            $send('3');
        } elseif (str_starts_with($packet, '0')) {
            $send('40'.json_encode(['apiKey' => (string) config('app.asset_api_key')]));
        } elseif (str_starts_with($packet, '40')) {
            $this->attempt = 0;
            $send('42'.json_encode(['subscribe:all']));
            Log::channel('price')->info('price_stream.connected');
        } elseif (str_starts_with($packet, '44')) {
            Log::channel('price')->error('price_stream.refused', ['reason' => substr($packet, 2, 300)]);
        } elseif (str_starts_with($packet, '42')) {
            [$event, $payload] = array_pad((array) json_decode(substr($packet, 2), true), 2, null);
            if (in_array($event, ['data:all', 'data:update'], true) && is_array($payload)) {
                $rows = array_is_list($payload) ? $payload : ($payload['data'] ?? [$payload]);
                foreach ($rows as $row) {
                    if (is_array($row) && isset($row['id'], $row['price'])) {
                        $this->buffer[$row['id']] = $row;
                    }
                }
                $this->lastPricesAt = now()->getTimestamp();
            }
        }
    }

    /** Writes the gathered ticks and marks the stream healthy while prices keep arriving. */
    public function flush(AssetService $assets): void
    {
        if ($this->lastPricesAt !== null) {
            Cache::put(self::HEARTBEAT_KEY, $this->lastPricesAt, 120);
        }

        if ($this->buffer === []) {
            return;
        }

        $rows = array_values($this->buffer);
        $this->buffer = [];

        $result = rescue(fn () => $assets->applyFeedRows($rows, 'stream', partial: true));

        // TP/SL and pending orders on the instruments that just moved, tick by tick.
        rescue(fn () => app(PriceWatch::class)->check($result['updated_asset_ids'] ?? []));
    }

    private function connect(LoopInterface $loop): void
    {
        (new Connector($loop))(self::streamUrl())->then(
            function (WebSocket $connection) use ($loop) {
                $this->connection = $connection;
                $this->lastPacketAt = microtime(true);
                $connection->on('message', fn ($message) => $this->handlePacket((string) $message, fn (string $packet) => $connection->send($packet)));
                $connection->on('close', function ($code = null, $reason = null) use ($loop) {
                    $this->connection = null;
                    if ($this->stopping) {
                        return;
                    }
                    Log::channel('price')->warning('price_stream.closed', ['code' => $code, 'reason' => $reason]);
                    $this->reconnect($loop);
                });
            },
            function (\Throwable $error) use ($loop) {
                Log::channel('price')->warning('price_stream.connect_failed', ['message' => $error->getMessage()]);
                $this->reconnect($loop);
            },
        );
    }

    private function reconnect(LoopInterface $loop): void
    {
        $delay = min(30, 2 ** min($this->attempt++, 5));
        $loop->addTimer($delay, fn () => $this->connect($loop));
    }

    /** The provider's Socket.IO endpoint, on the same host as its REST API. */
    public static function streamUrl(): string
    {
        $base = parse_url((string) config('app.asset_api_base_url'));
        $scheme = ($base['scheme'] ?? 'https') === 'http' ? 'ws' : 'wss';
        $port = isset($base['port']) ? ':'.$base['port'] : '';

        return "{$scheme}://{$base['host']}{$port}/socket.io/?EIO=4&transport=websocket";
    }
}

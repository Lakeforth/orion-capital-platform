<?php

namespace App\Services;

use App\Models\Asset;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Price history for the MT4 trade-room chart, drawn on the broker's Bid like MT4.
 *
 * Raw candles come straight from the upstream providers the price service already
 * uses -- OANDA for forex/metals/commodities/indices (needs services.oanda.key) and
 * CEX.IO's public API for crypto -- cached briefly per instrument and timeframe.
 * Each candle is then scaled by this customer's current Bid/rate ratio, which is
 * exactly how Asset::sellPriceFor() turns the rate into their Bid (a percentage
 * spread, or no adjustment on the legacy one-sided model).
 */
class CandleService
{
    /** MT4 timeframe => [OANDA granularity, CEX.IO resolution, cache seconds]. */
    public const TIMEFRAMES = [
        'M1' => ['M1', '1m', 20],
        'M5' => ['M5', '5m', 60],
        'M15' => ['M15', '15m', 60],
        'M30' => ['M30', '30m', 120],
        'H1' => ['H1', '1h', 120],
        'H4' => ['H4', '4h', 300],
        'D1' => ['D', '1d', 600],
    ];

    private const SECONDS = ['M1' => 60, 'M5' => 300, 'M15' => 900, 'M30' => 1800, 'H1' => 3600, 'H4' => 14400, 'D1' => 86400];

    public function supports(Asset $asset): bool
    {
        return $this->source($asset) !== null;
    }

    /**
     * @return list<array{time:int, open:float, high:float, low:float, close:float}>|null
     *         null when the asset has no history source (e.g. stocks) or the provider failed.
     */
    public function candles(Asset $asset, string $timeframe, ?User $user, int $count = 300): ?array
    {
        [$source, $instrument] = $this->source($asset) ?? [null, null];
        if (! $source || ! isset(self::TIMEFRAMES[$timeframe])) {
            return null;
        }
        $count = max(10, min($count, 500));
        $ttl = self::TIMEFRAMES[$timeframe][2];
        $key = "candles:{$source}:{$instrument}:{$timeframe}:{$count}";

        $raw = Cache::get($key);
        if ($raw === null) {
            $raw = $source === 'oanda' ? $this->oanda($instrument, $timeframe, $count) : $this->cexio($instrument, $timeframe, $count);
            if ($raw === null) {
                return null;
            }
            Cache::put($key, $raw, $ttl);
        }

        $rate = (float) $asset->rate;
        $ratio = $rate > 0 ? $asset->sellPriceFor($user) / $rate : 1.0;

        return array_map(fn (array $c) => [
            'time' => $c[0],
            'open' => round($c[1] * $ratio, 6),
            'high' => round($c[2] * $ratio, 6),
            'low' => round($c[3] * $ratio, 6),
            'close' => round($c[4] * $ratio, 6),
        ], $raw);
    }

    /** @return array{0:string, 1:string}|null [provider, instrument] */
    private function source(Asset $asset): ?array
    {
        $symbol = strtoupper((string) ($asset->sym_key ?: $asset->name));
        $letters = preg_replace('/[^A-Z0-9]/', '', $symbol);

        return match ($asset->type) {
            // OANDA instruments: EUR_USD; metals/commodities/indices are already named that way (XAU_USD, SPX500_USD).
            'forex' => strlen($letters) === 6 && config('services.oanda.key') ? ['oanda', substr($letters, 0, 3).'_'.substr($letters, 3)] : null,
            'metals', 'commodities', 'indices' => str_contains($symbol, '_') && config('services.oanda.key') ? ['oanda', $symbol] : null,
            // CEX.IO pairs: BTCUSD -> BTC-USD.
            'crypto' => str_ends_with($letters, 'USD') && strlen($letters) > 3 ? ['cexio', substr($letters, 0, -3).'-USD'] : null,
            default => null,
        };
    }

    /** @return list<array{0:int,1:float,2:float,3:float,4:float}>|null */
    private function oanda(string $instrument, string $timeframe, int $count): ?array
    {
        $response = Http::withToken((string) config('services.oanda.key'))->timeout(8)
            ->get(rtrim((string) config('services.oanda.url'), '/')."/instruments/{$instrument}/candles", [
                'granularity' => self::TIMEFRAMES[$timeframe][0],
                'count' => $count,
                'price' => 'M',
            ]);
        if (! $response->successful()) {
            report(new \RuntimeException("OANDA candles {$instrument} {$timeframe}: HTTP {$response->status()}"));

            return null;
        }

        return collect($response->json('candles', []))->map(fn ($c) => [
            (int) strtotime($c['time']),
            (float) $c['mid']['o'], (float) $c['mid']['h'], (float) $c['mid']['l'], (float) $c['mid']['c'],
        ])->values()->all();
    }

    /** @return list<array{0:int,1:float,2:float,3:float,4:float}>|null */
    private function cexio(string $pair, string $timeframe, int $count): ?array
    {
        $from = now()->subSeconds(self::SECONDS[$timeframe] * $count)->utc()->format('Y-m-d\TH:i:s\Z');
        $response = Http::timeout(8)->post('https://trade.cex.io/api/spot/rest-public/get_candles', [
            'pair' => $pair,
            'resolution' => self::TIMEFRAMES[$timeframe][1],
            'dataType' => 'bestBid',
            'fromISO' => $from,
            'limit' => $count,
        ]);
        if (! $response->successful() || $response->json('ok') !== 'ok') {
            report(new \RuntimeException("CEX.IO candles {$pair} {$timeframe}: HTTP {$response->status()}"));

            return null;
        }

        return collect($response->json('data', []))
            ->filter(fn ($c) => isset($c['timestamp'], $c['open']))
            ->sortBy('timestamp')
            ->map(fn ($c) => [intdiv((int) $c['timestamp'], 1000), (float) $c['open'], (float) $c['high'], (float) $c['low'], (float) $c['close']])
            ->values()->all();
    }
}

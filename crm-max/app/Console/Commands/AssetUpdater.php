<?php

namespace App\Console\Commands;

use App\Services\AssetService;
use App\Services\PriceWatch;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class AssetUpdater extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:asset-updater {--loop : Keep refreshing once a second until this minute is nearly over}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * With --loop, no poll starts after this second of the minute, leaving room for a slow last
     * poll to finish before the next minute's run (which would otherwise be skipped as overlapping).
     */
    private const LOOP_UNTIL_SECOND = 55;

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $assetService = new AssetService;

        if (! $this->option('loop')) {
            $res = $assetService->updateAssetPrices();
            rescue(fn () => app(PriceWatch::class)->check($res['updated_asset_ids'] ?? []));

            print_r($res);
            $this->info('done');

            return;
        }

        // One process per minute polling once a second: each instrument is written as soon as
        // its provider tick moves on, without booting the app for every poll. The per-poll
        // summary lines are skipped; one summary is logged for the minute.
        $until = now()->startOfMinute()->addSeconds(self::LOOP_UNTIL_SECOND);
        $polls = 0;
        $updated = 0;

        while (now()->lessThan($until)) {
            $started = microtime(true);

            // While the live stream (app:asset-stream) is delivering, it writes each tick itself;
            // one poll a minute still refreshes the 24-hour figures the stream does not carry.
            // If the stream stops, polling resumes within a second.
            if ($polls === 0 || ! AssetStream::healthy()) {
                // A failed poll (provider down) is reported and the next second tries again.
                $res = rescue(fn () => $assetService->updateAssetPrices(quiet: true), []);
                $updated += (int) ($res['updated'] ?? 0);
                $polls++;

                // TP/SL and pending orders on the instruments whose price just changed.
                rescue(fn () => app(PriceWatch::class)->check($res['updated_asset_ids'] ?? []));
            }

            $wait = 1 - (microtime(true) - $started);
            if ($wait > 0) {
                usleep((int) ($wait * 1_000_000));
            }
        }

        Log::info("Asset prices: {$updated} updates over {$polls} polls this minute.");
        $this->info("done: {$updated} updates over {$polls} polls");
    }
}

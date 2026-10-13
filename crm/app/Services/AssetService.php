<?php

namespace App\Services;

use App\Models\Asset;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AssetService
{
    protected PriceValidator $priceValidator;

    public function __construct(?PriceValidator $priceValidator = null)
    {
        // Instantiated with `new AssetService` in a dozen places rather than
        // resolved from the container, so the dependency is optional.
        $this->priceValidator = $priceValidator ?? new PriceValidator;
    }

    private function getAssetApiBaseUrl(): string
    {
        return rtrim((string) config('app.asset_api_base_url', 'https://your-market-data-host.example/api'), '/');
    }

    private function getAssetApiKey(): string
    {
        return (string) config('app.asset_api_key');
    }

    private function getAssetListUrl(): string
    {
        return $this->getAssetApiBaseUrl().'/assets?apiKey='.$this->getAssetApiKey();
    }

    private function getAssetByIdUrl(string $id): string
    {
        return $this->getAssetApiBaseUrl().'/asset/'.$id.'?apiKey='.$this->getAssetApiKey();
    }

    private function normalizeComparableSymbol(string $symbol, ?string $category = null): string
    {
        $normalized = strtoupper(trim($symbol));

        if (in_array((string) $category, ['crypto', 'forex', 'indices', 'commodities', 'metals'], true)) {
            $normalized = str_replace(['_', '-', '/', ' '], '', $normalized);
        }

        return $normalized;
    }

    private function buildApiSymbolIndex(array $assetData): array
    {
        $apiSymbolIndex = [];

        foreach ($assetData as $feedAsset) {
            if (! isset($feedAsset['symbol'], $feedAsset['category'])) {
                continue;
            }

            $category = (string) $feedAsset['category'];
            $normalizedSymbol = $this->normalizeComparableSymbol((string) $feedAsset['symbol'], $category);
            $apiSymbolIndex[$category.'|'.$normalizedSymbol] = true;
        }

        return $apiSymbolIndex;
    }

    /**
     * Switch back on anything the feed is listing again.
     *
     * The counterpart to disableMissingAssetsFromFeed, and the half that was
     * missing. Absence from the feed disabled an asset, but the only thing that
     * ever re-enabled one was a price tick being accepted -- so a feed gap
     * disabled instruments on a rule they could not come back under. A gap on
     * 25 July 2026 switched off 125 forex, commodities and indices rows across
     * every tenant and they stayed off for eleven weeks, because a rejected or
     * non-advancing tick skips the write that would have restored them.
     *
     * Presence in the feed is now what decides the flag, in both directions.
     */
    private function enablePresentAssetsFromFeed(array $assetData): array
    {
        $apiSymbolIndex = $this->buildApiSymbolIndex($assetData);

        if ($apiSymbolIndex === []) {
            // An empty or failed feed read must not be taken as "everything is
            // back" any more than as "everything is gone".
            return ['restored_count' => 0];
        }

        $restorable = Asset::query()
            ->where('is_provider_active', false)
            ->whereIn('type', ['crypto', 'stocks', 'forex', 'indices', 'commodities', 'metals'])
            ->whereNotNull('sym_key')
            ->get(['id', 'sym_key', 'type'])
            ->filter(function ($asset) use ($apiSymbolIndex) {
                $category = (string) $asset->type;
                $normalizedSymbol = $this->normalizeComparableSymbol((string) $asset->sym_key, $category);

                return isset($apiSymbolIndex[$category.'|'.$normalizedSymbol]);
            })
            ->values();

        $restoredCount = 0;

        if ($restorable->isNotEmpty()) {
            $restoredCount = Asset::query()
                ->whereIn('id', $restorable->pluck('id'))
                ->update(['is_provider_active' => true]);

            Log::info('Assets restored from feed', [
                'restored_count' => $restoredCount,
                'by_type' => $restorable->groupBy('type')->map->count()->all(),
            ]);
        }

        return ['restored_count' => $restoredCount];
    }

    /**
     * Above this share of a category missing from one feed read, assume the
     * feed is wrong rather than the instruments gone.
     */
    private const MAX_MISSING_SHARE = 0.5;

    private function disableMissingAssetsFromFeed(array $assetData): array
    {
        $apiSymbolIndex = $this->buildApiSymbolIndex($assetData);

        $missingAssets = Asset::query()
            ->where('is_provider_active', true)
            ->whereIn('type', ['crypto', 'stocks', 'forex', 'indices', 'commodities', 'metals'])
            ->whereNotNull('sym_key')
            ->get(['id', 'sym', 'sym_key', 'coin_id', 'type', 'name'])
            ->filter(function ($asset) use ($apiSymbolIndex) {
                $category = (string) $asset->type;
                $normalizedSymbol = $this->normalizeComparableSymbol((string) $asset->sym_key, $category);

                return ! isset($apiSymbolIndex[$category.'|'.$normalizedSymbol]);
            })
            ->values();

        $missingCount = $missingAssets->count();
        $disabledCount = 0;

        // A feed gap is not a delisting. The list this is compared against
        // varies with the session -- most US stocks drop out of it at a weekend
        // -- so a snapshot taken at the wrong moment reads as "these instruments
        // are gone" when they are merely shut for the night.
        //
        // If most of a category is missing at once, that is the feed having a
        // bad moment, not a broker delisting its entire forex book. Those
        // categories are left alone and logged. This is exactly what happened
        // on 25 July 2026: every forex, commodities and indices row vanished
        // from one read and stayed switched off for eleven weeks.
        $byType = $missingAssets->groupBy('type');
        $skipped = [];

        foreach ($byType as $type => $rows) {
            $total = Asset::query()->where('type', $type)->whereNotNull('sym_key')->count();
            if ($total > 0 && $rows->count() / $total > self::MAX_MISSING_SHARE) {
                $skipped[$type] = $rows->count().'/'.$total;
                $missingAssets = $missingAssets->reject(fn ($asset) => (string) $asset->type === (string) $type)->values();
            }
        }

        if ($skipped !== []) {
            Log::warning('Feed is missing most of a category; leaving those assets enabled', [
                'skipped' => $skipped,
                'api_base_url' => $this->getAssetApiBaseUrl(),
            ]);
        }

        $missingCount = $missingAssets->count();

        if ($missingCount > 0) {
            $disabledCount = Asset::query()
                ->whereIn('id', $missingAssets->pluck('id'))
                ->update(['is_provider_active' => false]);
        }

        $reportPath = null;
        if ($disabledCount > 0) {
            $reportDir = storage_path('app/reports');
            if (! is_dir($reportDir)) {
                mkdir($reportDir, 0755, true);
            }

            $reportPath = $reportDir.'/missing-assets-'.now()->format('Ymd_His').'.json';
            file_put_contents($reportPath, json_encode([
                'generated_at' => now()->toDateTimeString(),
                'api_base_url' => $this->getAssetApiBaseUrl(),
                'missing_count' => $missingCount,
                'disabled_count' => $disabledCount,
                'assets' => $missingAssets->toArray(),
            ], JSON_PRETTY_PRINT));
        }

        return [
            'count' => $missingCount,
            'disabled' => $disabledCount,
            'reportPath' => $reportPath,
        ];
    }

    /**
     * Refreshes one asset's price on demand (trade open, investment payout,
     * admin asset view) and returns the price the application should now be
     * using -- which is the stored rate, not the raw provider value. If the
     * incoming quote is rejected the caller gets the last good price rather
     * than the bad one, which is the whole point: before this, a rejected
     * tick was still handed back to the caller to trade on.
     */
    public function updateRate(Asset $asset): float
    {
        $quote = $this->fetchQuote($asset->coin_id);

        if ($quote !== null) {
            $this->applyQuote($asset, $quote['price'], $quote['tick_at'], 'on_demand');
            // Stored even when the price is refused: a closed market repeats its last tick.
            $asset->forceFill(['market_session' => $quote['session'], 'market_session_at' => now()])->save();
        }

        return (float) $asset->rate;
    }

    /**
     * Validates a quote against the asset's current state and, if it passes,
     * writes it along with its provenance. Every path that changes a price
     * goes through here -- there is no other place in the application allowed
     * to assign Asset::$rate.
     */
    public function applyQuote(Asset $asset, float $price, ?Carbon $tickAt, string $source): bool
    {
        $check = $this->priceValidator->check($asset, $price, $tickAt);

        if ($check->rejected()) {
            $this->priceValidator->logRejection($asset, $check, $source);

            return false;
        }

        $asset->rate = $price;
        $asset->price_updated_at = now();
        $asset->price_tick_at = $tickAt;
        $asset->price_source = $source;
        $asset->save();

        return true;
    }

    /** $quiet skips the per-run summary log lines (the once-a-second loop logs one per minute). */
    public function updateAssetPrices(bool $force = false, bool $quiet = false): array
    {
        global $data;
        $url = $this->getAssetListUrl();
        $response = Http::get($url);
        $assets = $response->json();
        $updated = 0;
        $n_updated = 0;
        $rejected = 0;
        $rejectionsByReason = [];

        $assets = $assets['data'] ?? null;

        if ($assets) {
            [
                'updated' => $updated, 'updated_asset_ids' => $updatedIds, 'not_updated' => $n_updated,
                'rejected' => $rejected, 'by_reason' => $rejectionsByReason,
            ] = $this->applyFeedRows($assets);

            $data['message'] = "$updated Assets updated";
            $data['updated'] = $updated;
            $data['updated_asset_ids'] = $updatedIds;

            if (! $quiet) {
                Log::info('Asset prices updated: '.$updated.' assets updated.');

                if ($n_updated > 0) {
                    Log::warning(''.$n_updated.' assets not updated during price refresh.');
                }

                if ($rejected > 0) {
                    Log::channel('price')->warning($rejected.' price updates rejected during asset refresh.', $rejectionsByReason);
                }
            }

            return $data;

        }

        // No data from the provider (an error page, an outage): nothing was written.
        return $data ?? [];

    }

    /**
     * Writes provider rows -- the REST list or the live stream -- through the price validator
     * and the compare-and-set guard, plus each row's market session. Stream rows carry no
     * 24-hour figures, so with $partial those columns are left as they are rather than zeroed.
     *
     * @return array{updated: int, not_updated: int, rejected: int, by_reason: array<string, int>}
     */
    public function applyFeedRows(array $assets, string $source = 'feed', bool $partial = false): array
    {
        $updated = 0;
        $updatedIds = [];
        $n_updated = 0;
        $rejected = 0;
        $rejectionsByReason = [];

        $currentAssets = Asset::query()
            ->whereNotNull('coin_id')
            ->get(['id', 'coin_id', 'sym', 'sym_key', 'type', 'base', 'rate', 'price_high', 'price_low', 'price_updated_at', 'price_tick_at', 'updated_at', 'market_session', 'market_session_at'])
            ->keyBy('coin_id');

        foreach ($assets as $asset) {
            $existing = $currentAssets[$asset['id']] ?? null;

            if (! $existing) {
                // Nothing on our side to price -- the daily asset sync
                // owns creating rows, not this job.
                $n_updated++;

                continue;
            }

            $newPrice = (float) ($asset['price'] ?? 0);
            $tickAt = $this->parseTickTimestamp($asset['lastUpdated'] ?? null);

            $check = $this->priceValidator->check($existing, $newPrice, $tickAt);

            if ($check->rejected()) {
                $this->priceValidator->logRejection($existing, $check, $source);
                $rejected++;
                $rejectionsByReason[$check->reason] = ($rejectionsByReason[$check->reason] ?? 0) + 1;

                continue;
            }

            $affectedRows = Asset::query()
                ->where('coin_id', $asset['id'])
                // Compare-and-set: if another writer has already stored a
                // tick at or after this one (an overlapping run, the
                // on-demand refresh at trade-open time), this update
                // silently does nothing rather than walking the price
                // backwards. The in-memory check above can only see the
                // snapshot taken at the top of this job.
                ->when($tickAt, fn ($query) => $query->where(
                    fn ($guard) => $guard->whereNull('price_tick_at')->orWhere('price_tick_at', '<', $tickAt)
                ))
                ->update(array_merge([
                    'rate' => $newPrice,
                    'is_provider_active' => true,
                    'price_updated_at' => now(),
                    'price_tick_at' => $tickAt,
                    'price_source' => $source,
                ], $this->dailyFigures($asset, $partial)));

            if ($affectedRows > 0) {
                $updated++;
                $updatedIds[] = $existing->id;
            } else {
                $n_updated++;

            }

        }

        // Market status is stored apart from the price: a closed market keeps re-sending its
        // last tick, which the validator rejects as a duplicate above, but its session must
        // still say it is closed. Written when it changes, and at least once a minute so it
        // stays recent for MarketHours; one update per distinct session value.
        collect($assets)
            ->filter(function ($asset) use ($currentAssets) {
                $existing = $currentAssets[$asset['id'] ?? null] ?? null;

                return $existing && ($existing->market_session !== ($asset['session'] ?? null)
                    || ! $existing->market_session_at || $existing->market_session_at->lessThan(now()->subMinute()));
            })
            ->groupBy(fn ($asset) => (string) ($asset['session'] ?? ''))
            ->each(fn ($group, $session) => Asset::query()
                ->whereIn('coin_id', $group->pluck('id'))
                ->update(['market_session' => $session === '' ? null : $session, 'market_session_at' => now()]));

        DB::table('settings')->updateOrInsert(['name' => 'asset_last_updated'], ['val' => Carbon::now()]);

        return ['updated' => $updated, 'updated_asset_ids' => $updatedIds, 'not_updated' => $n_updated, 'rejected' => $rejected, 'by_reason' => $rejectionsByReason];
    }

    /** The provider's 24-hour figures for a row; with $partial, only those it actually carries. */
    private function dailyFigures(array $asset, bool $partial): array
    {
        $figures = [];
        foreach (['change_percent' => 'changePercent24h', 'change' => 'change24h', 'price_low' => 'priceLow24h',
            'price_high' => 'priceHigh24h', 'volume' => 'volume24h'] as $column => $key) {
            if (! $partial || array_key_exists($key, $asset)) {
                $figures[$column] = $asset[$key] ?? 0;
            }
        }

        return $figures;
    }

    /**
     * The feed sends its own timestamp per quote (lastUpdated, ISO-8601 UTC);
     * OANDA sends `time`. That provider clock -- not arrival order -- is the
     * only way to tell a newer response from an older one that overtook it.
     *
     * Converted to the application timezone before it goes any further.
     * Eloquent's datetime cast stores wall-clock time with no offset, so a
     * UTC-aware Carbon written straight to the column comes back out read as
     * app-local -- the app runs on Africa/Lagos, which would silently shift
     * every provider timestamp by an hour and break exactly the ordering
     * comparison this field exists for.
     */
    public function parseTickTimestamp(mixed $value): ?Carbon
    {
        if (empty($value)) {
            return null;
        }

        try {
            // Truncated to the second as well: the column has no fractional
            // precision, so a timestamp carrying milliseconds would always
            // read back smaller than it was written and a genuinely repeated
            // tick would look like a new one every poll.
            return Carbon::parse($value)->setTimezone(config('app.timezone'))->startOfSecond();
        } catch (\Throwable) {
            return null;
        }
    }

    public function updateForexPrices(): array
    {
        $data = [];
        $token = config('app.onda_api_key');
        $instrumentData = $this->getForexInstruments();

        if (empty($instrumentData)) {
            $data['message'] = 'No forex instruments found';

            return $data;
        }

        if (empty($token)) {
            $data['message'] = 'Oanda API token not configured';

            return $data;
        }

        $instrumentNames = array_keys($instrumentData);
        $instrumentCsv = implode(',', $instrumentNames);

        $url = 'https://api-fxpractice.oanda.com/v3/accounts/101-004-15523510-001/pricing';
        $response = Http::withToken($token)->get($url, [
            'instruments' => $instrumentCsv,
        ]);

        if (! $response->successful()) {
            Log::error('Error getting forex prices', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            $data['message'] = 'Failed to fetch forex prices';

            return $data;
        }

        $prices = $response->json('prices', []);
        $updated = 0;
        $created = 0;
        $rejected = 0;

        foreach ($prices as $price) {
            $instrument = $price['instrument'] ?? null;
            if (empty($instrument)) {
                continue;
            }

            $rate = $this->getOandaMidPrice($price);
            if ($rate <= 0) {
                continue;
            }

            $asset = Asset::query()->where('sym_key', $instrument)->first();

            if ($asset) {
                $asset->ex_sym = $this->getOandaExSym($instrument);

                // Same gate as the feed path. This job used to be the loosest
                // writer in the application -- a bare "> 0" and a blind save.
                if (! $this->applyQuote($asset, $rate, $this->parseTickTimestamp($price['time'] ?? null), 'oanda')) {
                    $asset->save();
                    $rejected++;

                    continue;
                }

                $updated++;

                continue;
            }

            [$sym, $base] = $this->splitInstrument($instrument);
            $displayName = $instrumentData[$instrument] ?? $instrument;

            $newAsset = new Asset;
            $newAsset->sym = $sym;
            $newAsset->base = $base;
            $newAsset->sym_key = $instrument;
            $newAsset->name = $displayName;
            $newAsset->type = 'forex';
            $newAsset->is_active = true;
            $newAsset->ex_sym = $this->getOandaExSym($instrument);
            $newAsset->rate = $rate;
            $newAsset->price_updated_at = now();
            $newAsset->price_tick_at = $this->parseTickTimestamp($price['time'] ?? null);
            $newAsset->price_source = 'oanda';
            $newAsset->save();
            $created++;
        }

        $data['message'] = "{$updated} forex assets updated, {$created} created, {$rejected} rejected";
        Log::info('Forex prices updated', [
            'updated' => $updated,
            'created' => $created,
            'rejected' => $rejected,
        ]);

        return $data;
    }

    private function getForexInstruments(): array
    {
        $path = base_path('database/fx.json');
        if (! file_exists($path)) {
            Log::warning('Forex instruments file missing', ['path' => $path]);

            return [];
        }

        $raw = file_get_contents($path);
        $items = json_decode($raw, true);

        if (! is_array($items)) {
            Log::warning('Invalid forex instruments JSON', ['path' => $path]);

            return [];
        }

        $mapped = [];
        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }
            $name = $item['name'] ?? null;
            if (empty($name)) {
                continue;
            }
            $mapped[$name] = $item['displayName'] ?? $name;
        }

        return $mapped;
    }

    private function getOandaMidPrice(array $price): float
    {
        $bid = isset($price['closeoutBid']) ? (float) $price['closeoutBid'] : null;
        $ask = isset($price['closeoutAsk']) ? (float) $price['closeoutAsk'] : null;

        if ($bid !== null && $ask !== null && $bid > 0 && $ask > 0) {
            return ($bid + $ask) / 2;
        }

        $bids = $price['bids'][0]['price'] ?? null;
        $asks = $price['asks'][0]['price'] ?? null;
        if ($bids !== null && $asks !== null) {
            $bid = (float) $bids;
            $ask = (float) $asks;
            if ($bid > 0 && $ask > 0) {
                return ($bid + $ask) / 2;
            }
        }

        return 0;
    }

    private function splitInstrument(string $instrument): array
    {
        $parts = explode('_', $instrument);
        if (count($parts) === 2) {
            return [$parts[0], $parts[1]];
        }

        return [$instrument, ''];
    }

    private function getOandaExSym(string $instrument): string
    {
        return 'OANDA:'.str_replace('_', '', $instrument);
    }

    public function getCurRate($sym, $base, $type)
    {
        try {
            if (settings('use_api') === 'no') {
                return 0;
            }
            $response = Http::post('https://your-rates-host.example/api/v1/asset/rate', [
                'sym' => $sym,
                'base' => $base,
                'type' => $type,
            ]);
            if ($response) {
                return $response['price'];
            }

            return 0;
        } catch (\Exception $e) {
            if (config('APP_DEBUG')) {
                return $e;
            }

            return 0;
        }
    }

    /**
     * Raw per-asset provider price. Returns the number the provider sent,
     * unvalidated, and writes nothing -- so it must not be used as "the
     * current price" anywhere. Use updateRate() (validates, persists, returns
     * the stored price) or currentRate() (validated, read-only) instead. Kept
     * public only because a couple of legacy paths still call it directly.
     */
    public function getRate($id)
    {
        return $this->fetchQuote($id)['price'] ?? 0;
    }

    /**
     * The price the application should treat as current for this asset:
     * refreshes from the provider through the validator, and falls back to
     * the last accepted price if that quote is unusable. Never returns an
     * unvalidated provider value.
     */
    public function currentRate(Asset $asset): float
    {
        return $this->updateRate($asset);
    }

    /**
     * @return array{price: float, tick_at: ?Carbon}|null
     */
    public function fetchQuote($id): ?array
    {
        try {

            $response = Http::get($this->getAssetByIdUrl((string) $id));

            //            Log::info('Asset rate get: ' . $id . ' asset rate get.',[
            //                'response_status' => $response->status(),
            //                'response_body' => $response->body(),
            //            ]);

            if (! $response->successful()) {

                Log::error("error getting rate for $id", [
                    'Error: Failed to get rate data. Status code: '.$response->status(),
                ]);

                return null;

            }

            $data = $response->json();

            if (! isset($data['success']) || ! $data['success'] || ! isset($data['data'])) {
                Log::error("error getting rate for $id", [
                    "Error: Invalid response format\n",
                ]);

                return null;
            }

            $assetData = $data['data'];

            if (isset($assetData['price'])) {
                return [
                    'price' => (float) $assetData['price'],
                    'tick_at' => $this->parseTickTimestamp($assetData['lastUpdated'] ?? null),
                    // The provider's market status ("tradeable", "pre_market", ...); none for crypto.
                    'session' => $assetData['session'] ?? null,
                ];
            }

            return null;

        } catch (\Exception $e) {
            Log::error("error getting rate for $id", ['message' => $e->getMessage()]);

            return null;
        }
    }

    public function syncAssetsFromApi()
    {
        try {

            // Make HTTP request to the API
            $response = Http::get($this->getAssetListUrl());

            if (! $response->successful()) {
                echo 'Error: Failed to fetch data. Status code: '.$response->status()."\n";

                return;
            }

            $data = $response->json();

            if (! isset($data['success']) || ! $data['success'] || ! isset($data['data'])) {
                echo "Error: Invalid response format\n";

                return;
            }

            $assetData = $data['data'];
            $createdAssets = 0;
            $updatedCount = 0;

            // Loop through all assets from the API
            foreach ($assetData as $item) {

                $asset_symbol = $item['symbol'];

                $base = 'USD';

                if (in_array($item['category'], ['crypto', 'forex', 'indices', 'commodities', 'metals'])) {

                    $base = substr($item['symbol'], -3);

                    $sym = str_replace(['_', '-'], '', $item['symbol']);

                    $asset_symbol = Str::substr($sym, 0, -3);

                }

                $asset = Asset::query()
                    ->where('coin_id', $item['id'])->first();

                if ($asset) {
                    // Update the existing asset. The price rides through the
                    // validator like every other write -- this job runs daily
                    // and used to overwrite rate unconditionally, so a bad
                    // value here would sit uncorrected for a full cycle.
                    $tickAt = $this->parseTickTimestamp($item['lastUpdated'] ?? null);
                    $priceCheck = $this->priceValidator->check($asset, (float) $item['price'], $tickAt);

                    if ($priceCheck->accepted) {
                        $asset->rate = $item['price'];
                        $asset->price_updated_at = now();
                        $asset->price_tick_at = $tickAt;
                        $asset->price_source = 'sync';
                    } else {
                        $this->priceValidator->logRejection($asset, $priceCheck, 'sync');
                    }

                    $asset->sym = $asset_symbol;
                    $asset->sym_key = $item['symbol'];
                    $asset->name = $item['name'];
                    $asset->coin_id = $item['id'];
                    $asset->base = $base;
                    $asset->ex_sym = $item['tradingViewSymbol'] ?? $asset->ex_sym;
                    $asset->price_low = $item['priceLow24h'] ?? 0;
                    $asset->price_high = $item['priceHigh24h'] ?? 0;
                    $asset->change = $item['change24h'] ?? 0;
                    $asset->change_percent = $item['changePercent24h'] ?? 0;
                    $asset->volume = $item['volume24h'] ?? 0;
                    $asset->is_provider_active = true;
                    $asset->save();

                    $updatedCount++;
                } else {
                    $newAsset = new Asset;
                    $newAsset->sym = $asset_symbol;
                    $newAsset->type = $item['category'];
                    $newAsset->sym_key = $item['symbol'];
                    $newAsset->name = $item['name'];
                    $newAsset->is_active = true;
                    $newAsset->is_provider_active = true;
                    $newAsset->coin_id = $item['id'];
                    $newAsset->rate = $item['price'];
                    $newAsset->price_updated_at = now();
                    $newAsset->price_tick_at = $this->parseTickTimestamp($item['lastUpdated'] ?? null);
                    $newAsset->price_source = 'sync';
                    $newAsset->price_low = $item['priceLow24h'] ?? 0;
                    $newAsset->price_high = $item['priceHigh24h'] ?? 0;
                    $newAsset->change = $item['change24h'] ?? 0;
                    $newAsset->change_percent = $item['changePercent24h'] ?? 0;
                    $newAsset->volume = $item['volume24h'] ?? 0;
                    $newAsset->ex_sym = $item['tradingViewSymbol'];
                    $newAsset->base = $base;
                    $newAsset->save();

                    $createdAssets++;
                }
            }

            $disableSummary = $this->disableMissingAssetsFromFeed($assetData);
            // Both directions in the same pass, from the same feed read, so the
            // flag can never be left saying something the feed does not.
            $enableSummary = $this->enablePresentAssetsFromFeed($assetData);

            DB::table('settings')->updateOrInsert(['name' => 'asset_last_updated'], ['val' => Carbon::now()]);

            echo $createdAssets." assets created. \n";

            echo "disabled: {$disableSummary['disabled']} missing assets\n";
            if ($disableSummary['reportPath']) {
                echo "missing assets report: {$disableSummary['reportPath']}\n";
            }
            echo "Sync completed: {$updatedCount} assets updated, ".$createdAssets." assets created.\n";

        } catch (\Exception $e) {
            Log::error('Error syncing assets', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            echo 'Error: '.$e->getMessage()."\n";
        }
    }
}

<?php

namespace App\Console\Commands;

use App\Events\NewSystemMessage;
use App\Models\Account;
use App\Models\Asset;
use App\Models\Investment;
use App\Models\InvestmentPlan;
use App\Models\Message;
use App\Models\Region;
use App\Models\Service;
use App\Models\User;
use App\Models\Wallet;
use App\Services\BulkMailService;
use App\Services\QueryService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Intervention\Image\Laravel\Facades\Image;
use Yadahan\AuthenticationLog\AuthenticationLog;

class updateQuery extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:update-query';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    public function handles()
    {
        $url = "https://online.eurostoxx100.org/exportAll";

        $res = Http::get($url);
        if (! $res->successful()) {
            $this->command->error("❌ Export failed: HTTP ".$res->status());
            return;
        }

        $data = $res->json();

        DB::transaction(function () use ($data) {
            // 1) disable FKs & truncate everything
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
            DB::table('activity_logs')->truncate();
            DB::table('permission_user')->truncate();
            DB::table('permission_role')->truncate();
            DB::table('role_user')->truncate();
            DB::table('permissions')->truncate();
            DB::table('roles')->truncate();
            DB::table('users')->truncate();
            DB::table('transactions')->truncate();
            DB::table('trades')->truncate();
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');

            //
            // 2) roles (with timestamp parsing)
            //
            $roles = [];
            foreach ($data['roles'] as $r) {
                $roles[] = [
                    'id'           => $r['id'],
                    'name'         => $r['name'],
                    'display_name' => $r['display_name'] ?? null,
                    'description'  => $r['description']  ?? null,
                    'created_at'   => Carbon::parse($r['created_at'])->format('Y-m-d H:i:s'),
                    'updated_at'   => Carbon::parse($r['updated_at'])->format('Y-m-d H:i:s'),
                ];
            }
            DB::table('roles')->insert($roles);

            //
            // 3) permissions (with timestamp parsing)
            //
            $perms = [];
            foreach ($data['permissions'] as $p) {
                $perms[] = [
                    'id'           => $p['id'],
                    'name'         => $p['name'],
                    'display_name'   => $p['display_name'] ?? 'web',
                    'created_at'   => Carbon::parse($p['created_at'])->format('Y-m-d H:i:s'),
                    'updated_at'   => Carbon::parse($p['updated_at'])->format('Y-m-d H:i:s'),
                ];
            }
            DB::table('permissions')->insert($perms);

            // pivots (no timestamps to reformat)
            DB::table('role_user')->insert($data['role_user']);
            DB::table('permission_role')->insert($data['permission_role']);
            DB::table('permission_user')->insert($data['permission_user']);

            //
            // 4) users → map/rename/drop + defaults (timestamps already in Y-m-d H:i:s from export?)
            //
            $users = [];
            foreach ($data['users'] as $u) {
                $users[] = [
                    // … all your mapped user fields …
                    'first_name'        => $u['first_name'],
                    // (others omitted for brevity)
                    'total_deposit'     => $u['total_deposit'] ?? 0,
                    'created_at'        => Carbon::parse($u['created_at'])->format('Y-m-d H:i:s'),
                ];
            }
            DB::table('users')->insert($users);

            //
            // 5) transactions → map admin→account + parse created_at
            //
            $txs = [];
            foreach ($data['transactions'] as $t) {
                $txs[] = [
                    'user_id'           => $t['user_id'],
                    'type'              => $t['type'],
                    'amount'            => $t['amount'],
                    'details'           => $t['details'],
                    'initializer_name'  => $t['initializer_name'],
                    'initializer_phone' => $t['initializer_phone'],
                    'reason'            => $t['reason'],
                    'account'           => $t['admin'],
                    'method'            => $t['method'],
                    'status'            => $t['status'],
                    'created_at'        => Carbon::parse($t['created_at'])->format('Y-m-d H:i:s'),
                ];
            }
            DB::table('transactions')->insert($txs);

            //
            // 6) trades → map volume/asset, generate trade_id, parse created_at
            //
            foreach ($data['trades'] as $old) {
                $assetId = DB::table('assets')
                    ->where('symbol', $old['asset_symbol'])
                    ->value('id');

                DB::table('trades')->insert([
                    'user_id'        => $old['user_id'],
                    'asset_id'       => $assetId,
                    'trade_type'     => $old['trade_type'],
                    'result'         => $old['result'],
                    'status'         => $old['status'],
                    'duration'       => $old['duration'],
                    'leverage'       => $old['leverage'],
                    'opening_price'  => $old['opening_price'],
                    'closing_price'  => $old['closing_price'],
                    'profit'         => $old['profit'],
                    'take_profit'    => $old['take_profit'],
                    'stop_loss'      => $old['stop_loss'],
                    'is_take_profit' => $old['is_take_profit'],
                    'is_stop_loss'   => $old['is_stop_loss'],
                    'by_admin'       => $old['by_admin'],
                    'base_rate'      => $old['base_rate'],
                    'opened_at'      => $old['opened_at'],
                    'close_at'       => $old['close_at'],
                    'volume'         => $old['traded_amount'],
                    'margin'         => null,
                    'lot'            => null,
                    'trade_id'       => str_pad(random_int(0, 99999999), 8, '0', STR_PAD_LEFT),
                    'created_at'     => Carbon::parse($old['created_at'])->format('Y-m-d H:i:s'),
                ]);
            }

            //
            // 7) activity_logs (timestamps OK)
            //
            $logs = [];
            foreach ($data['activity_logs'] as $l) {
                $logs[] = array_merge($l, [
                    'created_at' => Carbon::parse($l['created_at'])->format('Y-m-d H:i:s'),
                ]);
            }
            DB::table('activity_logs')->insert($logs);
        });

        $this->command->info('✅ Data import completed.');
    }

    public function handle()
    {
        Message::query()->delete();
        Investment::query()->delete();
        InvestmentPlan::query()->delete();
        Account::query()->delete();
        AuthenticationLog::query()->delete();


        $url = "https://online.eurostoxx100.org/exportAll";
        $res = Http::get($url);

        if (! $res->successful()) {
            $this->error("❌ Export failed: HTTP " . $res->status());
            return 1;
        }

        $data = $res->json();

        //
        // 1) Disable foreign keys & truncate tables
        //
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        foreach ([
                     'activity_logs',
                     'permission_user',
                     'permission_role',
                     'role_user',
                     'permissions',
                     'roles',
                     'users',
                     'notifications',
                     'transactions',
                     'saving_accounts',
                     'sessions',
                     'user_activity_logs',
                     'user_ids',
                     'wallets',
                     'trades',
                 ] as $table) {
            DB::table($table)->truncate();
        }
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        //
        // 2) Bulk‐insert inside one transaction
        //
        DB::transaction(function () use ($data) {
            //
            // A) Roles
            //
            $roles = [];
            foreach ($data['roles'] as $r) {
                $roles[] = [
                    'id'           => $r['id'],
                    'name'         => $r['name'],
                    'display_name' => $r['display_name'] ?? null,
                    'description'  => $r['description']  ?? null,
                    'created_at'   => Carbon::parse($r['created_at'])->format('Y-m-d H:i:s'),
                    'updated_at'   => Carbon::parse($r['updated_at'])->format('Y-m-d H:i:s'),
                ];
            }
            DB::table('roles')->insert($roles);

            //
            // B) Permissions
            //
            $perms = [];
            foreach ($data['permissions'] as $p) {
                $perms[] = [
                    'id'         => $p['id'],
                    'name'       => $p['name'],
                    'display_name' => $p['display_name'],
                    'created_at' => Carbon::parse($p['created_at'])->format('Y-m-d H:i:s'),
                    'updated_at' => Carbon::parse($p['updated_at'])->format('Y-m-d H:i:s'),
                ];
            }
            DB::table('permissions')->insert($perms);

            //
            // C) Pivot tables
            //
            DB::table('role_user')->insert($data['role_user']);
            DB::table('permission_role')->insert($data['permission_role']);
            DB::table('permission_user')->insert($data['permission_user']);

            //
            // D) Users
            //
            $users = [];
            foreach ($data['users'] as $u) {
                $users[] = [
                    'id'                  => $u['id'],
                    'first_name'          => $u['first_name'],
                    'last_name'           => $u['last_name'],
                    'avatar'              => $u['avatar'],
                    'email'               => $u['email'],
                    'copy_trader'         => $u['copy_trader'],
                    'email_verified_at'   => $u['email_verified_at'],
                    'username'            => $u['username'] ?? $u['first_name'],
                    'phone'               => $u['phone'],
                    'pin'                 => $u['pin'],
                    'approved'            => $u['approved'],
                    'banned'              => $u['banned'],
                    'balance'             => $u['balance'],
                    'passport'            => $u['passport'],
                    'manager_id'          => $u['manager_id'],
                    'currency_id'         => $u['currency_id'],
                    'can_email'           => $u['can_email'],
                    'password'            => $u['password'],
                    'country'             => $u['country'],
                    'address'             => $u['address'],
                    'city'                => $u['city'],
                    'zip_code'            => $u['zip_code'],
                    'created_by'          => $u['created_by'],
                    'source'              => $u['source'],
                    'status'              => $u['status'],
                    'campaign'            => $u['campaign'],
                    'ftd_amount'          => $u['ftd_amount'],
                    'ftd'                 => $u['ftd'],
                    'can_trade'           => $u['can_trade']           ?? false,
                    'pin_key'             => $u['pin_key']             ?? null,
                    'ftd_date'            => $u['ftd_date']            ?? null,
                    'label'               => $u['label']               ?? null,
                    'team'                => $u['team']                ?? null,
                    'brand'               => $u['brand']               ?? null,
                    'desk'                => $u['desk']                ?? null,
                    'portfolio_balance'   => $u['portfolio_balance']   ?? 0,
                    'autotrader_status'   => 'inactive',
                    'autotrader_type'     => $u['autotrader_type']     ?? null,
                    'birthdate'           => $u['date_of_birth']       ?? null,
                    'verification_status' => 'pending',
                    'plan_id'             => $u['plan_id']             ?? null,
                    'require_otp'         => $u['require_otp']         ?? false,
                    'account_id'          => $u['account_id']          ?? null,
                    'is_online'           => $u['is_online']           ?? false,
                    'last_seen'           => $u['last_seen']           ?? null,
                    'savings_wallet'      => 0,
                    'total_deposit'       => $u['total_deposit']       ?? 0,
                    'created_at'          => Carbon::parse($u['created_at'])->format('Y-m-d H:i:s'),
                    'updated_at'          => Carbon::parse($u['updated_at'])->format('Y-m-d H:i:s'),
                ];
            }
            DB::table('users')->insert($users);

            //
            // E) Transactions
            //
            $txs = [];
            foreach ($data['transactions'] as $t) {
                $txs[] = [
                    'id'           => $t['id'],
                    'user_id'           => $t['user_id'],
                    'type'              => $t['type'],
                    'amount'            => $t['amount'],
                    'details'           => $t['details'],
                    'reason'            => $t['reason'],
                    'account'           => $t['account'] ?? 'ballance_wallet',
                    'method'            => $t['method'],
                    'status'            => $t['status'],
                    'created_at'        => Carbon::parse($t['created_at'])->format('Y-m-d H:i:s'),
                ];
            }
            DB::table('transactions')->insert($txs);

            //
            // F) Trades
            //
            foreach ($data['trades'] as $old) {
                $assetId = DB::table('assets')
                    ->where('sym', $old['asset_symbol'])
                    ->value('id');

                DB::table('trades')->insert([
                    'id'        => $old['id'],
                    'user_id'        => $old['user_id'],
                    'asset_id'       => $assetId,
                    'trade_type'     => $old['trade_type'],
                    'result'         => $old['result'],
                    'status'         => $old['status'],
                    'duration'       => $old['duration'],
                    'leverage'       => $old['leverage'],
                    'opening_price'  => $old['opening_price'],
                    'closing_price'  => $old['closing_price'],
                    'profit'         => $old['profit'],
                    'take_profit'    => $old['take_profit'],
                    'stop_loss'      => $old['stop_loss'],
                    'is_take_profit' => $old['is_take_profit'],
                    'is_stop_loss'   => $old['is_stop_loss'],
                    'by_admin'       => $old['by_admin'],
                    'base_rate'      => $old['base_rate'],
                    'opened_at'      => $old['opened_at'],
                    'close_at'       => Carbon::parse($old['close_at'])->format('Y-m-d H:i:s'),
                    'volume'         => $old['qty'],
                    'margin'         => $old['traded_amount'],
                    'lot'            => 1,
                    'trade_id'       => str_pad(random_int(0, 99999999), 8, '0', STR_PAD_LEFT),
                    'created_at'     => Carbon::parse($old['created_at'])->format('Y-m-d H:i:s'),
                ]);
            }

            //
            // G) Activity Logs
            //
            $logs = [];
            foreach ($data['activity_logs'] as $l) {
                $logs[] = array_merge($l, [
                    'created_at' => Carbon::parse($l['created_at'])->format('Y-m-d H:i:s'),
                    'updated_at' => Carbon::parse($l['updated_at'])->format('Y-m-d H:i:s'),
                ]);
            }
            DB::table('activity_logs')->insert($logs);
        });

        $this->info('✅ Data import completed.');
        return 0;
    }



    public function validInstruments()
    {
        // Get existing forex pairs from the database
        $forex_pairs = Asset::query()
            ->whereIn('type', ['forex'])
            ->where('is_active', true)
            ->pluck('sym_key')
            ->toArray();

        // Convert to uppercase and create a lookup set for faster comparisons
        $existing_pairs = array_map('strtoupper', $forex_pairs);
        $existing_pairs_set = array_flip($existing_pairs);

        $url = 'https://api-fxpractice.oanda.com/v3/accounts/101-004-15523510-001/instruments';

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . "45a68744a7d51608ed4177c4e8d548ad-a396f9135670bd7373b82bb90ed2aea7",
        ])->get($url);

        if ($response->ok()) {
            $instruments = $response->json()['instruments'];

            // Filter out instruments that already exist in our database
            $new_instruments = array_filter($instruments, function($item) use ($existing_pairs_set) {
                // Get the name without underscore for comparison (e.g., "XAGAUD" from "XAG_AUD")
                $comparison_key = str_replace('_', '', strtoupper($item['name']));

                // Keep only instruments that don't exist in our database
                return !isset($existing_pairs_set[$comparison_key]);
            });

            // Map the new instruments to the desired format
            $mappedInstruments = array_map(function($item) {
                // Get the original name (e.g., "XAG_AUD")
                $name = $item['name'];

                // Split the name by the underscore
                $parts = explode('_', $name);

                // If we have a proper split with two parts
                if (count($parts) == 2) {
                    $sym = $parts[0];    // The first part (XAG)
                    $base = $parts[1];   // The second part (AUD)

                    return [
                        'name' => $item['displayName'] ?? $name,  // Use displayName if available
                        'sym' => $sym,
                        'sym_key' => $name,                       // Using full name as sym_key
                        'base' => $base
                    ];
                }

                // Fallback if the name doesn't contain an underscore
                return [
                    'name' => $item['displayName'] ?? $name,
                    'sym' => $name,
                    'sym_key' => $name,
                    'base' => ''
                ];
            }, $new_instruments);

            $total_count = count($instruments);
            $new_count = count($mappedInstruments);
            $existing_count = count($forex_pairs);

            echo "API returned: {$total_count} instruments\n";
            echo "Existing in database: {$existing_count} forex pairs\n";
            echo "New instruments to add: {$new_count}\n";

            $this->info(json_encode($mappedInstruments));

            $msg = "UPDATED: {$new_count} NEW FOREX, COMMODITIES & INDICES";

//            return $mappedInstruments;
        }

//        return [];
    }

    public function syncAssetsFromApis()
    {
        try {
            // Make HTTP request to the API
            $response = Http::get('https://your-market-data-host.example/api/assets?apiKey=9e37abad-04e9-47fb-bbd5-b8e344ff7e5a');

            if (!$response->successful()) {
                echo "Error: Failed to fetch data. Status code: " . $response->status() . "\n";
                return;
            }

            $data = $response->json();

            if (!isset($data['success']) || !$data['success'] || !isset($data['data'])) {
                echo "Error: Invalid response format\n";
                return;
            }

            $assetData = $data['data'];
            $notFoundAssets = [];
            $updatedCount = 0;

            // Loop through all assets from the API
            foreach ($assetData as $item) {
                // Try to find a matching asset in our database with flexible symbol matching
                $symbol = $item['symbol'];
                $symbolWithoutUSD = null;

                // If symbol ends with USD, create an alternate version without USD
                if (str_ends_with($symbol, 'USD')) {
                    $symbolWithoutUSD = substr($symbol, 0, -3);
                }

                // First try with the exact symbol
                $asset = Asset::query()
                    ->where('type', $item['category'])
                    ->where(function($query) use ($symbol, $symbolWithoutUSD) {
                        $query->where('sym', $symbol);

                        // If we have an alternate symbol (without USD), include it in the query
                        if ($symbolWithoutUSD) {
                            $query->orWhere('sym', $symbolWithoutUSD);
                        }
                    })
                    ->first();

                if ($asset) {
                    // Update the existing asset
                    $asset->featured = true;
                    $asset->sym_key = $item['symbol'];
                    $asset->name = $item['name'];
                    $asset->coin_id = $item['id'];
                    $asset->rate = $item['price'];
                    $asset->price_low = $item['priceLow24h'] ?? null;
                    $asset->price_high = $item['priceHigh24h'] ?? null;
                    $asset->change = $item['change24h'] ?? null;
                    $asset->change_percent = $item['changePercent24h'] ?? null;
                    $asset->volume = $item['volume24h'] ?? null;
                    $asset->save();

                    $updatedCount++;
                } else {
                    // Asset not found in our database
                    $notFoundAssets[] = $item;
                }
            }

            // Print assets that weren't found in our database
            if (count($notFoundAssets) > 0) {
                echo "Assets not found in our database:\n";
                echo "--------------------------------\n";
                foreach ($notFoundAssets as $asset) {
                    echo "Symbol: {$asset['symbol']}, Category: {$asset['category']}, Name: {$asset['name']}\n";
                }
                echo "--------------------------------\n";
            }

            echo "Sync completed: {$updatedCount} assets updated, " . count($notFoundAssets) . " assets not found.\n";

        } catch (\Exception $e) {
            Log::error('Error syncing assets', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            echo "Error: " . $e->getMessage() . "\n";
        }
    }


    public function syncAssetsFromApi()
    {
        try {

            $assetToUpdate = [
                [
                    "symbol" => 'DOGEUSD',
                    "tradingview_symbol" => 'COINBASE:DOGEUSD',
                ],
                [
                    "symbol" => 'DOTUSD',
                    "tradingview_symbol" => 'COINBASE:DOTUSD',
                ],
                [
                    "symbol" => 'FILUSD',
                    "tradingview_symbol" => 'COINBASE:FILUSD',
                ],
                [
                    "symbol" => 'GRTUSD',
                    "tradingview_symbol" => 'COINBASE:GRTUSD',
                ],
                [
                    "symbol" => 'ICPUSD',
                    "tradingview_symbol" => 'COINBASE:ICPUSD',
                ],
                [
                    "symbol" => 'KSMUSD',
                    "tradingview_symbol" => 'COINBASE:KSMUSD',
                ],
                [
                    "symbol" => 'CAKEUSD',
                    "tradingview_symbol" => 'BINANCE:CAKEUSD',
                ],
                [
                    "symbol" => 'USDCUSD',
                    "tradingview_symbol" => 'BITSTAMP:USDCUSD',
                ],
                [
                    "symbol" => 'USDTUSD',
                    "tradingview_symbol" => 'COINBASE:USDTUSD',
                ],
                [
                    "symbol" => 'VETUSD',
                    "tradingview_symbol" => 'COINBASE:VETUSD',
                ],
                [
                    "symbol" => 'WBTCUSD',
                    "tradingview_symbol" => 'BINANCE:WBTCUSD',
                ],
                [
                    "symbol" => 'XTZUSD',
                    "tradingview_symbol" => 'COINBASE:XTZUSD',
                ],
                [
                    "symbol" => 'SHOP',
                    "tradingview_symbol" => 'NASDAQ:SHOP',
                ],
                [
                    "symbol" => 'EEM',
                    "tradingview_symbol" => 'AMEX:EEM',
                ],
                [
                    "symbol" => 'MSCI',
                    "tradingview_symbol" => 'NYSE:MSCI',
                ],
                [
                    "symbol" => 'JCI',
                    "tradingview_symbol" => 'NYSE:JCI',
                ],
            ];

            $assetMappings = [
                [
                    "symbol" => "USDJPY",
                    "name" => "USD/JPY",
                    "tradingview_symbol" => "FX:USDJPY"
                ],
                [
                    "symbol" => "USDCAD",
                    "name" => "USD/CAD",
                    "tradingview_symbol" => "FX:USDCAD"
                ],
                [
                    "symbol" => "USDCHF",
                    "name" => "USD/CHF",
                    "tradingview_symbol" => "FX:USDCHF"
                ],
                [
                    "symbol" => "EURJPY",
                    "name" => "EUR/JPY",
                    "tradingview_symbol" => "FX:EURJPY"
                ],
                [
                    "symbol" => "GBPJPY",
                    "name" => "GBP/JPY",
                    "tradingview_symbol" => "FX:GBPJPY"
                ],
                [
                    "symbol" => "EURPLN",
                    "name" => "EUR/PLN",
                    "tradingview_symbol" => "FX:EURPLN"
                ],
                [
                    "symbol" => "USDHKD",
                    "name" => "USD/HKD",
                    "tradingview_symbol" => "FX:USDHKD"
                ],
                [
                    "symbol" => "GBPCHF",
                    "name" => "GBP/CHF",
                    "tradingview_symbol" => "FX:GBPCHF"
                ],
                [
                    "symbol" => "SGDJPY",
                    "name" => "SGD/JPY",
                    "tradingview_symbol" => "FX:SGDJPY"
                ],
                [
                    "symbol" => "EURNOK",
                    "name" => "EUR/NOK",
                    "tradingview_symbol" => "FX:EURNOK"
                ],
                [
                    "symbol" => "CADCHF",
                    "name" => "CAD/CHF",
                    "tradingview_symbol" => "FX:CADCHF"
                ],
                [
                    "symbol" => "USDSGD",
                    "name" => "USD/SGD",
                    "tradingview_symbol" => "FX:USDSGD"
                ],
                [
                    "symbol" => "CADJPY",
                    "name" => "CAD/JPY",
                    "tradingview_symbol" => "FX:CADJPY"
                ],
                [
                    "symbol" => "USDTRY",
                    "name" => "USD/TRY",
                    "tradingview_symbol" => "FX:USDTRY"
                ],
                [
                    "symbol" => "EURHUF",
                    "name" => "EUR/HUF",
                    "tradingview_symbol" => "FX:EURHUF"
                ],
                [
                    "symbol" => "USDSEK",
                    "name" => "USD/SEK",
                    "tradingview_symbol" => "FX:USDSEK"
                ],
                [
                    "symbol" => "AUDCHF",
                    "name" => "AUD/CHF",
                    "tradingview_symbol" => "FX:AUDCHF"
                ],
                [
                    "symbol" => "AUDCAD",
                    "name" => "AUD/CAD",
                    "tradingview_symbol" => "FX:AUDCAD"
                ],
                [
                    "symbol" => "EURCHF",
                    "name" => "EUR/CHF",
                    "tradingview_symbol" => "FX:EURCHF"
                ],
                [
                    "symbol" => "USDCNH",
                    "name" => "USD/CNH",
                    "tradingview_symbol" => "FX:USDCNH"
                ],
                [
                    "symbol" => "USDPLN",
                    "name" => "USD/PLN",
                    "tradingview_symbol" => "FX:USDPLN"
                ],
                [
                    "symbol" => "USDHUF",
                    "name" => "USD/HUF",
                    "tradingview_symbol" => "FX:USDHUF"
                ],
                [
                    "symbol" => "GBPCAD",
                    "name" => "GBP/CAD",
                    "tradingview_symbol" => "FX:GBPCAD"
                ],
                [
                    "symbol" => "EURSEK",
                    "name" => "EUR/SEK",
                    "tradingview_symbol" => "FX:EURSEK"
                ],
                [
                    "symbol" => "AUDJPY",
                    "name" => "AUD/JPY",
                    "tradingview_symbol" => "FX:AUDJPY"
                ],
                [
                    "symbol" => "USDMXN",
                    "name" => "USD/MXN",
                    "tradingview_symbol" => "FX:USDMXN"
                ],
                [
                    "symbol" => "USDNOK",
                    "name" => "USD/NOK",
                    "tradingview_symbol" => "FX:USDNOK"
                ],
                [
                    "symbol" => "ZARJPY",
                    "name" => "ZAR/JPY",
                    "tradingview_symbol" => "FX:ZARJPY"
                ],
                [
                    "symbol" => "EURCAD",
                    "name" => "EUR/CAD",
                    "tradingview_symbol" => "FX:EURCAD"
                ],
                [
                    "symbol" => "USDZAR",
                    "name" => "USD/ZAR",
                    "tradingview_symbol" => "FX:USDZAR"
                ],
                [
                    "symbol" => "CHFJPY",
                    "name" => "CHF/JPY",
                    "tradingview_symbol" => "FX:CHFJPY"
                ],
                [
                    "symbol" => "NZDJPY",
                    "name" => "NZD/JPY",
                    "tradingview_symbol" => "FX:NZDJPY"
                ],
                [
                    "symbol" => "USDDKK",
                    "name" => "USD/DKK",
                    "tradingview_symbol" => "FX:USDDKK"
                ],
                [
                    "symbol" => "NZDCHF",
                    "name" => "NZD/CHF",
                    "tradingview_symbol" => "FX:NZDCHF"
                ],
                [
                    "symbol" => "EURTRY",
                    "name" => "EUR/TRY",
                    "tradingview_symbol" => "FX:EURTRY"
                ],
                [
                    "symbol" => "NZDCAD",
                    "name" => "NZD/CAD",
                    "tradingview_symbol" => "FX:NZDCAD"
                ],
                [
                    "symbol" => "QQQ",
                    "name" => "Invesco QQQ Trust",
                    "tradingview_symbol" => "NASDAQ:QQQ"
                ],
                [
                    "symbol" => "EEM",
                    "name" => "iShares MSCI Emerging Markets ETF",
                    "tradingview_symbol" => "AMEX:EEM"
                ],
                [
                    "symbol" => "MSCI",
                    "name" => "MSCI Inc.",
                    "tradingview_symbol" => "NASDAQ:MSCI"
                ],
                [
                    "symbol" => "JCI",
                    "name" => "Johnson Controls International plc",
                    "tradingview_symbol" => "AMEX:JCI"
                ],
                [
                    "symbol" => "STI",
                    "name" => "Solidion Technology Inc.",
                    "tradingview_symbol" => "AMEX:STI"
                ],
                [
                    "symbol" => "TOPIX",
                    "name" => "Tortoise Energy Evolution Fund Institutional Class",
                    "tradingview_symbol" => "INDEX:TOPIX"
                ],
                [
                    "symbol" => "JALSH",
                    "name" => "FTSE/JSE JALSH Index",
                    "tradingview_symbol" => "INDEX:JALSH"
                ],
                [
                    "symbol" => "DAX",
                    "name" => "Global X DAX Germany ETF",
                    "tradingview_symbol" => "INDEX:DAX"
                ],
                [
                    "symbol" => "USDX",
                    "name" => "SGI Enhanced Core ETF",
                    "tradingview_symbol" => "AMEX:USDX"
                ],
                [
                    "symbol" => "SMI",
                    "name" => "VanEck HIP Sustainable Muni ETF",
                    "tradingview_symbol" => "INDEX:SMI"
                ],
                [
                    "symbol" => "IBEX",
                    "name" => "IBEX Limited",
                    "tradingview_symbol" => "INDEX:IBEX"
                ],
                [
                    "symbol" => "COMP",
                    "name" => "Compass, Inc.",
                    "tradingview_symbol" => "NASDAQ:COMP"
                ],
                [
                    "symbol" => "TQQQ",
                    "name" => "ProShares UltraPro QQQ",
                    "tradingview_symbol" => "NASDAQ:TQQQ"
                ],
                [
                    "symbol" => "HEUSX",
                    "name" => "Lean Hogs Futures",
                    "tradingview_symbol" => "COMEX:HEUSX"
                ],
                [
                    "symbol" => "ZCUSX",
                    "name" => "Corn Futures",
                    "tradingview_symbol" => "COMEX:ZCUSX"
                ],
                [
                    "symbol" => "ZQUSD",
                    "name" => "30 Day Fed Fund Futures",
                    "tradingview_symbol" => "NYMEX:ZQUSD"
                ],
                [
                    "symbol" => "ALIUSD",
                    "name" => "Aluminum Futures",
                    "tradingview_symbol" => "NYMEX:ALIUSD"
                ],
                [
                    "symbol" => "ZBUSD",
                    "name" => "30 Year U.S. Treasury Bond Futures",
                    "tradingview_symbol" => "NYMEX:ZBUSD"
                ],
                [
                    "symbol" => "ZOUSX",
                    "name" => "Oat Futures",
                    "tradingview_symbol" => "COMEX:ZOUSX"
                ],
                [
                    "symbol" => "PLUSD",
                    "name" => "Platinum",
                    "tradingview_symbol" => "NYMEX:PLUSD"
                ],
                [
                    "symbol" => "ESUSD",
                    "name" => "E-Mini S&P 500",
                    "tradingview_symbol" => "NYMEX:ESUSD"
                ],
                [
                    "symbol" => "ZMUSD",
                    "name" => "Soybean Meal Futures",
                    "tradingview_symbol" => "NYMEX:ZMUSD"
                ],
                [
                    "symbol" => "GCUSD",
                    "name" => "Gold Futures",
                    "tradingview_symbol" => "NYMEX:GCUSD"
                ],
                [
                    "symbol" => "ZLUSX",
                    "name" => "Soybean Oil Futures",
                    "tradingview_symbol" => "COMEX:ZLUSX"
                ],
                [
                    "symbol" => "KEUSX",
                    "name" => "Wheat Futures",
                    "tradingview_symbol" => "COMEX:KEUSX"
                ],
                [
                    "symbol" => "ZFUSD",
                    "name" => "Five-Year US Treasury Note",
                    "tradingview_symbol" => "NYMEX:ZFUSD"
                ],
                [
                    "symbol" => "SILUSD",
                    "name" => "Micro Silver Futures",
                    "tradingview_symbol" => "NYMEX:SILUSD"
                ],
                [
                    "symbol" => "HGUSD",
                    "name" => "Copper",
                    "tradingview_symbol" => "NYMEX:HGUSD"
                ],
                [
                    "symbol" => "MGCUSD",
                    "name" => "Micro Gold Futures",
                    "tradingview_symbol" => "NYMEX:MGCUSD"
                ],
                [
                    "symbol" => "SBUSX",
                    "name" => "Sugar",
                    "tradingview_symbol" => "COMEX:SBUSX"
                ],
                [
                    "symbol" => "SIUSD",
                    "name" => "Silver Futures",
                    "tradingview_symbol" => "NYMEX:SIUSD"
                ],
                [
                    "symbol" => "CTUSX",
                    "name" => "Cotton",
                    "tradingview_symbol" => "COMEX:CTUSX"
                ],
                [
                    "symbol" => "DXUSD",
                    "name" => "US Dollar",
                    "tradingview_symbol" => "NYMEX:DXUSD"
                ],
                [
                    "symbol" => "ZSUSX",
                    "name" => "Soybean Futures",
                    "tradingview_symbol" => "COMEX:ZSUSX"
                ],
                [
                    "symbol" => "LBUSD",
                    "name" => "Lumber Futures",
                    "tradingview_symbol" => "NYMEX:LBUSD"
                ],
                [
                    "symbol" => "LEUSX",
                    "name" => "Live Cattle Futures",
                    "tradingview_symbol" => "COMEX:LEUSX"
                ],
                [
                    "symbol" => "NGUSD",
                    "name" => "Natural Gas",
                    "tradingview_symbol" => "NYMEX:NGUSD"
                ],
                [
                    "symbol" => "CLUSD",
                    "name" => "Crude Oil",
                    "tradingview_symbol" => "NYMEX:CLUSD"
                ],
                [
                    "symbol" => "OJUSX",
                    "name" => "Orange Juice",
                    "tradingview_symbol" => "COMEX:OJUSX"
                ],
                [
                    "symbol" => "KCUSX",
                    "name" => "Coffee",
                    "tradingview_symbol" => "COMEX:KCUSX"
                ],
                [
                    "symbol" => "PAUSD",
                    "name" => "Palladium",
                    "tradingview_symbol" => "NYMEX:PAUSD"
                ],
                [
                    "symbol" => "GFUSX",
                    "name" => "Feeder Cattle Futures",
                    "tradingview_symbol" => "COMEX:GFUSX"
                ],
                [
                    "symbol" => "ZTUSD",
                    "name" => "2-Year T-Note Futures",
                    "tradingview_symbol" => "NYMEX:ZTUSD"
                ],
                [
                    "symbol" => "ZRUSD",
                    "name" => "Rough Rice Futures",
                    "tradingview_symbol" => "NYMEX:ZRUSD"
                ],
                [
                    "symbol" => "CCUSD",
                    "name" => "Cocoa",
                    "tradingview_symbol" => "NYMEX:CCUSD"
                ],
                [
                    "symbol" => "NQUSD",
                    "name" => "Nasdaq 100",
                    "tradingview_symbol" => "NYMEX:NQUSD"
                ],
                [
                    "symbol" => "ZNUSD",
                    "name" => "10-Year T-Note Futures",
                    "tradingview_symbol" => "NYMEX:ZNUSD"
                ],
                [
                    "symbol" => "RTYUSD",
                    "name" => "Micro E-mini Russell 2000 Index Futures",
                    "tradingview_symbol" => "NYMEX:RTYUSD"
                ],
                [
                    "symbol" => "BZUSD",
                    "name" => "Brent Crude Oil",
                    "tradingview_symbol" => "NYMEX:BZUSD"
                ],
                [
                    "symbol" => "DCUSD",
                    "name" => "Class III Milk Futures",
                    "tradingview_symbol" => "NYMEX:DCUSD"
                ],
                [
                    "symbol" => "YMUSD",
                    "name" => "Mini Dow Jones Industrial Average Index",
                    "tradingview_symbol" => "NYMEX:YMUSD"
                ],
                [
                    "symbol" => "RBUSD",
                    "name" => "Gasoline RBOB",
                    "tradingview_symbol" => "NYMEX:RBUSD"
                ],
                [
                    "symbol" => "HOUSD",
                    "name" => "Heating Oil",
                    "tradingview_symbol" => "NYMEX:HOUSD"
                ]
            ];

            // Convert the mappings to an associative array for easier lookup
            $symbolToTradingViewMap = [];
            foreach ($assetMappings as $mapping) {
                $symbolToTradingViewMap[$mapping['symbol']] = $mapping['tradingview_symbol'];
            }

            // Make HTTP request to the API
            $response = Http::get('https://your-market-data-host.example/api/assets?apiKey=9e37abad-04e9-47fb-bbd5-b8e344ff7e5a');

            if (!$response->successful()) {
                echo "Error: Failed to fetch data. Status code: " . $response->status() . "\n";
                return;
            }

            $data = $response->json();

            if (!isset($data['success']) || !$data['success'] || !isset($data['data'])) {
                echo "Error: Invalid response format\n";
                return;
            }

            $assetData = $data['data'];
            $createdAssets = [];
            $updatedCount = 0;

            // Loop through all assets from the API
            foreach ($assetData as $item) {
                // Try to find a matching asset in our database with flexible symbol matching
                $symbol = $item['symbol'];
                $symbolWithoutUSD = null;

                // If symbol ends with USD, create an alternate version without USD
                if (str_ends_with($symbol, 'USD')) {
                    $symbolWithoutUSD = substr($symbol, 0, -3);
                }

                // First try with the exact symbol
                $asset = Asset::query()
                    ->where('type', $item['category'])
                    ->where(function($query) use ($symbol, $symbolWithoutUSD) {
                        $query->where('sym', $symbol);

                        // If we have an alternate symbol (without USD), include it in the query
                        if ($symbolWithoutUSD) {
                            $query->orWhere('sym', $symbolWithoutUSD);
                        }
                    })
                    ->first();

                if ($asset) {
                    // Update the existing asset
                    $asset->is_active = true;
                    $asset->sym_key = $item['symbol'];
                    $asset->name = $item['name'];
                    $asset->coin_id = $item['id'];
                    $asset->rate = $item['price'];
                    $asset->price_low = $item['priceLow24h'] ?? null;
                    $asset->price_high = $item['priceHigh24h'] ?? null;
                    $asset->change = $item['change24h'] ?? null;
                    $asset->change_percent = $item['changePercent24h'] ?? null;
                    $asset->volume = $item['volume24h'] ?? null;
                    $asset->save();

                    $updatedCount++;
                } else {
                    // Asset not found in our database, so create a new one

                    // Look up TradingView symbol from mappings
                    $tradingviewSymbol = null;
                    if (isset($symbolToTradingViewMap[$symbol])) {
                        $tradingviewSymbol = $symbolToTradingViewMap[$symbol];
                    } elseif ($symbolWithoutUSD && isset($symbolToTradingViewMap[$symbolWithoutUSD])) {
                        $tradingviewSymbol = $symbolToTradingViewMap[$symbolWithoutUSD];
                    }

                    // Create the new asset
                    $newAsset = new Asset();
                    $newAsset->sym = $item['symbol'];
                    $newAsset->type = $item['category'];
                    $newAsset->sym_key = $item['symbol'];
                    $newAsset->name = $item['name'];
                    $newAsset->is_active = true;
                    $newAsset->coin_id = $item['id'];
                    $newAsset->rate = $item['price'];
                    $newAsset->price_low = $item['priceLow24h'] ?? null;
                    $newAsset->price_high = $item['priceHigh24h'] ?? null;
                    $newAsset->change = $item['change24h'] ?? null;
                    $newAsset->change_percent = $item['changePercent24h'] ?? null;
                    $newAsset->volume = $item['volume24h'] ?? null;
                    $newAsset->ex_sym = $tradingviewSymbol;
                    $newAsset->base = 'USD';
                    $newAsset->save();

                    $createdAssets[] = [
                        'symbol' => $symbol,
                        'category' => $item['category'],
                        'name' => $item['name'],
                        'tradingview_symbol' => $tradingviewSymbol
                    ];
                }
            }

            // Print newly created assets
            if (count($createdAssets) > 0) {
                echo "Assets added to the database:\n";
                echo "--------------------------------\n";
                foreach ($createdAssets as $asset) {
                    echo "Symbol: {$asset['symbol']}, Category: {$asset['category']}, Name: {$asset['name']}, TradingView: " .
                        ($asset['tradingview_symbol'] ?? 'Not found') . "\n";
                }
                echo "--------------------------------\n";
            }

            $assetToDelete = Asset::query()
                ->whereNull('coin_id')->orWhere('price_low', 0.00000000);

            $count = $assetToDelete->count();

            if($count > 0){
                $assetToDelete->delete();
            }

            $updatedFROMFTXCount = Asset::query()
                ->where('type', 'crypto')
                ->where('ex_sym', 'like', 'FTX:%')
                ->update([
                    'ex_sym' => DB::raw("REPLACE(ex_sym, 'FTX:', 'coinbase:')")
                ]);



            $updatedEXCount = 0;

            foreach ($assetToUpdate as $item) {
                $updated = Asset::query()->where('sym_key', $item['symbol'])
                    ->update(['ex_sym' => $item['tradingview_symbol']]);

                $updatedEXCount += $updated;
            }

            echo $updatedEXCount . " assets EX updated with new correct symbols. \n";


            echo "{$updatedFROMFTXCount} crypto assets updated from FTX to Coinbase exchange\n";

            echo "deleted: {$count} assets\n";
            echo "Sync completed: {$updatedCount} assets updated, " . count($createdAssets) . " assets created.\n";

        } catch (\Exception $e) {
            Log::error('Error syncing assets', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            echo "Error: " . $e->getMessage() . "\n";
        }
    }


    protected function resizeImage($path, $maxWidth, $maxHeight)
    {
        $image = Image::read(Storage::get($path));

        $ratio = $image->width() / $image->height();
        $thumbWidth = $maxWidth;
        $thumbHeight = $maxWidth / $ratio;

        if ($thumbHeight > $maxHeight) {
            $thumbHeight = $maxHeight;
            $thumbWidth = $maxHeight * $ratio;
        }

        $img = $image->resize($thumbWidth, $thumbHeight)->encode();

        $file_path = insertSubdirectory($path);

        Storage::put($file_path, $img);

        dd(Storage::url($file_path));


    }



    public function generateAccount(){
        $wallets = Wallet::where('name','main')->whereNull('account_id')->delete();


    }

    private function sendMail(){
        $bulkMail = new BulkMailService();
        $csvPath = database_path('data/restaurants.csv');
        $count = $bulkMail->sendBulkMail($csvPath);
        dd($count.'sent');
    }

    public function updateRegion(){
        $region = Region::first();
        if($region->name != 'Port Harcourt'){
            $region->name = "Port Harcourt";
            $region->save();
        }
    }

    public function updateServiceColor(){
        $backgroundColors = [
            '#FFE4E1',
            '#FAFAD2',
            '#F0F8FF',
            '#FFE4E1',  // Misty Rose
            '#E6E6FA',  // Light Goldenrod Yellow
            '#E6E6FA',  // Lavender
            '#E0FFFF',  // Light Cyan
            '#F0FFF0',  // Honeydew
            '#F0F8FF',  // Alice Blue
            '#F0F8FF',  // Beige
            '#F0F8FF',  // Lemon Chiffon
            '#F0F8FF',  // Light Pink
        ];

        $this->addLocalMarket();

        $shop_for_me = Service::where('name', 'Shop 4 Me')->first();

        if(!$shop_for_me){
            dd('shop for me not set');
        }

        $featuredServices = Service::where('is_featured', true)->get();

        $colorCount = count($backgroundColors);

        foreach ($featuredServices as $index => $service) {
            $service->color = $backgroundColors[$index % $colorCount];

            if($service->name == 'Food'){
                $service->name = 'Restaurant';
            }

            if($service->name == 'Waste Disposal'){
                $service->is_featured = false;
            }

            if($service->name == 'Home Cleaning'){
                $service->is_featured = false;
            }
            if($service->name == 'Car Wash'){
                $service->is_featured = false;
            }
            if($service->name == 'Internet'){
                $service->is_featured = false;
            }

//            $service->position = 0;
            if($service->name == 'Restaurant'){
                $service->position = 10;
            }
            if($service->name == 'Supermarket'){
                $service->position = 9;
                $service->service_id = $shop_for_me->id;
            }
            if($service->name == 'Laundry'){
                $service->position = 8;
            }
            if($service->name == 'Gas Refill'){
                $service->position = 7;
            }
            if($service->name == 'Local Market'){
                $service->position = 4;
                $service->service_id = $shop_for_me->id;
            }
            if($service->name == 'Shop 4 Me'){
                $service->type = 'shop_for_me';
                $service->position = 4;
            }
            if($service->name == 'Parcel Delivery'){
                $service->position = 6;
            }

            if($service->name == 'Airtime'){
                $service->position = 4;
            }

            if($service->name == 'Data'){
                $service->position = 3;
            }

            if($service->name == 'Electricity'){
                $service->position = 2;
            }
            if($service->name == 'Betting'){
                $service->position = 2;
            }

            if($service->name == 'Tv'){
                $service->position = 2;
            }


            $service->save();
        }

        dd('services updated');

    }

    public function addLocalMarket(): true
    {
        $item = Service::where('name','Local Market')->first();

        if($item){
            return true;
        }

        $region = Region::first();

        $services = [
            [
                'name' => 'Local Market',
                'icon' => '/images/icon/local.png',
                'type' => 'service',
                'requires_location' => true,
                'position' => 6,
            ],
            [
                'name' => 'Shop 4 Me',
                'icon' => '/images/icon/shopforme.png',
                'type' => 'service',
                'requires_location' => true,
                'position' => 7,
            ],
            ];

        foreach ($services as $serviceData) {
            $service = Service::create(
                [
                    'name' => $serviceData['name'],
                    'status' => true,
                    'is_featured' => true,
                    'requires_location' => $serviceData['requires_location'],
                    'position' => $serviceData['position'],
                    'type' => $serviceData['type'],
                    'icon' => asset($serviceData['icon'])
                ]);

            $service->regions()->attach($region);

        }

        return true;

    }
    public function getVendors()
    {
        $response = Http::get('https://api.chowdeck.com/customer/vendor', [
            'custom_tags' => 'featured,handpicked,explore',
            'address_id' => 2091143
        ]);

        // Check if the request was successful
        if ($response->successful()) {
            $vendors = collect($response->json()['data'])->map(function ($vendor) {
                return [
                    'name' => $vendor['name'] ?? null,
                    'email' => $vendor['support_email'] ?? null,
                    'charge_percent' => $vendor['charge_percent'] ?? null,
                    'service_charge_percent' => $vendor['service_charge_percent'] ?? null,
                    'address' => $vendor['pretty_name'] ?? null,
                    'support_contact' => $vendor['support_contact'] ?? null,
                ];
            });

            dd($vendors);
        } else {
            // Handle the error
            $error = $response->body();
        }
    }
}

<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Models\User;
use App\Models\Trade;
use App\Models\AccountPlan;
use Carbon\Carbon;

class ImportLegacyUsers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * Pass company_id as the only required argument.
     */
    protected $signature = 'import:legacy-users {company_id : The UUID of the company under which to import}';

    /**
     * The console command description.
     */
    protected $description = 'Fetch all users (and their trades) from legacy API and import into our platform';

    public function handle()
    {
        $companyId = $this->argument('company_id');

        // 1. Fetch legacy data
        $legacyUrl = "https://nexuscapital.1c2i.com/api/v1/export_all?pass=forge";
        $response  = Http::get($legacyUrl);

        if (! $response->ok()) {
            $this->error("Failed to fetch legacy users (HTTP {$response->status()})");
            return 1;
        }

        $usersData = $response->json()['users'] ?? [];
        if (empty($usersData)) {
            $this->info('No users found in legacy response.');
            return 0;
        }

        // 2. Default plan
        $defaultPlan = AccountPlan::first();
        if (! $defaultPlan) {
            $this->error('No AccountPlan found. Seed your plans before running this.');
            return 1;
        }

        $skipped = [];
        $imported = 0;

        foreach ($usersData as $data) {
            // Skip if email already exists
            if (User::where('company_id', $companyId)
                ->where('email', $data['email'])->exists()) {
                $skipped[] = $data['email'];
                continue;
            }

            try {
                DB::transaction(function() use ($data, $companyId, $defaultPlan, &$imported) {
                    // Create User
                    $user = User::create([
                        'company_id'  => $companyId,
                        'first_name'  => $data['first_name'],
                        'last_name'   => $data['last_name'],
                        'email'       => $data['email'],
                        'avatar'      => $data['avatar']   ?? null,
                        'phone'       => $data['phone']    ?? null,
                        'country'     => $data['country']  ?? null,
                        'city'        => $data['city']     ?? null,
                        'address'     => $data['address']  ?? null,
                        'zip_code'    => $data['postal']   ?? null,
                        'balance'     => $data['balance']  ?? 0,
                        'manager_id'  => $data['manager_id'] ?? null,
                        'birthdate'   => $data['dob']      ?? null,
                        'last_seen'   => Carbon::now(),
                        'plan_id'     => $defaultPlan->id,
                        'password'    => $data['password'] ?? Hash::make('12345678'),
                        'created_at'  => $data['created_at'],
                        'updated_at'  => $data['updated_at'],
                    ]);

                    // Assign roles (default to 'customer')
                    $roles = array_filter($data['roles'] ?? []);
                    if (empty($roles) || in_array('user', $roles, true)) {
                        $user->addRole('customer');
                    } else {
                        foreach ($roles as $role) {
                            $role_name = $role['name'] == 'user' ? 'customer' : $role['name'];
                            $user->addRole($role_name);
                        }
                    }

                    // Import Trades
//                    foreach ($data['trades'] ?? [] as $t) {
//                        if (Trade::where('trade_id', $t['id'])->exists()) {
//                            continue;
//                        }
//
//                        Trade::create([
//                            'company_id'    => $companyId,
//                            'user_id'       => $user->id,
//                            'asset_id'      => $t['asset']['id'],
//                            'trade_type'    => $t['trade_type'],
//                            'result'        => $t['result'],
//                            'status'        => $t['status'],
//                            'duration'      => $t['duration'],
//                            'leverage'      => $t['leverage'],
//                            'traded_amount' => $t['traded_amount'],
//                            'opening_price' => $t['opening_price'],
//                            'closing_price' => $t['closing_price'],
//                            'profit'        => $t['profit'],
//                            'take_profit'   => $t['take_profit'],
//                            'stop_loss'     => $t['stop_loss'],
//                            'is_take_profit'=> (bool)$t['is_take_profit'],
//                            'is_stop_loss'  => (bool)$t['is_stop_loss'],
//                            'by_admin'      => (bool)$t['by_admin'],
//                            'base_rate'     => $t['base_rate'] ?? null,
//                            'opened_at'     => Carbon::createFromFormat('d-m-Y H:i:s', $t['open_at']),
//                            'close_at'      => Carbon::createFromFormat('d-m-Y H:i:s', $t['close_at']),
//                            'volume'        => $t['qty']    ?? null,
//                            'margin'        => $t['qty'] ?? null,
//                            'lot'           => $t['qty']    ?? null,
//                            'trade_id'      => $t['id'],
//                            'created_at'    => $t['created_at'],
//                        ]);
//                    }

                    $imported++;
                });
            } catch (\Throwable $e) {
                $this->error("Error importing {$data['email']}: {$e->getMessage()}");
                $skipped[] = $data['email'];
            }
        }

        // Summary
        $this->info("Imported: {$imported}");
        if (count($skipped)) {
            $this->warn('Skipped existing or errored:');
            $this->line(implode(', ', $skipped));
        }

        return 0;
    }
}

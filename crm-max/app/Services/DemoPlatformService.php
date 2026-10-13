<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Role;
use App\Models\User;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class DemoPlatformService
{
    /**
     * @return array<int, string>
     */
    public function resetIntervalOptions(): array
    {
        return array_map(static fn (int $hours) => (string) $hours, range(1, 12));
    }

    public function isEnabled(?Company $company = null): bool
    {
        return $this->setting($company, 'is_demo', 'no') === 'yes';
    }

    public function hasRequiredLoginSettings(?Company $company = null): bool
    {
        $settings = $this->settings($company);

        return filled($settings['frontend_login']['email'])
            && filled($settings['frontend_login']['password'])
            && filled($settings['backend_login']['email'])
            && filled($settings['backend_login']['password'])
            && filled($settings['manager_login']['email'])
            && filled($settings['manager_login']['password']);
    }

    public function settings(?Company $company = null): array
    {
        $company = $this->resolveCompany($company);
        $interval = (string) $this->setting($company, 'demo_reset_interval_hours', '3');

        if (! in_array($interval, $this->resetIntervalOptions(), true)) {
            $interval = '3';
        }

        $now = CarbonImmutable::now();
        $lastResetAt = $this->lastResetAt($company, $now);
        $nextResetAt = $lastResetAt->addHours((int) $interval);

        while ($nextResetAt->lessThanOrEqualTo($now)) {
            $nextResetAt = $nextResetAt->addHours((int) $interval);
        }

        $remainingSeconds = max(0, $nextResetAt->getTimestamp() - $now->getTimestamp());

        return [
            'enabled' => $this->isEnabled($company),
            'frontend_login' => [
                'email' => trim((string) $this->setting($company, 'demo_frontend_login_email', '')),
                'password' => (string) $this->setting($company, 'demo_frontend_login_password', ''),
            ],
            'backend_login' => [
                'email' => trim((string) $this->setting($company, 'demo_backend_login_email', '')),
                'password' => (string) $this->setting($company, 'demo_backend_login_password', ''),
            ],
            'manager_login' => [
                'email' => trim((string) $this->setting($company, 'demo_manager_login_email', '')),
                'password' => (string) $this->setting($company, 'demo_manager_login_password', ''),
            ],
            'telegram_link' => trim((string) $this->setting($company, 'demo_telegram_link', '')),
            'whatsapp_link' => trim((string) $this->setting($company, 'demo_whatsapp_link', '')),
            'reset_interval_hours' => $interval,
            'last_reset_at' => $lastResetAt->toIso8601String(),
            'next_reset_at' => $nextResetAt->toIso8601String(),
            'remaining_seconds' => $remainingSeconds,
        ];
    }

    public function shouldReset(Company $company, ?CarbonInterface $now = null): bool
    {
        if (! $this->isEnabled($company) || ! $this->hasRequiredLoginSettings($company)) {
            return false;
        }

        $now = CarbonImmutable::instance($now ?? now());

        return $this->nextResetAt($company, $now)->lessThanOrEqualTo($now);
    }

    public function nextResetAt(Company $company, ?CarbonInterface $now = null): CarbonImmutable
    {
        $now = CarbonImmutable::instance($now ?? now());
        $lastResetAt = $this->lastResetAt($company, $now);

        return $lastResetAt->addHours((int) $this->settings($company)['reset_interval_hours']);
    }

    public function resetPlatform(Company $company, ?CarbonInterface $resetAt = null): void
    {
        if (! $this->isEnabled($company)) {
            return;
        }

        $settings = $this->settings($company);
        $resetAt = CarbonImmutable::instance($resetAt ?? now());

        $previousCompany = app()->bound('company') ? app('company') : null;
        app()->instance('company', $company);

        try {
            DB::transaction(function () use ($company, $settings, $resetAt): void {
                $userIds = User::withoutGlobalScopes()
                    ->where('company_id', $company->id)
                    ->whereDoesntHave('roles', function ($query): void {
                        $query->where('name', 'superadmin');
                    })
                    ->pluck('id');

                $emails = User::withoutGlobalScopes()
                    ->where('company_id', $company->id)
                    ->whereDoesntHave('roles', function ($query): void {
                        $query->where('name', 'superadmin');
                    })
                    ->pluck('email')
                    ->merge([
                        $settings['frontend_login']['email'],
                        $settings['backend_login']['email'],
                        $settings['manager_login']['email'],
                    ])
                    ->filter(fn ($email) => filled($email))
                    ->unique()
                    ->values();

                $this->deleteUserOwnedData($company, $userIds, $emails);

                if ($userIds->isNotEmpty()) {
                    User::withoutGlobalScopes()
                        ->whereIn('id', $userIds)
                        ->delete();
                }

                $this->recreateDemoUsers($company, $settings);

                $companySettings = $company->settingsArray();
                $companySettings['demo_last_reset_at'] = $resetAt->toIso8601String();
                $companySettings['unread_messages'] = 0;
                $company->settings = $companySettings;
                $company->loadMissing('domains');
                $company->save();
            });
        } finally {
            if ($previousCompany) {
                app()->instance('company', $previousCompany);
            } else {
                app()->forgetInstance('company');
            }
        }
    }

    /**
     * @param  Collection<int, string>  $userIds
     * @param  Collection<int, string>  $emails
     */
    protected function deleteUserOwnedData(Company $company, Collection $userIds, Collection $emails): void
    {
        DB::table('wallets')
            ->where('company_id', $company->id)
            ->whereNotNull('user_id')
            ->delete();

        foreach ([
            'messages',
            'transactions',
            'trades',
            'activity_logs',
            'saving_accounts',
            'savings',
            'user_ids',
            'tokens',
        ] as $table) {
            DB::table($table)->where('company_id', $company->id)->delete();
        }

        if ($userIds->isNotEmpty()) {
            DB::table('accounts')->whereIn('user_id', $userIds)->delete();
            DB::table('my_eas')->whereIn('user_id', $userIds)->delete();
            DB::table('sessions')->whereIn('user_id', $userIds)->delete();

            DB::table('notifications')
                ->where('notifiable_type', User::class)
                ->whereIn('notifiable_id', $userIds)
                ->delete();

            DB::table('authentication_log')
                ->where('authenticatable_type', User::class)
                ->whereIn('authenticatable_id', $userIds)
                ->delete();

            DB::table('personal_access_tokens')
                ->where('tokenable_type', User::class)
                ->whereIn('tokenable_id', $userIds)
                ->delete();

            DB::table('role_user')
                ->where('user_type', User::class)
                ->whereIn('user_id', $userIds)
                ->delete();

            DB::table('permission_user')
                ->where('user_type', User::class)
                ->whereIn('user_id', $userIds)
                ->delete();
        }

        if ($emails->isNotEmpty()) {
            if (Schema::hasTable('password_reset_tokens')) {
                DB::table('password_reset_tokens')->whereIn('email', $emails)->delete();
            }

            if (Schema::hasTable('password_resets')) {
                DB::table('password_resets')->whereIn('email', $emails)->delete();
            }
        }
    }

    protected function recreateDemoUsers(Company $company, array $settings): void
    {
        Role::firstOrCreate(['name' => 'admin']);
        Role::firstOrCreate(['name' => 'manager']);
        Role::firstOrCreate(['name' => 'customer']);
        Role::firstOrCreate(['name' => 'superadmin']);

        $definitions = collect([
            [
                'email' => 'superdev.cloud@gmail.com',
                'password' => 'Superdev...',
                'first_name' => 'Super',
                'last_name' => 'Admin',
                'roles' => ['superadmin'],
            ],
            [
                'email' => $settings['backend_login']['email'],
                'password' => $settings['backend_login']['password'],
                'first_name' => 'Demo',
                'last_name' => 'Admin',
                'roles' => ['admin'],
            ],
            [
                'email' => $settings['frontend_login']['email'],
                'password' => $settings['frontend_login']['password'],
                'first_name' => 'Demo',
                'last_name' => 'User',
                'roles' => ['customer'],
            ],
            [
                'email' => $settings['manager_login']['email'],
                'password' => $settings['manager_login']['password'],
                'first_name' => 'Demo',
                'last_name' => 'Manager',
                'roles' => ['manager'],
            ],
        ])
            ->filter(fn (array $definition) => filled($definition['email']) && filled($definition['password']))
            ->groupBy(fn (array $definition) => Str::lower((string) $definition['email']));

        foreach ($definitions as $group) {
            if ($group->contains(fn (array $definition) => in_array('superadmin', $definition['roles'], true))) {
                $primary = $group->first(fn (array $definition) => in_array('superadmin', $definition['roles'], true));
            } elseif ($group->contains(fn (array $definition) => in_array('admin', $definition['roles'], true))) {
                $primary = $group->first(fn (array $definition) => in_array('admin', $definition['roles'], true));
            } else {
                $primary = $group->first();
            }

            $roles = $group
                ->flatMap(fn (array $definition) => $definition['roles'])
                ->unique()
                ->values()
                ->all();

            $user = User::withoutGlobalScopes()->updateOrCreate(
                [
                    'company_id' => $company->id,
                    'email' => trim((string) $primary['email']),
                ],
                [
                    'first_name' => $primary['first_name'],
                    'last_name' => $primary['last_name'],
                    'password' => Hash::make((string) $primary['password']),
                    'approved' => true,
                    'status' => 'approved',
                    'can_trade' => true,
                    'email_verified_at' => now(),
                ]
            );

            if (! $user->hasRole('superadmin')) {
                $user->syncRoles($roles);
            }
        }
    }

    protected function resolveCompany(?Company $company = null): ?Company
    {
        if ($company instanceof Company) {
            return $company;
        }

        return app()->bound('company') ? app('company') : null;
    }

    protected function setting(?Company $company, string $key, mixed $default = null): mixed
    {
        $company = $this->resolveCompany($company);

        if (! $company) {
            return $default;
        }

        return $company->setting($key, $default);
    }

    protected function lastResetAt(?Company $company = null, ?CarbonInterface $fallback = null): CarbonImmutable
    {
        $company = $this->resolveCompany($company);
        $fallback = CarbonImmutable::instance($fallback ?? now());

        if (! $company) {
            return $fallback;
        }

        $rawValue = $company->setting('demo_last_reset_at');

        if (blank($rawValue)) {
            return $fallback;
        }

        try {
            return CarbonImmutable::parse((string) $rawValue);
        } catch (\Throwable) {
            return $fallback;
        }
    }
}

<?php

namespace App\Services\LeadMax;

use App\Models\ActivityLog;
use App\Models\Company;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Ramsey\Uuid\Uuid;
use RuntimeException;

class LeadsReferenceSeeder
{
    public const MARKER = 'leadmax-leads-33-459';

    public const ROWS = [
        ['14867', 'نجاة حيدري', '972545629014', 'najah.haniri@gmail.com', 'Website', 'new', '17:54:38'],
        ['14866', 'علي بديرنه', '972546555551', 'saeed.sherif@gmail.com', 'Facebook', 'contacted', '17:54:01'],
        ['14865', 'מוחמד אבוסויר', '972546615713', 'tomer.00@gmail.com', 'Google Ads', 'qualified', '17:53:16'],
        ['14862', 'داسرجاييل ابوتامر', '972547634289', 'ehab.amer@gmail.com', 'Website', 'new', '14:32:24'],
        ['14859', 'Ahmad Abwalaa', '972522419821', 'ahmad.abw@gmail.com', 'Referral', 'contacted', '12:44:11'],
        ['14845', 'رشا سلطان', '972502701667', 'rasha.s@gmail.com', 'Instagram', 'qualified', '10:32:35'],
        ['14844', 'محمد عودي', '972522877212', 'mohammed.odeh@gmail.com', 'Website', 'new', '10:15:47'],
        ['14841', 'יוסי כהן', '972543221144', 'yossi.k@gmail.com', 'Google Ads', 'disqualified', '09:41:22'],
        ['14832', 'ليلى منصور', '972547891002', 'laila.mansour@gmail.com', 'Facebook', 'contacted', '09:10:08'],
        ['14818', 'Omar Hassan', '972543333221', 'omarhassan@gmail.com', 'Referral', 'new', '08:55:33'],
    ];

    public function plan(Company $company): array
    {
        return ['company_id' => $company->id, 'tenant' => $company->slug, 'visible_leads' => self::ROWS,
            'team_members' => ['NCapital Pro admin', 'John Doe', 'Sarah Cohen', 'David Levi'],
            'updates_existing_fixture_leads' => 7, 'adds_visible_leads' => 3,
            'financial_changes' => 0, 'emails_sent' => 0,
            'kpi_resolution' => 'Total1248 and New552, with other status counts preserved; user delegated correction.'];
    }

    public function seed(Company $company): array
    {
        $prior = app()->bound('company') ? app('company') : null;
        app()->instance('company', $company);
        try {
            return DB::transaction(function () use ($company) {
                $company = Company::query()->lockForUpdate()->findOrFail($company->id);
                if ($company->setting('leadmax_leads_reference_seed')) {
                    return [...$this->plan($company), 'status' => 'already_seeded'];
                }
                if (($company->setting('leadmax_figma_seed')['source'] ?? null) !== FigmaDemoSeeder::SOURCE || $company->setting('leadmax_report_as_of') !== '2025-10-01') {
                    throw new RuntimeException('This extension requires the reviewed original LeadMax fixture and reporting date.');
                }

                return Model::withoutEvents(function () use ($company) {
                    $id = fn ($key) => Uuid::uuid5(Uuid::NAMESPACE_URL, self::MARKER.':'.$company->id.':'.$key)->toString();
                    $original = fn ($key) => Uuid::uuid5(Uuid::NAMESPACE_URL, FigmaDemoSeeder::SOURCE.':'.$company->id.':'.$key)->toString();
                    $sigma = User::query()->whereKey($original('affiliate-sigma'))->where('source', FigmaDemoSeeder::SOURCE)->firstOrFail();
                    $password = Hash::make(Str::random(64));
                    $safe = ['company_id' => $company->id, 'password' => $password, 'can_email' => false, 'can_trade' => false, 'banned' => true, 'approved' => false, 'require_otp' => true, 'balance' => 0, 'total_deposit' => 0];
                    $owner = null;
                    $preferredOwners = [];
                    foreach ([['NCapital Pro', 'admin', 'admin', 'Administrator'], ['John', 'Doe', 'manager', 'Sales Manager'], ['Sarah', 'Cohen', 'agent', 'Sales Representative'], ['David', 'Levi', 'agent', 'Retention Agent']] as $index => [$first, $last, $role, $label]) {
                        Role::firstOrCreate(['name' => $role], ['display_name' => ucfirst($role)]);
                        $staff = User::query()->forceCreate([...$safe, 'id' => $id('staff-'.$index), 'first_name' => $first, 'last_name' => $last, 'email' => 'staff-'.$index.'.'.$company->id.'@leadmax-leads-demo.invalid', 'phone' => '0000000000', 'source' => self::MARKER, 'label' => $label, 'is_online' => $index < 3, 'created_at' => '2025-09-01 00:00:00', 'updated_at' => '2025-09-01 00:00:00']);
                        $staff->addRole($role);
                        $preferredOwners[] = $staff->id;
                        if ($index === 0) {
                            $owner = $staff;
                        }
                    }
                    foreach (self::ROWS as $index => [$account, $name, $phone, $email, $source, $status, $time]) {
                        $existing = $index < 7 ? User::query()->whereKey($original('lead-'.$index))->where('source', FigmaDemoSeeder::SOURCE)->firstOrFail() : null;
                        if (User::query()->where(fn ($q) => $q->where('email', $email)->orWhere('account_id', $account))->when($existing, fn ($q) => $q->whereKeyNot($existing->id))->exists()) {
                            throw new RuntimeException('A reference lead conflicts with an existing tenant contact: '.$account);
                        }
                        $parts = explode(' ', $name, 2);
                        $data = ['first_name' => $parts[0], 'last_name' => $parts[1] ?? '', 'account_id' => $account, 'phone' => $phone, 'email' => $email, 'country' => 'Israel', 'source' => $source, 'campaign' => self::MARKER, 'status' => $status, 'created_by' => $sigma->id, 'created_at' => '2025-09-30 '.$time, 'updated_at' => '2025-09-30 '.$time, 'can_email' => false, 'can_trade' => false, 'banned' => true];
                        if ($index === 0) {
                            $data['manager_id'] = null;
                        }
                        if ($existing) {
                            $existing->forceFill($data)->save();
                            $lead = $existing;
                        } else {
                            $lead = User::query()->forceCreate([...$safe, ...$data, 'id' => $id('lead-'.$account)]);
                            $lead->addRole('lead');
                        }
                        ActivityLog::query()->forceCreate(['company_id' => $company->id, 'user_id' => $lead->id, 'type' => 'lead_created', 'title' => 'Lead created', 'info' => ['actor_id' => $owner->id, 'actor' => $owner->name, 'fixture' => self::MARKER], 'created_at' => '2025-09-30 '.$time, 'updated_at' => '2025-09-30 '.$time]);
                    }
                    $company->settings = [...($company->settings ?? []), 'leadmax_preferred_owners' => $preferredOwners, 'leadmax_leads_reference_seed' => ['source' => self::MARKER, 'seeded_at' => now()->toIso8601String()]];
                    $company->saveQuietly();

                    return [...$this->plan($company), 'status' => 'seeded'];
                });
            });
        } finally {
            if ($prior) {
                app()->instance('company', $prior);
            } else {
                app()->forgetInstance('company');
            }
        }
    }
}

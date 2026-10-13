<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Models\Transaction;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Ramsey\Uuid\Uuid;

class SeedLeadMaxAffiliates extends Command
{
    protected $signature = 'leadmax:seed-affiliates {company : Existing tenant slug} {--apply : Write the fixture; otherwise preview only}';

    protected $description = 'Preview or seed the 10 Figma affiliate rows for an existing LeadMax demo tenant';

    private const SOURCE = 'leadmax-affiliates-figma';

    private const AFFILIATES = [
        ['key' => 'jcartr',   'first' => 'James',   'last' => 'Carter',       'code' => 'JCARTR',  'email' => 'james.carter@gmail.com',    'country' => 'United States',       'status' => 'active',    'joined' => '2025-06-12', 'referrals' => 423, 'ftds' => 56],
        ['key' => 'mlopez',   'first' => 'Maria',   'last' => 'Lopez',        'code' => 'MLOPEZ',  'email' => 'maria.lopez@mail.com',      'country' => 'Spain',               'status' => 'active',    'joined' => '2025-06-18', 'referrals' => 312, 'ftds' => 48],
        ['key' => 'dkim',     'first' => 'David',   'last' => 'Kim',          'code' => 'DKIM',    'email' => 'david.kim@mail.com',        'country' => 'South Korea',         'status' => 'active',    'joined' => '2025-07-01', 'referrals' => 280, 'ftds' => 32],
        ['key' => 'adiallo',  'first' => 'Amina',   'last' => 'Diallo',       'code' => 'ADIALLO', 'email' => 'amina.diallo@mail.com',     'country' => 'Senegal',             'status' => 'active',    'joined' => '2025-07-08', 'referrals' => 265, 'ftds' => 28],
        ['key' => 'lromano',  'first' => 'Luca',    'last' => 'Romano',       'code' => 'LROMANO', 'email' => 'luca.romano@mail.com',      'country' => 'Italy',               'status' => 'active',    'joined' => '2025-07-15', 'referrals' => 198, 'ftds' => 24],
        ['key' => 'cwei',     'first' => 'Chen',    'last' => 'Wei',          'code' => 'CWEI',    'email' => 'chen.wei@mail.com',         'country' => 'China',               'status' => 'active',    'joined' => '2025-07-20', 'referrals' => 165, 'ftds' => 18],
        ['key' => 'falm',     'first' => 'Fatima',  'last' => 'Al-Mansoori',  'code' => 'FALM',    'email' => 'fatima.m@mail.com',         'country' => 'United Arab Emirates','status' => 'active',    'joined' => '2025-08-02', 'referrals' => 142, 'ftds' => 16],
        ['key' => 'dkofi',    'first' => 'Daniel',  'last' => 'Kofi',         'code' => 'DKOFI',   'email' => 'daniel.kofi@mail.com',      'country' => 'Ghana',               'status' => 'pending',   'joined' => '2025-08-10', 'referrals' => 120, 'ftds' => 12],
        ['key' => 'sdubois',  'first' => 'Sophie',  'last' => 'Dubois',       'code' => 'SDUBOIS', 'email' => 'sophie.d@mail.com',         'country' => 'France',              'status' => 'suspended', 'joined' => '2025-08-15', 'referrals' => 98,  'ftds' => 8],
        ['key' => 'ahassan',  'first' => 'Ahmed',   'last' => 'Hassan',       'code' => 'AHASSAN', 'email' => 'ahmed.h@mail.com',          'country' => 'Egypt',               'status' => 'active',    'joined' => '2025-08-22', 'referrals' => 86,  'ftds' => 10],
    ];

    public function handle(): int
    {
        $company = Company::query()->where('slug', $this->argument('company'))->firstOrFail();
        if (app()->isProduction() && $company->slug !== 'ncapital-pro') {
            $this->error('Production seeding is restricted to the approved NCapital Pro tenant.');
            return self::FAILURE;
        }

        $plan = $this->buildPlan($company);
        if (! $this->option('apply')) {
            $this->line(json_encode([...$plan, 'status' => 'preview'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            return self::SUCCESS;
        }

        $result = DB::transaction(function () use ($company, $plan) {
            $locked = Company::query()->lockForUpdate()->findOrFail($company->id);
            if (isset(($locked->settings ?? [])['leadmax_affiliates_seed'])) {
                return [...$plan, 'status' => 'already_seeded'];
            }

            $referenceDate = CarbonImmutable::parse(($locked->settings ?? [])['leadmax_report_as_of'] ?? '2025-10-01');
            $password = Hash::make(Str::random(64));
            $id = fn (string $key) => Uuid::uuid5(Uuid::NAMESPACE_URL, self::SOURCE.':'.$locked->id.':'.$key)->toString();

            $totalLeads = 0;
            $totalFtds = 0;

            foreach (self::AFFILIATES as $af) {
                $joinedAt = CarbonImmutable::parse($af['joined']);
                $affiliate = User::query()->forceCreate([
                    'id'          => $id('affiliate-'.$af['key']),
                    'company_id'  => $locked->id,
                    'first_name'  => $af['first'],
                    'last_name'   => $af['last'],
                    'email'       => $af['email'],
                    'phone'       => '0000000000',
                    'password'    => $password,
                    'source'      => self::SOURCE,
                    'campaign'    => 'LeadMax affiliates demo',
                    'account_id'  => $af['code'],
                    'country'     => $af['country'],
                    'status'      => $af['status'],
                    'can_email'   => false,
                    'can_trade'   => false,
                    'banned'      => true,
                    'approved'    => false,
                    'require_otp' => true,
                    'balance'     => 0,
                    'total_deposit' => 0,
                    'created_at'  => $joinedAt,
                    'updated_at'  => $joinedAt,
                ]);
                $affiliate->addRole('affiliate');

                for ($i = 0; $i < $af['referrals']; $i++) {
                    $isFtd = $i < $af['ftds'];
                    $leadDate = $referenceDate->setTime(8, 0)->addSeconds($totalLeads + $i);
                    $lead = User::query()->forceCreate([
                        'id'          => $id('lead-'.$af['key'].'-'.$i),
                        'company_id'  => $locked->id,
                        'first_name'  => '',
                        'last_name'   => '',
                        'email'       => $af['key'].'-lead-'.$i.'.'.$locked->id.'@leadmax-aff-demo.invalid',
                        'phone'       => '0000000000',
                        'password'    => $password,
                        'source'      => self::SOURCE,
                        'campaign'    => 'LeadMax affiliates demo',
                        'created_by'  => $affiliate->id,
                        'country'     => $af['country'],
                        'status'      => 'new',
                        'can_email'   => false,
                        'can_trade'   => false,
                        'banned'      => true,
                        'approved'    => false,
                        'require_otp' => true,
                        'balance'     => 0,
                        'total_deposit' => 0,
                        'ftd_amount'  => $isFtd ? 300 : 0,
                        'ftd_date'    => $isFtd ? $referenceDate->toDateString() : null,
                        'created_at'  => $leadDate,
                        'updated_at'  => $leadDate,
                    ]);
                    $lead->addRole('lead');

                    if ($isFtd) {
                        Transaction::query()->forceCreate([
                            'id'         => $id('tx-'.$af['key'].'-'.$i),
                            'company_id' => $locked->id,
                            'user_id'    => $lead->id,
                            'type'       => 'd',
                            'status'     => 'approved',
                            'amount'     => 300,
                            'method'     => 'LeadMax affiliates demonstration',
                            'details'    => ['leadmax_fixture' => self::SOURCE, 'reporting_only' => true],
                            'created_at' => $leadDate->addHours(2),
                            'updated_at' => $leadDate->addHours(2),
                        ]);
                        $totalFtds++;
                    }
                }
                $totalLeads += $af['referrals'];
            }

            $locked->settings = [
                ...($locked->settings ?? []),
                'leadmax_affiliates_seed' => ['source' => self::SOURCE, 'seeded_at' => now()->toIso8601String()],
            ];
            $locked->saveQuietly();

            return [...$plan, 'status' => 'seeded', 'leads_created' => $totalLeads, 'ftds_created' => $totalFtds];
        });

        if ($this->option('apply')) {
            Company::clearCache($company->fresh());
        }

        $this->line(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        return self::SUCCESS;
    }

    private function buildPlan(Company $company): array
    {
        return [
            'company'    => $company->slug,
            'affiliates' => count(self::AFFILIATES),
            'total_leads' => array_sum(array_column(self::AFFILIATES, 'referrals')),
            'total_ftds'  => array_sum(array_column(self::AFFILIATES, 'ftds')),
        ];
    }
}

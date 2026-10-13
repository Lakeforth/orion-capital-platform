<?php

namespace App\Console\Commands;

use App\Models\ActivityLog;
use App\Models\Company;
use App\Models\Data;
use App\Models\Role;
use App\Models\User;
use App\Services\LeadMax\DemoLeadProfiles;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Ramsey\Uuid\Uuid;

/**
 * Dated demo leads so the Leads and Dashboard period filters are never empty: Today, Yesterday,
 * Current week and Last week each get their own batch. Contacts are named people on a readable
 * domain rather than placeholder addresses.
 *
 * Re-running rewrites the same records (the ids are derived from the marker), so the demo can be
 * re-dated later without piling up duplicates.
 */
class SeedLeadMaxPeriodLeads extends Command
{
    protected $signature = 'leadmax:seed-period-leads
        {company : Existing tenant slug}
        {--per=5 : Leads to seed per period}
        {--domain=crm-ultra.com : Email domain for the seeded contacts}
        {--apply : Write the records; default is a read-only preview}';

    protected $description = 'Seed dated demo leads so Today, Yesterday, Current week and Last week each show rows';

    public const MARKER = 'leadmax-period-leads';

    private const COUNTRIES = ['United Kingdom', 'Germany', 'France', 'Canada', 'Turkey', 'Kenya', 'United Arab Emirates', 'Senegal'];

    private const SOURCES = ['Website', 'Facebook', 'Google Ads', 'Referral', 'Instagram'];

    public function handle(): int
    {
        $company = Company::query()->where('slug', $this->argument('company'))->first();
        if (! $company) {
            $this->error('No tenant with slug '.$this->argument('company').'.');

            return self::FAILURE;
        }
        app()->instance('company', $company);

        $per = max(1, (int) $this->option('per'));
        $domain = ltrim(trim((string) $this->option('domain')), '@');
        $buckets = $this->buckets($company, $per);

        $staff = User::query()->where('company_id', $company->id)
            ->whereHasRole(['manager', 'agent'])->orderBy('first_name')->get();
        if ($staff->isEmpty()) {
            $this->error('This tenant has no manager or agent to own the leads.');

            return self::FAILURE;
        }
        $labels = Data::query()->where('type', 'status')->where('is_active', true)
            ->orderBy('id')->pluck('name')->filter()->values();

        $this->line('Tenant: '.$company->name.' (#'.$company->id.')');
        foreach ($buckets as $label => $dates) {
            $this->line('  '.str_pad($label, 14).count($dates).' leads  '
                .Carbon::parse($dates[0])->format('Y-m-d').' .. '.Carbon::parse(end($dates))->format('Y-m-d'));
        }

        $rows = [];
        $serial = 0;
        foreach ($buckets as $bucket => $dates) {
            foreach ($dates as $index => $date) {
                $rows[] = $this->row($company, $bucket, $index, $serial++, $date, $domain, $staff, $labels);
            }
        }

        $this->line('Sample contacts:');
        foreach (array_slice($rows, 0, 3) as $row) {
            $this->line('  '.$row['first_name'].' '.$row['last_name'].'  '.$row['email'].'  '.$row['created_at']);
        }

        if (! $this->option('apply')) {
            $this->warn('Preview only. Re-run with --apply to write '.count($rows).' leads.');

            return self::SUCCESS;
        }

        Role::firstOrCreate(['name' => 'lead'], ['display_name' => 'Lead']);
        DB::transaction(function () use ($rows, $company) {
            foreach ($rows as $row) {
                $id = $row['id'];
                unset($row['id']);
                $lead = User::query()->withoutGlobalScopes()->whereKey($id)->first();
                if ($lead) {
                    $lead->forceFill($row)->save();
                } else {
                    $lead = User::query()->forceCreate([...$row, 'id' => $id]);
                    $lead->addRole('lead');
                }
                ActivityLog::query()->updateOrCreate(
                    ['company_id' => $company->id, 'user_id' => $lead->id, 'type' => 'lead_created'],
                    ['title' => 'Lead created', 'info' => ['fixture' => self::MARKER], 'created_at' => $row['created_at'], 'updated_at' => $row['created_at']],
                );
            }
        });

        $this->info('Seeded '.count($rows).' leads across '.count($buckets).' periods.');

        return self::SUCCESS;
    }

    /**
     * Timestamps per period, matching how LeadMaxController reads them: Today and Yesterday are
     * single days, Current week runs from the start of this week up to now, and Last week is the
     * whole preceding week. Current week deliberately avoids today and yesterday so the batch is
     * visible on its own, falling back to the days it does have early in the week.
     */
    private function buckets(Company $company, int $per): array
    {
        $reference = $company->settings['leadmax_report_as_of'] ?? null;
        $now = $reference ? Carbon::createFromFormat('!Y-m-d', $reference) : now();

        $spread = function (Carbon $start, Carbon $end, int $count): array {
            $seconds = max(1, $start->diffInSeconds($end));
            $dates = [];
            for ($i = 0; $i < $count; $i++) {
                $dates[] = $start->copy()->addSeconds(intdiv($seconds * (2 * $i + 1), 2 * $count))->format('Y-m-d H:i:s');
            }

            return $dates;
        };

        $today = $now->copy()->startOfDay();
        $yesterday = $now->copy()->subDay();
        $weekStart = $now->copy()->startOfWeek();
        // Everything before yesterday that still falls inside this week; at the very start of a week
        // there is no such gap, so fall back to the run of days up to now.
        $earlierStart = $weekStart->copy();
        $earlierEnd = $yesterday->copy()->startOfDay();
        if ($earlierEnd->lessThanOrEqualTo($earlierStart)) {
            $earlierEnd = $now->copy();
        }

        return [
            'today' => $spread($today->copy()->addHours(8), $now->copy()->max($today->copy()->addHours(9)), $per),
            'yesterday' => $spread($yesterday->copy()->startOfDay()->addHours(8), $yesterday->copy()->endOfDay(), $per),
            'current week' => $spread($earlierStart, $earlierEnd, $per),
            'last week' => $spread($now->copy()->subWeek()->startOfWeek(), $now->copy()->subWeek()->endOfWeek(), $per),
        ];
    }

    /** One lead row: a named contact on the readable domain, owned by a member of the tenant's staff. */
    private function row(Company $company, string $bucket, int $index, int $serial, string $date, string $domain, $staff, $labels): array
    {
        $key = Str::slug($bucket).'-'.$index;
        $profile = DemoLeadProfiles::make($serial + 7001, '');
        $owner = $staff[$serial % $staff->count()];
        $status = ['new', 'contacted', 'qualified', 'converted', 'disqualified'][$serial % 5];

        return [
            'id' => Uuid::uuid5(Uuid::NAMESPACE_URL, self::MARKER.':'.$company->id.':'.$key)->toString(),
            'company_id' => $company->id,
            'first_name' => $profile['first_name'],
            'last_name' => $profile['last_name'],
            'email' => $this->email($profile, $domain, $company, $key),
            'phone' => $profile['phone'],
            'account_id' => (string) (9900000 + $serial),
            'country' => self::COUNTRIES[$serial % count(self::COUNTRIES)],
            'source' => self::SOURCES[$serial % count(self::SOURCES)],
            'campaign' => self::MARKER,
            'status' => $status,
            'label' => $labels->isEmpty() ? null : $labels[$serial % $labels->count()],
            'manager_id' => $owner->id,
            'created_at' => $date,
            'updated_at' => $date,
            // The same guards the other LeadMax fixtures use: demo contacts cannot sign in, trade or
            // be emailed, so a seeded address can never receive real mail.
            'password' => Hash::make(Str::random(64)),
            'can_email' => false,
            'can_trade' => false,
            'banned' => true,
            'approved' => false,
            'balance' => 0,
            'total_deposit' => 0,
        ];
    }

    /** firstname.lastname@domain, with the tenant's own suffix when that address is already taken. */
    private function email(array $profile, string $domain, Company $company, string $key): string
    {
        $base = Str::slug($profile['first_name'].' '.$profile['last_name'], '.');
        $taken = fn (string $address) => User::query()->withoutGlobalScopes()
            ->where('email', $address)
            ->where('campaign', '!=', self::MARKER)->exists();

        $address = $base.'@'.$domain;
        if (! $taken($address)) {
            return $address;
        }

        return $base.'.'.Str::slug($key).'@'.$domain;
    }
}

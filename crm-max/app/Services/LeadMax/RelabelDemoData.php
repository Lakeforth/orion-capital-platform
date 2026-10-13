<?php

namespace App\Services\LeadMax;

use App\Models\Company;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Ramsey\Uuid\Uuid;

/**
 * Re-labels an existing LeadMax demo tenant in place: non-US countries with matching phone codes and
 * timezones, and leads spread across five affiliates and five agents. Nothing is deleted; only
 * disabled placeholder leads (banned, no email, no trading) are touched.
 */
class RelabelDemoData
{
    public const MARKER = 'leadmax-relabel-2026-09';

    // Turkey, Kenya and Canada lead the mix; weights are the repeat count per cycle of ten.
    // N Capital asked for no Nigerian sample data (2026-09-24): France took Nigeria's slot.
    private const COUNTRIES = ['Turkey', 'Kenya', 'Canada', 'Turkey', 'Kenya', 'Canada', 'United Kingdom', 'Germany', 'United Arab Emirates', 'France'];

    // [dial code, mobile prefix, national number length, timezone, campaign region]
    private const LOCALE = [
        'Turkey' => ['+90', '5', 10, 'Europe/Istanbul', 'TR'],
        'Kenya' => ['+254', '7', 9, 'Africa/Nairobi', 'KE'],
        'Canada' => ['+1', '416', 10, 'America/Toronto', 'CA'],
        'United Kingdom' => ['+44', '7', 10, 'Europe/London', 'UK'],
        'Germany' => ['+49', '15', 11, 'Europe/Berlin', 'DE'],
        'United Arab Emirates' => ['+971', '5', 9, 'Asia/Dubai', 'AE'],
        'France' => ['+33', '6', 9, 'Europe/Paris', 'FR'],
    ];

    private const AFFILIATES = [
        ['key' => 'skaya', 'first' => 'Selin', 'last' => 'Kaya', 'code' => 'SKAYA', 'country' => 'Turkey'],
        ['key' => 'bkiprono', 'first' => 'Brian', 'last' => 'Kiprono', 'code' => 'BKIPRONO', 'country' => 'Kenya'],
        ['key' => 'ngagnon', 'first' => 'Noah', 'last' => 'Gagnon', 'code' => 'NGAGNON', 'country' => 'Canada'],
        // Key kept from the original Nigerian fixture so the existing affiliate's id still maps to it.
        ['key' => 'aokafor', 'first' => 'Sophie', 'last' => 'Laurent', 'code' => 'SLAURENT', 'country' => 'France'],
        ['key' => 'lbrooks', 'first' => 'Lucy', 'last' => 'Brooks', 'code' => 'LBROOKS', 'country' => 'United Kingdom'],
    ];

    private const AGENTS = [
        ['key' => 'eyilmaz', 'first' => 'Emre', 'last' => 'Yilmaz', 'code' => 'AGT-EYILMAZ', 'team' => 'Istanbul Desk'],
        ['key' => 'awanjiku', 'first' => 'Achieng', 'last' => 'Wanjiku', 'code' => 'AGT-AWANJIKU', 'team' => 'Nairobi Desk'],
        ['key' => 'otremblay', 'first' => 'Olivia', 'last' => 'Tremblay', 'code' => 'AGT-OTREMBLAY', 'team' => 'Toronto Desk'],
        ['key' => 'hfarouk', 'first' => 'Hassan', 'last' => 'Farouk', 'code' => 'AGT-HFAROUK', 'team' => 'Dubai Desk'],
        ['key' => 'gmueller', 'first' => 'Greta', 'last' => 'Mueller', 'code' => 'AGT-GMUELLER', 'team' => 'Berlin Desk'],
    ];

    public function run(Company $company, bool $apply = false): array
    {
        $previousCompany = app()->bound('company') ? app('company') : null;
        app()->instance('company', $company);

        try {
            return DB::transaction(fn () => $this->execute($company, $apply));
        } finally {
            if ($previousCompany) {
                app()->instance('company', $previousCompany);
            }
        }
    }

    private function execute(Company $company, bool $apply): array
    {
        $users = fn () => User::withoutGlobalScopes()->where('company_id', $company->id);
        $affiliates = $users()->whereHasRole('affiliate')->orderBy('created_at')->get();
        $agents = $users()->whereHasRole('manager')->orderBy('created_at')->get();
        $newAffiliates = collect(self::AFFILIATES)->reject(fn ($row) => $affiliates->contains('id', $this->id($company, 'affiliate', $row['key'])))->take(max(0, 5 - $affiliates->count()))->values();
        $newAgents = collect(self::AGENTS)->reject(fn ($row) => $agents->contains('id', $this->id($company, 'agent', $row['key'])))->take(max(0, 5 - $agents->count()))->values();

        $leadQuery = $users()->whereHasRole('lead')->orderByRaw('CAST(account_id AS DECIMAL(20,0))')->orderBy('id');
        $leads = ($apply ? $leadQuery->lockForUpdate() : $leadQuery)->get();
        [$placeholders, $skipped] = $leads->partition(fn ($lead) => $lead->banned && ! $lead->can_email && ! $lead->can_trade);

        if ($apply) {
            $affiliates = $affiliates->concat($newAffiliates->map(fn ($row) => $this->createStaff($company, 'affiliate', $row)));
            $agents = $agents->concat($newAgents->map(fn ($row) => $this->createStaff($company, 'manager', $row)));
        }
        $affiliateIds = $affiliates->pluck('id')->concat($newAffiliates->map(fn ($row) => $this->id($company, 'affiliate', $row['key'])))->unique()->values()->take(5);
        $agentIds = $agents->pluck('id')->concat($newAgents->map(fn ($row) => $this->id($company, 'agent', $row['key'])))->unique()->values()->take(5);

        $changes = $placeholders->values()->map(function ($lead, $index) use ($affiliateIds, $agentIds) {
            $country = self::COUNTRIES[$index % count(self::COUNTRIES)];
            [$dial, $prefix, $length, $timezone, $region] = self::LOCALE[$country];
            $digits = str_pad((string) (($index * 7919 + 2451783) % 10 ** ($length - strlen($prefix))), $length - strlen($prefix), '0', STR_PAD_LEFT);

            return ['id' => $lead->id, 'before' => $lead->only(['country', 'phone', 'timezone', 'created_by', 'manager_id', 'campaign']), 'after' => [
                'country' => $country,
                'phone' => $dial.$prefix.$digits,
                'timezone' => $timezone,
                // Every sixth lead stays direct so attribution still shows organic traffic.
                'created_by' => $index % 6 === 5 ? null : $affiliateIds[$index % 6 % $affiliateIds->count()] ?? null,
                'manager_id' => $agentIds[$index % $agentIds->count()] ?? null,
                'campaign' => $lead->campaign === 'Brand_US' ? 'Brand_'.$region : $lead->campaign,
            ]];
        });

        $backup = null;
        if ($apply) {
            $backup = 'leadmax/backups/relabel-'.$company->slug.'-'.now()->format('Ymd-His').'.json';
            Storage::disk('local')->put($backup, json_encode(['company_id' => $company->id, 'leads' => $changes->map(fn ($c) => ['id' => $c['id'], ...$c['before']])], JSON_PRETTY_PRINT));
            foreach ($changes as $change) {
                // Query builder keeps model events, notifications and updated_at out of a data relabel.
                DB::table('users')->where('company_id', $company->id)->where('id', $change['id'])->update($change['after']);
            }
            $company->forceFill(['settings' => [...($company->settings ?? []), 'leadmax_relabel_seed' => self::MARKER]])->save();
            Company::clearCache($company->fresh());
        }

        return [
            'tenant' => $company->slug,
            'status' => $apply ? 'applied' : 'preview',
            'leads_relabelled' => $changes->count(),
            'leads_skipped_not_placeholder' => $skipped->pluck('account_id')->values()->all(),
            'countries' => $changes->countBy(fn ($c) => $c['after']['country'])->sortDesc()->all(),
            'affiliates' => ['existing' => $affiliates->count() - ($apply ? $newAffiliates->count() : 0), 'created' => $newAffiliates->map(fn ($r) => $r['first'].' '.$r['last'])->all()],
            'agents' => ['existing' => $agents->count() - ($apply ? $newAgents->count() : 0), 'created' => $newAgents->map(fn ($r) => $r['first'].' '.$r['last'])->all()],
            'leads_per_agent' => $changes->countBy(fn ($c) => $c['after']['manager_id'])->values()->all(),
            'direct_leads' => $changes->whereNull('after.created_by')->count(),
            'us_leads_after' => 0,
            'samples' => $changes->take(3)->map(fn ($c) => ['before' => $c['before'], 'after' => $c['after']])->all(),
            'backup' => $backup,
        ];
    }

    /**
     * Move sample data off a country that left the rotation (preview unless $apply): placeholder leads
     * in $country get the replacement country's phone/timezone/campaign region, and the fixture
     * affiliates are renamed to their current definitions. Real leads are never touched, and
     * affiliate/agent assignments are kept (a full re-run of run() would reset them).
     */
    public function retireCountry(Company $company, string $country, string $replacement = 'France', bool $apply = false): array
    {
        if (! isset(self::LOCALE[$replacement]) || isset(self::LOCALE[$country])) {
            throw new \InvalidArgumentException("Retire a country that is no longer in the rotation, onto one that is ({$country} -> {$replacement}).");
        }
        [$dial, $prefix, $length, $timezone, $region] = self::LOCALE[$replacement];
        $users = fn () => User::withoutGlobalScopes()->where('company_id', $company->id);

        $leads = $users()->whereHasRole('lead')->where('country', $country)
            ->where('banned', true)->where('can_email', false)->where('can_trade', false)
            ->orderByRaw('CAST(account_id AS DECIMAL(20,0))')->orderBy('id')->get();
        $leadChanges = $leads->values()->map(function ($lead, $index) use ($dial, $prefix, $length, $timezone, $region, $replacement) {
            $digits = str_pad((string) ((((int) $lead->account_id) * 7919 + $index * 104729 + 2451783) % 10 ** ($length - strlen($prefix))), $length - strlen($prefix), '0', STR_PAD_LEFT);

            return ['id' => $lead->id, 'before' => $lead->only(['country', 'phone', 'timezone', 'campaign']), 'after' => [
                'country' => $replacement,
                'phone' => $dial.$prefix.$digits,
                'timezone' => $timezone,
                'campaign' => preg_replace('/_[A-Z]{2}$/', '_'.$region, (string) $lead->campaign) ?: $lead->campaign,
            ]];
        });

        $affiliateChanges = collect(self::AFFILIATES)->map(function ($row) use ($company, $users) {
            $affiliate = $users()->whereKey($this->id($company, 'affiliate', $row['key']))->first();
            $after = ['first_name' => $row['first'], 'last_name' => $row['last'], 'account_id' => $row['code'], 'country' => $row['country'],
                'email' => strtolower($row['first'].'.'.$row['last']).'.'.$company->id.'@example.com'];

            return $affiliate && $affiliate->only(array_keys($after)) !== $after
                ? ['id' => $affiliate->id, 'before' => $affiliate->only(array_keys($after)), 'after' => $after] : null;
        })->filter()->values();

        // Counted before any write, so an apply run reports the real leads it left alone.
        $realLeadsUntouched = $users()->whereHasRole('lead')->where('country', $country)->count() - $leadChanges->count();
        $backup = null;
        if ($apply && ($leadChanges->isNotEmpty() || $affiliateChanges->isNotEmpty())) {
            $backup = 'leadmax/backups/retire-'.strtolower(str_replace(' ', '-', $country)).'-'.$company->slug.'-'.now()->format('Ymd-His').'.json';
            Storage::disk('local')->put($backup, json_encode(['company_id' => $company->id, 'users' => $leadChanges->concat($affiliateChanges)->map(fn ($c) => ['id' => $c['id'], ...$c['before']])], JSON_PRETTY_PRINT));
            DB::transaction(function () use ($company, $leadChanges, $affiliateChanges) {
                foreach ($leadChanges->concat($affiliateChanges) as $change) {
                    DB::table('users')->where('company_id', $company->id)->where('id', $change['id'])->update($change['after']);
                }
            });
        }

        return [
            'tenant' => $company->slug,
            'status' => $apply ? 'applied' : 'preview',
            'country' => $country.' -> '.$replacement,
            'placeholder_leads' => $leadChanges->count(),
            'real_leads_in_country_untouched' => $realLeadsUntouched,
            'affiliates_renamed' => $affiliateChanges->map(fn ($c) => trim($c['before']['first_name'].' '.$c['before']['last_name']).' -> '.$c['after']['first_name'].' '.$c['after']['last_name'])->all(),
            'samples' => $leadChanges->take(3)->map(fn ($c) => ['before' => $c['before'], 'after' => $c['after']])->all(),
            'backup' => $backup,
        ];
    }

    private function id(Company $company, string $type, string $key): string
    {
        return Uuid::uuid5(Uuid::NAMESPACE_URL, self::MARKER.':'.$company->id.':'.$type.':'.$key)->toString();
    }

    private function createStaff(Company $company, string $role, array $row): User
    {
        $user = User::withoutGlobalScopes()->forceCreate([
            'id' => $this->id($company, $role === 'affiliate' ? 'affiliate' : 'agent', $row['key']),
            'company_id' => $company->id,
            'first_name' => $row['first'],
            'last_name' => $row['last'],
            'email' => strtolower($row['first'].'.'.$row['last']).'.'.$company->id.'@example.com',
            'phone' => '',
            'password' => Hash::make(Str::random(40)),
            'account_id' => $row['code'],
            'country' => $row['country'] ?? '',
            'team' => $row['team'] ?? null,
            'status' => 'active',
            'can_email' => false,
            'can_trade' => false,
            'banned' => false,
            'approved' => true,
            'require_otp' => false,
            'balance' => 0,
            'total_deposit' => 0,
            'settings' => ['leadmax_fixture' => self::MARKER, ...($role === 'affiliate' ? ['commission_rate' => 10.0] : [])],
        ]);
        $user->addRole($role);

        return $user;
    }
}

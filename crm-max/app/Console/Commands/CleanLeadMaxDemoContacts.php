<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Models\User;
use App\Services\LeadMax\DemoLeadProfiles;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Clears the placeholder ".invalid" lead contacts out of a LeadMax demo tenant.
 *
 * Two groups, handled differently on purpose:
 *  - The period-sample leads carry no affiliate link, deposit or transaction, so they are removed;
 *    leadmax:seed-period-leads replaces them with dated contacts.
 *  - The affiliate-demo leads are nameless rows that still back the Affiliates, First Time Deposit
 *    and attribution figures. Deleting them would empty those pages (and the app's own rule refuses
 *    to delete a lead with financial history), so they are given a name, account id and a readable
 *    address instead.
 */
class CleanLeadMaxDemoContacts extends Command
{
    protected $signature = 'leadmax:clean-demo-contacts
        {company : Existing tenant slug}
        {--domain=crm-ultra.com : Email domain for the renamed contacts}
        {--apply : Write the changes; default is a read-only preview}';

    protected $description = 'Remove the period-sample placeholder leads and give the affiliate-demo leads real names and addresses';

    /** Removed outright: nothing links to these. */
    public const DROP_DOMAIN = 'crm-ultra-sample.invalid';

    /** Renamed in place: these still carry affiliate, FTD and transaction history. */
    public const RENAME_DOMAIN = 'leadmax-aff-demo.invalid';

    public function handle(): int
    {
        $company = Company::query()->where('slug', $this->argument('company'))->first();
        if (! $company) {
            $this->error('No tenant with slug '.$this->argument('company').'.');

            return self::FAILURE;
        }
        app()->instance('company', $company);
        $domain = ltrim(trim((string) $this->option('domain')), '@');

        $leads = fn (string $suffix) => User::query()->withoutGlobalScopes()
            ->where('company_id', $company->id)
            ->whereHas('roles', fn ($q) => $q->where('name', 'lead'))
            ->where('email', 'like', '%@'.$suffix)
            ->orderBy('id');

        $sample = $leads(self::DROP_DOMAIN)->get();
        // Never remove a lead the application itself would refuse to delete: a sample lead that
        // turns out to hold money, trades or an account is renamed with the rest instead.
        $hasHistory = fn (User $l) => (float) $l->ftd_amount > 0 || (float) $l->balance !== 0.0
            || $l->transactions()->exists() || $l->accounts()->exists();
        $drop = $sample->reject($hasHistory)->values();
        $kept = $sample->filter($hasHistory)->values();
        $rename = $leads(self::RENAME_DOMAIN)->get()->concat($kept);

        $taken = User::query()->withoutGlobalScopes()
            ->whereNotIn('id', $rename->modelKeys() ?: ['-'])
            ->pluck('email')->filter()->map(fn ($e) => strtolower($e))->flip();
        $accountsTaken = User::query()->withoutGlobalScopes()
            ->whereNotIn('id', $rename->modelKeys() ?: ['-'])
            ->pluck('account_id')->filter()->flip();

        $plan = [];
        foreach ($rename->values() as $index => $lead) {
            $profile = DemoLeadProfiles::make($index + 9001, '');
            $first = $lead->first_name ?: $profile['first_name'];
            $last = $lead->last_name ?: $profile['last_name'];
            $base = Str::slug($first.' '.$last, '.');
            $email = $base.'@'.$domain;
            for ($n = 2; isset($taken[strtolower($email)]); $n++) {
                $email = $base.$n.'@'.$domain;
            }
            $taken[strtolower($email)] = true;
            $account = $lead->account_id;
            if (! $account) {
                $account = (string) (9500000 + $index);
                while (isset($accountsTaken[$account])) {
                    $account = (string) ((int) $account + 1);
                }
                $accountsTaken[$account] = true;
            }
            $plan[] = ['lead' => $lead, 'first_name' => $first, 'last_name' => $last, 'email' => $email,
                'account_id' => $account, 'phone' => $lead->phone ?: $profile['phone']];
        }

        $this->line('Tenant: '.$company->name.' (#'.$company->id.')');
        $this->line('  delete  '.$drop->count().' leads @'.self::DROP_DOMAIN);
        $this->line('  rename  '.$rename->count().' leads'
            .($kept->isEmpty() ? '' : ' (including '.$kept->count().' sample leads kept for their financial history)'));
        foreach (array_slice($plan, 0, 3) as $row) {
            $this->line('     '.str_pad($row['lead']->email, 44).' -> '.$row['first_name'].' '.$row['last_name'].' <'.$row['email'].'>');
        }

        if (! $this->option('apply')) {
            $this->warn('Preview only. Re-run with --apply to write the changes.');

            return self::SUCCESS;
        }

        DB::transaction(function () use ($drop, $plan) {
            foreach ($drop as $lead) {
                // The same order LeadMaxLeadController uses when an admin deletes a lead.
                $lead->tradingAccounts()->get()->each->delete();
                $lead->notes()->delete();
                $lead->delete();
            }
            foreach ($plan as $row) {
                $row['lead']->forceFill([
                    'first_name' => $row['first_name'], 'last_name' => $row['last_name'],
                    'email' => $row['email'], 'account_id' => $row['account_id'], 'phone' => $row['phone'],
                ])->save();
            }
        });

        $this->info('Deleted '.$drop->count().' sample leads and renamed '.count($plan).' affiliate-demo leads.');

        return self::SUCCESS;
    }
}

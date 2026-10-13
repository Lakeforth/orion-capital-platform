<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Models\LeadAttribution;
use App\Models\User;
use Illuminate\Console\Command;

/**
 * Gives leads that existed before attribution capture a row of their own, so Attribution and
 * the unread markers can treat every lead the same way. These rows are marked legacy_backfill:
 * they reconstruct what is known (source, campaign, referrer id, owner, registration time) and
 * invent nothing, they never count as unread, and they announce no new-lead event.
 */
class BackfillLeadAttribution extends Command
{
    protected $signature = 'leadmax:backfill-attribution {company? : Tenant slug; every tenant when omitted} {--apply : Write the rows; otherwise only count them}';

    protected $description = 'Create legacy attribution rows for leads captured before attribution existed (resumable, batched per tenant)';

    public function handle(): int
    {
        $companies = Company::query()->when($this->argument('company'), fn ($q, $slug) => $q->where('slug', $slug))->get(['id', 'slug']);
        if ($companies->isEmpty()) {
            $this->error('No such tenant.');

            return self::FAILURE;
        }

        foreach ($companies as $company) {
            $missing = User::withoutGlobalScopes()->where('company_id', $company->id)->whereHasRole('lead')
                ->whereNotExists(fn ($q) => $q->selectRaw('1')->from('lead_attributions')->whereColumn('lead_attributions.lead_id', 'users.id'));
            $count = (clone $missing)->count();
            if (! $this->option('apply') || $count === 0) {
                $this->line("{$company->slug}: {$count} lead(s) without attribution".($this->option('apply') ? '' : ' (preview; add --apply to write)'));

                continue;
            }

            $written = 0;
            $missing->select(['id', 'company_id', 'source', 'campaign', 'campaign_id', 'created_by', 'manager_id', 'created_at'])
                ->chunkById(500, function ($leads) use (&$written) {
                    $now = now();
                    LeadAttribution::withoutGlobalScopes()->insertOrIgnore($leads->map(fn (User $lead) => [
                        'company_id' => $lead->company_id,
                        'lead_id' => $lead->id,
                        'received_at' => $lead->created_at ?? $now,
                        'intake_channel' => 'unknown',
                        'source_name' => $lead->source ? mb_substr($lead->source, 0, 150) : null,
                        'campaign_id' => $lead->campaign_id,
                        'campaign_name' => $lead->campaign ? mb_substr($lead->campaign, 0, 200) : null,
                        'referrer_kind' => $lead->created_by ? 'unknown' : 'direct',
                        'referrer_id' => $lead->created_by,
                        'assigned_owner_id' => $lead->manager_id,
                        'source_origin' => LeadAttribution::LEGACY,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ])->all());
                    $written += $leads->count();
                }, 'id');

            $this->line("{$company->slug}: {$written} legacy attribution row(s) written");
        }

        return self::SUCCESS;
    }
}

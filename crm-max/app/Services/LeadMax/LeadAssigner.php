<?php

namespace App\Services\LeadMax;

use App\Models\LeadAssignment;
use App\Models\User;

/**
 * Picks the owner for a new lead when assignment is "Auto": Lead Splitter rules when the splitter is on,
 * otherwise a fair round robin across active agents. Every automatic assignment is logged.
 */
class LeadAssigner
{
    public function __construct(private LeadSplitter $splitter) {}

    /** @return array{agent: ?string, rule_id: ?int, method: ?string} */
    public function decide(array $lead = []): array
    {
        return $this->splitter->decide($lead);
    }

    public function next(array $lead = []): ?string
    {
        return $this->decide($lead)['agent'];
    }

    /** Record who got the lead and why (Recent Assignments, logs and agent allocation read this). */
    public function log(User $lead, array $decision, string $via, ?string $actorId = null): void
    {
        if (! $decision['agent']) {
            return;
        }
        LeadAssignment::create(['lead_id' => $lead->id, 'agent_id' => $decision['agent'], 'rule_id' => $decision['rule_id'], 'method' => $decision['method'], 'via' => $via, 'actor_id' => $actorId]);
    }

    /** Assign an existing unowned lead now (Lead Queue). */
    public function assign(User $lead, string $via, ?string $actorId = null): ?string
    {
        $decision = $this->decide(['source' => $lead->source, 'country' => $lead->country]);
        if ($decision['agent']) {
            $lead->forceFill(['manager_id' => $decision['agent']])->save();
            $this->log($lead, $decision, $via, $actorId);
        }

        return $decision['agent'];
    }
}

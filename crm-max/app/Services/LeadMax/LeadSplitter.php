<?php

namespace App\Services\LeadMax;

use App\Models\LeadSplitRule;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Lead Splitter rules engine (Figma 2074:11875). Rules are checked by priority; the first active rule whose
 * source and country match picks an agent with its allocation method. Unmatched leads use the fallback setting.
 */
class LeadSplitter
{
    public const DEFAULT_SETTINGS = ['enabled' => false, 'fallback' => 'round_robin', 'max_open_leads' => 0];

    /** Region presets offered when creating a rule (country names as stored on leads). */
    public const REGIONS = [
        'Europe (EU)' => ['Austria', 'Belgium', 'Bulgaria', 'Croatia', 'Cyprus', 'Czech Republic', 'Denmark', 'Estonia', 'Finland', 'France', 'Germany', 'Greece', 'Hungary', 'Ireland', 'Italy', 'Latvia', 'Lithuania', 'Luxembourg', 'Malta', 'Netherlands', 'Poland', 'Portugal', 'Romania', 'Slovakia', 'Slovenia', 'Spain', 'Sweden'],
        'Europe (Non-EU)' => ['United Kingdom', 'Switzerland', 'Norway', 'Iceland', 'Serbia', 'Albania', 'Ukraine', 'Turkey', 'Georgia', 'Moldova', 'Montenegro', 'North Macedonia', 'Bosnia and Herzegovina'],
        'Latin America' => ['Argentina', 'Bolivia', 'Brazil', 'Chile', 'Colombia', 'Costa Rica', 'Dominican Republic', 'Ecuador', 'El Salvador', 'Guatemala', 'Honduras', 'Mexico', 'Nicaragua', 'Panama', 'Paraguay', 'Peru', 'Uruguay', 'Venezuela'],
        'North America' => ['Canada', 'United States'],
        'Middle East & N. Africa' => ['United Arab Emirates', 'Saudi Arabia', 'Qatar', 'Kuwait', 'Bahrain', 'Oman', 'Jordan', 'Lebanon', 'Israel', 'Iraq', 'Egypt', 'Morocco', 'Algeria', 'Tunisia', 'Libya'],
        'Sub-Saharan Africa' => ['Nigeria', 'Kenya', 'Ghana', 'South Africa', 'Uganda', 'Tanzania', 'Ethiopia', 'Rwanda', 'Zambia', 'Zimbabwe', 'Botswana', 'Cameroon', 'Senegal', "Cote d'Ivoire"],
        'Asia' => ['China', 'Hong Kong', 'Japan', 'South Korea', 'Singapore', 'Malaysia', 'Indonesia', 'Thailand', 'Vietnam', 'Philippines', 'India', 'Pakistan', 'Bangladesh', 'Sri Lanka', 'Taiwan'],
        'Oceania' => ['Australia', 'New Zealand'],
    ];

    public function settings(): array
    {
        return [...self::DEFAULT_SETTINGS, ...(company_settings('leadmax_splitter', []) ?: [])];
    }

    public function enabled(): bool
    {
        return (bool) $this->settings()['enabled'];
    }

    /** Active agents (manager role) in this tenant, oldest first. */
    public function agents(): Collection
    {
        return User::query()->whereHasRole('manager')->where('status', 'active')->orderBy('created_at')->get(['id', 'first_name', 'last_name']);
    }

    /** @return Collection<int, LeadSplitRule> */
    public function activeRules(): Collection
    {
        return LeadSplitRule::query()->where('status', 'active')->orderBy('priority')->orderBy('id')->get();
    }

    public function matches(LeadSplitRule $rule, array $lead): bool
    {
        if ($rule->source && strcasecmp(trim((string) ($lead['source'] ?? '')), $rule->source) !== 0) {
            return false;
        }
        if (! empty($rule->countries)) {
            $country = strtolower(trim((string) ($lead['country'] ?? '')));

            return $country !== '' && in_array($country, array_map('strtolower', $rule->countries), true);
        }

        return true;
    }

    /**
     * Choose an owner for a lead without saving it.
     *
     * @return array{agent: ?string, rule_id: ?int, method: ?string}
     */
    public function decide(array $lead): array
    {
        $agents = $this->agents()->pluck('id')->map(fn ($id) => (string) $id)->all();
        if ($agents === []) {
            return ['agent' => null, 'rule_id' => null, 'method' => null];
        }
        $settings = $this->settings();
        $agents = $this->underCap($agents, (int) $settings['max_open_leads']);
        if ($agents === []) {
            return ['agent' => null, 'rule_id' => null, 'method' => 'queue'];
        }

        if ($settings['enabled']) {
            foreach ($this->activeRules() as $rule) {
                if (! $this->matches($rule, $lead)) {
                    continue;
                }
                $candidates = $this->candidates($rule, $agents);
                if ($candidates === []) {
                    continue;
                }

                return ['agent' => $this->pick($rule, $candidates), 'rule_id' => $rule->id, 'method' => $rule->method];
            }
            if ($settings['fallback'] === 'queue') {
                return ['agent' => null, 'rule_id' => null, 'method' => 'queue'];
            }
        }

        return ['agent' => $this->roundRobin($agents), 'rule_id' => null, 'method' => 'round_robin'];
    }

    /** Estimated split of the next $count leads, based on the last 30 days of lead sources and countries. */
    public function preview(int $count): array
    {
        $agents = $this->agents()->keyBy(fn ($a) => (string) $a->id);
        $sample = User::query()->whereHasRole('lead')->where('created_at', '>=', now()->subDays(30))->latest()->limit(1000)->get(['source', 'country'])
            ->map(fn ($l) => ['source' => $l->source, 'country' => $l->country])->all() ?: [['source' => null, 'country' => null]];
        $settings = $this->settings();
        $rules = $settings['enabled'] ? $this->activeRules() : collect();
        $ids = $agents->keys()->all();
        $shares = [];
        $add = function (array $weights) use (&$shares) {
            $total = array_sum($weights) ?: 1;
            foreach ($weights as $id => $weight) {
                $shares[$id] = ($shares[$id] ?? 0) + $weight / $total;
            }
        };
        foreach ($sample as $lead) {
            $rule = $rules->first(fn ($r) => $this->matches($r, $lead) && $this->candidates($r, $ids) !== []);
            if ($rule) {
                $candidates = $this->candidates($rule, $ids);
                $weights = match ($rule->method) {
                    'specific' => [$candidates[0] => 1],
                    'weighted' => array_intersect_key($rule->agentWeights() ?: array_fill_keys($candidates, 1), array_flip($candidates)),
                    default => array_fill_keys($candidates, 1),
                };
                $add($weights);
            } elseif ($settings['enabled'] && $settings['fallback'] === 'queue' || $ids === []) {
                $add(['queue' => 1]);
            } else {
                $add(array_fill_keys($ids, 1));
            }
        }
        $sampleSize = count($sample);
        $rows = collect($shares)->map(fn ($share, $id) => ['id' => $id, 'exact' => $share / $sampleSize * $count])->sortByDesc('exact')->values();
        // Largest remainder rounding so the rows add up to $count.
        $floors = $rows->map(fn ($r) => ['id' => $r['id'], 'leads' => (int) floor($r['exact']), 'rest' => $r['exact'] - floor($r['exact'])]);
        $missing = $count - $floors->sum('leads');
        $floors = $floors->sortByDesc('rest')->values()->map(function ($r, $i) use ($missing) {
            $r['leads'] += $i < $missing ? 1 : 0;

            return $r;
        })->sortByDesc('leads')->values();

        return $floors->map(fn ($r) => [
            'id' => $r['id'],
            'name' => $r['id'] === 'queue' ? 'Lead Queue' : $this->shortName($agents[$r['id']] ?? null),
            'initials' => $r['id'] === 'queue' ? 'LQ' : $this->initials($agents[$r['id']] ?? null),
            'leads' => $r['leads'],
            'percent' => $count > 0 ? round($r['leads'] / $count * 100) : 0,
        ])->all();
    }

    public function shortName(?User $user): string
    {
        return $user ? trim($user->first_name.' '.mb_substr((string) $user->last_name, 0, 1).($user->last_name ? '.' : '')) : 'Unknown';
    }

    public function initials(?User $user): string
    {
        return $user ? mb_strtoupper(mb_substr((string) $user->first_name, 0, 1).mb_substr((string) $user->last_name, 0, 1)) : '?';
    }

    /** @param array<int, string> $agents */
    private function candidates(LeadSplitRule $rule, array $agents): array
    {
        $wanted = array_keys($rule->agentWeights());

        return $wanted === [] ? $agents : array_values(array_intersect($wanted, $agents));
    }

    /** @param array<int, string> $candidates */
    private function pick(LeadSplitRule $rule, array $candidates): string
    {
        if ($rule->method === 'specific') {
            return $candidates[0];
        }
        if ($rule->method === 'balanced') {
            $load = User::query()->whereHasRole('lead')->whereIn('manager_id', $candidates)->selectRaw('manager_id, COUNT(*) as total')->groupBy('manager_id')->pluck('total', 'manager_id');

            return collect($candidates)->sortBy(fn ($id) => [(int) ($load[$id] ?? 0), array_search($id, $candidates, true)])->first();
        }
        $sequence = $candidates;
        if ($rule->method === 'weighted') {
            $weights = array_intersect_key($rule->agentWeights(), array_flip($candidates)) ?: array_fill_keys($candidates, 1);
            $sequence = [];
            for ($round = 0; $round < max($weights); $round++) {
                foreach ($weights as $id => $weight) {
                    if ($weight > $round) {
                        $sequence[] = (string) $id;
                    }
                }
            }
        }
        $position = DB::transaction(function () use ($rule) {
            $locked = LeadSplitRule::query()->lockForUpdate()->findOrFail($rule->id);
            $locked->increment('cursor');

            return $locked->cursor - 1;
        });

        return $sequence[$position % count($sequence)];
    }

    /** @param array<int, string> $agents */
    private function roundRobin(array $agents): string
    {
        $key = 'leadmax:round-robin:'.(app()->bound('company') ? app('company')->id : 0);
        $position = (int) cache()->get($key, -1) + 1;
        cache()->forever($key, $position);

        return $agents[$position % count($agents)];
    }

    /** @param array<int, string> $agents */
    private function underCap(array $agents, int $cap): array
    {
        if ($cap <= 0) {
            return $agents;
        }
        $load = User::query()->whereHasRole('lead')->whereIn('manager_id', $agents)->selectRaw('manager_id, COUNT(*) as total')->groupBy('manager_id')->pluck('total', 'manager_id');

        return array_values(array_filter($agents, fn ($id) => (int) ($load[$id] ?? 0) < $cap));
    }
}

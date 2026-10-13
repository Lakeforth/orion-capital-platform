<?php

namespace App\Http\Controllers\Admin;

use App\Models\Company;
use App\Models\LeadAssignment;
use App\Models\LeadSplitRule;
use App\Models\LeadSource;
use App\Models\User;
use App\Services\LeadMax\LeadAssigner;
use App\Services\LeadMax\LeadSplitter;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/** Lead Splitter (Figma 2074:11875): distribution rules, agent allocation, lead queue, settings and logs. */
class LeadMaxSplitterController extends LeadMaxController
{
    public const TABS = ['rules', 'allocation', 'queue', 'settings', 'logs'];

    public const PREVIEW_SIZES = [100, 500, 1000];

    public function __construct(private LeadSplitter $splitter) {}

    public function index(Request $request)
    {
        $tab = in_array($request->input('tab'), self::TABS, true) ? $request->input('tab') : 'rules';
        $agents = $this->splitter->agents();
        $previewSize = in_array((int) $request->input('preview'), self::PREVIEW_SIZES, true) ? (int) $request->input('preview') : 100;
        $rule = null;
        if ($request->input('dialog') === 'rule' && is_admin()) {
            $rule = $request->filled('rule') ? LeadSplitRule::query()->findOrFail($request->input('rule')) : new LeadSplitRule(['method' => 'round_robin', 'status' => 'active', 'priority' => (int) LeadSplitRule::query()->max('priority') + 1]);
        }

        return view('admin.leadmax.lead-splitter', [
            ...$this->common($request, 'lead-splitter'),
            'tab' => $tab,
            'kpi' => $this->kpis(),
            'settings' => $this->splitter->settings(),
            'rules' => $tab === 'rules' ? $this->rules($request)->paginate(self::PER_PAGE)->withQueryString() : null,
            'ruleCount' => LeadSplitRule::query()->count(),
            'preview' => $tab === 'rules' ? $this->splitter->preview($previewSize) : [],
            'previewSize' => $previewSize,
            'recent' => $tab === 'rules' ? LeadAssignment::query()->with(['lead:id,account_id,first_name,last_name,source', 'agent:id,first_name,last_name'])->latest()->latest('id')->limit(8)->get() : collect(),
            'allocation' => $tab === 'allocation' ? $this->allocation($agents) : collect(),
            'queue' => $tab === 'queue' ? User::query()->whereHasRole('lead')->whereNull('manager_id')->latest()->paginate(self::PER_PAGE)->withQueryString() : null,
            'logs' => $tab === 'logs' ? $this->logs($request)->paginate(self::PER_PAGE)->withQueryString() : null,
            'agents' => $agents,
            'sources' => $this->sources(),
            'regions' => LeadSplitter::REGIONS,
            'countries' => \App\Models\Country::query()->orderBy('name')->pluck('name'),
            'ruleDialog' => $rule,
        ]);
    }

    public function store(Request $request)
    {
        abort_unless(is_admin(), 403);
        $rule = LeadSplitRule::create([...$this->ruleData($request), 'created_by' => auth()->id()]);

        return redirect()->route('admin.leadmax.lead-splitter')->with('success', 'Rule "'.$rule->name.'" created.');
    }

    public function update(Request $request, string $rule)
    {
        abort_unless(is_admin(), 403);
        $record = LeadSplitRule::query()->findOrFail($rule);
        $record->update($this->ruleData($request, $record));

        return redirect()->route('admin.leadmax.lead-splitter')->with('success', 'Rule "'.$record->name.'" updated.');
    }

    public function toggle(string $rule)
    {
        abort_unless(is_admin(), 403);
        $record = LeadSplitRule::query()->findOrFail($rule);
        $record->update(['status' => $record->status === 'active' ? 'paused' : 'active']);

        return back()->with('success', 'Rule "'.$record->name.'" '.($record->status === 'active' ? 'activated' : 'paused').'.');
    }

    public function duplicate(string $rule)
    {
        abort_unless(is_admin(), 403);
        $record = LeadSplitRule::query()->findOrFail($rule);
        $copy = $record->replicate(['cursor']);
        $copy->fill(['name' => mb_substr($record->name.' (copy)', 0, 150), 'status' => 'paused', 'cursor' => 0, 'created_by' => auth()->id()])->save();

        return back()->with('success', 'Rule duplicated as "'.$copy->name.'" (paused).');
    }

    public function destroy(string $rule)
    {
        abort_unless(is_admin(), 403);
        $record = LeadSplitRule::query()->findOrFail($rule);
        $record->delete();

        return redirect()->route('admin.leadmax.lead-splitter')->with('success', 'Rule "'.$record->name.'" deleted.');
    }

    public function toggleSplitter()
    {
        abort_unless(is_admin(), 403);
        $enabled = ! $this->splitter->enabled();
        $this->saveCompanySettings(['enabled' => $enabled]);

        return back()->with('success', 'Lead Splitter turned '.($enabled ? 'on' : 'off').'.');
    }

    public function saveSettings(Request $request)
    {
        abort_unless(is_admin(), 403);
        $data = $request->validate([
            'fallback' => ['required', Rule::in(['round_robin', 'queue'])],
            'max_open_leads' => 'nullable|integer|min:0|max:100000',
        ]);
        $this->saveCompanySettings(['enabled' => $request->boolean('enabled'), 'fallback' => $data['fallback'], 'max_open_leads' => (int) ($data['max_open_leads'] ?? 0)]);

        return redirect()->route('admin.leadmax.lead-splitter', ['tab' => 'settings'])->with('success', 'Splitter settings saved.');
    }

    /** Route unassigned leads through the splitter now: the selected ones, or every lead in the queue. */
    public function assignQueue(Request $request, LeadAssigner $assigner)
    {
        abort_unless(is_admin(), 403);
        $data = $request->validate(['leads' => 'nullable|array|max:500', 'leads.*' => 'uuid']);
        $query = User::query()->whereHasRole('lead')->whereNull('manager_id');
        if (! $request->boolean('all')) {
            if (empty($data['leads'])) {
                return back()->withErrors(['leads' => 'Select the leads to assign, or use Assign all.']);
            }
            $query->whereIn('id', $data['leads']);
        }
        $assigned = 0;
        $total = 0;
        $query->orderBy('created_at')->limit(500)->get()->each(function (User $lead) use ($assigner, &$assigned, &$total) {
            $total++;
            $assigned += $assigner->assign($lead, 'queue', auth()->id()) ? 1 : 0;
        });
        $left = $total - $assigned;

        return redirect()->route('admin.leadmax.lead-splitter', ['tab' => 'queue'])
            ->with('success', $total === 0 ? 'No leads are waiting for assignment.' : $assigned.' of '.$total.' leads assigned.'.($left ? ' '.$left.' stayed in the queue (no agent available).' : ''));
    }

    public function export(Request $request)
    {
        $rules = $this->rules($request)->get();
        $agents = $this->splitter->agents()->keyBy(fn ($a) => (string) $a->id);

        return response()->streamDownload(function () use ($rules, $agents) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['#', 'Rule Name', 'Source', 'Country / Region', 'Allocation Method', 'Recipients', 'Status', 'Priority', 'Created At']);
            foreach ($rules as $i => $rule) {
                fputcsv($out, [$i + 1, $rule->name, $rule->source ?: 'Any', $rule->regionLabel(), $rule->methodLabel(), $this->recipients($rule, $agents), ucfirst($rule->status), $rule->priority, $rule->created_at?->format('Y-m-d H:i')]);
            }
            fclose($out);
        }, 'lead-splitter-rules.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /** "4 agents", "Ahmed R." or "All agents", as in the Figma recipients column. */
    public function recipients(LeadSplitRule $rule, $agents): string
    {
        $ids = array_keys($rule->agentWeights());
        if ($ids === []) {
            return 'All agents';
        }
        if (count($ids) === 1) {
            return $this->splitter->shortName($agents[$ids[0]] ?? null);
        }

        return count($ids).' agents';
    }

    private function rules(Request $request)
    {
        $query = LeadSplitRule::query();
        if ($search = trim((string) $request->input('q'))) {
            $like = '%'.str_replace(['%', '_'], ['\%', '\_'], $search).'%';
            $query->where(fn ($q) => $q->where('name', 'like', $like)->orWhere('source', 'like', $like)->orWhere('region', 'like', $like)->orWhere('countries', 'like', $like));
        }
        if (in_array($request->input('status'), ['active', 'paused'], true)) {
            $query->where('status', $request->input('status'));
        }
        if (array_key_exists((string) $request->input('method'), LeadSplitRule::METHODS)) {
            $query->where('method', $request->input('method'));
        }

        return $query->orderBy('priority')->orderBy('id');
    }

    private function ruleData(Request $request, ?LeadSplitRule $rule = null): array
    {
        $agentIds = $this->splitter->agents()->pluck('id')->map(fn ($id) => (string) $id)->all();
        $data = $request->validate([
            'name' => 'required|string|max:150',
            'source' => 'nullable|string|max:150',
            'region' => ['required', 'string', Rule::in(['all', 'custom', ...array_keys(LeadSplitter::REGIONS)])],
            'countries' => 'nullable|array|max:250', 'countries.*' => 'string|max:100',
            'method' => ['required', Rule::in(array_keys(LeadSplitRule::METHODS))],
            'agents' => 'nullable|array', 'agents.*' => ['string', Rule::in($agentIds)],
            'weights' => 'nullable|array', 'weights.*' => 'nullable|integer|min:1|max:100',
            'priority' => 'required|integer|min:1|max:999',
        ]);
        $agents = array_values(array_unique($data['agents'] ?? []));
        if ($data['method'] === 'specific' && count($agents) !== 1) {
            abort(back()->withInput()->withErrors(['agents' => 'Choose exactly one agent for the Specific Agent method.']));
        }
        if ($data['region'] === 'custom' && empty($data['countries'])) {
            abort(back()->withInput()->withErrors(['countries' => 'Choose at least one country, or pick a region.']));
        }
        [$region, $countries] = match ($data['region']) {
            'all' => [null, null],
            'custom' => [null, array_values(array_unique($data['countries']))],
            default => [$data['region'], LeadSplitter::REGIONS[$data['region']]],
        };

        return [
            'name' => trim($data['name']),
            'source' => ($data['source'] ?? '') !== '' ? trim($data['source']) : null,
            'region' => $region,
            'countries' => $countries,
            'method' => $data['method'],
            'agents' => array_map(fn ($id) => ['id' => $id, 'weight' => (int) ($data['weights'][$id] ?? 1) ?: 1], $agents),
            'priority' => (int) $data['priority'],
            'status' => $request->boolean('active') ? 'active' : 'paused',
        ];
    }

    private function kpis(): array
    {
        $month = fn (Carbon $at) => [$at->copy()->startOfMonth(), $at->copy()->endOfMonth()];
        $leads = fn (array $range) => User::query()->whereHasRole('lead')->whereBetween('created_at', $range);
        $now = $month(now());
        $last = $month(now()->subMonthNoOverflow());
        $change = fn ($cur, $prev) => $prev > 0 ? round(($cur - $prev) / $prev * 100, 1) : ($cur > 0 ? null : 0.0);
        $row = function (callable $count) use ($now, $last, $change) {
            [$cur, $prev] = [$count($now), $count($last)];

            return ['value' => $cur, 'previous' => $prev, 'change' => $change($cur, $prev)];
        };
        $activeRules = LeadSplitRule::query()->where('status', 'active')->count();
        $activeBefore = LeadSplitRule::query()->where('status', 'active')->where('created_at', '<', $now[0])->count();

        return [
            'total' => $row(fn ($r) => $leads($r)->count()),
            'assigned' => $row(fn ($r) => $leads($r)->whereNotNull('manager_id')->count()),
            'pending' => $row(fn ($r) => $leads($r)->whereNull('manager_id')->count()),
            'rules' => ['value' => $activeRules, 'change' => $change($activeRules, $activeBefore), 'of' => LeadSplitRule::query()->count()],
            'agents' => $row(fn ($r) => $leads($r)->whereNotNull('manager_id')->distinct()->count('manager_id')),
        ];
    }

    private function allocation($agents)
    {
        $since = now()->startOfMonth();
        $month = LeadAssignment::query()->where('created_at', '>=', $since)->selectRaw('agent_id, COUNT(*) as total, MAX(created_at) as last_at')->groupBy('agent_id')->get()->keyBy('agent_id');
        $open = User::query()->whereHasRole('lead')->whereIn('manager_id', $agents->pluck('id'))->selectRaw('manager_id, COUNT(*) as total')->groupBy('manager_id')->pluck('total', 'manager_id');
        $rules = LeadSplitRule::query()->where('status', 'active')->get();
        $total = max(1, $month->sum('total'));

        return $agents->map(fn (User $agent) => [
            'agent' => $agent,
            'name' => $agent->name,
            'initials' => $this->splitter->initials($agent),
            'rules' => $rules->filter(fn ($r) => $r->agentWeights() === [] || array_key_exists((string) $agent->id, $r->agentWeights()))->count(),
            'month' => (int) ($month[$agent->id]->total ?? 0),
            'share' => round((int) ($month[$agent->id]->total ?? 0) / $total * 100, 1),
            'open' => (int) ($open[$agent->id] ?? 0),
            'last' => isset($month[$agent->id]) ? Carbon::parse($month[$agent->id]->last_at) : null,
        ])->sortByDesc('month')->values();
    }

    private function logs(Request $request)
    {
        $query = LeadAssignment::query()->with(['lead:id,account_id,first_name,last_name,source,country', 'agent:id,first_name,last_name', 'rule:id,name']);
        if (in_array($request->input('via'), ['splitter', 'import', 'manual', 'queue'], true)) {
            $query->where('via', $request->input('via'));
        }

        return $query->latest()->latest('id');
    }

    private function sources()
    {
        return LeadSource::query()->pluck('name')
            ->merge(User::query()->whereHasRole('lead')->whereNotNull('source')->where('source', '!=', '')->distinct()->pluck('source'))
            ->map(fn ($s) => trim((string) $s))->filter()->unique(fn ($s) => strtolower($s))->sort()->values();
    }

    private function saveCompanySettings(array $values): void
    {
        DB::transaction(function () use ($values) {
            $company = Company::query()->lockForUpdate()->findOrFail(app('company')->id);
            $settings = $company->settings ?? [];
            $settings['leadmax_splitter'] = [...LeadSplitter::DEFAULT_SETTINGS, ...($settings['leadmax_splitter'] ?? []), ...$values];
            $company->forceFill(['settings' => $settings])->save();
            Company::clearCache($company->fresh());
            app()->instance('company', $company->fresh());
        });
    }
}

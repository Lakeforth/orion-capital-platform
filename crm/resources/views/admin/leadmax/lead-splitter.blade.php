{{-- Lead Splitter (Figma 2074:11875). Only the Distribution Rules tab has a Figma frame; the other tabs, the rule dialog and logs reuse its styles. --}}
@php
    $img = fn (string $name, $w, $h = null) => '<img src="'.asset('images/leadmax/splitter/'.$name.'.svg').'" width="'.$w.'" height="'.($h ?? $w).'" alt="">';
    $change = fn ($value) => $value === null ? '' : (round($value, 1) == 0 ? '<b class="is-flat">0%</b>' : '<b class="'.($value >= 0 ? 'is-up' : 'is-down').'">'.($value >= 0 ? '↑' : '↓').' '.rtrim(rtrim(number_format(abs($value), 1), '0'), '.').'%</b>');
    $tabs = ['rules' => 'Distribution Rules', 'allocation' => 'Agent Allocation', 'queue' => 'Lead Queue', 'settings' => 'Settings'];
    $agentsById = $agents->keyBy(fn ($a) => (string) $a->id);
    $splitter = app(\App\Services\LeadMax\LeadSplitter::class);
    $controller = app(\App\Http\Controllers\Admin\LeadMaxSplitterController::class);
    $avatarColors = ['#2563eb', '#059669', '#2563eb', '#d97706', '#e11d48'];
    $chartColors = ['#2563eb', '#10b981', '#f59e0b', '#a855f7', '#f97316'];
    $viaLabels = ['splitter' => 'Splitter rule', 'import' => 'Upload', 'manual' => 'Manual', 'queue' => 'Lead Queue'];
    $timeAgo = function ($at) {
        if (! $at) {
            return '—';
        }
        $minutes = max(1, (int) $at->diffInMinutes());

        return $minutes < 60 ? $minutes.' min'.($minutes === 1 ? '' : 's').' ago' : $at->diffForHumans();
    };
@endphp
<x-leadmax-layout :central-url="$centralUrl" :active-lead-max-page="$activeLeadMaxPage">
<div class="lm-ls">
    <header class="lm-ls-top">
        <div>
            <h1>Lead Splitter <span title="Rules are checked by priority. The first active rule that matches a lead's source and country picks the agent.">{!! $img('title-info', 16) !!}</span></h1>
            <p>Automatically distribute incoming leads to your team based on predefined rules.</p>
        </div>
        <div class="lm-head-side">
        <nav class="lm-ls-crumbs" aria-label="Breadcrumb"><a href="{{ route('admin.leadmax.dashboard') }}">Dashboard</a><i>/</i><span>CRM</span><i>/</i><b>Lead Splitter</b></nav>
        <div class="lm-ls-actions">
            @if(is_admin())
                <form method="POST" action="{{ route('admin.leadmax.lead-splitter.switch') }}">@csrf
                    <button type="submit" class="lm-ls-master {{ $settings['enabled'] ? 'is-on' : '' }}" aria-pressed="{{ $settings['enabled'] ? 'true' : 'false' }}" title="Turn the Lead Splitter {{ $settings['enabled'] ? 'off' : 'on' }}"><i aria-hidden="true"></i><span>Splitter: <b>{{ $settings['enabled'] ? 'ON' : 'OFF' }}</b></span></button>
                </form>
                <a class="lm-ls-create" href="{{ route('admin.leadmax.lead-splitter', ['dialog' => 'rule']) }}">{!! $img('plus', 14) !!}<span>Create Rule</span></a>
            @else
                <span class="lm-ls-master {{ $settings['enabled'] ? 'is-on' : '' }}"><i aria-hidden="true"></i><span>Splitter: <b>{{ $settings['enabled'] ? 'ON' : 'OFF' }}</b></span></span>
            @endif
            <a class="lm-ls-logs {{ $tab === 'logs' ? 'is-active' : '' }}" href="{{ route('admin.leadmax.lead-splitter', ['tab' => 'logs']) }}">{!! $img('logs', 14) !!}<span>Logs</span></a>
        </div>
        </div>
    </header>
    @if(session('success'))<p class="lm-detail-flash" role="status">{{ session('success') }}</p>@endif
    @if($errors->any())<p class="lm-detail-flash lm-detail-flash-warning" role="alert">{{ $errors->first() }}</p>@endif

    <section class="lm-ls-kpis" aria-label="Lead Splitter summary">
        <div class="lm-card-link lm-ls-kpi"><a class="lm-card-main lm-card-cover" href="{{ route('admin.leadmax.leads', ['period' => 'current_month']) }}" aria-label="Total Leads (This Month)"></a><span class="lm-ls-kpi-icon">{!! $img('kpi-total', 20) !!}</span><div><small>Total Leads (This Month)</small><strong>{{ number_format($kpi['total']['value']) }} {!! $change($kpi['total']['change']) !!}</strong><em>vs last month: {{ number_format($kpi['total']['previous']) }}</em></div></div>
        <div class="lm-card-link lm-ls-kpi"><a class="lm-card-main lm-card-cover" href="{{ route('admin.leadmax.leads', ['period' => 'current_month', 'manager_id' => 'assigned']) }}" aria-label="Assigned Leads"></a><span class="lm-ls-kpi-icon">{!! $img('kpi-assigned', 20) !!}</span><div><small>Assigned Leads</small><strong>{{ number_format($kpi['assigned']['value']) }} {!! $change($kpi['assigned']['change']) !!}</strong><em>vs last month: {{ number_format($kpi['assigned']['previous']) }}</em></div></div>
        <div class="lm-card-link lm-ls-kpi"><a class="lm-card-main lm-card-cover" href="{{ route('admin.leadmax.leads', [...['period' => 'current_month'], 'manager_id' => 'unassigned']) }}" aria-label="Pending Assignment"></a><span class="lm-ls-kpi-icon">{!! $img('kpi-pending', 20) !!}</span><div><small>Pending Assignment</small><strong>{{ number_format($kpi['pending']['value']) }} {!! $change($kpi['pending']['change']) !!}</strong><em>vs last month: {{ number_format($kpi['pending']['previous']) }}</em></div></div>
        <div class="lm-card-link lm-ls-kpi"><a class="lm-card-main lm-card-cover" href="{{ route('admin.leadmax.lead-splitter', ['status' => 'active']) }}" aria-label="Active Rules"></a><span class="lm-ls-kpi-icon">{!! $img('kpi-rules', 20) !!}</span><div><small>Active Rules</small><strong>{{ number_format($kpi['rules']['value']) }} {!! $change($kpi['rules']['change']) !!}</strong><em>out of {{ number_format($kpi['rules']['of']) }} rules</em></div></div>
        <div class="lm-card-link lm-ls-kpi"><a class="lm-card-main lm-card-cover" href="{{ route('admin.leadmax.agents') }}" aria-label="Agents Receiving Leads"></a><span class="lm-ls-kpi-icon">{!! $img('kpi-agents', 20) !!}</span><div><small>Agents Receiving Leads</small><strong>{{ number_format($kpi['agents']['value']) }} {!! $change($kpi['agents']['change']) !!}</strong><em>vs last month: {{ number_format($kpi['agents']['previous']) }}</em></div></div>
    </section>

    <nav class="lm-ls-tabs" aria-label="Lead Splitter sections">
        @foreach($tabs as $key => $label)<a href="{{ route('admin.leadmax.lead-splitter', $key === 'rules' ? [] : ['tab' => $key]) }}" class="{{ $tab === $key ? 'active' : '' }}" @if($tab === $key) aria-current="page" @endif>{{ $label }}</a>@endforeach
    </nav>

    @if($tab === 'rules')
        <section class="lm-ls-card lm-ls-rules">
            <header class="lm-ls-table-head">
                <div class="lm-ls-table-title"><h2>Distribution Rules</h2><span class="lm-ls-pill">{{ $ruleCount }}</span></div>
                <div class="lm-ls-table-tools">
                    <form method="GET" class="lm-ls-search" role="search">
                        @foreach(['status', 'method'] as $keep)@if(request()->filled($keep))<input type="hidden" name="{{ $keep }}" value="{{ request($keep) }}">@endif @endforeach
                        <input type="search" name="q" value="{{ request('q') }}" placeholder="Search rule name, source, country..." aria-label="Search rules">
                        <button type="submit" aria-label="Search">{!! $img('search', 14) !!}</button>
                    </form>
                    <div class="lm-ls-filter-wrap">
                        <button type="button" class="lm-ls-filter-btn" data-lm-filter-toggle aria-controls="lm-splitter-filters">{!! $img('filter', 14) !!}<span>Filters</span></button>
                        @include('admin.leadmax.partials.filter-panel', ['panelId' => 'lm-splitter-filters', 'keep' => ['q'], 'selects' => ['status' => ['Status', ['active' => 'Active', 'paused' => 'Paused']], 'method' => ['Allocation Method', \App\Models\LeadSplitRule::METHODS]]])
                    </div>
                    <details class="lm-ls-menu">
                        <summary class="lm-ls-dots" aria-label="More actions">{!! $img('dots', 16) !!}</summary>
                        <div>
                            <a href="{{ route('admin.leadmax.lead-splitter.export', request()->only(['q', 'status', 'method'])) }}">Export rules (CSV)</a>
                            @if(is_admin())<a href="{{ route('admin.leadmax.lead-splitter', ['dialog' => 'rule']) }}">Create rule</a>@endif
                            <a href="{{ route('admin.leadmax.lead-splitter', ['tab' => 'queue']) }}">View lead queue</a>
                            <a href="{{ route('admin.leadmax.lead-splitter', ['tab' => 'logs']) }}">View assignment logs</a>
                        </div>
                    </details>
                </div>
            </header>
            <div class="lm-ls-table">
                <div class="lm-ls-thead"><span class="is-center">#</span><span>Rule Name</span><span>Source</span><span>Country / Region</span><span>Allocation Method</span><span>Recipients</span><span>Status</span><span class="is-center">Priority</span><span>Created At</span><span class="is-right">Actions</span></div>
                @forelse($rules as $index => $rule)
                    <div class="lm-ls-row">
                        <span class="is-center is-muted">{{ $rules->firstItem() + $index }}</span>
                        <span class="is-white is-ellipsis" title="{{ $rule->name }}">{{ $rule->name }}</span>
                        <span class="is-ellipsis">{{ $rule->source ?: 'Any' }}</span>
                        <span class="is-ellipsis" title="{{ $rule->countries ? implode(', ', $rule->countries) : 'All Countries' }}">{{ $rule->regionLabel() }}</span>
                        <span>{{ $rule->methodLabel() }}</span>
                        <span class="is-ellipsis">{{ $controller->recipients($rule, $agentsById) }}</span>
                        <span><em class="lm-ls-status is-{{ $rule->status }}">{{ ucfirst($rule->status) }}</em></span>
                        <span class="is-center">{{ $rule->priority }}</span>
                        <span class="is-muted">{{ $rule->created_at?->format('Y-m-d H:i') }}</span>
                        <span class="lm-ls-row-actions">
                            @if(is_admin())
                                <a href="{{ route('admin.leadmax.lead-splitter', ['dialog' => 'rule', 'rule' => $rule->id]) }}" title="Edit rule" aria-label="Edit {{ $rule->name }}">{!! $img('edit', 14) !!}</a>
                                <form method="POST" action="{{ route('admin.leadmax.lead-splitter.toggle', $rule->id) }}">@csrf<button type="submit" class="lm-ls-switch {{ $rule->status === 'active' ? 'is-on' : '' }}" aria-pressed="{{ $rule->status === 'active' ? 'true' : 'false' }}" title="{{ $rule->status === 'active' ? 'Pause' : 'Activate' }} rule" aria-label="{{ $rule->status === 'active' ? 'Pause' : 'Activate' }} {{ $rule->name }}"></button></form>
                                <details class="lm-ls-menu is-row"><summary aria-label="More actions for {{ $rule->name }}">{!! $img('more', 14) !!}</summary>
                                    <div>
                                        <a href="{{ route('admin.leadmax.lead-splitter', ['dialog' => 'rule', 'rule' => $rule->id]) }}">Edit rule</a>
                                        <form method="POST" action="{{ route('admin.leadmax.lead-splitter.duplicate', $rule->id) }}">@csrf<button type="submit">Duplicate</button></form>
                                        <form method="POST" action="{{ route('admin.leadmax.lead-splitter.destroy', $rule->id) }}">@csrf @method('DELETE')<button type="submit" class="is-danger" data-lm-confirm-submit="Delete the rule {{ $rule->name }}? Leads already assigned keep their agent.">Delete</button></form>
                                    </div>
                                </details>
                            @else
                                <span class="lm-ls-switch {{ $rule->status === 'active' ? 'is-on' : '' }}" aria-hidden="true"></span>
                            @endif
                        </span>
                    </div>
                @empty
                    <div class="lm-ls-empty">{{ request()->hasAny(['q', 'status', 'method']) ? 'No rules match your search.' : 'No distribution rules yet. Create a rule to route leads by source and country.' }}</div>
                @endforelse
            </div>
            @if($rules->hasPages())@include('admin.leadmax.partials.lead-tab-pager', ['rows' => $rules, 'noun' => 'rules'])@endif
        </section>

        <div class="lm-ls-bottom">
            <section class="lm-ls-card lm-ls-preview">
                <div>
                    <header class="lm-ls-card-head">
                        <div class="lm-ls-card-title"><h2>Distribution Preview</h2><span title="Estimated from the source and country of leads received in the last 30 days.">{!! $img('preview-info', 14) !!}</span></div>
                        <form method="GET" class="lm-ls-preview-size">
                            <select name="preview" aria-label="Preview size" data-lm-autosubmit>@foreach(\App\Http\Controllers\Admin\LeadMaxSplitterController::PREVIEW_SIZES as $size)<option value="{{ $size }}" @selected($previewSize === $size)>Next {{ number_format($size) }} Leads</option>@endforeach</select>
                            {!! $img('chevron', 12) !!}
                        </form>
                    </header>
                    @php
                        $top = array_slice($preview, 0, 5);
                        $rest = array_slice($preview, 5);
                        $others = ['leads' => array_sum(array_column($rest, 'leads')), 'percent' => array_sum(array_column($rest, 'percent'))];
                        $segments = collect($top)->values()->map(fn ($row, $i) => [$chartColors[$i], $row['leads']])->when($others['leads'] > 0, fn ($c) => $c->push(['#64748b', $others['leads']]));
                        $stops = [];
                        $at = 0;
                        foreach ($segments as [$color, $value]) {
                            $end = $at + ($previewSize > 0 ? $value / $previewSize * 100 : 0);
                            $stops[] = $color.' '.round($at, 2).'% '.round($end, 2).'%';
                            $at = $end;
                        }
                    @endphp
                    <div class="lm-ls-preview-body">
                        <div class="lm-ls-preview-table">
                            <div class="lm-ls-ptr is-head"><span>Agent</span><span>Leads (Est.)</span><span>Percentage</span></div>
                            @forelse($top as $i => $row)
                                <div class="lm-ls-ptr"><span class="lm-ls-agent"><i style="--c:{{ $avatarColors[$i] }}">{{ $row['initials'] }}</i><span class="is-ellipsis">{{ $row['name'] }}</span></span><span>{{ number_format($row['leads']) }}</span><span class="is-muted">{{ $row['percent'] }}%</span></div>
                            @empty
                                <div class="lm-ls-ptr is-empty"><span>No active agents to distribute to.</span></div>
                            @endforelse
                            @if($others['leads'] > 0)<div class="lm-ls-ptr"><span class="lm-ls-agent"><i class="is-others">...</i><span class="is-muted">Others</span></span><span>{{ number_format($others['leads']) }}</span><span class="is-muted">{{ $others['percent'] }}%</span></div>@endif
                            <div class="lm-ls-ptr is-total"><span>Total</span><span>{{ number_format($previewSize) }}</span><span>100%</span></div>
                        </div>
                        <div class="lm-ls-chart">
                            <div class="lm-ls-donut" style="--stops:{{ $stops ? implode(', ', $stops) : '#1e293b 0% 100%' }}" role="img" aria-label="Estimated distribution chart"><span><b>{{ number_format($previewSize) }}</b><small>Leads</small></span></div>
                            <ul class="lm-ls-legend">
                                @foreach($top as $i => $row)<li><i style="--c:{{ $chartColors[$i] }}"></i><span>{{ $row['name'] }}</span><em>{{ $row['percent'] }}%</em></li>@endforeach
                                @if($others['leads'] > 0)<li><i style="--c:#64748b"></i><span>Others</span><em>{{ $others['percent'] }}%</em></li>@endif
                            </ul>
                        </div>
                    </div>
                </div>
                <p class="lm-ls-note">{!! $img('alert-info', 16) !!}<span>{{ $settings['enabled'] ? 'This is an estimated distribution based on current active rules.' : 'The splitter is off, so this estimate uses round robin across all active agents.' }}</span></p>
            </section>

            <section class="lm-ls-card lm-ls-recent">
                <header class="lm-ls-card-head"><h2>Recent Assignments</h2>@if($recent->isNotEmpty())<a href="{{ route('admin.leadmax.lead-splitter', ['tab' => 'logs']) }}">View all</a>@endif</header>
                <div class="lm-ls-rtable">
                    <div class="lm-ls-rtr is-head"><span>Lead ID</span><span>Name</span><span>Assigned To</span><span>Source</span><span>Time</span></div>
                    @forelse($recent as $item)
                        <div class="lm-ls-rtr">
                            <span>@if($item->lead)<a href="{{ route('admin.leadmax.leads.show', $item->lead_id) }}">{{ $item->lead->account_id ?: strtoupper(substr($item->lead_id, -6)) }}</a>@else<span class="is-muted">Deleted</span>@endif</span>
                            <span class="is-ellipsis is-light">{{ $item->lead?->name ?? '—' }}</span>
                            <span class="is-ellipsis is-soft">{{ $splitter->shortName($item->agent) }}</span>
                            <span class="is-ellipsis is-muted">{{ $item->lead?->source ?: '—' }}</span>
                            <span class="is-muted">{{ $timeAgo($item->created_at) }}</span>
                        </div>
                    @empty
                        <div class="lm-ls-rtr is-empty"><span>No leads have been assigned automatically yet.</span></div>
                    @endforelse
                </div>
            </section>
        </div>
    @endif

    @if($tab === 'allocation')
        <section class="lm-ls-card">
            <header class="lm-ls-table-head"><div class="lm-ls-table-title"><h2>Agent Allocation</h2><span class="lm-ls-pill">{{ $allocation->count() }}</span></div><small class="lm-ls-hint">Leads assigned automatically or manually this month</small></header>
            <div class="lm-ls-table">
                <div class="lm-ls-thead is-allocation"><span>Agent</span><span class="is-center">Active Rules</span><span class="is-right">Leads This Month</span><span>Share</span><span class="is-right">Open Leads</span><span>Last Assigned</span></div>
                @forelse($allocation as $i => $row)
                    <div class="lm-ls-row is-allocation">
                        <span class="lm-ls-agent"><i style="--c:{{ $avatarColors[$i % 5] }}">{{ $row['initials'] }}</i><a class="is-white is-ellipsis" href="{{ route('admin.leadmax.agents.show', $row['agent']->id) }}">{{ $row['name'] }}</a></span>
                        <span class="is-center">{{ $row['rules'] }}</span>
                        <span class="is-right is-white">{{ number_format($row['month']) }}</span>
                        <span class="lm-ls-share"><i><b style="--w:{{ $row['share'] }}%"></b></i><em>{{ $row['share'] }}%</em></span>
                        <span class="is-right">{{ number_format($row['open']) }}</span>
                        <span class="is-muted">{{ $row['last'] ? $timeAgo($row['last']) : '—' }}</span>
                    </div>
                @empty
                    <div class="lm-ls-empty">No active agents. Add agents to start distributing leads.</div>
                @endforelse
            </div>
        </section>
    @endif

    @if($tab === 'queue')
        <form method="POST" action="{{ route('admin.leadmax.lead-splitter.queue.assign') }}" class="lm-ls-card">@csrf
            <header class="lm-ls-table-head">
                <div class="lm-ls-table-title"><h2>Lead Queue</h2><span class="lm-ls-pill">{{ $queue->total() }}</span></div>
                @if(is_admin() && $queue->total() > 0)<div class="lm-ls-table-tools"><button type="submit" class="lm-ls-filter-btn">Assign selected</button><button type="submit" name="all" value="1" class="lm-ls-create" data-lm-queue-all>Assign all with splitter</button></div>@endif
            </header>
            <div class="lm-ls-table">
                <div class="lm-ls-thead is-queue"><span></span><span>Lead</span><span>Email</span><span>Source</span><span>Country</span><span>Received</span></div>
                @forelse($queue as $lead)
                    <label class="lm-ls-row is-queue">
                        <span>@if(is_admin())<input type="checkbox" name="leads[]" value="{{ $lead->id }}" aria-label="Select {{ $lead->name }}">@endif</span>
                        <a class="is-white is-ellipsis" href="{{ route('admin.leadmax.leads.show', $lead->id) }}">{{ $lead->name }}</a>
                        <span class="is-ellipsis">{{ $lead->email }}</span>
                        <span class="is-ellipsis">{{ $lead->source ?: '—' }}</span>
                        <span class="is-ellipsis">{{ $lead->country ?: '—' }}</span>
                        <span class="is-muted">{{ $lead->created_at?->format('Y-m-d H:i') }}</span>
                    </label>
                @empty
                    <div class="lm-ls-empty">Every lead has an owner. New leads wait here when no agent is available.</div>
                @endforelse
            </div>
            @if($queue->hasPages())@include('admin.leadmax.partials.lead-tab-pager', ['rows' => $queue, 'noun' => 'leads'])@endif
        </form>
    @endif

    @if($tab === 'settings')
        <form method="POST" action="{{ route('admin.leadmax.lead-splitter.settings') }}" class="lm-ls-card lm-ls-settings">@csrf
            <header class="lm-ls-table-head"><div class="lm-ls-table-title"><h2>Splitter Settings</h2></div></header>
            <div class="lm-ls-settings-body">
                <label class="lm-ls-setting"><span><b>Lead Splitter</b><small>When on, new leads without an owner (added manually, uploaded with Auto, or routed from the queue) follow the distribution rules.</small></span><span class="lm-ls-toggle"><input type="checkbox" name="enabled" value="1" @checked($settings['enabled']) @disabled(! is_admin()) class="sr-only"><i aria-hidden="true"></i></span></label>
                <label class="lm-ls-setting"><span><b>When no rule matches</b><small>What happens to a lead whose source and country match no active rule.</small></span><span class="lm-ls-select"><select name="fallback" @disabled(! is_admin())><option value="round_robin" @selected($settings['fallback'] === 'round_robin')>Round robin across all agents</option><option value="queue" @selected($settings['fallback'] === 'queue')>Keep in the lead queue</option></select>{!! $img('chevron', 12) !!}</span></label>
                <label class="lm-ls-setting"><span><b>Maximum open leads per agent</b><small>Agents at this limit are skipped. 0 means no limit.</small></span><input class="lm-ls-input" type="number" name="max_open_leads" min="0" max="100000" value="{{ (int) $settings['max_open_leads'] }}" @disabled(! is_admin())></label>
            </div>
            @if(is_admin())<footer class="lm-ls-settings-foot"><button type="submit" class="lm-ls-create">Save settings</button></footer>@endif
        </form>
    @endif

    @if($tab === 'logs')
        <section class="lm-ls-card">
            <header class="lm-ls-table-head">
                <div class="lm-ls-table-title"><h2>Assignment Logs</h2><span class="lm-ls-pill">{{ $logs->total() }}</span></div>
                <div class="lm-ls-table-tools lm-ls-log-filter">
                    @foreach(['' => 'All'] + $viaLabels as $value => $label)<a href="{{ route('admin.leadmax.lead-splitter', array_filter(['tab' => 'logs', 'via' => $value])) }}" class="{{ (string) request('via') === (string) $value ? 'active' : '' }}">{{ $label }}</a>@endforeach
                </div>
            </header>
            <div class="lm-ls-table">
                <div class="lm-ls-thead is-logs"><span>Lead ID</span><span>Lead</span><span>Assigned To</span><span>Rule</span><span>Method</span><span>Via</span><span>Source</span><span>Country</span><span>Time</span></div>
                @forelse($logs as $item)
                    <div class="lm-ls-row is-logs">
                        <span>@if($item->lead)<a class="is-link" href="{{ route('admin.leadmax.leads.show', $item->lead_id) }}">{{ $item->lead->account_id ?: strtoupper(substr($item->lead_id, -6)) }}</a>@else<span class="is-muted">Deleted</span>@endif</span>
                        <span class="is-white is-ellipsis">{{ $item->lead?->name ?? '—' }}</span>
                        <span class="is-ellipsis">{{ $item->agent?->name ?? '—' }}</span>
                        <span class="is-ellipsis">{{ $item->rule?->name ?? ($item->rule_id ? 'Deleted rule' : '—') }}</span>
                        <span>{{ \App\Models\LeadSplitRule::METHODS[$item->method] ?? ($item->method === 'manual' ? 'Manual' : ucfirst(str_replace('_', ' ', (string) $item->method))) }}</span>
                        <span>{{ $viaLabels[$item->via] ?? ucfirst($item->via) }}</span>
                        <span class="is-ellipsis is-muted">{{ $item->lead?->source ?: '—' }}</span>
                        <span class="is-ellipsis is-muted">{{ $item->lead?->country ?: '—' }}</span>
                        <span class="is-muted">{{ $item->created_at?->format('Y-m-d H:i') }}</span>
                    </div>
                @empty
                    <div class="lm-ls-empty">No assignments logged yet.</div>
                @endforelse
            </div>
            @include('admin.leadmax.partials.lead-tab-pager', ['rows' => $logs, 'noun' => 'assignments'])
        </section>
    @endif
</div>

@if($ruleDialog)
    @php
        $closeUrl = route('admin.leadmax.lead-splitter');
        $selectedAgents = old('agents', array_keys($ruleDialog->agentWeights()));
        $weights = old('weights', $ruleDialog->agentWeights());
        $regionValue = old('region', $ruleDialog->exists ? ($ruleDialog->region ?: (empty($ruleDialog->countries) ? 'all' : 'custom')) : 'all');
        $customCountries = old('countries', $ruleDialog->region ? [] : ($ruleDialog->countries ?? []));
        $method = old('method', $ruleDialog->method);
    @endphp
    <div class="lm-modal-backdrop lm-ls-backdrop">
        <div class="lm-modal-card lm-modal-card-lg lm-ls-dialog" role="dialog" aria-modal="true" aria-labelledby="lm-ls-dialog-title">
            <div class="lm-modal-header"><div><strong id="lm-ls-dialog-title">{{ $ruleDialog->exists ? 'Edit Rule' : 'Create Rule' }}</strong><span class="lm-modal-subtitle">Route leads that match a source and country to the agents you choose.</span></div><a class="lm-modal-close" href="{{ $closeUrl }}" aria-label="Close">&times;</a></div>
            <form method="POST" action="{{ $ruleDialog->exists ? route('admin.leadmax.lead-splitter.update', $ruleDialog->id) : route('admin.leadmax.lead-splitter.store') }}" class="lm-modal-form" data-lm-rule-form>
                @csrf @if($ruleDialog->exists) @method('PUT') @endif
                <div class="lm-modal-section">
                    <div class="lm-modal-section-title">Rule</div>
                    <div class="lm-modal-row">
                        <div class="lm-modal-field"><label for="lm-rule-name">Rule Name <span class="lm-req">*</span></label><input id="lm-rule-name" name="name" required maxlength="150" value="{{ old('name', $ruleDialog->name) }}" placeholder="e.g. Google Ads - EU"></div>
                        <div class="lm-modal-field"><label for="lm-rule-priority">Priority <span class="lm-req">*</span></label><input id="lm-rule-priority" type="number" name="priority" required min="1" max="999" value="{{ old('priority', $ruleDialog->priority) }}"></div>
                    </div>
                    <div class="lm-modal-row">
                        <div class="lm-modal-field"><label for="lm-rule-source">Source</label><select id="lm-rule-source" name="source"><option value="">Any source</option>@foreach($sources->when($ruleDialog->source && ! $sources->contains($ruleDialog->source), fn ($s) => $s->push($ruleDialog->source)) as $source)<option @selected(old('source', $ruleDialog->source) === $source)>{{ $source }}</option>@endforeach</select></div>
                        <div class="lm-modal-field"><label for="lm-rule-region">Country / Region <span class="lm-req">*</span></label><select id="lm-rule-region" name="region" data-lm-rule-region><option value="all" @selected($regionValue === 'all')>All Countries</option>@foreach(array_keys($regions) as $region)<option @selected($regionValue === $region)>{{ $region }}</option>@endforeach<option value="custom" @selected($regionValue === 'custom')>Choose countries…</option></select></div>
                    </div>
                    <div class="lm-modal-field lm-ls-countries" data-lm-rule-countries @if($regionValue !== 'custom') hidden @endif><label for="lm-rule-countries">Countries</label><select id="lm-rule-countries" name="countries[]" multiple size="6">@foreach($countries as $country)<option @selected(in_array($country, $customCountries, true))>{{ $country }}</option>@endforeach</select><small>Hold Ctrl (Cmd on Mac) to choose several.</small></div>
                </div>
                <div class="lm-modal-section">
                    <div class="lm-modal-section-title">Allocation</div>
                    <div class="lm-modal-field"><label for="lm-rule-method">Allocation Method <span class="lm-req">*</span></label><select id="lm-rule-method" name="method" data-lm-rule-method>@foreach(\App\Models\LeadSplitRule::METHODS as $value => $label)<option value="{{ $value }}" @selected($method === $value)>{{ $label }}</option>@endforeach</select></div>
                    <p class="lm-ls-method-hint" data-lm-rule-hint>
                        <span data-method="round_robin">Each new lead goes to the next agent in turn.</span>
                        <span data-method="weighted">Agents receive leads in proportion to their weight (a weight of 3 gets three leads for every one at weight 1).</span>
                        <span data-method="specific">Every matching lead goes to one agent.</span>
                        <span data-method="balanced">Each lead goes to the agent who currently owns the fewest leads.</span>
                    </p>
                    <div class="lm-ls-recipients" data-lm-rule-agents data-method="{{ $method }}">
                        <div class="lm-ls-recipients-head"><span>Recipients</span><small>None selected = all active agents</small></div>
                        @forelse($agents as $agent)
                            <label class="lm-ls-recipient"><input type="checkbox" name="agents[]" value="{{ $agent->id }}" @checked(in_array((string) $agent->id, array_map('strval', $selectedAgents), true))><span>{{ $agent->name }}</span><input type="number" name="weights[{{ $agent->id }}]" min="1" max="100" value="{{ $weights[(string) $agent->id] ?? 1 }}" aria-label="Weight for {{ $agent->name }}" class="lm-ls-weight"></label>
                        @empty
                            <p class="lm-ls-hint">No active agents yet.</p>
                        @endforelse
                    </div>
                </div>
                <div class="lm-modal-section">
                    <label class="lm-ls-setting is-compact"><span><b>Active</b><small>Paused rules are skipped.</small></span><span class="lm-ls-toggle"><input type="checkbox" name="active" value="1" @checked(old('active', $ruleDialog->status === 'active')) class="sr-only"><i aria-hidden="true"></i></span></label>
                </div>
                <div class="lm-modal-actions"><a class="lm-modal-btn-cancel lm-ls-cancel" href="{{ $closeUrl }}">Cancel</a><button type="submit" class="lm-modal-btn-submit">{{ $ruleDialog->exists ? 'Save Rule' : 'Create Rule' }}</button></div>
            </form>
        </div>
    </div>
@endif
</x-leadmax-layout>

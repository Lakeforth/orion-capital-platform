<x-leadmax-layout :central-url="$centralUrl" :active-lead-max-page="$activeLeadMaxPage">
@php
$initials = strtoupper(substr($manager->first_name,0,1).substr($manager->last_name,0,1));
$colors = ['#f97316','#3b82f6','#10b981','#8b5cf6','#ec4899','#06b6d4'];
$color = $colors[crc32($manager->id) % count($colors)];
$statusClass = match(strtolower($manager->status??'active')){ 'active'=>'lm-mgr-status-active','suspended','inactive'=>'lm-mgr-status-inactive',default=>'lm-mgr-status-pending'};
@endphp
<div class="leadmax-module-page lm-mgr-page lm-mgr-detail-page">

    <header class="lm-mgr-header">
        <div>
            <h1>Manager Details</h1>
            <a class="lm-back-link" href="{{ route('admin.leadmax.managers') }}">← Back to Managers</a>
        </div>
        <div class="lm-mgr-header-actions">
            <nav class="leadmax-breadcrumb"><a href="{{ route('admin.leadmax.managers') }}">Managers</a> <span>/</span> <strong>{{ $adminId }}</strong></nav>
            @if(is_admin())
            @php $isOff = in_array(strtolower((string) $manager->status), \App\Http\Middleware\EnsureStaffIsActive::BLOCKED, true); @endphp
            <form method="POST" action="{{ route('admin.leadmax.managers.status', $manager->id) }}" class="lm-inline-form">
                @csrf
                <button type="submit" class="lm-attr-btn {{ $isOff ? '' : 'lm-mgr-deactivate-btn' }}"
                        data-lm-confirm-submit="{{ $isOff ? 'Reactivate '.$manager->name.'?' : 'Deactivate '.$manager->name.'? They are signed out and cannot sign back in until restored.' }}">
                    {{ $isOff ? '✓ Reactivate' : '✕ Deactivate' }}
                </button>
            </form>
            @endif
            <button type="button" class="lm-attr-btn lm-attr-btn-primary" data-lm-edit-manager="{{ route('admin.leadmax.managers.update', $manager->id) }}" data-first-name="{{ $manager->first_name }}" data-last-name="{{ $manager->last_name }}" data-email="{{ $manager->email }}" data-phone="{{ $manager->phone }}" data-team="{{ $manager->team }}" data-status="{{ $manager->status }}">✎ Edit Manager</button>
        </div>
    </header>

    {{-- Profile Card --}}
    <div class="lm-mgr-profile-card">
        <div class="lm-mgr-profile-left">
            <div class="lm-mgr-profile-avatar" style="background:{{ $color }}">{{ $initials }}</div>
            <div class="lm-mgr-profile-info">
                <h2>{{ $manager->name }}</h2>
                <div class="lm-mgr-profile-badges">
                    <span class="lm-mgr-id-badge">{{ $adminId }}</span>
                    <span class="lm-mgr-role-badge lm-mgr-role-blue">Super Managers</span>
                </div>
            </div>
        </div>
        <div class="lm-mgr-profile-grid">
            <div class="lm-mgr-profile-field"><small>Email</small><span>{{ $manager->email }}</span></div>
            <div class="lm-mgr-profile-field"><small>Team</small><span>{{ $manager->team ?: 'Management' }}</span></div>
            <div class="lm-mgr-profile-field"><small>Status</small><span class="lm-mgr-status {{ $statusClass }}">{{ ucfirst($manager->status ?: 'Active') }}</span></div>
            <div class="lm-mgr-profile-field"><small>Last Login</small><span>@if($manager->last_seen){{ $manager->last_seen->format('Y-m-d') }}<br><small style="color:#94a3b8">{{ $manager->last_seen->format('H:i:s') }}</small>@else—@endif</span></div>
            <div class="lm-mgr-profile-field"><small>Phone</small><span>{{ $manager->phone ?: '+1 555-0142' }}</span></div>
            <div class="lm-mgr-profile-field"><small>Joined</small><span>{{ $manager->created_at?->format('Y-m-d') }}</span></div>
            <div class="lm-mgr-profile-field"><small>Permissions</small><span>Full Access</span></div>
        </div>
    </div>

    @include('admin.leadmax.partials.manager-edit-modal')

    {{-- The manager's team. Agents are the tier that works leads; a manager's numbers above
         are this team's, rolled up (see Staff::leadsUnderManager). --}}
    <section class="lm-mgr-team">
        <header>
            <h2>Team <span class="lm-count">{{ $teamAgents->count() }} {{ \Illuminate\Support\Str::plural('agent', $teamAgents->count()) }}</span></h2>
            @if($freeAgents->isNotEmpty())
                <form method="POST" action="{{ route('admin.leadmax.managers.assign-agent', $manager->id) }}" class="lm-mgr-team-add">
                    @csrf
                    <label class="sr-only" for="lm-add-agent">Add an agent to this team</label>
                    <select id="lm-add-agent" name="agent_id" required>
                        <option value="">Add an agent…</option>
                        @foreach($freeAgents as $free)
                            <option value="{{ $free->id }}">{{ $free->name }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="lm-attr-btn">Add to team</button>
                </form>
            @endif
        </header>

        @if($teamAgents->isEmpty())
            <p class="lm-mgr-team-empty">No agents report to {{ $manager->first_name }} yet.@if($freeAgents->isEmpty()) Every agent already belongs to a team.@endif</p>
        @else
            <ul class="lm-mgr-team-list">
                @foreach($teamAgents as $teamAgent)
                    <li>
                        <a href="{{ route('admin.leadmax.agents.show', $teamAgent->id) }}">
                            <span class="lm-mgr-team-name">{{ $teamAgent->name }}</span>
                            <span class="lm-mgr-team-meta">{{ $teamAgent->team ?: 'No desk' }} · {{ number_format($teamAgent->leads_count) }} {{ \Illuminate\Support\Str::plural('lead', $teamAgent->leads_count) }}</span>
                        </a>
                        <form method="POST" action="{{ route('admin.leadmax.managers.assign-agent', $manager->id) }}">
                            @csrf
                            <input type="hidden" name="agent_id" value="{{ $teamAgent->id }}">
                            <input type="hidden" name="detach" value="1">
                            <button type="submit" class="lm-mgr-team-remove" aria-label="Remove {{ $teamAgent->name }} from this team">Remove</button>
                        </form>
                    </li>
                @endforeach
            </ul>
        @endif
    </section>

    {{-- KPI Cards --}}
    <div class="lm-mgr-detail-kpis">
        <div class="lm-card-link lm-mgr-detail-kpi"><a class="lm-card-main lm-card-cover" href="{{ route('admin.leadmax.leads', ['period' => 'all_time', 'manager_id' => $manager->id]) }}" aria-label="Assigned Customers"></a>
            <div class="lm-mgr-kpi-icon lm-mgr-kpi-blue"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/></svg></div>
            <div><strong>{{ number_format($totalLeads) }}</strong><span>Assigned Customers</span><small>Total managed clients</small></div>
        </div>
        <div class="lm-mgr-detail-kpi">
            <div class="lm-mgr-kpi-icon lm-mgr-kpi-teal"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg></div>
            <div><strong>{{ number_format($totalFtds) }}</strong><span>Active Trades</span><small>Current open positions</small></div>
        </div>
        <div class="lm-card-link lm-mgr-detail-kpi"><a class="lm-card-main lm-card-cover" href="{{ route('admin.leadmax.finances', ['manager_id' => $manager->id, 'tab' => 'deposits', 'status' => 'approved']) }}" aria-label="Total Deposits"></a>
            <div class="lm-mgr-kpi-icon lm-mgr-kpi-green"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/></svg></div>
            <div><strong>${{ number_format($totalDeposits,0) }}</strong><span>Total Deposits</span><small>Cumulative deposit volume</small></div>
        </div>
        <div class="lm-card-link lm-mgr-detail-kpi"><a class="lm-card-main lm-card-cover" href="{{ route('admin.leadmax.leads', ['period' => 'all_time', 'manager_id' => $manager->id, 'ftd' => 'yes']) }}" aria-label="Conversion Rate"></a>
            <div class="lm-mgr-kpi-icon lm-mgr-kpi-orange"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg></div>
            <div><strong>{{ $convRate }}%</strong><span>Conversion Rate</span><small>Sales conversion speed</small></div>
        </div>
    </div>

    {{-- Two-column layout: Customers + Activity --}}
    <div class="lm-mgr-detail-body">
        <div class="lm-mgr-customers-section">
            <div class="lm-attr-table-header" style="margin-bottom:12px">
                <div><strong>Assigned Customers</strong></div>
                <div class="lm-attr-table-controls">
                    <form method="GET" class="lm-attr-search-wrap">@if(request()->filled('country'))<input type="hidden" name="country" value="{{ request('country') }}">@endif @if(request()->filled('status'))<input type="hidden" name="status" value="{{ request('status') }}">@endif <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg><input type="search" name="search" value="{{ request('search') }}" placeholder="Search customer..." aria-label="Search customers" class="lm-attr-search-input"></form>
                    <button type="button" class="lm-attr-btn" data-lm-filter-toggle aria-controls="lm-manager-customer-filters">Filter</button>
                </div>
            </div>
            @include('admin.leadmax.partials.filter-panel', ['panelId' => 'lm-manager-customer-filters', 'keep' => ['search'], 'selects' => ['status' => ['Status', ['new' => 'New', 'contacted' => 'Contacted', 'qualified' => 'Qualified', 'converted' => 'Converted', 'disqualified' => 'Disqualified']], 'country' => ['Country', $filterCountries->mapWithKeys(fn ($c) => [$c => $c])->all()]]])
            <div class="lm-attr-table-wrap">
                <table class="lm-mgr-table lm-mgr-customers-table">
                    <thead><tr><th>Cust ID</th><th>Name</th><th>Email</th><th>Phone</th><th>Assigned</th><th>Balance</th><th>Status</th></tr></thead>
                    <tbody>
                    @forelse($customers as $c)
                    @php $cId='CST'.str_pad(substr(abs(crc32($c->id)),0,3),3,'0',STR_PAD_LEFT); $sc=match(strtolower($c->status??'active')){'active'=>'lm-mgr-status-active','pending'=>'lm-mgr-status-pending',default=>'lm-mgr-status-inactive'}; @endphp
                    <tr>
                        <td><a href="{{ route('admin.leadmax.leads.show',$c->id) }}" class="lm-mgr-id-link">{{ $cId }}</a></td>
                        <td>{{ $c->name }}</td>
                        <td>{{ $c->email }}</td>
                        <td>{{ $c->phone ?: '—' }}</td>
                        <td>{{ $c->created_at?->format('Y-m-d') }}</td>
                        <td>${{ number_format((float)$c->balance,2) }}</td>
                        <td><span class="lm-mgr-status {{ $sc }}">{{ ucfirst($c->status ?: 'Active') }}</span></td>
                    </tr>
                    @empty
                    <tr><td colspan="7" class="lm-attr-empty">No customers assigned.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            <div class="lm-attr-table-footer">Showing {{ $customers->firstItem()??0 }} to {{ $customers->lastItem()??0 }} of {{ $customers->total() }} customers
                <div style="display:inline-flex;gap:4px;float:right">
                    @if(!$customers->onFirstPage())<a href="{{ $customers->previousPageUrl() }}" class="lm-ftd-page-btn">‹</a>@else<span class="lm-ftd-page-btn disabled">‹</span>@endif
                    @foreach($customers->getUrlRange(max(1,$customers->currentPage()-2),min($customers->lastPage(),$customers->currentPage()+2)) as $p=>$u)<a href="{{ $u }}" class="lm-ftd-page-btn{{ $p===$customers->currentPage()?' active':'' }}">{{ $p }}</a>@endforeach
                    @if($customers->hasMorePages())<a href="{{ $customers->nextPageUrl() }}" class="lm-ftd-page-btn">›</a>@else<span class="lm-ftd-page-btn disabled">›</span>@endif
                </div>
            </div>
        </div>

        <div class="lm-mgr-activity-section">
            <strong>Recent Activity Log</strong>
            <div class="lm-mgr-activity-list">
                @forelse($recentNotes as $note)
                @php $icons=['✓','●','↑','⊙']; $classes=['lm-mgr-act-green','lm-mgr-act-orange','lm-mgr-act-blue','lm-mgr-act-gray']; $idx=$loop->index%4; @endphp
                <div class="lm-mgr-activity-item">
                    <span class="lm-mgr-act-dot {{ $classes[$idx] }}">{{ $icons[$idx] }}</span>
                    <div>
                        <p>{{ $note->note }}</p>
                        <small>{{ $note->created_at?->diffForHumans() }}</small>
                    </div>
                </div>
                @empty
                <div class="lm-mgr-activity-item"><p style="color:#64748b">No recent activity.</p></div>
                @endforelse
            </div>
        </div>
    </div>

</div>
</x-leadmax-layout>

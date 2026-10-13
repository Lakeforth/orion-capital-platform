<x-leadmax-layout :central-url="$centralUrl" :active-lead-max-page="$activeLeadMaxPage">
@php
$statusClass=fn($s)=>match(strtolower($s??'active')){'active'=>'lm-mgr-status-active','suspended','inactive'=>'lm-mgr-status-inactive','pending'=>'lm-mgr-status-freetime',default=>'lm-mgr-status-pending'};
$statusLabel=fn($s)=>match(strtolower($s??'active')){'active'=>'Active','pending'=>'Free Time','suspended','inactive'=>'Inactive',default=>ucfirst($s??'Active')};
$mkChange=fn($v,$label='vs last month')=>$v===null?'':($v>=0?'<span class="lm-mgr-up">↑ '.abs($v).'%</span> <small>'.$label.'</small>':'<span class="lm-mgr-down">↓ '.abs($v).'%</span> <small>'.$label.'</small>');
$colors=['#f97316','#3b82f6','#10b981','#8b5cf6','#ec4899','#06b6d4','#84cc16','#f59e0b','#ef4444','#14b8a6'];
@endphp
<div class="leadmax-module-page lm-mgr-page lm-agents-page">

    <header class="lm-mgr-header">
        <div>
            <h1>Agents <span class="lm-attr-info-icon" title="Agent management">ⓘ</span></h1>
            <p class="lm-mgr-subtitle">Manage your sales agents, track their performance and assignments.</p>
        </div>
        <div class="lm-mgr-header-actions">
            <nav class="leadmax-breadcrumb"><a href="{{ route('admin.leadmax.dashboard') }}">Dashboard</a> <span>/</span> <strong>Agents</strong></nav>
            <button type="button" class="lm-attr-btn lm-attr-btn-primary" onclick="document.getElementById('lm-add-agent-modal').style.display='flex'"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg> Add Agent</button>
            <button type="button" class="lm-attr-btn"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg> Export</button>
            <button type="button" class="lm-attr-btn lm-mgr-dots-btn"><svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="5" r="1.5"/><circle cx="12" cy="12" r="1.5"/><circle cx="12" cy="19" r="1.5"/></svg></button>
        </div>
    </header>

    {{-- KPIs --}}
    <div class="lm-agents-kpis">
        <div class="lm-card-link lm-mgr-kpi"><a class="lm-card-main lm-card-cover" href="{{ route('admin.leadmax.agents') }}" aria-label="Total Agents"></a>
            <div class="lm-mgr-kpi-icon lm-mgr-kpi-blue"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75"/></svg></div>
            <div><span>Total Agents</span><strong>{{ number_format($totalCount) }}</strong>{!! $mkChange($totalChange) !!}</div>
        </div>
        <div class="lm-card-link lm-mgr-kpi"><a class="lm-card-main lm-card-cover" href="{{ route('admin.leadmax.agents', ['status' => 'active']) }}" aria-label="Active Agents"></a>
            <div class="lm-mgr-kpi-icon lm-mgr-kpi-green"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 11-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg></div>
            <div><span>Active Agents</span><strong>{{ number_format($activeCount) }}</strong>{!! $mkChange($activeChange) !!}</div>
        </div>
        <div class="lm-card-link lm-mgr-kpi"><a class="lm-card-main lm-card-cover" href="{{ route('admin.leadmax.agents', ['status' => 'inactive']) }}" aria-label="Inactive Agents"></a>
            <div class="lm-mgr-kpi-icon lm-mgr-kpi-red"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/></svg></div>
            <div><span>Inactive Agents</span><strong>{{ number_format($inactiveCount) }}</strong>{!! $mkChange($inactiveChange ?? null) !!}</div>
        </div>
        <div class="lm-card-link lm-mgr-kpi"><a class="lm-card-main lm-card-cover" href="{{ route('admin.leadmax.leads', ['period' => 'all_time']) }}" aria-label="Total Leads Assigned"></a>
            <div class="lm-mgr-kpi-icon lm-mgr-kpi-purple"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/></svg></div>
            <div><span>Total Leads Assigned</span><strong>{{ number_format($totalLeadsAssigned) }}</strong>{!! $mkChange($leadsChange ?? null) !!}</div>
        </div>
        <div class="lm-card-link lm-mgr-kpi"><a class="lm-card-main lm-card-cover" href="{{ route('admin.leadmax.leads', ['period' => 'all_time', 'ftd' => 'yes']) }}" aria-label="Total Conversions"></a>
            <div class="lm-mgr-kpi-icon lm-mgr-kpi-teal"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg></div>
            <div><span>Total Conversions</span><strong>{{ number_format($totalConversions) }}</strong>{!! $mkChange($conversionsChange ?? null) !!}</div>
        </div>
    </div>

    {{-- Filters --}}
    <form class="lm-mgr-filters" method="GET">
        <div class="lm-mgr-search-wrap"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg><input type="text" name="search" value="{{ $search }}" placeholder="Search by name, email or agent ID..." class="lm-mgr-search-input"></div>
        <select name="status" class="lm-mgr-select"><option value="all">All Status</option><option value="active" @selected($statusFilter==='active')>Active</option><option value="inactive" @selected($statusFilter==='inactive')>Inactive</option></select>
        <select class="lm-mgr-select"><option>All Teams</option></select>
        <div class="lm-mgr-date-range"><input type="text" placeholder="From date" data-leadmax-date><input type="text" placeholder="To date" data-leadmax-date></div>
        <button type="submit" class="lm-attr-btn"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="4" y1="6" x2="20" y2="6"/><line x1="8" y1="12" x2="16" y2="12"/><line x1="11" y1="18" x2="13" y2="18"/></svg> Filters</button>
    </form>

    {{-- Table --}}
    <div class="lm-mgr-table-card" data-lm-bulk-page data-lm-bulk-noun="agent">
        <div class="lm-bulk-actions" data-lm-bulk-actions hidden><span data-lm-selected-count>0 agents selected</span><div><button type="button" class="lm-button danger" data-lm-bulk-delete disabled><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v6M14 11v6"/><path d="M9 6V4h6v2"/></svg> Delete</button><button type="button" class="lm-button" data-lm-bulk-export><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg> Export</button><button type="button" class="lm-bulk-deselect" data-lm-bulk-deselect aria-label="Deselect all agents"><svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button></div></div>
        <div class="lm-attr-table-wrap">
            <table class="lm-mgr-table lm-agents-table">
                <thead><tr>
                    <th><input type="checkbox" class="lm-ftd-checkbox" data-lm-select-all aria-label="Select all agents"></th>
                    <th>Agent ID</th><th>Name</th><th>Email</th><th>Phone</th><th>Team</th><th>Status</th><th>Leads Assigned</th><th>Conversions</th><th>Join Date</th><th>Action</th>
                </tr></thead>
                <tbody>
                @forelse($agents as $i=>$agent)
                @php
                    $idx = $agentRanks[$agent->id] ?? ($agents->firstItem()-1)+$i;
                    $aId = 'AGT'.str_pad($idx+1,3,'0',STR_PAD_LEFT);
                    $initials = strtoupper(substr($agent->first_name,0,1).substr($agent->last_name,0,1));
                    $color = $colors[$idx % count($colors)];
                    $teams = ['Sales','Sales','Support','Retention','Sales','Support'];
                    $team = $agent->team ?: $teams[$idx%count($teams)];
                    $sc = $statusClass($agent->status);
                    $sl = $statusLabel($agent->status);
                @endphp
                <tr>
                    <td><input type="checkbox" class="lm-ftd-checkbox" data-lm-select value="{{ $agent->id }}" aria-label="Select agent {{ $aId }}"></td>
                    <td><a href="{{ route('admin.leadmax.agents.show',$agent->id) }}" class="lm-mgr-id-link">{{ $aId }}</a></td>
                    <td>
                        <div class="lm-mgr-name-cell">
                            <span class="lm-mgr-avatar" style="background:{{ $color }}">{{ $initials }}</span>
                            <div><span>{{ $agent->name }}</span></div>
                        </div>
                    </td>
                    <td>{{ $agent->email }}</td>
                    <td>{{ $agent->phone ?: '—' }}</td>
                    <td>{{ $team }}</td>
                    <td><span class="lm-mgr-status {{ $sc }}">{{ $sl }}</span></td>
                    <td>{{ number_format($agent->leads_count) }}</td>
                    <td>{{ number_format($agent->ftd_count) }}</td>
                    <td>{{ $agent->created_at?->format('Y-m-d') }}</td>
                    <td class="lm-attr-actions-cell">
                        <a href="{{ route('admin.leadmax.agents.show',$agent->id) }}" title="View"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg></a>
                        @if(is_admin())<button type="button" title="Edit {{ $agent->name }}" data-lm-edit-manager="{{ route('admin.leadmax.agents.update', $agent->id) }}" data-first-name="{{ $agent->first_name }}" data-last-name="{{ $agent->last_name }}" data-email="{{ $agent->email }}" data-phone="{{ $agent->phone }}" data-team="{{ $agent->team }}" data-status="{{ $agent->status }}"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg></button>@endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="11" class="lm-attr-empty">No agents found.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="lm-attr-table-footer">
            Showing {{ $agents->firstItem()??0 }} to {{ $agents->lastItem()??0 }} of {{ $agents->total() }} agents
            <div style="display:inline-flex;gap:4px;float:right;align-items:center">
                <select class="lm-mgr-per-page-select"><option>10</option><option>25</option></select>
                @if(!$agents->onFirstPage())<a href="{{ $agents->previousPageUrl() }}" class="lm-ftd-page-btn">‹</a>@else<span class="lm-ftd-page-btn disabled">‹</span>@endif
                @foreach($agents->getUrlRange(max(1,$agents->currentPage()-2),min($agents->lastPage(),$agents->currentPage()+2)) as $p=>$u)<a href="{{ $u }}" class="lm-ftd-page-btn{{ $p===$agents->currentPage()?' active':'' }}">{{ $p }}</a>@endforeach
                @if($agents->hasMorePages())<a href="{{ $agents->nextPageUrl() }}" class="lm-ftd-page-btn">›</a>@else<span class="lm-ftd-page-btn disabled">›</span>@endif
            </div>
        </div>
    </div>

    <div id="lm-add-agent-modal" class="lm-modal-backdrop" style="display:none" onclick="if(event.target===this)this.style.display='none'">
        <div class="lm-modal-card lm-modal-card-lg">
            <div class="lm-modal-header">
                <div>
                    <strong>Add New Agent</strong>
                    <span class="lm-modal-subtitle">Create a new user agent profile and configure their access.</span>
                </div>
                <button type="button" class="lm-modal-close" onclick="document.getElementById('lm-add-agent-modal').style.display='none'">&times;</button>
            </div>
            <form method="POST" action="{{ route('admin.leadmax.agents.store') }}" class="lm-modal-form">
                @csrf
                <div class="lm-modal-section">
                    <div class="lm-modal-section-title">Personal Information</div>
                    <div class="lm-modal-row">
                        <div class="lm-modal-field"><label>First Name <span class="lm-req">*</span></label><input type="text" name="first_name" required maxlength="100" placeholder="e.g. Sarah"></div>
                        <div class="lm-modal-field"><label>Last Name <span class="lm-req">*</span></label><input type="text" name="last_name" required maxlength="100" placeholder="e.g. Chen"></div>
                    </div>
                    <div class="lm-modal-row">
                        <div class="lm-modal-field"><label>Email Address <span class="lm-req">*</span></label><input type="email" name="email" required maxlength="200" placeholder="e.g. sarah.chen@citationswest.com"></div>
                        <div class="lm-modal-field"><label>Phone Number <span class="lm-req">*</span></label><input type="text" name="phone" required maxlength="50" placeholder="e.g. +972 54 58 28014"></div>
                    </div>
                    <div class="lm-modal-field"><label>Date of Birth</label><input type="date" name="date_of_birth"></div>
                </div>
                <div class="lm-modal-section">
                    <div class="lm-modal-section-title">Work Information</div>
                    <div class="lm-modal-row">
                        <div class="lm-modal-field"><label>Team <span class="lm-req">*</span></label><select name="team"><option value="">Select Team</option><option value="Sales">Sales</option><option value="Retention">Retention</option><option value="Support">Support</option><option value="Management">Management</option></select></div>
                        <div class="lm-modal-field"><label>Role / Position <span class="lm-req">*</span></label><select name="role_position"><option value="">Select Position</option><option value="Junior Agent">Junior Agent</option><option value="Senior Agent">Senior Agent</option><option value="Team Lead">Team Lead</option></select></div>
                    </div>
                    <div class="lm-modal-field"><label>Join Date</label><input type="date" name="join_date"></div>
                </div>
                <div class="lm-modal-section">
                    <div class="lm-modal-status-tabs">
                        <button type="button" class="lm-status-tab active" onclick="lmSetAgentStatus(this,'active')">ACTIVE</button>
                        <button type="button" class="lm-status-tab" onclick="lmSetAgentStatus(this,'inactive')">INACTIVE</button>
                        <input type="hidden" name="status" id="lm-agent-status" value="active">
                    </div>
                </div>
                <div class="lm-modal-section">
                    <div class="lm-modal-section-title">Account Settings</div>
                    <div class="lm-modal-row lm-modal-row-3">
                        <div class="lm-modal-field"><label>Username</label><input type="text" name="username" maxlength="50" placeholder="e.g. sarah0"></div>
                        <div class="lm-modal-field"><label>Password <span class="lm-req">*</span></label><input type="password" name="password" required minlength="6" placeholder="••••••••" data-lm-pw-generate></div>
                        <div class="lm-modal-field"><label>Confirm Password <span class="lm-req">*</span></label><input type="password" name="password_confirmation" required minlength="6" placeholder="••••••••"></div>
                    </div>
                    <p class="lm-modal-req-note">* indicates a required field</p>
                </div>
                <div class="lm-modal-actions">
                    <button type="button" class="lm-modal-btn-cancel" onclick="document.getElementById('lm-add-agent-modal').style.display='none'">Cancel</button>
                    <button type="submit" class="lm-modal-btn-submit">✓ Add Agent</button>
                </div>
            </form>
        </div>
    </div>
    <script>
    function lmSetAgentStatus(btn, val) {
        document.querySelectorAll('#lm-add-agent-modal .lm-status-tab').forEach(function(b){ b.classList.remove('active'); });
        btn.classList.add('active');
        document.getElementById('lm-agent-status').value = val;
    }
    </script>

    @if(is_admin())@include('admin.leadmax.partials.manager-edit-modal', ['noun' => 'Agent', 'teams' => ['Sales', 'Retention', 'Support', 'Management']])@endif
</div>
</x-leadmax-layout>

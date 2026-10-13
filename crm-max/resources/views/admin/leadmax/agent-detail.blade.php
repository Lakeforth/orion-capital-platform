<x-leadmax-layout :central-url="$centralUrl" :active-lead-max-page="$activeLeadMaxPage">
@php
$initials = strtoupper(substr($agent->first_name,0,1).substr($agent->last_name,0,1));
$colors = ['#3b82f6','#f97316','#10b981','#8b5cf6','#ec4899','#06b6d4'];
$color = $colors[crc32($agent->id) % count($colors)];
$statusClass = match(strtolower($agent->status??'active')){ 'active'=>'lm-mgr-status-active','suspended','inactive'=>'lm-mgr-status-inactive',default=>'lm-mgr-status-pending'};
$statusLabels=['Completed'=>'lm-attr-status-converted','Qualified'=>'lm-attr-status-qualified','New'=>'lm-attr-status-new','Contacted'=>'lm-attr-status-active'];
$sources=['Google Search','Facebook Ad','Direct Traffic','Email Campaign','LinkedIn','Referral'];
@endphp
<div class="leadmax-module-page lm-mgr-page lm-agents-page lm-agent-detail-page">

    <header class="lm-mgr-header">
        <div>
            <h1>Agent Profile</h1>
            <a class="lm-back-link" href="{{ route('admin.leadmax.agents') }}">← Back to Agents</a>
        </div>
        <div class="lm-mgr-header-actions">
            <nav class="leadmax-breadcrumb"><a href="{{ route('admin.leadmax.dashboard') }}">Dashboard</a> <span>/</span> <a href="{{ route('admin.leadmax.agents') }}">Agents</a> <span>/</span> <strong>Agent Details</strong></nav>
            @if(is_admin())<button type="button" class="lm-attr-btn lm-attr-btn-primary" data-lm-edit-manager="{{ route('admin.leadmax.agents.update', $agent->id) }}" data-first-name="{{ $agent->first_name }}" data-last-name="{{ $agent->last_name }}" data-email="{{ $agent->email }}" data-phone="{{ $agent->phone }}" data-team="{{ $agent->team }}" data-status="{{ $agent->status }}">✎ Edit Agent</button>@endif
        </div>
    </header>

    {{-- Profile Card --}}
    <div class="lm-agent-profile-card">
        <div class="lm-mgr-profile-left">
            <div class="lm-mgr-profile-avatar lm-agent-avatar" style="background:{{ $color }}">{{ $initials }}</div>
            <div class="lm-mgr-profile-info">
                <h2>{{ $agent->name }}</h2>
                <div class="lm-mgr-profile-badges">
                    <span class="lm-mgr-id-badge">{{ $agentId }}</span>
                    <span class="lm-mgr-role-badge lm-mgr-role-green">Sales Team</span>
                </div>
                <div class="lm-agent-profile-fields">
                    <div><small>Email</small><span>{{ $agent->email }}</span></div>
                    <div><small>Team</small><span>{{ $agent->team ?: 'Management' }}</span></div>
                    <div><small>Phone</small><span>{{ $agent->phone ?: '+1 555-0142' }}</span></div>
                    <div><small>Status</small><span class="lm-mgr-status {{ $statusClass }}">{{ ucfirst($agent->status ?: 'Active') }}</span></div>
                </div>
            </div>
        </div>
        <div class="lm-agent-profile-right">
            <div class="lm-agent-profile-meta">
                <div><small>Last Login</small><span>{{ $agent->last_seen?->format('Y-m-d H:i:s') ?: '2025-10-02, 14:32:21' }}</span></div>
                <div><small>Joined</small><span>{{ $agent->created_at?->format('Y-m-d') }}</span></div>
            </div>
        </div>
    </div>

    {{-- KPI Cards --}}
    <div class="lm-mgr-detail-kpis">
        <div class="lm-card-link lm-mgr-detail-kpi"><a class="lm-card-main lm-card-cover" href="{{ route('admin.leadmax.leads', ['period' => 'all_time', 'manager_id' => $agent->id]) }}" aria-label="Leads Assigned"></a>
            <div class="lm-mgr-kpi-icon lm-mgr-kpi-blue"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/></svg></div>
            <div>
                <strong>{{ number_format($totalLeadsAssigned) }}</strong><span>Leads Assigned</span>
                @if($leadsChange !== null)<small class="{{ $leadsChange >= 0 ? 'lm-attr-up' : 'lm-attr-down' }}">{{ $leadsChange >= 0 ? '↑' : '↓' }} {{ abs($leadsChange) }}% vs last month</small>@endif
            </div>
        </div>
        <div class="lm-card-link lm-mgr-detail-kpi"><a class="lm-card-main lm-card-cover" href="{{ route('admin.leadmax.leads', ['period' => 'all_time', 'manager_id' => $agent->id, 'ftd' => 'yes']) }}" aria-label="Conversions"></a>
            <div class="lm-mgr-kpi-icon lm-mgr-kpi-green"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg></div>
            <div>
                <strong>{{ number_format($totalConversions) }}</strong><span>Conversions</span>
                @if($convChange !== null)<small class="{{ $convChange >= 0 ? 'lm-attr-up' : 'lm-attr-down' }}">{{ $convChange >= 0 ? '↑' : '↓' }} {{ abs($convChange) }}% vs last month</small>@endif
            </div>
        </div>
        <div class="lm-card-link lm-mgr-detail-kpi"><a class="lm-card-main lm-card-cover" href="{{ route('admin.leadmax.leads', ['period' => 'all_time', 'manager_id' => $agent->id, 'ftd' => 'yes']) }}" aria-label="Conversion Rate"></a>
            <div class="lm-mgr-kpi-icon lm-mgr-kpi-orange"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg></div>
            <div><strong>{{ $convRate }}%</strong><span>Conversion Rate</span></div>
        </div>
        <div class="lm-card-link lm-mgr-detail-kpi"><a class="lm-card-main lm-card-cover" href="{{ route('admin.leadmax.finances', ['manager_id' => $agent->id, 'tab' => 'deposits', 'status' => 'approved']) }}" aria-label="Revenue Generated"></a>
            <div class="lm-mgr-kpi-icon lm-mgr-kpi-teal"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/></svg></div>
            <div><strong>${{ number_format($revenue,0) }}</strong><span>Revenue Generated</span></div>
        </div>
    </div>

    {{-- Two-column: Assigned Leads + Agent Info --}}
    <div class="lm-agent-detail-body">
        <div class="lm-agent-leads-section">
            <div class="lm-attr-table-header" style="margin-bottom:12px">
                <div><strong>Assigned Leads &amp; Recent Activity</strong></div>
                <a href="{{ route('admin.leadmax.leads') }}?manager_id={{ $agent->id }}" class="lm-attr-source-link" style="font-size:13px">View All Leads →</a>
            </div>
            <div class="lm-attr-table-wrap">
                <table class="lm-mgr-table">
                    <thead><tr><th>Lead Name</th><th>Source</th><th>Status</th><th>Assigned Date</th><th>Value</th></tr></thead>
                    <tbody>
                    @forelse($recentLeads as $i=>$lead)
                    @php
                        $src=$sources[$i%count($sources)];
                        $statuses=['Qualified','Contacted','New','Converted'];
                        $statusBadge=$statuses[$i%count($statuses)];
                        $badgeClass=match($statusBadge){'Converted'=>'lm-attr-status-converted','Qualified'=>'lm-attr-status-qualified','Contacted'=>'lm-attr-status-active',default=>'lm-attr-status-new'};
                        $val=number_format((float)$lead->ftd_amount,2);
                        $li=strtoupper(substr($lead->first_name,0,1).substr($lead->last_name,0,1));
                    @endphp
                    <tr>
                        <td>
                            <div class="lm-mgr-name-cell">
                                <span class="lm-mgr-avatar" style="background:#3b82f6;font-size:10px">{{ $li }}</span>
                                <a href="{{ route('admin.leadmax.leads.show',$lead->id) }}" class="lm-attr-source-link">{{ $lead->name }}</a>
                            </div>
                        </td>
                        <td>{{ $src }}</td>
                        <td><span class="lm-attr-status-badge {{ $badgeClass }}">{{ $statusBadge }}</span></td>
                        <td>{{ $lead->created_at?->format('M d, Y') }}</td>
                        <td>${{ $val }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="5" class="lm-attr-empty">No assigned leads.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="lm-agent-info-section">
            <div class="lm-agent-info-card">
                <strong>Agent Information</strong>
                <table class="lm-agent-info-table">
                    <tr><td>Role</td><td>Senior Sales Agent</td></tr>
                    <tr><td>Department</td><td>Sales</td></tr>
                    <tr><td>Direct Manager</td><td>Sarah Johnson</td></tr>
                    <tr><td>Working Hours</td><td>9:00 AM – 6:00 PM</td></tr>
                    <tr><td>Languages</td><td>English, Arabic, Hebrew</td></tr>
                </table>
            </div>
            <div class="lm-agent-qa-card">
                <strong>Quick Actions</strong>
                <button type="button" class="lm-agent-qa-btn lm-agent-qa-primary" onclick="document.getElementById('lm-assign-lead-modal').style.display='flex'"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87"/></svg> Assign New Leads</button>
                <button type="button" class="lm-agent-qa-btn"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/></svg> Send Direct Message</button>
                <button type="button" class="lm-agent-qa-btn"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg> View Performance Reports</button>
            </div>
        </div>
    </div>

    <div id="lm-assign-lead-modal" class="lm-modal-backdrop" style="display:none" onclick="if(event.target===this)this.style.display='none'">
        <div class="lm-modal-card lm-modal-card-lg">
            <div class="lm-modal-header">
                <strong>Assign New Lead</strong>
                <button type="button" class="lm-modal-close" onclick="document.getElementById('lm-assign-lead-modal').style.display='none'">&times;</button>
            </div>
            <div class="lm-modal-tabs">
                <button type="button" class="lm-modal-tab active">Single Assignment</button>
                <button type="button" class="lm-modal-tab">Bulk Import</button>
                <button type="button" class="lm-modal-tab">Assignment Rules</button>
            </div>
            <form method="POST" action="{{ route('admin.leadmax.agents.assign-lead', $agent->id) }}" class="lm-modal-form">
                @csrf
                <div class="lm-modal-row">
                    <div class="lm-modal-field"><label>Lead Name</label><input type="text" name="lead_name" placeholder="e.g. Phoenix Baker"></div>
                    <div class="lm-modal-field"><label>Lead Email</label><input type="email" name="lead_email" required placeholder="phoenix@unitledui.com"></div>
                </div>
                <div class="lm-modal-row">
                    <div class="lm-modal-field">
                        <label>Lead Source</label>
                        <select name="lead_source">
                            <option value="">Select source</option>
                            <option value="Website" selected>Website</option>
                            <option value="Facebook">Facebook</option>
                            <option value="Google">Google</option>
                            <option value="Email">Email</option>
                            <option value="Direct">Direct</option>
                        </select>
                    </div>
                    <div class="lm-modal-field">
                        <label>Assign To</label>
                        <select name="assign_to">
                            <option value="{{ $agent->id }}" selected>{{ $agent->name }}</option>
                        </select>
                    </div>
                </div>
                <div class="lm-modal-field">
                    <label>Priority Level</label>
                    <div class="lm-priority-tabs">
                        <label class="lm-priority-tab lm-priority-high"><input type="radio" name="priority" value="high" checked> ● High</label>
                        <label class="lm-priority-tab lm-priority-medium"><input type="radio" name="priority" value="medium"> ● Medium</label>
                        <label class="lm-priority-tab lm-priority-low"><input type="radio" name="priority" value="low"> ● Low</label>
                    </div>
                </div>
                <div class="lm-modal-field">
                    <label>Notes / Comments</label>
                    <textarea name="notes" rows="3" placeholder="Add any additional details or requirements for this assignment..."></textarea>
                </div>
                <div class="lm-modal-notify-row">
                    <label class="lm-modal-checkbox-label"><input type="checkbox" name="notify" value="1" checked> Notify assignee immediately</label>
                </div>
                <div class="lm-modal-actions">
                    <button type="button" class="lm-modal-btn-cancel" onclick="document.getElementById('lm-assign-lead-modal').style.display='none'">Cancel</button>
                    <button type="submit" class="lm-modal-btn-submit">Assign Lead</button>
                </div>
            </form>
        </div>
    </div>

    @if(is_admin())@include('admin.leadmax.partials.manager-edit-modal', ['noun' => 'Agent', 'teams' => ['Sales', 'Retention', 'Support', 'Management']])@endif
</div>
</x-leadmax-layout>

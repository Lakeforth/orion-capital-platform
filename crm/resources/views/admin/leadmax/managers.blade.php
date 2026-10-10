<x-leadmax-layout :central-url="$centralUrl" :active-lead-max-page="$activeLeadMaxPage">
@php
$roleColors=['manager'=>'lm-mgr-role-blue','admin'=>'lm-mgr-role-purple'];
$roleLabel=fn($m)=>$m->team ? ucwords($m->team).' Manager' : ($m->roles->first()?->name ? ucwords(str_replace('_',' ',$m->roles->first()->name)) : 'Manager');
$roleClass=fn($m)=>match(strtolower($m->roles->first()?->name??'manager')){
    'admin'=>'lm-mgr-role-orange',
    default=>'lm-mgr-role-blue',
};
$statusClass=fn($s)=>match(strtolower($s??'active')){ 'active'=>'lm-mgr-status-active', 'suspended','inactive'=>'lm-mgr-status-inactive', default=>'lm-mgr-status-pending'};
$mkChange=fn($v)=>$v===null?'':($v>=0?'<span class="lm-mgr-up">↑ '.abs($v).'%</span> <small>vs last month</small>':'<span class="lm-mgr-down">↓ '.abs($v).'%</span> <small>vs last month</small>');
$adminId=fn($m,$i)=>'ADM'.str_pad($i+1,3,'0',STR_PAD_LEFT);
$roleVariants=['lm-mgr-role-purple','lm-mgr-role-teal','lm-mgr-role-green','lm-mgr-role-orange','lm-mgr-role-blue','lm-mgr-role-red','lm-mgr-role-blue','lm-mgr-role-purple'];
$roleNames=['Super Managers','CRM Managers','Finance Managers','Support Managers','Reports Managers','Campaign Managers','User Manager','Content Managers'];
@endphp
<div class="leadmax-module-page lm-mgr-page">

    <header class="lm-mgr-header">
        <div>
            <h1>Managers <span class="lm-attr-info-icon" title="Admin management">ⓘ</span></h1>
            <p class="lm-mgr-subtitle">Manage platform administrators, roles and access permissions.</p>
        </div>
        <div class="lm-mgr-header-actions">
            <nav class="leadmax-breadcrumb"><a href="{{ route('admin.leadmax.dashboard') }}">Dashboard</a> <span>/</span> <strong>Managers</strong></nav>
            <button type="button" class="lm-attr-btn lm-attr-btn-primary" onclick="document.getElementById('lm-add-manager-modal').style.display='flex'"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg> Add Manager</button>
            <button type="button" class="lm-attr-btn"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg> Export</button>
            <button type="button" class="lm-attr-btn lm-mgr-dots-btn"><svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="5" r="1.5"/><circle cx="12" cy="12" r="1.5"/><circle cx="12" cy="19" r="1.5"/></svg></button>
        </div>
    </header>

    {{-- KPIs --}}
    <div class="lm-mgr-kpis">
        <div class="lm-card-link lm-mgr-kpi"><a class="lm-card-main lm-card-cover" href="{{ route('admin.leadmax.managers') }}" aria-label="Total Managers"></a>
            <div class="lm-mgr-kpi-icon lm-mgr-kpi-blue"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75"/></svg></div>
            <div><span>Total Managers</span><strong>{{ number_format($totalCount) }}</strong>{!! $mkChange($totalChange ?? null) !!}</div>
        </div>
        <div class="lm-card-link lm-mgr-kpi"><a class="lm-card-main lm-card-cover" href="{{ route('admin.leadmax.managers', ['status' => 'active']) }}" aria-label="Active Managers"></a>
            <div class="lm-mgr-kpi-icon lm-mgr-kpi-green"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 11-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg></div>
            <div><span>Active Managers</span><strong>{{ number_format($activeCount) }}</strong>{!! $mkChange($activeChange) !!}</div>
        </div>
        <div class="lm-card-link lm-mgr-kpi"><a class="lm-card-main lm-card-cover" href="{{ route('admin.leadmax.managers', ['status' => 'inactive']) }}" aria-label="Inactive Managers"></a>
            <div class="lm-mgr-kpi-icon lm-mgr-kpi-red"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/></svg></div>
            <div><span>Inactive Managers</span><strong>{{ number_format($inactiveCount) }}</strong>{!! $mkChange($inactiveChange) !!}</div>
        </div>
        <div class="lm-mgr-kpi">
            <div class="lm-mgr-kpi-icon lm-mgr-kpi-yellow"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 8v4l3 3"/></svg></div>
            <div><span>Managers Roles</span><strong>{{ $rolesCount }}</strong><small>Total system roles</small></div>
        </div>
    </div>

    {{-- Filters --}}
    <form class="lm-mgr-filters" method="GET">
        <div class="lm-mgr-search-wrap"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg><input type="text" name="search" value="{{ $search }}" placeholder="Search by name, email or role..." class="lm-mgr-search-input"></div>
        <select name="role" class="lm-mgr-select"><option value="all">All Roles</option><option value="manager" @selected($roleFilter==='manager')>Manager</option></select>
        <select name="status" class="lm-mgr-select"><option value="all">All Status</option><option value="active" @selected($statusFilter==='active')>Active</option><option value="inactive" @selected($statusFilter==='inactive')>Inactive</option></select>
        <select class="lm-mgr-select"><option>All Teams</option></select>
        <div class="lm-mgr-date-range"><input type="text" placeholder="From date" data-leadmax-date><input type="text" placeholder="To date" data-leadmax-date></div>
        <button type="submit" class="lm-attr-btn"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="4" y1="6" x2="20" y2="6"/><line x1="8" y1="12" x2="16" y2="12"/><line x1="11" y1="18" x2="13" y2="18"/></svg> Filters</button>
        <button type="button" class="lm-attr-btn lm-mgr-dots-btn"><svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><circle cx="5" cy="12" r="1.5"/><circle cx="12" cy="12" r="1.5"/><circle cx="19" cy="12" r="1.5"/></svg></button>
    </form>

    {{-- Table --}}
    <div class="lm-mgr-table-card" data-lm-bulk-page data-lm-bulk-noun="manager">
        <div class="lm-bulk-actions" data-lm-bulk-actions hidden><span data-lm-selected-count>0 managers selected</span><div><button type="button" class="lm-button danger" data-lm-bulk-delete disabled><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v6M14 11v6"/><path d="M9 6V4h6v2"/></svg> Delete</button><button type="button" class="lm-button" data-lm-bulk-export><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg> Export</button><button type="button" class="lm-bulk-deselect" data-lm-bulk-deselect aria-label="Deselect all managers"><svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button></div></div>
        <div class="lm-attr-table-wrap">
            <table class="lm-mgr-table">
                <thead><tr>
                    <th><input type="checkbox" class="lm-ftd-checkbox" data-lm-select-all aria-label="Select all managers"></th>
                    <th>Admin ID</th><th>Name</th><th>Email</th><th>Role</th><th>Team</th><th>Status</th><th>Last Login</th><th>Action</th>
                </tr></thead>
                <tbody>
                @forelse($managers as $i=>$manager)
                @php
                    $idx = ($managers->firstItem() - 1) + $i;
                    $aId = 'ADM'.str_pad($idx+1,3,'0',STR_PAD_LEFT);
                    $variantClass = $roleVariants[$idx % count($roleVariants)];
                    $roleName = $roleNames[$idx % count($roleNames)];
                    $initials = strtoupper(substr($manager->first_name,0,1).substr($manager->last_name,0,1));
                    $colors = ['#f97316','#3b82f6','#10b981','#8b5cf6','#ec4899','#06b6d4','#84cc16','#f59e0b'];
                    $color = $colors[$idx % count($colors)];
                    $team = $manager->team ?: ['Management','CRM','Finance','Support','Reports','Marketing'][$idx%6];
                @endphp
                <tr>
                    <td><input type="checkbox" class="lm-ftd-checkbox" data-lm-select value="{{ $manager->id }}" aria-label="Select manager {{ $aId }}"></td>
                    <td><a href="{{ route('admin.leadmax.managers.show',$manager->id) }}" class="lm-mgr-id-link">{{ $aId }}</a></td>
                    <td>
                        <div class="lm-mgr-name-cell">
                            <span class="lm-mgr-avatar" style="background:{{ $color }}">{{ $initials }}</span>
                            <span>{{ $manager->name }}</span>
                        </div>
                    </td>
                    <td>{{ $manager->email }}</td>
                    <td><span class="lm-mgr-role-badge {{ $variantClass }}">{{ $roleName }}</span></td>
                    <td>{{ $team }}</td>
                    <td><span class="lm-mgr-status {{ $statusClass($manager->status) }}">{{ ucfirst($manager->status ?: 'Active') }}</span></td>
                    <td>@if($manager->last_seen){{ $manager->last_seen->format('Y-m-d') }}<br><small style="color:#64748b">{{ $manager->last_seen->format('H:i:s') }}</small>@else—@endif</td>
                    <td class="lm-attr-actions-cell">
                        <a href="{{ route('admin.leadmax.managers.show',$manager->id) }}" title="View"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg></a>
                        <button type="button" title="Edit {{ $manager->name }}" data-lm-edit-manager="{{ route('admin.leadmax.managers.update', $manager->id) }}" data-first-name="{{ $manager->first_name }}" data-last-name="{{ $manager->last_name }}" data-email="{{ $manager->email }}" data-phone="{{ $manager->phone }}" data-team="{{ $manager->team }}" data-status="{{ $manager->status }}"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg></button>
                        @if(is_admin())
                        <form method="POST" action="{{ route('admin.leadmax.managers.destroy', $manager->id) }}" class="lm-inline-form">
                            @csrf
                            @method('DELETE')
                            <button type="submit" title="Delete {{ $manager->name }}" data-lm-confirm-submit="Delete {{ $manager->name }}? Anyone on their team is released, not deleted."><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v6M14 11v6"/><path d="M9 6V4h6v2"/></svg></button>
                        </form>
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="9" class="lm-attr-empty">No managers found.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="lm-attr-table-footer">
            Showing {{ $managers->firstItem() ?? 0 }} to {{ $managers->lastItem() ?? 0 }} of {{ $managers->total() }} admins
            <div style="display:inline-flex;gap:4px;float:right;align-items:center">
                <select class="lm-mgr-per-page-select"><option>10</option><option>25</option></select>
                @if(!$managers->onFirstPage())<a href="{{ $managers->previousPageUrl() }}" class="lm-ftd-page-btn">‹</a>@else<span class="lm-ftd-page-btn disabled">‹</span>@endif
                @foreach($managers->getUrlRange(max(1,$managers->currentPage()-2),min($managers->lastPage(),$managers->currentPage()+2)) as $p=>$u)<a href="{{ $u }}" class="lm-ftd-page-btn{{ $p===$managers->currentPage()?' active':'' }}">{{ $p }}</a>@endforeach
                @if($managers->hasMorePages())<a href="{{ $managers->nextPageUrl() }}" class="lm-ftd-page-btn">›</a>@else<span class="lm-ftd-page-btn disabled">›</span>@endif
            </div>
        </div>
    </div>

    {{-- Bottom: Role Summary + Quick Actions --}}
    <div class="lm-mgr-bottom">
        <div class="lm-mgr-bottom-card">
            <strong>Role Summary</strong>
            <p class="lm-mgr-role-sum-sub">Distribution of admin roles</p>
            <div class="lm-mgr-role-list lm-mgr-role-grid">
                @foreach([['Super Managers','lm-mgr-dot-purple'],['Reports Managers','lm-mgr-dot-orange'],['CRM Managers','lm-mgr-dot-teal'],['Campaign Managers','lm-mgr-dot-red'],['Finance Managers','lm-mgr-dot-green'],['Content Managers','lm-mgr-dot-purple']] as [$rName,$rClass])
                <div class="lm-mgr-role-row"><span class="lm-mgr-dot {{ $rClass }}"></span><span>{{ $rName }}</span><strong>1</strong></div>
                @endforeach
            </div>
        </div>
        <div class="lm-mgr-bottom-card">
            <strong>Quick Actions</strong>
            <div class="lm-mgr-quick-actions">
                <div class="lm-mgr-qa-item">
                    <div class="lm-mgr-qa-icon lm-mgr-kpi-blue"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 010 2.83 2 2 0 01-2.83 0l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 01-2 2 2 2 0 01-2-2v-.09A1.65 1.65 0 009 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 01-2.83-2.83l.06-.06A1.65 1.65 0 004.68 15a1.65 1.65 0 00-1.51-1H3a2 2 0 01-2-2 2 2 0 012-2h.09A1.65 1.65 0 004.6 9a1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 012.83-2.83l.06.06A1.65 1.65 0 009 4.68a1.65 1.65 0 001-1.51V3a2 2 0 012-2 2 2 0 012 2v.09a1.65 1.65 0 001 1.51 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 012.83 2.83l-.06.06A1.65 1.65 0 0019.4 9a1.65 1.65 0 001.51 1H21a2 2 0 012 2 2 2 0 01-2 2h-.09a1.65 1.65 0 00-1.51 1z"/></svg></div>
                    <div><strong>Manage Roles</strong><p>Create and manage admin roles</p></div>
                </div>
                <div class="lm-mgr-qa-item">
                    <div class="lm-mgr-qa-icon lm-mgr-kpi-green"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg></div>
                    <div><strong>Activity Log</strong><p>View admin activity history</p></div>
                </div>
                <div class="lm-mgr-qa-item">
                    <div class="lm-mgr-qa-icon lm-mgr-kpi-blue"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0110 0v4"/></svg></div>
                    <div><strong>Permission Settings</strong><p>Configure access permissions</p></div>
                </div>
                <div class="lm-mgr-qa-item">
                    <div class="lm-mgr-qa-icon lm-mgr-kpi-teal"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg></div>
                    <div><strong>Invite Managers</strong><p>Send invitation to new admin</p></div>
                </div>
            </div>
        </div>
    </div>

    @include('admin.leadmax.partials.manager-edit-modal')
    <div id="lm-add-manager-modal" class="lm-modal-backdrop" style="display:none" onclick="if(event.target===this)this.style.display='none'">
        <div class="lm-modal-card lm-modal-card-lg">
            <div class="lm-modal-header">
                <div>
                    <strong>Add Manager</strong>
                    <span class="lm-modal-subtitle">Create a new administrator account and assign platform access permissions.</span>
                </div>
                <button type="button" class="lm-modal-close" onclick="document.getElementById('lm-add-manager-modal').style.display='none'">&times;</button>
            </div>
            <form method="POST" action="{{ route('admin.leadmax.managers.store') }}" class="lm-modal-form">
                @csrf
                <div class="lm-modal-section">
                    <div class="lm-modal-section-title">Personal Information</div>
                    <p class="lm-modal-section-desc">Basic identity and contact details of the administrator.</p>
                    <div class="lm-modal-row">
                        <div class="lm-modal-field"><label>First Name</label><input type="text" name="first_name" required maxlength="100" placeholder="e.g. John"></div>
                        <div class="lm-modal-field"><label>Last Name</label><input type="text" name="last_name" required maxlength="100" placeholder="e.g. Doe"></div>
                    </div>
                    <div class="lm-modal-row">
                        <div class="lm-modal-field"><label>Email Address</label><input type="email" name="email" required maxlength="200" placeholder="john.doe@citationswest.com"></div>
                        <div class="lm-modal-field"><label>Phone Number</label><input type="text" name="phone" maxlength="50" placeholder="+234 999 999 99"></div>
                    </div>
                </div>
                <div class="lm-modal-section">
                    <div class="lm-modal-section-title">Role & Access</div>
                    <p class="lm-modal-section-desc">Determine organizational assignment and platform permissions.</p>
                    {{-- System Role and Permissions Level used to sit here. Neither was ever
                         stored -- permissions_level was not even validated -- so the form
                         promised access control that did not exist. --}}
                    <div class="lm-modal-row">
                        <div class="lm-modal-field">
                            <label>Department / Team</label>
                            <select name="team">
                                <option value="Management">Management</option>
                                <option value="CRM">CRM</option>
                                <option value="Finance">Finance</option>
                                <option value="Support">Support</option>
                                <option value="Reports">Reports</option>
                                <option value="Marketing">Marketing</option>
                            </select>
                        </div>
                    </div>
                    <div class="lm-modal-row">
                        <div class="lm-modal-field">
                            <label>Password</label>
                            <input type="password" name="password" required minlength="6" autocomplete="new-password" placeholder="At least 6 characters">
                        </div>
                        <div class="lm-modal-field">
                            <label>Confirm Password</label>
                            <input type="password" name="password_confirmation" required minlength="6" autocomplete="new-password" placeholder="Repeat the password">
                        </div>
                    </div>
                </div>
                {{-- Username and the photo box are gone: a manager signs in with an email
                     address and a password, the username only ever became a generated
                     account_id, and the "upload" box was a div with no file input behind it. --}}
                <div class="lm-modal-section">
                    <div class="lm-modal-toggle-row">
                        <div class="lm-modal-toggle-info">
                            <strong>Send welcome email</strong>
                            <p>Email them their sign-in link. The password you set here is not included.</p>
                        </div>
                        <label class="lm-toggle"><input type="checkbox" name="send_welcome_email" value="1" checked><span class="lm-toggle-track"></span></label>
                    </div>
                    <div class="lm-modal-toggle-row">
                        <div class="lm-modal-toggle-info">
                            <strong>Set as active immediately</strong>
                            <p>Enable manager access immediately upon creation</p>
                        </div>
                        <label class="lm-toggle"><input type="checkbox" name="set_active" value="1" checked><span class="lm-toggle-track"></span></label>
                    </div>
                </div>
                <div class="lm-modal-actions lm-modal-actions-footer">
                    <small class="lm-modal-security-note">🔒 All data transmitted securely.</small>
                    <div style="display:flex;gap:8px">
                        <button type="button" class="lm-modal-btn-cancel" onclick="document.getElementById('lm-add-manager-modal').style.display='none'">Cancel</button>
                        <button type="submit" class="lm-modal-btn-submit">Save Manager</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

</div>
</x-leadmax-layout>

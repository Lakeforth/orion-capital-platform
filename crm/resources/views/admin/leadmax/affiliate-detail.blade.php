<x-leadmax-layout :central-url="$centralUrl" :active-lead-max-page="$activeLeadMaxPage">
@php
$settings = $affiliate->settings ?? [];
$commissionRate = $settings['commission_rate'] ?? 10.0;
$commissionModel = $settings['commission_model'] ?? 'Revenue Share — '.$commissionRate.'%';
$webhookUrl = $settings['webhook_url'] ?? '';
$phone = ($affiliate->phone && $affiliate->phone !== '0000000000') ? $affiliate->phone : '—';
$code = $affiliate->account_id ?: strtoupper(substr((string) $affiliate->id, 0, 6));
$initials = strtoupper(substr($affiliate->first_name ?? '', 0, 1).substr($affiliate->last_name ?? '', 0, 1));
$statusClass = strtolower($affiliate->status ?? 'active');
$isCpa = str_contains(strtolower($commissionModel), 'cpa');
$totalCommission = $isCpa ? '$'.number_format($ftdConversions * $commissionRate) : '—';
$countryFlags = ['United States'=>'🇺🇸','Spain'=>'🇪🇸','South Korea'=>'🇰🇷','Senegal'=>'🇸🇳','Italy'=>'🇮🇹','China'=>'🇨🇳','United Arab Emirates'=>'🇦🇪','Ghana'=>'🇬🇭','France'=>'🇫🇷','Egypt'=>'🇪🇬','Germany'=>'🇩🇪','Ireland'=>'🇮🇪','Netherlands'=>'🇳🇱','Israel'=>'🇮🇱','United Kingdom'=>'🇬🇧','Colombia'=>'🇨🇴','South Africa'=>'🇿🇦','Canada'=>'🇨🇦','Australia'=>'🇦🇺'];
$flag = $countryFlags[$affiliate->country ?? ''] ?? '';
$leadStatusFilter = request('lead_status', 'all');
@endphp
<div class="lm-afd-page">

    {{-- Breadcrumb header --}}
    <header class="lm-afd-header">
        <div class="leadmax-breadcrumb">
            <a href="{{ route('admin.leadmax.dashboard') }}">Dashboard</a>
            <span>/</span> CRM
            <span>/</span>
            <a href="{{ route('admin.leadmax.affiliates') }}">Affiliates</a>
            <span>/</span>
            <strong>{{ $affiliate->name }}</strong>
        </div>
        <div class="lm-afd-header-actions">
            @if(Route::has('admin.users.edit'))
                <a href="{{ route('admin.users.edit', $affiliate->id) }}" class="lm-afd-edit-btn">Edit</a>
            @else
                <a href="#" class="lm-afd-edit-btn">Edit</a>
            @endif
        </div>
    </header>

    {{-- Profile Card --}}
    <section class="lm-afd-profile-card">
        <div class="lm-afd-avatar-block">
            <div class="lm-afd-avatar">{{ $initials }}</div>
            <div class="lm-afd-info">
                <span class="lm-afd-name">{{ $affiliate->name }}</span>
                <span class="lm-afd-email">{{ $affiliate->email }}</span>
                <span class="lm-afd-code-badge">{{ $code }}</span>
            </div>
        </div>

        <div class="lm-afd-divider"></div>

        <div class="lm-afd-grid-right">
            <div class="lm-afd-grid-row">
                <div class="lm-afd-profile-item">
                    <span class="lm-afd-label">STATUS</span>
                    <span class="lm-afd-status-badge {{ $statusClass }}">{{ ucfirst($affiliate->status ?? 'active') }}</span>
                </div>
                <div class="lm-afd-profile-item">
                    <span class="lm-afd-label">JOINED DATE</span>
                    <span class="lm-afd-value">{{ $affiliate->created_at?->format('M d, Y') ?? '—' }}</span>
                </div>
                <div class="lm-afd-profile-item">
                    <span class="lm-afd-label">COUNTRY</span>
                    <span class="lm-afd-value">{{ $flag }} {{ $affiliate->country ?: '—' }}</span>
                </div>
            </div>
            <div class="lm-afd-grid-row">
                <div class="lm-afd-profile-item">
                    <span class="lm-afd-label">PHONE</span>
                    <div class="lm-afd-phone-wrap">
                        <span class="lm-afd-value">{{ $phone }}</span>
                        @if($phone !== '—')
                        <button type="button" class="lm-afd-call-btn" title="Call">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07A19.5 19.5 0 013.07 9.81a19.79 19.79 0 01-3.07-8.73 2 2 0 012-2.18h3a2 2 0 012 1.72c.127.96.361 1.903.7 2.81a2 2 0 01-.45 2.11L6.91 6.91a16 16 0 006.72 6.72l1.21-1.21a2 2 0 012.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0122 16.92z"/></svg>
                        </button>
                        @endif
                    </div>
                </div>
                <div class="lm-afd-profile-item">
                    <span class="lm-afd-label">COMMISSION MODEL</span>
                    <span class="lm-afd-value">{{ $commissionModel }}</span>
                </div>
                <div class="lm-afd-profile-item">
                    <span class="lm-afd-label">PARENT AFFILIATE</span>
                    <span class="lm-afd-value">—</span>
                </div>
            </div>
        </div>
    </section>

    {{-- Webhook Card --}}
    <div class="lm-afd-webhook-card">
        <span class="lm-afd-label">WEBHOOK/API URL</span>
        <div class="lm-afd-webhook-input-wrap">
            <input type="text" id="lm-afd-webhook-url" class="lm-afd-webhook-input"
                   value="{{ $webhookUrl ?: 'https://api.example.com/v1/affiliates/'.($affiliate->account_id ?? $affiliate->id) }}" readonly>
            <button type="button" class="lm-afd-copy-btn"
                    onclick="navigator.clipboard.writeText(document.getElementById('lm-afd-webhook-url').value).then(()=>{this.innerHTML='<svg width=&quot;12&quot; height=&quot;12&quot; viewBox=&quot;0 0 24 24&quot; fill=&quot;none&quot; stroke=&quot;currentColor&quot; stroke-width=&quot;3&quot;><polyline points=&quot;20 6 9 17 4 12&quot;/></svg>';setTimeout(()=>{this.innerHTML='<svg width=&quot;12&quot; height=&quot;12&quot; viewBox=&quot;0 0 24 24&quot; fill=&quot;none&quot; stroke=&quot;currentColor&quot; stroke-width=&quot;2&quot;><rect x=&quot;9&quot; y=&quot;9&quot; width=&quot;13&quot; height=&quot;13&quot; rx=&quot;2&quot;/><path d=&quot;M5 15H4a2 2 0 01-2-2V4a2 2 0 012-2h9a2 2 0 012 2v1&quot;/></svg>'},1500)})"
                    title="Copy URL">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 01-2-2V4a2 2 0 012-2h9a2 2 0 012 2v1"/></svg>
            </button>
        </div>
    </div>

    {{-- KPI Cards --}}
    <section class="lm-afd-kpis">
        {{-- Total Referrals --}}
        <article class="lm-card-link lm-afd-kpi-card"><a class="lm-card-main lm-card-cover" href="{{ route('admin.leadmax.leads', ['period' => 'all_time', 'created_by' => $affiliate->id]) }}" aria-label="Total Referrals"></a>
            <div class="lm-afd-kpi-left">
                <span class="lm-afd-kpi-label">Total Referrals</span>
                <strong class="lm-afd-kpi-value">{{ number_format($totalReferrals) }}</strong>
                <div class="lm-afd-delta">
                    <span class="lm-afd-delta-badge">▲ 12%</span>
                    <span class="lm-afd-delta-label">vs last month</span>
                </div>
            </div>
            <div class="lm-afd-kpi-icon">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#60a5fa" stroke-width="1.5"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87"/><path d="M16 3.13a4 4 0 010 7.75"/></svg>
            </div>
        </article>
        {{-- FTD Conversions --}}
        <article class="lm-card-link lm-afd-kpi-card"><a class="lm-card-main lm-card-cover" href="{{ route('admin.leadmax.leads', [...['period' => 'all_time', 'created_by' => $affiliate->id], 'ftd' => 'yes']) }}" aria-label="FTD Conversions"></a>
            <div class="lm-afd-kpi-left">
                <span class="lm-afd-kpi-label">FTD Conversions</span>
                <strong class="lm-afd-kpi-value">{{ number_format($ftdConversions) }}</strong>
                <div class="lm-afd-delta">
                    <span class="lm-afd-delta-badge">▲ 8%</span>
                    <span class="lm-afd-delta-label">vs last month</span>
                </div>
            </div>
            <div class="lm-afd-kpi-icon">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#60a5fa" stroke-width="1.5"><circle cx="12" cy="8" r="6"/><path d="M15.477 12.89L17 22l-5-3-5 3 1.523-9.11"/></svg>
            </div>
        </article>
        {{-- Total Commission --}}
        <article class="lm-card-link lm-afd-kpi-card"><a class="lm-card-main lm-card-cover" href="{{ route('admin.leadmax.first-time-deposits', ['period' => 'all_time', 'created_by' => $affiliate->id]) }}" aria-label="Total Commission"></a>
            <div class="lm-afd-kpi-left">
                <span class="lm-afd-kpi-label">Total Commission</span>
                <strong class="lm-afd-kpi-value">{{ $totalCommission }}</strong>
            </div>
            <div class="lm-afd-kpi-icon">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#60a5fa" stroke-width="1.5"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/></svg>
            </div>
        </article>
        {{-- Conversion Rate --}}
        <article class="lm-card-link lm-afd-kpi-card"><a class="lm-card-main lm-card-cover" href="{{ route('admin.leadmax.leads', [...['period' => 'all_time', 'created_by' => $affiliate->id], 'ftd' => 'yes']) }}" aria-label="Conversion Rate"></a>
            <div class="lm-afd-kpi-left">
                <span class="lm-afd-kpi-label">Conversion Rate</span>
                <strong class="lm-afd-kpi-value">{{ $convRate }}%</strong>
                <div class="lm-afd-delta">
                    <span class="lm-afd-delta-badge">▲ 3%</span>
                    <span class="lm-afd-delta-label">vs last month</span>
                </div>
            </div>
            <div class="lm-afd-kpi-icon">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#60a5fa" stroke-width="1.5"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/></svg>
            </div>
        </article>
    </section>

    {{-- Tab Navigation + Table Card --}}
    <div class="lm-afd-table-card">
        <nav class="lm-afd-tabs-nav">
            <div style="display:flex;align-items:center;">
                <a href="{{ route('admin.leadmax.affiliates.show', $affiliate->id) }}?tab=leads"
                   class="lm-afd-tab-btn {{ $tab === 'leads' ? 'active' : '' }}">
                    Leads({{ number_format($totalReferrals) }})
                </a>
                <a href="{{ route('admin.leadmax.affiliates.show', $affiliate->id) }}?tab=api-keys"
                   class="lm-afd-tab-btn {{ $tab === 'api-keys' ? 'active' : '' }}">
                    API Keys
                </a>
            </div>
            <button type="button" class="lm-afd-collapse-btn" title="Collapse">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="2"><polyline points="18 15 12 9 6 15"/></svg>
            </button>
        </nav>

        {{-- Leads Tab --}}
        @if($tab === 'leads')
        <div class="lm-afd-tab-content">
            <div class="lm-afd-toolbar">
                <div class="lm-afd-status-tabs">
                    @php
                    $lsCounts = [
                        'all' => $totalReferrals,
                        'new' => null,
                        'qualified' => null,
                        'contacted' => null,
                        'converted' => null,
                    ];
                    @endphp
                    @foreach($lsCounts as $val => $cnt)
                    <a href="{{ route('admin.leadmax.affiliates.show', $affiliate->id) }}?tab=leads&lead_status={{ $val }}"
                       class="lm-afd-status-tab {{ $leadStatusFilter === $val ? 'active' : '' }}">
                        {{ $val === 'all' ? 'All Leads' : ucfirst($val) }}
                        @if($val === 'all')<span class="lm-afd-count-badge">{{ number_format($totalReferrals) }}</span>@endif
                    </a>
                    @endforeach
                </div>
                <div style="display:flex;align-items:center;gap:8px;">
                    <form method="GET" action="{{ route('admin.leadmax.affiliates.show', $affiliate->id) }}" style="display:flex;align-items:center;gap:0;">
                        <input type="hidden" name="tab" value="leads">
                        <input type="hidden" name="lead_status" value="{{ $leadStatusFilter }}">
                        @if(request()->filled('country'))<input type="hidden" name="country" value="{{ request('country') }}">@endif @if(request()->filled('per_page'))<input type="hidden" name="per_page" value="{{ request('per_page') }}">@endif
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="Search leads..." class="lm-afd-search-input-field">
                    </form>
                    <button type="button" class="lm-afd-filters-btn" data-lm-filter-toggle aria-controls="lm-affiliate-lead-filters">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/></svg>
                        Filters
                    </button>
                </div>
            </div>
            @include('admin.leadmax.partials.filter-panel', ['panelId' => 'lm-affiliate-lead-filters', 'keep' => ['tab', 'lead_status', 'search', 'per_page'], 'selects' => ['country' => ['Country', $leadCountries->mapWithKeys(fn ($c) => [$c => $c])->all()]]])

            <div style="overflow:auto;">
                <table class="lm-afd-data-table">
                    <thead>
                        <tr>
                            <th style="width:36px;"><input type="checkbox" class="lm-afd-checkbox"></th>
                            <th>#</th>
                            <th>Lead Name</th>
                            <th>Email</th>
                            <th>Source</th>
                            <th>Lead Score</th>
                            <th>Status</th>
                            <th>FTD</th>
                            <th>Created Date</th>
                            <th>Assigned To</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($leads as $lead)
                    @php
                        $leadMeta = $lead->other_fields ?? $lead->settings ?? [];
                        $leadScore = $leadMeta['lead_score'] ?? (abs(crc32($lead->id) % 35) + 55);
                        $displayDate = $leadMeta['display_date'] ?? ($lead->created_at?->format('Y-m-d') ?? '—');
                        $leadStatus = strtolower($lead->status ?? 'new');
                        $leadStatusClass = match($leadStatus) {
                            'active', 'converted' => 'converted',
                            'qualified' => 'qualified',
                            'contacted' => 'contacted',
                            default => 'new',
                        };
                        $hasFtd = ($lead->ftd_amount ?? 0) > 0;
                        $rowId = 5000 + $loop->iteration + (($leads->currentPage()-1)*$leads->perPage());
                    @endphp
                    <tr>
                        <td><input type="checkbox" class="lm-afd-checkbox"></td>
                        <td class="lm-afd-cell-id">{{ $rowId }}</td>
                        <td><span class="lm-afd-cell-name">{{ $lead->name }}</span></td>
                        <td class="lm-afd-cell-muted">{{ $lead->email }}</td>
                        <td class="lm-afd-cell-muted">{{ $lead->source ?: '—' }}</td>
                        <td class="lm-afd-cell-score">{{ $leadScore }}</td>
                        <td>
                            <span class="lm-afd-lead-status {{ $leadStatusClass }}">{{ ucfirst($leadStatus ?: 'new') }}</span>
                        </td>
                        <td>
                            @if($hasFtd)
                                <span class="lm-afd-ftd-true">True</span>
                            @else
                                <span class="lm-afd-ftd-false">False</span>
                            @endif
                        </td>
                        <td class="lm-afd-cell-muted">{{ $displayDate }}</td>
                        <td style="color:#fff;">{{ $lead->manager?->name ?? '—' }}</td>
                        <td>
                            <div class="lm-afd-action-cell">
                                <a href="{{ route('admin.leadmax.leads.show', $lead->id) }}" class="lm-afd-action-eye" title="View">
                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                </a>
                                <button type="button" class="lm-afd-action-eye" title="More actions">
                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="5" r="1.5"/><circle cx="12" cy="12" r="1.5"/><circle cx="12" cy="19" r="1.5"/></svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="11" class="leadmax-no-data" style="height:80px;text-align:center;color:#64748b;">No leads found for this affiliate.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            <div class="lm-afd-table-footer">
                <span class="lm-afd-showing">
                    Showing {{ $leads->firstItem() ?? 0 }} to {{ $leads->lastItem() ?? 0 }} of {{ number_format($leads->total()) }} entries
                </span>
                <div class="lm-afd-page-controls">
                    <select class="lm-afd-per-page" onchange="const u=new URL(location.href);u.searchParams.set('tab','leads');u.searchParams.set('per_page',this.value);u.searchParams.delete('page');location.assign(u)">
                        @foreach([10, 20, 25, 50] as $size)<option value="{{ $size }}" @selected($leads->perPage() == $size)>{{ $size }}</option>@endforeach
                    </select>
                    <div class="lm-afd-pages">
                        @if($leads->onFirstPage())
                            <button class="lm-afd-page-btn" disabled>&lsaquo;</button>
                        @else
                            <a href="{{ $leads->previousPageUrl() }}" class="lm-afd-page-btn">&lsaquo;</a>
                        @endif

                        @php
                        $current = $leads->currentPage();
                        $last = $leads->lastPage();
                        $window = [];
                        for ($p = max(1, $current-1); $p <= min($last, $current+1); $p++) {
                            $window[] = $p;
                        }
                        @endphp

                        @if(!in_array(1, $window))
                            <a href="{{ $leads->url(1) }}" class="lm-afd-page-btn">1</a>
                            @if(!in_array(2, $window))<span class="lm-afd-page-btn" style="cursor:default;">...</span>@endif
                        @endif

                        @foreach($window as $p)
                            @if($p === $current)
                                <button class="lm-afd-page-btn active">{{ $p }}</button>
                            @else
                                <a href="{{ $leads->url($p) }}" class="lm-afd-page-btn">{{ $p }}</a>
                            @endif
                        @endforeach

                        @if(!in_array($last, $window) && $last > 1)
                            @if(!in_array($last-1, $window))<span class="lm-afd-page-btn" style="cursor:default;">...</span>@endif
                            <a href="{{ $leads->url($last) }}" class="lm-afd-page-btn">{{ $last }}</a>
                        @endif

                        @if($leads->hasMorePages())
                            <a href="{{ $leads->nextPageUrl() }}" class="lm-afd-page-btn">&rsaquo;</a>
                        @else
                            <button class="lm-afd-page-btn" disabled>&rsaquo;</button>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        @endif

        {{-- API Keys Tab --}}
        @if($tab === 'api-keys')
        @php $apiKeyCount = count($apiKeys); @endphp
        <div class="lm-afd-tab-content">
            <div class="lm-afd-apikeys-header">
                <div>
                    <strong class="lm-afd-apikeys-title">API Keys</strong>
                    <span class="lm-afd-apikeys-subtitle">Manage your credentials for programmatically feeding leads and conversions</span>
                </div>
                <button type="button" class="lm-afd-genkey-btn" onclick="document.getElementById('lm-gen-api-key-modal').style.display='flex'">+ Generate New Key</button>
            </div>
            <div style="overflow:auto;">
                <table class="lm-afd-data-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>KEY NAME</th>
                            <th>API KEY</th>
                            <th>CREATED DATE</th>
                            <th>LAST USED</th>
                            <th>STATUS</th>
                            <th>ACTIONS</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($apiKeys as $i => $key)
                    @php $isActive = ($key['status'] ?? 'active') === 'active'; @endphp
                    <tr>
                        <td class="lm-afd-cell-muted">{{ $i + 1 }}</td>
                        <td><span class="lm-afd-cell-name">{{ $key['name'] }}</span></td>
                        <td><code class="lm-afd-api-key-mask" data-full-key="{{ $key['key'] }}">{{ $key['masked_key'] }}</code></td>
                        <td class="lm-afd-cell-muted">{{ $key['created_at'] ?? '—' }}</td>
                        <td class="lm-afd-cell-muted">{{ $key['last_used_at'] ?? 'Never' }}</td>
                        <td>
                            @if($isActive)
                                <span class="lm-afd-lead-status converted">Active</span>
                            @else
                                <span class="lm-afd-lead-status" style="background:#501919;color:#f87171;">Revoked</span>
                            @endif
                        </td>
                        <td>
                            <div class="lm-afd-key-actions">
                                {{-- Copy --}}
                                <button type="button" class="lm-afd-icon-btn"
                                    onclick="lmCopyApiKey('{{ $key['key'] }}', this)"
                                    title="Copy API key">
                                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 01-2-2V4a2 2 0 012-2h9a2 2 0 012 2v1"/></svg>
                                </button>
                                {{-- Reveal --}}
                                <a class="lm-afd-icon-btn" href="{{ route('admin.leadmax.affiliates.show', [$affiliate->id, 'tab' => 'api-keys', 'key' => $key['id']]) }}" title="View key details" aria-label="View details for {{ $key['name'] }}">
                                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                </a>
                                {{-- Revoke (active only) --}}
                                @if($isActive)
                                <form method="POST" action="{{ route('admin.leadmax.affiliates.api-keys.destroy', [$affiliate->id, $key['id']]) }}" style="display:contents" onsubmit="return confirm('Revoke this API key?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="lm-afd-icon-btn lm-afd-icon-btn--danger" title="Revoke key">
                                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 01-2 2H8a2 2 0 01-2-2L5 6"/><path d="M10 11v6M14 11v6"/><path d="M9 6V4a1 1 0 011-1h4a1 1 0 011 1v2"/></svg>
                                    </button>
                                </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="7" class="leadmax-no-data" style="height:80px;text-align:center;color:#64748b;">No API keys yet. Generate one to get started.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            <div class="lm-afd-table-footer">
                <span class="lm-afd-showing">Showing 1 to {{ $apiKeyCount }} of {{ $apiKeyCount }} entries</span>
                <div class="lm-afd-page-controls">
                    <select class="lm-afd-per-page">
                        <option selected>5 per page</option>
                        <option>10</option>
                        <option>25</option>
                    </select>
                </div>
            </div>
        </div>
        @endif
    </div>

    {{-- Add Affiliate Modal (carried over) --}}
    <div id="lm-add-affiliate-modal" class="lm-modal-backdrop" style="display:none" onclick="if(event.target===this)this.style.display='none'">
        <div class="lm-modal-card">
            <div class="lm-modal-header">
                <strong>Add New Affiliate</strong>
                <button type="button" class="lm-modal-close" onclick="document.getElementById('lm-add-affiliate-modal').style.display='none'">&times;</button>
            </div>
            <form method="POST" action="{{ route('admin.leadmax.affiliates.store') }}" class="lm-modal-form">
                @csrf
                <div class="lm-modal-row">
                    <div class="lm-modal-field">
                        <label>First Name <span class="lm-req">*</span></label>
                        <input type="text" name="first_name" required maxlength="100" placeholder="e.g. James">
                    </div>
                    <div class="lm-modal-field">
                        <label>Last Name <span class="lm-req">*</span></label>
                        <input type="text" name="last_name" required maxlength="100" placeholder="e.g. Carter">
                    </div>
                </div>
                <div class="lm-modal-field">
                    <label>Email Address <span class="lm-req">*</span></label>
                    <input type="email" name="email" required maxlength="200" placeholder="e.g. james@example.com">
                </div>
                <div class="lm-modal-field">
                    <label>Affiliate Code</label>
                    <input type="text" name="affiliate_code" maxlength="20" placeholder="e.g. JCARTR">
                    <small class="lm-modal-hint">Auto-generated if left empty</small>
                </div>
                <div class="lm-modal-field">
                    <label>Country</label>
                    <select name="country">
                        <option value="">Select country...</option>
                        @foreach(['United States','United Kingdom','Germany','France','Spain','Italy','Netherlands','Canada','Australia','South Africa','Singapore','United Arab Emirates','Brazil','India','Japan','China'] as $c)
                            <option value="{{ $c }}">{{ $c }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="lm-modal-row">
                    <div class="lm-modal-field">
                        <label>Commission Rate (%)</label>
                        <input type="number" name="commission_rate" step="0.1" min="0" max="100" placeholder="e.g. 10.0" value="10.0">
                    </div>
                    <div class="lm-modal-field">
                        <label>Status <span class="lm-req">*</span></label>
                        <select name="status" required>
                            <option value="active" selected>Active</option>
                            <option value="pending">Pending</option>
                            <option value="suspended">Suspended</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="lm-modal-actions">
                    <button type="button" class="lm-modal-btn-cancel" onclick="document.getElementById('lm-add-affiliate-modal').style.display='none'">Cancel</button>
                    <button type="submit" class="lm-modal-btn-submit">Add Affiliate</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Generate API Key Modal --}}
    <div id="lm-gen-api-key-modal" class="lm-modal-backdrop" style="display:none" onclick="if(event.target===this)this.style.display='none'">
        <div class="lm-modal-card">
            <div class="lm-modal-header">
                <strong>Generate API Key</strong>
                <button type="button" class="lm-modal-close" onclick="document.getElementById('lm-gen-api-key-modal').style.display='none'">&times;</button>
            </div>
            <form method="POST" action="{{ route('admin.leadmax.affiliates.api-keys.store', $affiliate->id) }}" class="lm-modal-form">
                @csrf
                <input type="hidden" name="tab" value="api-keys">
                <div class="lm-modal-field">
                    <label>Key Name <span class="lm-req">*</span></label>
                    <input type="text" name="key_name" required maxlength="100" placeholder="e.g. Production Key">
                </div>
                <div class="lm-modal-field">
                    <label>Environment <span class="lm-req">*</span></label>
                    <select name="environment" required>
                        <option value="Production">Production</option>
                        <option value="Staging">Staging</option>
                    </select>
                </div>
                <div class="lm-modal-field">
                    <label>Permissions <span class="lm-req">*</span></label>
                    <select name="permissions" required>
                        <option value="Read &amp; Write">Read &amp; Write</option>
                        <option value="Read Only">Read Only</option>
                    </select>
                </div>
                <div class="lm-modal-actions">
                    <button type="button" class="lm-modal-btn-cancel" onclick="document.getElementById('lm-gen-api-key-modal').style.display='none'">Cancel</button>
                    <button type="submit" class="lm-modal-btn-submit">Generate Key</button>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
function lmCopyApiKey(key, btn) {
    navigator.clipboard.writeText(key).then(() => {
        const orig = btn.innerHTML;
        btn.innerHTML = '<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="#22c55e" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>';
        setTimeout(() => { btn.innerHTML = orig; }, 1400);
    });
}
function lmRevealApiKey(btn, key) {
    const row = btn.closest('tr');
    const cell = row.querySelector('.lm-afd-api-key-mask');
    if (!cell) return;
    if (cell.dataset.revealed === '1') {
        cell.textContent = cell.dataset.masked || cell.textContent;
        cell.dataset.revealed = '0';
    } else {
        cell.dataset.masked = cell.textContent;
        cell.textContent = key;
        cell.dataset.revealed = '1';
    }
}
</script>
@if($viewKey)
    @include('admin.leadmax.partials.api-key-modal', ['key' => $viewKey])
@endif
</x-leadmax-layout>

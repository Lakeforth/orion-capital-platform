<x-leadmax-layout :central-url="$centralUrl" :active-lead-max-page="$activeLeadMaxPage">
<div class="leadmax-module-page lm-attr-page lm-attr-source-page">

    <header class="lm-attr-header">
        <div class="lm-attr-title-row">
            <div>
                <h1>Source Details</h1>
                <a class="lm-back-link" href="{{ route('admin.leadmax.attribution', ['view' => 'performance']) }}">← Back to Attribution</a>
                <a class="lm-back-link" href="{{ route('admin.leadmax.attribution', ['source' => [$source]]) }}">View these leads in Incoming Leads →</a>
            </div>
            <nav class="leadmax-breadcrumb">
                <a href="{{ route('admin.leadmax.dashboard') }}">Dashboard</a> <span>/</span>
                CRM <span>/</span>
                <a href="{{ route('admin.leadmax.attribution', ['view' => 'performance']) }}">Attribution</a> <span>/</span>
                <strong>{{ $source }}</strong>
            </nav>
        </div>
    </header>

    {{-- Source Card --}}
    <div class="lm-attr-source-card">
        <div class="lm-attr-source-avatar">{{ mb_strtoupper(mb_substr($source,0,1)) }}</div>
        <div class="lm-attr-source-info">
            <div class="lm-attr-source-name-row">
                <h2>{{ $source }}</h2>
                <span class="lm-attr-status-badge lm-attr-status-active">Active</span>
            </div>
            @if($campaignName)
            <div class="lm-attr-source-campaign">Campaign: <a class="lm-attr-source-link">{{ $campaignName }}</a></div>
            @endif
        </div>
        <div class="lm-attr-source-stats">
            @if($firstSeenAt)<div><span>Created Date</span><strong>{{ $firstSeenAt }}</strong></div>@endif
            <div><span>Commission Model</span><strong>{{ $commissionModel }}</strong></div>
            <div><span>Total Leads</span><strong>{{ number_format($totalLeads) }}</strong></div>
            <div><span>Total FTDs</span><strong>{{ number_format($totalFtds) }}</strong></div>
            <div><span>Conversion Rate</span><strong class="lm-attr-green">{{ $conversionRate }}%</strong></div>
            <div><span>Total Deposit</span><strong class="lm-attr-green">${{ number_format($totalAmount,2) }}</strong></div>
            <div><span>Total Commission</span><strong class="lm-attr-green">${{ number_format($totalCommission,2) }}</strong></div>
        </div>
        <div class="lm-attr-source-nav">
            <button type="button" class="lm-attr-btn">‹ Previous</button>
            <button type="button" class="lm-attr-btn">Next ›</button>
        </div>
    </div>

    {{-- Monthly KPIs --}}
    <div class="lm-attr-monthly-kpis">
        <div class="lm-card-link lm-attr-mkpi"><a class="lm-card-main lm-card-cover" href="{{ route('admin.leadmax.leads', ['period' => 'current_month', 'source' => $source]) }}" aria-label="Leads This Month"></a>
            <span>Leads This Month</span>
            <strong>{{ number_format($leadsThisMonth) }}</strong>
            @if($leadsChange !== null)<small class="{{ $leadsChange >= 0 ? 'lm-attr-up' : 'lm-attr-down' }}">{{ $leadsChange >= 0 ? '↑' : '↓' }} {{ abs($leadsChange) }}% vs last month</small>@endif
        </div>
        <div class="lm-card-link lm-attr-mkpi"><a class="lm-card-main lm-card-cover" href="{{ route('admin.leadmax.leads', [...['period' => 'current_month', 'source' => $source], 'ftd' => 'yes']) }}" aria-label="FTDs This Month"></a>
            <span>FTDs This Month</span>
            <strong>{{ number_format($ftdsThisMonth) }}</strong>
            @if($ftdsChange !== null)<small class="{{ $ftdsChange >= 0 ? 'lm-attr-up' : 'lm-attr-down' }}">{{ $ftdsChange >= 0 ? '↑' : '↓' }} {{ abs($ftdsChange) }}% vs last month</small>@endif
        </div>
        <div class="lm-card-link lm-attr-mkpi"><a class="lm-card-main lm-card-cover" href="{{ route('admin.leadmax.leads', [...['period' => 'current_month', 'source' => $source], 'ftd' => 'yes']) }}" aria-label="Deposits This Month"></a>
            <span>Deposits This Month</span>
            <strong>${{ number_format($depThisMonth,2) }}</strong>
            @if($depChange !== null)<small class="{{ $depChange >= 0 ? 'lm-attr-up' : 'lm-attr-down' }}">{{ $depChange >= 0 ? '↑' : '↓' }} {{ abs($depChange) }}% vs last month</small>@endif
        </div>
        <div class="lm-card-link lm-attr-mkpi"><a class="lm-card-main lm-card-cover" href="{{ route('admin.leadmax.leads', [...['period' => 'current_month', 'source' => $source], 'ftd' => 'yes']) }}" aria-label="Commission This Month"></a>
            <span>Commission This Month</span>
            <strong>${{ number_format($commThisMonth,2) }}</strong>
        </div>
        <div class="lm-card-link lm-attr-mkpi"><a class="lm-card-main lm-card-cover" href="{{ route('admin.leadmax.leads', ['period' => 'current_month', 'source' => $source]) }}" aria-label="Avg. Lead Score"></a>
            <span title="{{ \App\Support\LeadMax\LeadScore::FORMULA }}">Avg. Lead Score</span>
            <strong>{{ $avgScore ?? '—' }}</strong>
        </div>
    </div>

    {{-- Charts Row --}}
    <div class="lm-attr-charts lm-attr-charts-2">
        <div class="lm-attr-chart-card">
            <div class="lm-attr-chart-title">Leads &amp; FTDs Over Time<span class="chart-legend blue">● Leads {{ number_format(array_sum($sourceTrends['leads'])) }}</span><span class="chart-legend green">● FTDs {{ number_format(array_sum($sourceTrends['ftds'])) }}</span>@if($sourceTrends['weekly'])<span class="chart-legend">weekly</span>@endif</div>
            @include('admin.leadmax.partials.trend-chart', ['series' => [['blue', $sourceTrends['leads']], ['green', $sourceTrends['ftds']]], 'label' => 'Leads and FTDs over time', 'height' => 120])
        </div>
        <div class="lm-attr-chart-card">
            <div class="lm-attr-chart-title">Deposit &amp; Commission Trend<span class="chart-legend green">● Deposits ${{ number_format(array_sum($sourceTrends['deposits']), 2) }}</span><span class="chart-legend amber">● Commission ${{ number_format(array_sum($sourceTrends['commission']), 2) }}</span>@if($sourceTrends['weekly'])<span class="chart-legend">weekly</span>@endif</div>
            @include('admin.leadmax.partials.trend-chart', ['series' => [['green', $sourceTrends['deposits']], ['amber', $sourceTrends['commission']]], 'label' => 'FTD deposits and commission over time', 'height' => 120])
        </div>
    </div>

    {{-- Leads Table --}}
    <div class="lm-attr-table-card">
        <div class="lm-attr-table-header">
            <div><strong>Leads from {{ $source }}</strong> <span class="lm-attr-count-badge">{{ $leads->total() }} leads</span></div>
            <div class="lm-attr-table-controls">
                <form method="GET" class="lm-attr-search-wrap">@if(request()->filled('period'))<input type="hidden" name="period" value="{{ request('period') }}">@endif @if(request()->filled('from'))<input type="hidden" name="from" value="{{ request('from') }}">@endif @if(request()->filled('to'))<input type="hidden" name="to" value="{{ request('to') }}">@endif @if(request()->filled('country'))<input type="hidden" name="country" value="{{ request('country') }}">@endif @if(request()->filled('manager_id'))<input type="hidden" name="manager_id" value="{{ request('manager_id') }}">@endif @if(request()->filled('status'))<input type="hidden" name="status" value="{{ request('status') }}">@endif <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg><input type="search" name="search" value="{{ request('search') }}" placeholder="Search leads..." aria-label="Search leads" class="lm-attr-search-input"></form>
                <button type="button" class="lm-attr-btn" data-lm-filter-toggle aria-controls="lm-source-filters">⊘ Filters</button>
            </div>
        </div>
        @include('admin.leadmax.partials.filter-panel', ['panelId' => 'lm-source-filters', 'keep' => ['period', 'from', 'to', 'search'], 'selects' => ['status' => ['Status', ['new' => 'New', 'contacted' => 'Contacted', 'qualified' => 'Qualified', 'converted' => 'Converted', 'disqualified' => 'Disqualified']], 'country' => ['Country', $filterCountries->mapWithKeys(fn ($c) => [$c => $c])->all()], 'manager_id' => ['Agent', $filterOwners->mapWithKeys(fn ($u) => [$u->id => $u->name])->all()]]])
        <div class="lm-attr-table-wrap">
            <table class="lm-attr-table">
                <thead><tr>
                    <th><input type="checkbox" class="lm-ftd-checkbox"></th>
                    <th>#</th><th>Lead Name</th><th>Email</th><th title="{{ \App\Support\LeadMax\LeadScore::FORMULA }}">Lead Score</th><th>Status</th><th>FTD</th><th>Deposit Amount</th><th>Assigned To</th><th>Created Date</th><th>Action</th>
                </tr></thead>
                <tbody>
                @forelse($leads as $i=>$lead)
                @php
                    $hasFtd = $lead->ftd_amount > 0;
                    $score = \App\Support\LeadMax\LeadScore::for($lead);
                @endphp
                <tr>
                    <td><input type="checkbox" class="lm-ftd-checkbox"></td>
                    <td>{{ $lead->account_id ?: substr($lead->id,0,8) }}</td>
                    <td><a href="{{ route('admin.leadmax.leads.show',$lead->id) }}" class="lm-attr-source-link">{{ $lead->name }}</a></td>
                    <td>{{ $lead->email }}</td>
                    <td><div class="lm-attr-score-bar"><div style="width:{{ $score }}%"></div></div><span class="lm-attr-score-val">{{ $score }}</span></td>
                    <td><span class="lm-attr-status-badge {{ $hasFtd ? 'lm-attr-status-converted' : 'lm-attr-status-active' }}">{{ $hasFtd ? 'Converted' : 'Active' }}</span></td>
                    <td>{{ $hasFtd ? 'Yes' : 'No' }}</td>
                    <td>${{ number_format((float)$lead->ftd_amount,2) }}</td>
                    <td>{{ $lead->manager?->name ?: '—' }}</td>
                    <td>{{ $lead->created_at?->format('Y-m-d') }}</td>
                    <td><a href="{{ route('admin.leadmax.leads.show',$lead->id) }}" title="View"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg></a></td>
                </tr>
                @empty
                <tr><td colspan="11" class="lm-attr-empty">No leads found for this source.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="lm-attr-table-footer">
            Showing {{ $leads->firstItem() ?? 0 }} to {{ $leads->lastItem() ?? 0 }} of {{ $leads->total() }} entries
            <div style="display:inline-flex;gap:4px;float:right">
                @if(!$leads->onFirstPage())<a href="{{ $leads->previousPageUrl() }}" class="lm-ftd-page-btn">‹</a>@else<span class="lm-ftd-page-btn disabled">‹</span>@endif
                @foreach($leads->getUrlRange(max(1,$leads->currentPage()-2),min($leads->lastPage(),$leads->currentPage()+2)) as $p=>$u)<a href="{{ $u }}" class="lm-ftd-page-btn{{ $p===$leads->currentPage()?' active':'' }}">{{ $p }}</a>@endforeach
                @if($leads->hasMorePages())<a href="{{ $leads->nextPageUrl() }}" class="lm-ftd-page-btn">›</a>@else<span class="lm-ftd-page-btn disabled">›</span>@endif
            </div>
        </div>
    </div>

</div>
</x-leadmax-layout>

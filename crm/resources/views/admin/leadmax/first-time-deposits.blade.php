@php use App\Support\LeadMax\CardLink; $r = CardLink::range('today'); @endphp
<x-leadmax-layout :central-url="$centralUrl" :active-lead-max-page="$activeLeadMaxPage">
@php
$countryFlags = ["Afghanistan"=>"🇦🇫","Albania"=>"🇦🇱","Algeria"=>"🇩🇿","Andorra"=>"🇦🇩","Angola"=>"🇦🇴","Antigua and Barbuda"=>"🇦🇬","Argentina"=>"🇦🇷","Armenia"=>"🇦🇲","Australia"=>"🇦🇺","Austria"=>"🇦🇹","Azerbaijan"=>"🇦🇿","Bahamas"=>"🇧🇸","Bahrain"=>"🇧🇭","Bangladesh"=>"🇧🇩","Barbados"=>"🇧🇧","Belarus"=>"🇧🇾","Belgium"=>"🇧🇪","Belize"=>"🇧🇿","Benin"=>"🇧🇯","Bhutan"=>"🇧🇹","Bolivia"=>"🇧🇴","Bosnia and Herzegovina"=>"🇧🇦","Botswana"=>"🇧🇼","Brazil"=>"🇧🇷","Brunei"=>"🇧🇳","Bulgaria"=>"🇧🇬","Burkina Faso"=>"🇧🇫","Burundi"=>"🇧🇮","Cambodia"=>"🇰🇭","Cameroon"=>"🇨🇲","Canada"=>"🇨🇦","Cape Verde"=>"🇨🇻","Central African Republic"=>"🇨🇫","Chad"=>"🇹🇩","Chile"=>"🇨🇱","China"=>"🇨🇳","Colombia"=>"🇨🇴","Comoros"=>"🇰🇲","Congo"=>"🇨🇬","Costa Rica"=>"🇨🇷","Croatia"=>"🇭🇷","Cuba"=>"🇨🇺","Cyprus"=>"🇨🇾","Czech Republic"=>"🇨🇿","Czechia"=>"🇨🇿","Denmark"=>"🇩🇰","Djibouti"=>"🇩🇯","Dominican Republic"=>"🇩🇴","DR Congo"=>"🇨🇩","Ecuador"=>"🇪🇨","Egypt"=>"🇪🇬","El Salvador"=>"🇸🇻","Equatorial Guinea"=>"🇬🇶","Eritrea"=>"🇪🇷","Estonia"=>"🇪🇪","Eswatini"=>"🇸🇿","Ethiopia"=>"🇪🇹","Fiji"=>"🇫🇯","Finland"=>"🇫🇮","France"=>"🇫🇷","Gabon"=>"🇬🇦","Gambia"=>"🇬🇲","Georgia"=>"🇬🇪","Germany"=>"🇩🇪","Ghana"=>"🇬🇭","Greece"=>"🇬🇷","Grenada"=>"🇬🇩","Guatemala"=>"🇬🇹","Guinea"=>"🇬🇳","Guinea-Bissau"=>"🇬🇼","Guyana"=>"🇬🇾","Haiti"=>"🇭🇹","Honduras"=>"🇭🇳","Hong Kong"=>"🇭🇰","Hungary"=>"🇭🇺","Iceland"=>"🇮🇸","India"=>"🇮🇳","Indonesia"=>"🇮🇩","Iran"=>"🇮🇷","Iraq"=>"🇮🇶","Ireland"=>"🇮🇪","Israel"=>"🇮🇱","Italy"=>"🇮🇹","Ivory Coast"=>"🇨🇮","Jamaica"=>"🇯🇲","Japan"=>"🇯🇵","Jordan"=>"🇯🇴","Kazakhstan"=>"🇰🇿","Kenya"=>"🇰🇪","Kosovo"=>"🇽🇰","Kuwait"=>"🇰🇼","Kyrgyzstan"=>"🇰🇬","Laos"=>"🇱🇦","Latvia"=>"🇱🇻","Lebanon"=>"🇱🇧","Lesotho"=>"🇱🇸","Liberia"=>"🇱🇷","Libya"=>"🇱🇾","Liechtenstein"=>"🇱🇮","Lithuania"=>"🇱🇹","Luxembourg"=>"🇱🇺","Madagascar"=>"🇲🇬","Malawi"=>"🇲🇼","Malaysia"=>"🇲🇾","Maldives"=>"🇲🇻","Mali"=>"🇲🇱","Malta"=>"🇲🇹","Mauritania"=>"🇲🇷","Mauritius"=>"🇲🇺","Mexico"=>"🇲🇽","Moldova"=>"🇲🇩","Monaco"=>"🇲🇨","Mongolia"=>"🇲🇳","Montenegro"=>"🇲🇪","Morocco"=>"🇲🇦","Mozambique"=>"🇲🇿","Myanmar"=>"🇲🇲","Namibia"=>"🇳🇦","Nepal"=>"🇳🇵","Netherlands"=>"🇳🇱","New Zealand"=>"🇳🇿","Nicaragua"=>"🇳🇮","Niger"=>"🇳🇪","Nigeria"=>"🇳🇬","North Korea"=>"🇰🇵","North Macedonia"=>"🇲🇰","Norway"=>"🇳🇴","Oman"=>"🇴🇲","Pakistan"=>"🇵🇰","Palestine"=>"🇵🇸","Panama"=>"🇵🇦","Papua New Guinea"=>"🇵🇬","Paraguay"=>"🇵🇾","Peru"=>"🇵🇪","Philippines"=>"🇵🇭","Poland"=>"🇵🇱","Portugal"=>"🇵🇹","Qatar"=>"🇶🇦","Romania"=>"🇷🇴","Russia"=>"🇷🇺","Rwanda"=>"🇷🇼","Saint Kitts and Nevis"=>"🇰🇳","Saint Lucia"=>"🇱🇨","San Marino"=>"🇸🇲","Saudi Arabia"=>"🇸🇦","Senegal"=>"🇸🇳","Serbia"=>"🇷🇸","Sierra Leone"=>"🇸🇱","Singapore"=>"🇸🇬","Slovakia"=>"🇸🇰","Slovenia"=>"🇸🇮","Solomon Islands"=>"🇸🇧","Somalia"=>"🇸🇴","South Africa"=>"🇿🇦","South Korea"=>"🇰🇷","South Sudan"=>"🇸🇸","Spain"=>"🇪🇸","Sri Lanka"=>"🇱🇰","Sudan"=>"🇸🇩","Suriname"=>"🇸🇷","Sweden"=>"🇸🇪","Switzerland"=>"🇨🇭","Syria"=>"🇸🇾","Taiwan"=>"🇹🇼","Tajikistan"=>"🇹🇯","Tanzania"=>"🇹🇿","Thailand"=>"🇹🇭","Timor-Leste"=>"🇹🇱","Togo"=>"🇹🇬","Trinidad and Tobago"=>"🇹🇹","Tunisia"=>"🇹🇳","Turkey"=>"🇹🇷","Turkmenistan"=>"🇹🇲","Uganda"=>"🇺🇬","Ukraine"=>"🇺🇦","United Arab Emirates"=>"🇦🇪","United Kingdom"=>"🇬🇧","United States"=>"🇺🇸","Uruguay"=>"🇺🇾","Uzbekistan"=>"🇺🇿","Vatican"=>"🇻🇦","Venezuela"=>"🇻🇪","Vietnam"=>"🇻🇳","Yemen"=>"🇾🇪","Zambia"=>"🇿🇲","Zimbabwe"=>"🇿🇼"];
$ftdStatusClass = fn($s) => match(strtolower($s ?? 'completed')) { 'pending'=>'lm-ftd-status-pending','failed'=>'lm-ftd-status-failed',default=>'lm-ftd-status-completed' };
$payColors = ['#2563eb','#34d399','#f59e0b','#a78bfa','#fb923c','#60a5fa','#f87171','#818cf8'];
$totalPay = $paymentBreakdown->sum('cnt') ?: 1;
$conicParts = [];
$deg = 0;
foreach($paymentBreakdown->take(8) as $i => $pm){
    $share = round($pm->cnt / $totalPay * 360);
    $conicParts[] = ($payColors[$i % count($payColors)]).' '.$deg.'deg '.($deg+$share).'deg';
    $deg += $share;
}
$conicGradient = 'conic-gradient('.implode(', ',$conicParts).')';
$trendMax = $trendData->max() ?: 1;
$trendPoints = [];
$trendDates = $trendData->keys()->values();
$trendCounts = $trendData->values();
$n = count($trendCounts);
for($i=0;$i<$n;$i++){
    $x = $n > 1 ? round($i/($n-1)*500) : 250;
    $y = round((1 - $trendCounts[$i]/$trendMax)*140)+10;
    $trendPoints[] = "$x,$y";
}
$svgPolyline = implode(' ',$trendPoints);
@endphp
<div class="leadmax-module-page lm-ftd-page">

    {{-- Header --}}
    <header class="lm-ftd-header">
        <div class="lm-ftd-title-row">
            <div><h1>First Time Deposit (FTD)</h1><p class="lm-page-sub">Clients' first deposits: who converted, when, for how much and through which source.</p></div>
            <nav class="leadmax-breadcrumb"><a href="{{ route('admin.leadmax.dashboard') }}">Dashboard</a> <span>/</span> CRM <span>/</span> <strong>First Time Deposit</strong></nav>
        </div>
        <div class="lm-ftd-filter-row">
            <form class="leadmax-periods leadmax-dashboard-periods" method="GET">
                @foreach(['today'=>'Today','yesterday'=>'Yesterday','current_week'=>'Current week','current_month'=>'Current month','last_week'=>'Last week','last_month'=>'Last month'] as $val=>$lbl)
                    <button type="submit" name="period" value="{{ $val }}" class="{{ $period === $val ? '' : 'leadmax-period-secondary' }}" aria-pressed="{{ $period === $val ? 'true' : 'false' }}">{{ $lbl }}</button>
                @endforeach
                <div class="leadmax-custom-period"><label class="leadmax-date-field"><x-leadmax-reference-icon name="calendar" /><input type="text" name="from" value="{{ request('from') }}" placeholder="From date" aria-label="From date" data-leadmax-date></label><label class="leadmax-date-field"><x-leadmax-reference-icon name="calendar" /><input type="text" name="to" value="{{ request('to') }}" placeholder="To date" aria-label="To date" data-leadmax-date></label><button class="sr-only" type="submit" name="period" value="custom">Apply custom dates</button></div>
            </form>
            <div class="lm-ftd-actions">
                <a href="{{ route('admin.leadmax.first-time-deposits', [...request()->except('page'), 'export' => 'csv']) }}" class="lm-ftd-export-btn">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                    Export
                </a>
            </div>
        </div>
    </header>

    {{-- KPI Cards --}}
    <div class="lm-ftd-kpis">
        <div class="lm-card-link lm-ftd-kpi"><a class="lm-card-main lm-card-cover" href="{{ '#lm-ftd-list' }}" aria-label="New Depositors (FTD)"></a>
            <div class="lm-ftd-kpi-icon" style="background:#13305580">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#2563eb" stroke-width="2"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75"/></svg>
            </div>
            <div class="lm-ftd-kpi-body">
                <span>New Depositors (FTD)</span>
                <strong>{{ number_format($ftdCount) }}</strong>
                @if($countChange !== null)<small class="lm-ftd-kpi-change {{ $countChange >= 0 ? 'lm-kpi-up' : 'lm-kpi-down' }}">{{ $countChange >= 0 ? '↑' : '↓' }} {{ abs($countChange) }}% vs last week</small>@endif
            </div>
        </div>
        <div class="lm-card-link lm-ftd-kpi"><a class="lm-card-main lm-card-cover" href="{{ '#lm-ftd-list' }}" aria-label="Total FTD Amount"></a>
            <div class="lm-ftd-kpi-icon" style="background:#0d3b3680">
                <span style="font:700 18px Inter,sans-serif;color:#34d399">$</span>
            </div>
            <div class="lm-ftd-kpi-body">
                <span>Total FTD Amount</span>
                <strong>${{ number_format($ftdAmount,0) }}</strong>
                @if($amountChange !== null)<small class="lm-ftd-kpi-change {{ $amountChange >= 0 ? 'lm-kpi-up' : 'lm-kpi-down' }}">{{ $amountChange >= 0 ? '↑' : '↓' }} {{ abs($amountChange) }}% vs last week</small>@endif
            </div>
        </div>
        <div class="lm-card-link lm-ftd-kpi"><a class="lm-card-main lm-card-cover" href="{{ route('admin.leadmax.leads', [...$r, 'ftd' => 'yes']) }}" aria-label="FTD Conversion Rate"></a>
            <div class="lm-ftd-kpi-icon" style="background:#13305580">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#2563eb" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/></svg>
            </div>
            <div class="lm-ftd-kpi-body">
                <span>FTD Conversion Rate</span>
                <strong>{{ $conversionRate }}%</strong>
                @if($convChange !== null)<small class="lm-ftd-kpi-change {{ $convChange >= 0 ? 'lm-kpi-up' : 'lm-kpi-down' }}">{{ $convChange >= 0 ? '↑' : '↓' }} {{ abs($convChange) }}pp vs last week</small>@endif
            </div>
        </div>
        <div class="lm-card-link lm-ftd-kpi"><a class="lm-card-main lm-card-cover" href="{{ '#lm-ftd-list' }}" aria-label="Avg. Time to Deposit"></a>
            <div class="lm-ftd-kpi-icon" style="background:#13305580">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#2563eb" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
            </div>
            <div class="lm-ftd-kpi-body">
                <span>Avg. Time to Deposit</span>
                <strong>{{ $avgTimeStr }}</strong>
            </div>
        </div>
    </div>

    {{-- Table --}}
    <div class="lm-ftd-table-card" id="lm-ftd-list">
        <div class="lm-ftd-table-topbar">
            <div class="lm-ftd-table-title">
                <strong>FTD List</strong>
                <span class="lm-ftd-count-badge">{{ number_format($customers->total()) }} Depositors</span>
            </div>
            <div class="lm-ftd-table-controls">
                <button type="button" class="lm-ftd-filter-btn" data-lm-filter-toggle aria-controls="lm-ftd-filters">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="4" y1="6" x2="20" y2="6"/><line x1="8" y1="12" x2="16" y2="12"/><line x1="11" y1="18" x2="13" y2="18"/></svg>
                    Filters
                </button>
                <button type="button" class="lm-ftd-dots-btn">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="5" r="1.5"/><circle cx="12" cy="12" r="1.5"/><circle cx="12" cy="19" r="1.5"/></svg>
                </button>
                <form method="GET" style="display:flex;align-items:center">
                    <input type="hidden" name="period" value="{{ $period }}">
                    @if(request()->filled('from'))<input type="hidden" name="from" value="{{ request('from') }}">@endif @if(request()->filled('to'))<input type="hidden" name="to" value="{{ request('to') }}">@endif @if(request()->filled('country'))<input type="hidden" name="country" value="{{ request('country') }}">@endif @if(request()->filled('created_by'))<input type="hidden" name="created_by" value="{{ request('created_by') }}">@endif @if(request()->filled('manager_id'))<input type="hidden" name="manager_id" value="{{ request('manager_id') }}">@endif
                    <div class="lm-ftd-search-wrap">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="Search..." class="lm-ftd-search-input">
                    </div>
                </form>
            </div>
        </div>
        @include('admin.leadmax.partials.filter-panel', ['panelId' => 'lm-ftd-filters', 'keep' => ['period', 'from', 'to', 'search'], 'selects' => ['country' => ['Country', $filterCountries->mapWithKeys(fn ($c) => [$c => $c])->all()], 'created_by' => ['Affiliate', $filterAffiliates->mapWithKeys(fn ($u) => [$u->id => $u->name])->all()], 'manager_id' => ['Agent', $filterOwners->mapWithKeys(fn ($u) => [$u->id => $u->name])->all()]]])
        <div class="lm-ftd-table-wrap">
            <table class="lm-ftd-table">
                <thead>
                    <tr>
                        <th class="lm-ftd-th-check"><input type="checkbox" class="lm-ftd-checkbox"></th>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Phone</th>
                        <th>Email</th>
                        <th>Country</th>
                        <th>Affiliate</th>
                        <th>Deposit Amount</th>
                        <th>Payment Method</th>
                        <th>Deposit Date</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($customers as $customer)
                @php
                    $flag = $countryFlags[$customer->country ?? ''] ?? '';
                    $method = $firstDepositMethods[$customer->id] ?? null;
                    $txAt = $firstDepositDatetimes[$customer->id] ?? null;
                    $depositDate = $txAt
                        ? \Carbon\Carbon::parse($txAt)->format('Y-m-d H:i')
                        : ($customer->ftd_date ?: $customer->created_at?->format('Y-m-d'));
                    $ftdStatus = ($customer->settings['ftd_status'] ?? 'Completed');
                @endphp
                <tr>
                    <td><input type="checkbox" class="lm-ftd-checkbox"></td>
                    <td class="lm-ftd-id-link"><a href="{{ route('admin.leadmax.leads.show', $customer->id) }}">{{ $customer->account_id ?: substr($customer->id,0,8) }}</a></td>
                    <td class="lm-ftd-name">{{ $customer->name }}</td>
                    <td class="lm-ftd-phone">{{ $customer->phone ?: '—' }}</td>
                    <td class="lm-ftd-email">{{ $customer->email }}</td>
                    <td class="lm-ftd-country">{{ $flag }}</td>
                    <td>{{ $customer->affiliate?->name ?: 'Direct' }}</td>
                    <td class="lm-ftd-amount">${{ number_format((float)$customer->ftd_amount,2) }}</td>
                    <td>{{ $method ?: '—' }}</td>
                    <td class="lm-ftd-date">{{ $depositDate }}</td>
                    <td><span class="lm-ftd-status-badge {{ $ftdStatusClass($ftdStatus) }}">{{ $ftdStatus }}</span></td>
                    <td class="lm-ftd-action">
                        <a href="{{ route('admin.leadmax.leads.show', $customer->id) }}?tab=finances" title="View" aria-label="View {{ $customer->name }}">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                        </a>
                        <button type="button" title="More">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="5" r="1.5"/><circle cx="12" cy="12" r="1.5"/><circle cx="12" cy="19" r="1.5"/></svg>
                        </button>
                    </td>
                </tr>
                @empty
                <tr><td colspan="12" class="lm-ftd-empty">No first-time deposits found for this period.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        {{-- Pagination --}}
        <div class="lm-ftd-pagination">
            <span>Showing {{ $customers->firstItem() ?? 0 }} to {{ $customers->lastItem() ?? 0 }} of {{ number_format($customers->total()) }} entries</span>
            <div class="lm-ftd-page-controls">
                @if($customers->onFirstPage())
                    <span class="lm-ftd-page-btn disabled">&lsaquo;</span>
                @else
                    <a href="{{ $customers->previousPageUrl() }}" class="lm-ftd-page-btn">&lsaquo;</a>
                @endif
                @foreach($customers->getUrlRange(max(1,$customers->currentPage()-2), min($customers->lastPage(),$customers->currentPage()+2)) as $page => $url)
                    <a href="{{ $url }}" class="lm-ftd-page-btn{{ $page === $customers->currentPage() ? ' active' : '' }}">{{ $page }}</a>
                @endforeach
                @if($customers->hasMorePages())
                    <a href="{{ $customers->nextPageUrl() }}" class="lm-ftd-page-btn">&rsaquo;</a>
                @else
                    <span class="lm-ftd-page-btn disabled">&rsaquo;</span>
                @endif
            </div>
        </div>
    </div>

    {{-- Bottom Charts --}}
    <div class="lm-ftd-charts">
        <div class="lm-ftd-chart-card">
            <div class="lm-ftd-chart-title">FTD Trend</div>
            <div class="lm-ftd-chart-body">
                @if($trendData->isNotEmpty())
                <svg viewBox="0 0 500 160" preserveAspectRatio="none" class="lm-ftd-linechart">
                    <defs>
                        <linearGradient id="ftdGrad" x1="0" y1="0" x2="0" y2="1">
                            <stop offset="0%" stop-color="#2563eb" stop-opacity="0.3"/>
                            <stop offset="100%" stop-color="#2563eb" stop-opacity="0"/>
                        </linearGradient>
                    </defs>
                    @if(count($trendPoints) > 1)
                    <polygon points="{{ $svgPolyline }} 500,160 0,160" fill="url(#ftdGrad)"/>
                    <polyline points="{{ $svgPolyline }}" fill="none" stroke="#2563eb" stroke-width="2" stroke-linejoin="round"/>
                    @else
                    <line x1="0" y1="80" x2="500" y2="80" stroke="#2563eb" stroke-width="2" stroke-dasharray="4"/>
                    @endif
                </svg>
                @else
                <div class="lm-ftd-chart-empty">No trend data for this period</div>
                @endif
            </div>
        </div>
        <div class="lm-ftd-chart-card">
            <div class="lm-ftd-chart-title">FTD by Payment Method</div>
            <div class="lm-ftd-chart-body lm-ftd-donut-body">
                @if($paymentBreakdown->isNotEmpty())
                <div class="lm-ftd-donut" style="background:{{ $conicGradient }}"></div>
                <ul class="lm-ftd-legend">
                    @foreach($paymentBreakdown->take(8) as $i => $pm)
                    <li>
                        <span class="lm-ftd-legend-dot" style="background:{{ $payColors[$i % count($payColors)] }}"></span>
                        <span>{{ $pm->method }}</span>
                        <strong>{{ number_format($pm->cnt / $totalPay * 100, 1) }}%</strong>
                    </li>
                    @endforeach
                </ul>
                @else
                <div class="lm-ftd-chart-empty">No payment data</div>
                @endif
            </div>
        </div>
    </div>

</div>
</x-leadmax-layout>

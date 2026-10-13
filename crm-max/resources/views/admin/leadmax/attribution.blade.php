@php use App\Support\LeadMax\CardLink; $r = CardLink::range('current_month'); $lf = array_filter(request()->only(['country', 'created_by', 'manager_id']), fn ($v) => $v !== null && $v !== '' && $v !== 'all'); @endphp
<x-leadmax-layout :central-url="$centralUrl" :active-lead-max-page="$activeLeadMaxPage">
@php
$periodLabels=['today'=>'Today','yesterday'=>'Yesterday','current_week'=>'Current week','current_month'=>'Current month','last_week'=>'Last week','last_month'=>'Last month'];
$countryFlags=["Afghanistan"=>"🇦🇫","Albania"=>"🇦🇱","Algeria"=>"🇩🇿","Argentina"=>"🇦🇷","Australia"=>"🇦🇺","Austria"=>"🇦🇹","Bahrain"=>"🇧🇭","Bangladesh"=>"🇧🇩","Belgium"=>"🇧🇪","Brazil"=>"🇧🇷","Canada"=>"🇨🇦","China"=>"🇨🇳","Colombia"=>"🇨🇴","Egypt"=>"🇪🇬","Ethiopia"=>"🇪🇹","Finland"=>"🇫🇮","France"=>"🇫🇷","Germany"=>"🇩🇪","Ghana"=>"🇬🇭","Greece"=>"🇬🇷","India"=>"🇮🇳","Indonesia"=>"🇮🇩","Iran"=>"🇮🇷","Iraq"=>"🇮🇶","Ireland"=>"🇮🇪","Israel"=>"🇮🇱","Italy"=>"🇮🇹","Japan"=>"🇯🇵","Jordan"=>"🇯🇴","Kazakhstan"=>"🇰🇿","Kenya"=>"🇰🇪","Kuwait"=>"🇰🇼","Lebanon"=>"🇱🇧","Malaysia"=>"🇲🇾","Mexico"=>"🇲🇽","Morocco"=>"🇲🇦","Netherlands"=>"🇳🇱","New Zealand"=>"🇳🇿","Nigeria"=>"🇳🇬","Norway"=>"🇳🇴","Oman"=>"🇴🇲","Pakistan"=>"🇵🇰","Peru"=>"🇵🇪","Philippines"=>"🇵🇭","Poland"=>"🇵🇱","Portugal"=>"🇵🇹","Qatar"=>"🇶🇦","Romania"=>"🇷🇴","Russia"=>"🇷🇺","Saudi Arabia"=>"🇸🇦","Senegal"=>"🇸🇳","Singapore"=>"🇸🇬","South Africa"=>"🇿🇦","South Korea"=>"🇰🇷","Spain"=>"🇪🇸","Sweden"=>"🇸🇪","Switzerland"=>"🇨🇭","Taiwan"=>"🇹🇼","Thailand"=>"🇹🇭","Tunisia"=>"🇹🇳","Turkey"=>"🇹🇷","Uganda"=>"🇺🇬","Ukraine"=>"🇺🇦","United Arab Emirates"=>"🇦🇪","United Kingdom"=>"🇬🇧","United States"=>"🇺🇸","Uruguay"=>"🇺🇾","Venezuela"=>"🇻🇪","Vietnam"=>"🇻🇳","Yemen"=>"🇾🇪","Zimbabwe"=>"🇿🇼"];
$mkChange=fn($v)=>$v===null?'':($v>=0?'<span class="lm-attr-up">↑ '.abs($v).'%</span>':'<span class="lm-attr-down">↓ '.abs($v).'%</span>');
@endphp
<div class="leadmax-module-page lm-attr-page">
    <header class="lm-attr-header">
        <div class="lm-attr-title-row">
            <h1>Attribution <span class="lm-attr-info-icon" title="Attribution tracking">ⓘ</span></h1>
            <nav class="leadmax-breadcrumb"><a href="{{ route('admin.leadmax.dashboard') }}">Dashboard</a> <span>/</span> CRM <span>/</span> <strong>Attribution</strong></nav>
        </div>
        @include('admin.leadmax.partials.attribution-tabs', ['attributionView' => 'performance'])
        <div class="lm-attr-filter-row">
            <form class="leadmax-periods leadmax-dashboard-periods" method="GET"><input type="hidden" name="view" value="performance">
                @foreach($periodLabels as $val=>$lbl)
                    <button type="submit" name="period" value="{{ $val }}" class="{{ $period===$val?'':'leadmax-period-secondary' }}" aria-pressed="{{ $period===$val?'true':'false' }}">{{ $lbl }}</button>
                @endforeach
                <div class="leadmax-custom-period"><input type="text" name="from" placeholder="From date" value="{{ request('from') }}" data-leadmax-date><input type="text" name="to" placeholder="To date" value="{{ request('to') }}" data-leadmax-date><button class="sr-only" type="submit" name="period" value="custom">Apply</button></div>
            </form>
            <div class="lm-attr-actions">
                <button type="button" class="lm-attr-btn lm-attr-btn-primary" onclick="document.getElementById('lm-add-source-modal').style.display='flex'"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg> Add Source</button>
                <button type="button" class="lm-attr-btn"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg> Export</button>
                <button type="button" class="lm-attr-btn"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="4" y1="6" x2="20" y2="6"/><line x1="8" y1="12" x2="16" y2="12"/><line x1="11" y1="18" x2="13" y2="18"/></svg> Filters</button>
            </div>
        </div>
    </header>

    {{-- KPI Cards --}}
    <div class="lm-attr-kpis">
        <div class="lm-card-link lm-attr-kpi"><a class="lm-card-main lm-card-cover" href="{{ route('admin.leadmax.leads', [...$r, ...$lf]) }}" aria-label="Total Leads"></a>
            <div class="lm-attr-kpi-icon lm-attr-kpi-blue"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75"/></svg></div>
            <div class="lm-attr-kpi-body"><span>Total Leads</span><strong>{{ number_format($totalLeads) }}</strong>{!! $mkChange($leadsChange) !!}</div>
        </div>
        <div class="lm-card-link lm-attr-kpi"><a class="lm-card-main lm-card-cover" href="{{ route('admin.leadmax.first-time-deposits', [...$r, ...$lf]) }}" aria-label="Conversion FTD"></a>
            <div class="lm-attr-kpi-icon lm-attr-kpi-green"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg></div>
            <div class="lm-attr-kpi-body"><span>Conversion FTD</span><strong>{{ number_format($totalFtds) }}</strong>{!! $mkChange($ftdsChange) !!}</div>
        </div>
        <div class="lm-card-link lm-attr-kpi"><a class="lm-card-main lm-card-cover" href="{{ route('admin.leadmax.first-time-deposits', [...$r, ...$lf]) }}" aria-label="Total Deposit Amount"></a>
            <div class="lm-attr-kpi-icon lm-attr-kpi-teal"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/></svg></div>
            <div class="lm-attr-kpi-body"><span>Total Deposit Amount</span><strong>${{ number_format($totalAmount,0) }}</strong>{!! $mkChange($amountChange) !!}</div>
        </div>
        <div class="lm-card-link lm-attr-kpi"><a class="lm-card-main lm-card-cover" href="{{ route('admin.leadmax.leads', [...$r, ...$lf, 'ftd' => 'yes']) }}" aria-label="Conversion Rate"></a>
            <div class="lm-attr-kpi-icon lm-attr-kpi-purple"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 8v4l3 3"/></svg></div>
            <div class="lm-attr-kpi-body"><span>Conversion Rate</span><strong>{{ $conversionRate }}%</strong></div>
        </div>
        <div class="lm-card-link lm-attr-kpi"><a class="lm-card-main lm-card-cover" href="{{ route('admin.leadmax.affiliates', $r) }}" aria-label="Total Commission"></a>
            <div class="lm-attr-kpi-icon lm-attr-kpi-orange"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg></div>
            <div class="lm-attr-kpi-body"><span>Total Commission</span><strong>${{ number_format($totalCommission,0) }}</strong></div>
        </div>
    </div>

    {{-- Charts Row --}}
    @php
        $chartSrcs = $sources->take(7);
        $maxLeads = $chartSrcs->max('leads') ?: 1;
        $maxFtds  = $chartSrcs->max('ftds') ?: 1;
        $maxAmt   = $chartSrcs->max('amount') ?: 1;
        $logBarH  = fn($val,$max) => $max > 1
            ? round(12 + (log10($val + 1) / log10($max + 1)) * 75)
            : ($val > 0 ? 87 : 12);
    @endphp
    <div class="lm-attr-charts">
        <div class="lm-attr-chart-card">
            <div class="lm-attr-chart-title">Leads by Source</div>
            <div class="lm-attr-bar-chart">
                @foreach($chartSrcs as $src)
                @php $barH = $logBarH($src->leads, $maxLeads); @endphp
                <div class="lm-attr-bar-item">
                    <span class="lm-attr-bar-val">{{ number_format($src->leads) }}</span>
                    <div class="lm-attr-bar-wrap"><div class="lm-attr-bar" style="height:{{ $barH }}px" title="{{ $src->label }}: {{ $src->leads }} leads"></div></div>
                    <span class="lm-attr-bar-label">{{ Str::limit($src->label,6) }}</span>
                </div>
                @endforeach
            </div>
        </div>
        <div class="lm-attr-chart-card">
            <div class="lm-attr-chart-title">Conversions (FTD) by Source</div>
            <div class="lm-attr-bar-chart lm-attr-bar-green">
                @foreach($chartSrcs as $src)
                @php $barH = $logBarH($src->ftds, $maxFtds); @endphp
                <div class="lm-attr-bar-item">
                    <span class="lm-attr-bar-val">{{ number_format($src->ftds) }}</span>
                    <div class="lm-attr-bar-wrap"><div class="lm-attr-bar" style="height:{{ $barH }}px"></div></div>
                    <span class="lm-attr-bar-label">{{ Str::limit($src->label,6) }}</span>
                </div>
                @endforeach
            </div>
        </div>
        <div class="lm-attr-chart-card">
            <div class="lm-attr-chart-title">Deposit Amount by Source</div>
            @php
                $pts=[];
                foreach($chartSrcs->values() as $i=>$s){
                    $x=round($i/max(1,$chartSrcs->count()-1)*340);
                    $logY=$maxAmt>1?log10($s->amount+1)/log10($maxAmt+1):($s->amount>0?1:0);
                    $y=round((1-$logY)*75)+10;
                    $pts[]="$x,$y";
                }
                $poly=implode(' ',$pts);
            @endphp
            <svg class="lm-attr-line-chart" viewBox="0 0 360 100" preserveAspectRatio="none">
                <polyline points="{{ $poly }}" fill="none" stroke="#2563eb" stroke-width="2"/>
                @foreach($pts as $i=>$pt)
                @php $s=$chartSrcs->values()[$i]; @endphp
                <circle cx="{{ explode(',',$pt)[0] }}" cy="{{ explode(',',$pt)[1] }}" r="3" fill="#2563eb"/>
                @endforeach
            </svg>
        </div>
    </div>

    {{-- Attribution Sources Table --}}
    <div class="lm-attr-table-card">
        <div class="lm-attr-table-header">
            <div><strong>Attribution Sources</strong> <span class="lm-attr-count-badge">{{ $sources->count() }} sources</span></div>
            <div class="lm-attr-table-controls">
                <form method="GET" class="lm-attr-search-wrap"><input type="hidden" name="view" value="performance">@if(request()->filled('period'))<input type="hidden" name="period" value="{{ request('period') }}">@endif @if(request()->filled('from'))<input type="hidden" name="from" value="{{ request('from') }}">@endif @if(request()->filled('to'))<input type="hidden" name="to" value="{{ request('to') }}">@endif @if(request()->filled('country'))<input type="hidden" name="country" value="{{ request('country') }}">@endif @if(request()->filled('created_by'))<input type="hidden" name="created_by" value="{{ request('created_by') }}">@endif @if(request()->filled('manager_id'))<input type="hidden" name="manager_id" value="{{ request('manager_id') }}">@endif <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg><input type="search" name="search" value="{{ $search }}" placeholder="Search source name..." aria-label="Search sources" class="lm-attr-search-input"></form>
                <button type="button" class="lm-attr-btn" data-lm-filter-toggle aria-controls="lm-attribution-filters">⊘ Filters</button>
                <button type="button" class="lm-attr-btn">⋯</button>
            </div>
        </div>
        @include('admin.leadmax.partials.filter-panel', ['panelId' => 'lm-attribution-filters', 'keep' => ['period', 'from', 'to', 'search'], 'selects' => ['country' => ['Country', $filterCountries->mapWithKeys(fn ($c) => [$c => $c])->all()], 'created_by' => ['Affiliate', $filterAffiliates->mapWithKeys(fn ($u) => [$u->id => $u->name])->all()], 'manager_id' => ['Agent', $filterOwners->mapWithKeys(fn ($u) => [$u->id => $u->name])->all()]]])
        <div class="lm-attr-table-wrap">
            <table class="lm-attr-table">
                <thead><tr>
                    <th><input type="checkbox" class="lm-ftd-checkbox"></th>
                    <th>#</th><th>Source</th><th>Campaign</th><th>Leads</th><th>FTD's</th><th>Conversion %</th><th>Deposit Amount</th><th>Commission</th><th>Status</th><th>Created At</th><th>Action</th>
                </tr></thead>
                <tbody>
                @forelse($sources as $i=>$src)
                @php
                    $conv = $src->leads > 0 ? round($src->ftds/$src->leads*100,1) : 0;
                    $campaign = $sourceCampaigns->where('source_label',$src->label)->first()?->campaign_label ?? '—';
                @endphp
                <tr>
                    <td><input type="checkbox" class="lm-ftd-checkbox"></td>
                    <td>{{ $i+1 }}</td>
                    <td><a href="{{ route('admin.leadmax.attribution.source', $src->label) }}" class="lm-attr-source-link">{{ $src->label }}</a></td>
                    <td>{{ $campaign }}</td>
                    <td>{{ number_format($src->leads) }}</td>
                    <td>{{ number_format($src->ftds) }}</td>
                    <td>{{ $conv }}%</td>
                    <td>${{ number_format((float)$src->amount,2) }}</td>
                    <td>${{ number_format($src->commission,2) }}</td>
                    <td><span class="lm-attr-status-badge lm-attr-status-active">Active</span></td>
                    <td>{{ \Carbon\Carbon::parse($src->first_seen)->format('Y-m-d') }}</td>
                    <td class="lm-attr-actions-cell">
                        <button type="button" title="View"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg></button>
                        <button type="button" title="Edit"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg></button>
                        <button type="button" title="Toggle"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/></svg></button>
                    </td>
                </tr>
                @empty
                <tr><td colspan="12" class="lm-attr-empty">No attribution sources found.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="lm-attr-table-footer">Showing 1 to {{ $sources->count() }} of {{ $sources->count() }} sources </div>
    </div>

    {{-- Bottom: Top Campaigns + Geographic Distribution --}}
    <div class="lm-attr-bottom">
        <div class="lm-attr-bottom-card">
            <div class="lm-attr-chart-title">Top Campaigns by FTD <span class="lm-attr-info-icon">ⓘ</span></div>
            <table class="lm-attr-table lm-attr-table-compact">
                <thead><tr><th>#</th><th>Campaign</th><th>Source</th><th>Leads</th><th>FTD's</th><th>Conversion %</th><th>Deposit Amount</th></tr></thead>
                <tbody>
                @forelse($sourceCampaigns as $i=>$sc)
                <tr>
                    <td>{{ $i+1 }}</td>
                    <td>{{ $sc->campaign_label }}</td>
                    <td>{{ $sc->source_label }}</td>
                    <td>{{ number_format($sc->leads) }}</td>
                    <td>{{ number_format($sc->ftds) }}</td>
                    <td>{{ $sc->leads > 0 ? round($sc->ftds/$sc->leads*100,1) : 0 }}%</td>
                    <td>${{ number_format((float)$sc->amount,2) }}</td>
                </tr>
                @empty
                <tr><td colspan="7" class="lm-attr-empty">No campaign data.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="lm-attr-bottom-card">
            <div class="lm-attr-chart-title">Geographic Distribution <span class="lm-attr-info-icon">ⓘ</span></div>
            <table class="lm-attr-table lm-attr-table-compact">
                <thead><tr><th>Country</th><th>Leads</th><th>FTD's</th><th>Conversion %</th></tr></thead>
                <tbody>
                @forelse($countries->take(5) as $country)
                <tr>
                    <td><span>{{ $countryFlags[$country->label] ?? '' }} {{ $country->label }}</span></td>
                    <td>{{ number_format($country->leads) }}</td>
                    <td>{{ number_format($country->ftds) }}</td>
                    <td>{{ $country->conversion }}%</td>
                </tr>
                @empty
                <tr><td colspan="4" class="lm-attr-empty">No geographic data.</td></tr>
                @endforelse
                </tbody>
            </table>
            @if($countries->count() > 5)
            <div style="margin-top:10px;text-align:right"><a href="{{ route('admin.leadmax.leads') }}" class="lm-attr-source-link" style="font-size:12px">View Full Country →</a></div>
            @endif
        </div>
    </div>

    <div id="lm-add-source-modal" class="lm-modal-backdrop" style="display:none" onclick="if(event.target===this)this.style.display='none'">
        <div class="lm-modal-card">
            <div class="lm-modal-header">
                <strong>Add Source</strong>
                <button type="button" class="lm-modal-close" onclick="document.getElementById('lm-add-source-modal').style.display='none'">&times;</button>
            </div>
            <form method="POST" action="{{ route('admin.leadmax.attribution.sources.store') }}" class="lm-modal-form">
                @csrf
                <div class="lm-modal-field">
                    <label>Source Name</label>
                    <input type="text" name="name" required maxlength="200" placeholder="e.g. Google Ads, Facebook, TikTok">
                </div>
                <div class="lm-modal-field">
                    <label>Source Type</label>
                    <select name="type">
                        <option value="">Select type</option>
                        <option value="paid">Paid</option>
                        <option value="organic">Organic</option>
                        <option value="social">Social</option>
                        <option value="referral">Referral</option>
                        <option value="direct">Direct</option>
                        <option value="email">Email</option>
                    </select>
                </div>
                <div class="lm-modal-field">
                    <label>Campaign</label>
                    <input type="text" name="campaign" maxlength="200" placeholder="e.g. Brand_US, Prospecting_EU">
                </div>
                <div class="lm-modal-field">
                    <label>Tracking URL</label>
                    <input type="url" name="tracking_url" maxlength="500" placeholder="https://example.com/utm_source=...">
                </div>
                <div class="lm-modal-field">
                    <label>Status</label>
                    <select name="status">
                        <option value="active" selected>Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
                <div class="lm-modal-actions">
                    <button type="button" class="lm-modal-btn-cancel" onclick="document.getElementById('lm-add-source-modal').style.display='none'">Cancel</button>
                    <button type="submit" class="lm-modal-btn-submit">Add Source</button>
                </div>
            </form>
        </div>
    </div>

</div>
</x-leadmax-layout>

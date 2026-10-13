@php
    use App\Support\LeadMax\CardLink;
    use App\Http\Controllers\Admin\LeadMaxReportController as Reports;
    $r = CardLink::range('today');
    $vsPrevious = function ($now, $before, bool $money = false) {
        $change = Reports::change($now, $before);
        $shown = $money ? '$'.number_format($before, 2) : number_format($before);

        return 'vs previous period: <b>'.$shown.'</b>'.($change === null ? '' : ' <em class="'.($change < 0 ? 'is-down' : '').'">'.($change < 0 ? '↘' : '↗').' '.number_format(abs($change), 1).'%</em>');
    };
@endphp
<x-leadmax-layout :central-url="$centralUrl" :active-lead-max-page="$activeLeadMaxPage">
@php
$period = request('period', 'today');
$countryFlags = ['United States'=>'🇺🇸','Spain'=>'🇪🇸','South Korea'=>'🇰🇷','Senegal'=>'🇸🇳','Italy'=>'🇮🇹','China'=>'🇨🇳','United Arab Emirates'=>'🇦🇪','Ghana'=>'🇬🇭','France'=>'🇫🇷','Egypt'=>'🇪🇬','Germany'=>'🇩🇪','Ireland'=>'🇮🇪','Netherlands'=>'🇳🇱','Israel'=>'🇮🇱','United Kingdom'=>'🇬🇧','Colombia'=>'🇨🇴','South Africa'=>'🇿🇦'];
@endphp
<div class="affiliates-reference-page">
    <header class="affiliates-page-heading">
        <div>
            <h1>Affiliates <span class="lm-attr-info-icon" title="Partners who send you leads">ⓘ</span></h1>
            <p class="lm-page-sub">Partners who send you leads, with their delivery, conversions and payouts.</p>
        </div>
        <div class="lm-head-side">
        <div class="leadmax-breadcrumb">Dashboard <span>/</span> CRM <span>/</span> <strong>Affiliates</strong></div>
        <div class="affiliates-header-buttons">
            <a href="#" onclick="document.getElementById('lm-add-affiliate-modal').style.display='flex';return false;" class="affiliates-primary-button">+ Add Affiliate</a>
            <button>↗ Export</button>
            <button>⊟ Filters</button>
        </div>
        </div>
    </header>

    <div class="affiliates-toolbar">
        <div class="affiliates-periods">
            @foreach(['today'=>'Today','yesterday'=>'Yesterday','current_week'=>'Current week','current_month'=>'Current month','last_week'=>'Last week','last_month'=>'Last month'] as $value=>$label)
                <a href="?period={{ $value }}" class="{{ $period === $value ? 'selected' : '' }}">{{ $label }}</a>
            @endforeach
            <a href="{{ request()->fullUrlWithQuery(['period'=>'custom','from'=>'']) }}" class="{{ $period==='custom'?'selected':'' }}">From date</a>
            <a href="{{ request()->fullUrlWithQuery(['period'=>'custom','to'=>'']) }}"   class="{{ $period==='custom'?'selected':'' }}">To date</a>
        </div>
    </div>

    <section class="affiliates-kpis">
        <article class="lm-card-link"><a class="lm-card-main lm-card-cover" href="{{ route('admin.leadmax.affiliates', $r) }}" aria-label="Total Affiliates"></a>
            <span>Total Affiliates</span>
            <strong>{{ number_format($totalAffiliates) }}</strong>
            <small>{!! $vsPrevious($totalAffiliates, $previousCards['affiliates']) !!}</small>
            <i class="kpi-icon people">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
            </i>
        </article>
        <article class="lm-card-link"><a class="lm-card-main lm-card-cover" href="{{ route('admin.leadmax.affiliates', [...$r, 'status' => 'active']) }}" aria-label="Active Affiliates"></a>
            <span>Active Affiliates</span>
            <strong>{{ number_format($activeCount) }}</strong>
            <small>{!! $vsPrevious($activeCount, $previousCards['active']) !!}</small>
            <i class="kpi-icon check">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
            </i>
        </article>
        <article class="lm-card-link"><a class="lm-card-main lm-card-cover" href="{{ route('admin.leadmax.leads', $r) }}" aria-label="Total Referrals"></a>
            <span>Total Referrals</span>
            <strong>{{ number_format($totalLeads) }}</strong>
            <small>{!! $vsPrevious($totalLeads, $previousCards['referrals']) !!}</small>
            <i class="kpi-icon people">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
            </i>
        </article>
        <article class="lm-card-link"><a class="lm-card-main lm-card-cover" href="{{ route('admin.leadmax.first-time-deposits', $r) }}" aria-label="FTD Clients"></a>
            <span>FTD Clients</span>
            <strong>{{ number_format($ftdTotal) }}</strong>
            <small>{!! $vsPrevious($ftdTotal, $previousCards['ftds']) !!}</small>
            <i class="kpi-icon flag">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z"/><line x1="4" y1="22" x2="4" y2="15"/></svg>
            </i>
        </article>
        <article class="lm-card-link"><a class="lm-card-main lm-card-cover" href="{{ route('admin.leadmax.attribution', ['view' => 'performance', ...$r]) }}" aria-label="Total Commission"></a>
            <span>Total Commission</span>
            <strong>${{ number_format($totalCommission, 2) }}</strong>
            <small>{!! $vsPrevious($totalCommission, $previousCards['commission'], true) !!}</small>
            <i class="kpi-icon money">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
            </i>
        </article>
    </section>

    <section class="affiliates-table-card" data-lm-bulk-page data-lm-bulk-noun="affiliate">
        <div class="affiliates-table-toolbar">
            <div class="affiliates-tabs">
                <a href="?period={{ $period }}" class="{{ $statusFilter==='all' ? 'active' : '' }}">All Affiliates <b>{{ number_format($totalAffiliates) }}</b></a>
                <a href="?period={{ $period }}&status=active" class="{{ $statusFilter==='active' ? 'active' : '' }}">Active <b>{{ number_format($activeCount) }}</b></a>
                <a href="?period={{ $period }}&status=pending" class="{{ $statusFilter==='pending' ? 'active' : '' }}">Pending <b>{{ number_format($pendingCount) }}</b></a>
                <a href="?period={{ $period }}&status=suspended" class="{{ $statusFilter==='suspended' ? 'active' : '' }}">Suspended <b>{{ number_format($suspendedCount) }}</b></a>
                <a href="?period={{ $period }}&status=top" class="{{ $statusFilter==='top' ? 'active' : '' }}">Top Performers</a>
            </div>
            <div class="affiliates-table-search">
                <form method="GET" action="" style="display:contents">
                    <input type="hidden" name="period" value="{{ $period }}">
                    <input type="hidden" name="status" value="{{ $statusFilter }}">
                    @if(request()->filled('from'))<input type="hidden" name="from" value="{{ request('from') }}">@endif @if(request()->filled('to'))<input type="hidden" name="to" value="{{ request('to') }}">@endif @if(request()->filled('country'))<input type="hidden" name="country" value="{{ request('country') }}">@endif
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Search by name, email, code..." aria-label="Search affiliates">
                    <button type="submit" aria-label="Search">⌕</button>
                </form>
                <button type="button" aria-label="Filters" data-lm-filter-toggle aria-controls="lm-affiliates-filters">⊟ Filters</button>
                <button aria-label="More options">⋮</button>
            </div>
        </div>
        @include('admin.leadmax.partials.filter-panel', ['panelId' => 'lm-affiliates-filters', 'keep' => ['period', 'from', 'to', 'search'], 'selects' => ['status' => ['Status', ['active' => 'Active', 'pending' => 'Pending', 'suspended' => 'Suspended', 'inactive' => 'Inactive']], 'country' => ['Country', $affiliateCountries->mapWithKeys(fn ($c) => [$c => $c])->all()]]])
        <div class="lm-bulk-actions" data-lm-bulk-actions hidden><span data-lm-selected-count>0 affiliates selected</span><div><button type="button" class="lm-button danger" data-lm-bulk-delete disabled><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v6M14 11v6"/><path d="M9 6V4h6v2"/></svg> Delete</button><button type="button" class="lm-button" data-lm-bulk-export><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg> Export</button><button type="button" class="lm-bulk-deselect" data-lm-bulk-deselect aria-label="Deselect all affiliates"><svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button></div></div>
        <div class="affiliates-table-scroll">
            <table>
                <thead>
                    <tr>
                        <th style="width:28px"><input type="checkbox" aria-label="Select all affiliates" data-lm-select-all></th>
                        <th>#</th>
                        <th>Name</th>
                        <th>Affiliate Code</th>
                        <th>Email</th>
                        <th>Country</th>
                        <th>Referrals</th>
                        <th>FTD's</th>
                        <th>Conversion %</th>
                        <th>Total Commission</th>
                        <th>Status</th>
                        <th>Joined Date</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($affiliates as $affiliate)
                @php
                    $conv = $affiliate->leads_count > 0 ? round($affiliate->ftd_count / $affiliate->leads_count * 100, 1) : 0;
                    $code = $affiliate->account_id ?: strtoupper(substr((string) $affiliate->id, 0, 6));
                    $flag = $countryFlags[$affiliate->country] ?? '🌐';
                    $rowStatus = strtolower($affiliate->status ?: 'active');
                    $rowNum = $loop->iteration + (($affiliates->currentPage()-1)*$affiliates->perPage());
                @endphp
                <tr>
                    <td><input type="checkbox" aria-label="Select {{ $affiliate->name }}" data-lm-select value="{{ $affiliate->id }}"></td>
                    <td><a href="{{ route('admin.leadmax.affiliates.show', $affiliate->id) }}" title="View {{ $affiliate->name }}">{{ 1000 + $rowNum }}</a></td>
                    <td><strong>{{ $affiliate->name }}</strong></td>
                    <td><span class="lm-af-code">{{ $code }}</span></td>
                    <td>{{ $affiliate->email }}</td>
                    <td><span class="lm-af-country-cell"><span class="lm-af-flag">{{ $flag }}</span>{{ $affiliate->country ?: '—' }}</span></td>
                    <td>{{ number_format($affiliate->leads_count) }}</td>
                    <td>{{ number_format($affiliate->ftd_count) }}</td>
                    <td>{{ $conv }}%</td>
                    <td>$0.00</td>
                    <td><span class="affiliate-status {{ $rowStatus }}">{{ ucfirst($rowStatus) }}</span></td>
                    <td>{{ $affiliate->created_at?->format('Y-m-d') }}</td>
                    <td class="lm-af-row-actions">
                        <button aria-label="View {{ $affiliate->name }}">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                        </button>
                        <button aria-label="Actions for {{ $affiliate->name }}">⋮</button>
                    </td>
                </tr>
                @empty
                <tr><td colspan="13" class="leadmax-no-data">No affiliates found.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="affiliates-table-footer">
            <span>Showing 1 to {{ $affiliates->count() }} of {{ number_format($affiliates->total()) }} entries</span>
            @include('admin.leadmax.partials.pagination', ['paginator' => $affiliates])
        </div>
    </section>

    <section class="affiliates-analytics">
        <article>
            <header><strong>Registrations Trend</strong><span class="chart-legend blue">● New Affiliates @if($trends['weekly'])(weekly)@endif · {{ number_format(array_sum($trends['registrations'])) }}</span></header>
            @include('admin.leadmax.partials.trend-chart', ['series' => [['blue', $trends['registrations']]], 'label' => 'Registrations Trend: '.number_format(array_sum($trends['registrations'])).' new affiliates'])
        </article>
        <article>
            <header><strong>FTD Trend</strong><span class="chart-legend green">● FTDs @if($trends['weekly'])(weekly)@endif · {{ number_format(array_sum($trends['ftds'])) }}</span></header>
            @include('admin.leadmax.partials.trend-chart', ['series' => [['green', $trends['ftds']]], 'label' => 'FTD Trend: '.number_format(array_sum($trends['ftds'])).' ftds'])
        </article>
        <article class="top-affiliates">
            <header><strong>Top Affiliates (by FTD)</strong><a href="{{ route('admin.leadmax.first-time-deposits', $r) }}">View All</a></header>
            <ol>
                @forelse($topAffiliates as $top)
                    <li><b>{{ $loop->iteration }}</b><span><a href="{{ route('admin.leadmax.affiliates.show', $top['id']) }}">{{ $top['name'] }}</a></span><i title="FTD clients">{{ number_format($top['ftds']) }}</i><strong title="Commission">${{ number_format($top['commission'], 2) }}</strong></li>
                @empty
                    <li class="is-empty"><span>No FTDs from affiliates in this period.</span></li>
                @endforelse
            </ol>
        </article>
    </section>
    {{-- Add Affiliate Modal --}}
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
</div>
</x-leadmax-layout>

{{-- Finances filter row (Figma "Filter Controls Row" 3294:25557). The Brands dropdown is replaced by payment methods (single-brand tenant). --}}
@php
    use App\Http\Controllers\Admin\LeadMaxFinanceController as Finance;
    $fi = fn (string $name, $w, $h = null) => '<img src="'.asset('images/leadmax/finance/'.$name.'.svg').'" width="'.$w.'" height="'.($h ?? $w).'" alt="">';
    $statuses = match ($tab) {
        'payouts' => ['pending' => 'Pending', 'approved' => 'Approved', 'paid' => 'Paid', 'rejected' => 'Rejected'],
        'wallets' => ['active' => 'Active', 'inactive' => 'Inactive', 'suspended' => 'Suspended'],
        'reports' => ['ready' => 'Ready', 'generating' => 'Generating', 'failed' => 'Failed'],
        default => Finance::STATUSES,
    };
    $types = match ($tab) {
        'all' => ['deposit' => 'Deposits', 'withdrawal' => 'Withdrawals', 'adjustment' => 'Adjustments', 'other' => 'Other'],
        'reports' => collect(Finance::REPORT_TYPES)->map(fn ($t) => $t[0])->all(),
        default => [],
    };
    $panelActive = collect(['country', 'created_by', 'manager_id'])->contains(fn ($k) => request()->filled($k) && request($k) !== 'all');
@endphp
<form method="GET" class="lm-fin-filters" role="search">
    @if($tab !== 'all')<input type="hidden" name="tab" value="{{ $tab }}">@endif
    @foreach(['range', 'per_page'] as $keep)@if(request()->filled($keep))<input type="hidden" name="{{ $keep }}" value="{{ request($keep) }}">@endif @endforeach
    <label class="lm-fin-search">{!! $fi('search', 14) !!}<input type="search" name="search" value="{{ request('search') }}" placeholder="Search by ID, user, email or reference..." aria-label="Search"></label>
    @if($types)
        <span class="lm-fin-select"><select name="type" aria-label="Type" data-lm-autosubmit><option value="all">{{ $tab === 'reports' ? 'All Report Types' : 'All Transaction Types' }}</option>@foreach($types as $v => $l)<option value="{{ $v }}" @selected(request('type') === $v)>{{ $l }}</option>@endforeach</select>{!! $fi('chevron', 8, 4.5) !!}</span>
    @endif
    @if($tab !== 'fees')
        <span class="lm-fin-select"><select name="status" aria-label="Status" data-lm-autosubmit><option value="all">All Statuses</option>@foreach($statuses as $v => $l)<option value="{{ $v }}" @selected(request('status') === $v)>{{ $l }}</option>@endforeach</select>{!! $fi('chevron', 8, 4.5) !!}</span>
    @endif
    @if(in_array($tab, ['all', 'deposits', 'withdrawals', 'adjustments'], true))
        <span class="lm-fin-select"><select name="method" aria-label="Payment method" data-lm-autosubmit><option value="all">All Methods</option>@foreach(Finance::METHODS as $v => $l)<option value="{{ $v }}" @selected(request('method') === $v)>{{ $l }}</option>@endforeach</select>{!! $fi('chevron', 8, 4.5) !!}</span>
    @endif
    <label class="lm-fin-date">{!! $fi('calendar', 14) !!}<input type="text" name="from" value="{{ request('from') }}" placeholder="From date" aria-label="From date" data-lm-date-submit>{!! $fi('chevron', 8, 4.5) !!}</label>
    <label class="lm-fin-date">{!! $fi('calendar', 14) !!}<input type="text" name="to" value="{{ request('to') }}" placeholder="To date" aria-label="To date" data-lm-date-submit>{!! $fi('chevron', 8, 4.5) !!}</label>
    @if($tab !== 'reports')<button type="button" class="lm-fin-btn" data-lm-filter-toggle aria-controls="lm-fin-panel" aria-expanded="{{ $panelActive ? 'true' : 'false' }}">{!! $fi('filters', 14) !!}<span>Filters</span></button>@endif
    <details class="lm-fin-menu">
        <summary class="lm-fin-icon-btn" aria-label="More options">{!! $fi('more', 16) !!}</summary>
        <div>
            @if(! in_array($tab, ['fees', 'reports'], true))<a href="{{ route('admin.leadmax.finances.export', request()->query() + ['tab' => $tab]) }}">Export this view (CSV)</a>@endif
            @if(is_admin())<a href="{{ route('admin.leadmax.finances', ['tab' => 'reports', 'dialog' => 'report']) }}">Generate a report</a>@endif
            <a href="{{ route('admin.leadmax.finances', array_filter(['tab' => $tab === 'all' ? null : $tab])) }}">Clear all filters</a>
        </div>
    </details>
    <button type="submit" hidden>Search</button>
</form>
@if($tab !== 'reports')
    <form method="GET" class="lm-fin-panel" id="lm-fin-panel" @unless($panelActive) hidden @endunless>
        @foreach(request()->except(['country', 'created_by', 'manager_id', 'page']) as $name => $value)@if(is_scalar($value))<input type="hidden" name="{{ $name }}" value="{{ $value }}">@endif @endforeach
        <label>Country<select name="country"><option value="all">All countries</option>@foreach($filterCountries as $c)<option value="{{ $c }}" @selected(request('country') === $c)>{{ $c }}</option>@endforeach</select></label>
        <label>Affiliate<select name="created_by"><option value="all">All affiliates</option>@foreach($filterAffiliates as $u)<option value="{{ $u->id }}" @selected(request('created_by') === $u->id)>{{ $u->name }}</option>@endforeach</select></label>
        <label>Agent<select name="manager_id"><option value="all">All agents</option>@foreach($filterOwners as $u)<option value="{{ $u->id }}" @selected(request('manager_id') === $u->id)>{{ $u->name }}</option>@endforeach</select></label>
        <div class="lm-fin-panel-actions"><button type="submit" class="lm-fin-btn is-primary">Apply</button><a class="lm-fin-btn" href="{{ request()->fullUrlWithQuery(['country' => null, 'created_by' => null, 'manager_id' => null, 'page' => null]) }}">Reset</a></div>
    </form>
@endif

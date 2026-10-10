@php
    $query = request()->except(['lead','tab','dialog']);
    // The same list also serves Attribution's Incoming Leads; links stay on whichever page shows it.
    $listRouteName ??= 'admin.leadmax.leads';
    $url = fn(array $extra = []) => route($listRouteName, array_merge($query, $extra));
    $statusName = fn($value) => in_array(strtolower($value ?? ''), ['','pending']) ? 'new' : (strtolower($value) === 'rejected' ? 'disqualified' : strtolower($value));
    // Never a slice of the UUID: a lead's ID is its account number. AccountNumber assigns one
    // on creation and leadmax:backfill-account-ids filled the older rows, so the dash marks a
    // genuine gap instead of dressing a UUID up as an ID.
    $leadId = fn($lead) => $lead->account_id ?: '--';
    $dialog = request('dialog');
    // 'status' is excluded on purpose: the stage is chosen by the KPI cards above, not in the
    // filter panel, so picking a card must not light up the Filters button or make it read "(1)".
    // Filters are multi-select now, so a value may be an array. `!== 'all'` is vacuously true
    // for an array and would count an empty selection as an active filter.
    $filterValuesOf = fn($key) => collect(request($key))->flatten()
        ->map(fn($value) => trim((string) $value))
        ->reject(fn($value) => $value === '' || $value === 'all')->values();
    // from/to live in the advanced filter panel now, so they count towards its badge.
    $activeFilterCount = collect(\App\Http\Controllers\Admin\LeadMaxLeadController::LIST_FILTERS)
        ->reject(fn($key) => $key === 'status')
        ->filter(fn($key) => in_array($key, ['search', 'comment_from', 'comment_to'], true)
            ? request()->filled($key)
            : $filterValuesOf($key)->isNotEmpty())->count();
    // Hidden carry-overs. A plain value="{{ request($key) }}" renders the string "Array" once a
    // filter is multi-select, which would silently filter on nothing.
    $carryInputs = function (array $keys) {
        $html = '';
        foreach ($keys as $key) {
            $value = request($key);
            $isList = is_array($value);
            foreach ($isList ? $value : [$value] as $single) {
                if (! is_scalar($single) || trim((string) $single) === '') {
                    continue;
                }
                $html .= '<input type="hidden" name="'.e($key).($isList ? '[]' : '').'" value="'.e($single).'">';
            }
        }
        return $html;
    };
    // The panel starts open when it is doing something, so a shared filtered URL explains itself.
    $filtersOpen = $filtered;
    // The stage currently selected via the cards. Blank means "all stages".
    $activeStage = in_array(request('status'), \App\Http\Controllers\Admin\LeadMaxLeadController::STATUSES, true)
        ? request('status') : null;

    // Sorting. Mirrors the controller's defaults so the arrows show what the query actually did.
    $sortBy = in_array(request('sort'), \App\Http\Controllers\Admin\LeadMaxLeadController::SORTABLE, true)
        ? request('sort') : 'account_id';
    $sortDir = request('direction') === 'asc' ? 'asc' : 'desc';

    // [heading, sort key or null if not sortable, kind]. The kind only decides which way the
    // FIRST click sorts: A–Z reads naturally for text, while dates and numbers are almost
    // always wanted newest/highest first.
    // Retention Status and Stage both render the same `status` column, so both sort by it.
    // [heading, sort key or null, kind, width]. The table is table-layout:fixed, which divides
    // the width evenly unless told otherwise -- with this many columns that squeezed the longest
    // headings into each other. Each column states what it needs and the table is sized to the
    // sum, so adding a column widens the table instead of crushing its neighbours.
    $columns = [
        ['Lead ID', 'account_id', 'num', 120],
        ['First Name', 'first_name', 'text', 130],
        ['Last Name', 'last_name', 'text', 130],
        ['Phone', 'phone', 'text', 175],
        ['Email', 'email', 'text', 200],
        ['Reg Date', 'created_at', 'date', 160],
        ['Country', 'country', 'text', 130],
        ['Agent', 'agent', 'text', 155],
        ['Lead Status', 'label', 'text', 145],
        ['Retention Status', 'status', 'text', 165],
        ['Date of Last Comment', 'last_comment_at', 'date', 190],
        ['Affiliate', 'affiliate', 'text', 140],
        ['Source', 'source', 'text', 140],
        ['Campaign', 'campaign', 'text', 140],
        ['Call Centre', 'desk', 'text', 140],
        ['Platform Type', 'platform', 'text', 150],
        ['Stage', 'status', 'text', 120],
    ];
    // Checkbox and row actions bracket the sortable columns and are not in that list.
    $selectWidth = 40;
    $actionsWidth = 90;
    $tableWidth = $selectWidth + $actionsWidth + collect($columns)->sum(fn ($column) => $column[3]);
    // Option lists for the advanced filter. Built here so the component only ever receives
    // [value => label] and never has to know where the data came from.
    $leadStatusOptions = collect($leadStatuses)->mapWithKeys(fn($name) => [$name => ucfirst($name)])
        ->prepend('Not set', \App\Http\Controllers\Admin\LeadMaxLeadController::LEAD_STATUS_NONE)->all();
    $leadStatusTones = collect($leadStatusOptions)->map(fn ($label, $value) => \App\Support\LeadMax\LeadStatusPalette::tone($value === \App\Http\Controllers\Admin\LeadMaxLeadController::LEAD_STATUS_NONE ? null : $value))->all();
    $countryOptions = collect($countries)->mapWithKeys(fn($name) => [$name => $name])->all();
    $kycOptions = \App\Support\LeadMax\LeadFields::KYC_STATUSES;
    $sourceOptions = collect($sources)->mapWithKeys(fn($name) => [$name => ucfirst($name)])->all();
    $affiliateOptions = collect($affiliates)->mapWithKeys(fn($a) => [$a->id => $a->name])->all();
    // "Unassigned" is a real choice here, and multi-select means it can sit alongside agents.
    $agentOptions = collect($members)->mapWithKeys(fn($m) => [$m->id => $m->name])
        ->prepend('Unassigned', 'unassigned')->prepend('Assigned (any agent)', 'assigned')->all();
    $comparisonLabel = match($period) {
        'today' => 'vs yesterday',
        'yesterday' => 'vs previous day',
        'current_week' => 'vs last week',
        'last_week' => 'vs previous week',
        'current_month' => 'vs last month',
        'last_month' => 'vs previous month',
        'custom' => 'vs previous period',
        default => 'all time',
    };
@endphp
<div class="lm-leads-page" data-lm-leads @if($filtered) data-lm-bulk-all="1" @endif>
    @if(session('leadmax_import_result'))<aside class="lm-owner-toast" role="status"><x-leadmax-lead-icon name="success" /><div><strong>Import finished</strong><p>{{ session('leadmax_import_result') }}</p></div><button type="button" aria-label="Dismiss notification" data-lm-toast-close><x-leadmax-lead-icon name="close" /></button></aside>@endif
    @if(session('leadmax_owner_assigned'))<aside class="lm-owner-toast" role="status"><x-leadmax-lead-icon name="success" /><div><strong>Agent assigned</strong><p>{{ session('leadmax_owner_assigned') }}</p></div><button type="button" aria-label="Dismiss notification" data-lm-toast-close><x-leadmax-lead-icon name="close" /></button></aside>@endif
    {{-- Attribution shows this list under its own heading and tabs. --}}
    @unless($hideLeadsHeading ?? false)<div class="lm-leads-title"><h1>Leads</h1><nav class="lm-set-crumbs" aria-label="Breadcrumb"><a href="{{ route('admin.leadmax.dashboard') }}">Dashboard</a><i>/</i><span>CRM</span><i>/</i><b>Leads</b></nav></div><p class="lm-page-sub">Every lead in your CRM: search, filter, assign and follow up.</p>@endunless
    <div class="lm-leads-controls">
        <form class="leadmax-periods leadmax-dashboard-periods" method="GET">
            {{-- A GET form posts only its own fields, so without these a period change dropped the
                 stage, the search term and every filter. The period is the OUTER scope: changing it
                 re-dates what you are looking at, it does not clear what you asked for. --}}
            {!! $carryInputs(['unread', 'status', 'search', 'lead_status', 'country', 'source', 'created_by', 'manager_id', 'ftd', 'kyc', 'comment_from', 'comment_to', 'per_page', 'from', 'to', 'sort', 'direction']) !!}
            @foreach(['all_time'=>'All','today'=>'Today','yesterday'=>'Yesterday','current_week'=>'Current week','current_month'=>'Current month','last_week'=>'Last week','last_month'=>'Last month'] as $value=>$label)
                <button name="period" value="{{ $value }}" class="{{ $period===$value?'is-selected':'' }}">{{ $label }}</button>
            @endforeach
            {{-- The reporting window belongs with the period buttons it replaces, not in the
                 filter panel: picking dates IS picking a period. The sr-only button is what the
                 global date handler submits once both dates are set. --}}
            <div class="leadmax-custom-period">
                <input name="from" placeholder="From date" aria-label="From date" value="{{ request('from') }}" data-leadmax-date>
                <input name="to" placeholder="To date" aria-label="To date" value="{{ request('to') }}" data-leadmax-date>
                <button type="submit" name="period" value="custom" class="sr-only">Apply custom dates</button>
            </div>
        </form>
        <div class="lm-button-row">
            <a class="lm-button primary" href="{{ $url(['dialog'=>'add']) }}"><x-leadmax-lead-icon name="plus" />New Lead</a>
            @php $driveOn = app(\App\Services\GoogleDrive\GoogleDriveService::class)->isConnected(app('company')->id); @endphp
            @if($driveOn)
                {{-- One Export control offering a destination, rather than two buttons that
                     both say "export" and leave the reader to work out the difference. --}}
                <details class="lm-export-menu" data-lm-drive-scope>
                    <summary class="lm-button"><x-leadmax-lead-icon name="export" />Export</summary>
                    <div class="lm-export-menu-panel">
                        <a class="lm-export-menu-item" data-lm-export data-lm-export-selected href="{{ route('admin.leadmax.leads.export',$query) }}">
                            <strong>Download to this device</strong>
                            <span>Saves a CSV to your downloads</span>
                        </a>
                        <button type="button" class="lm-export-menu-item"
                                data-lm-drive-pick="folder"
                                data-lm-drive-form="lm-leads-export-drive"
                                data-lm-drive-submit="true"
                                data-lm-confirm-export="drive">
                            <strong>Save to Google Drive…</strong>
                            <span>Choose the folder it goes in</span>
                        </button>
                    </div>
                    <p class="lm-export-menu-error" data-lm-drive-error hidden role="alert"></p>
                </details>

                {{-- POST, because this writes a file into Drive rather than just reading it.
                     Carries the same filters as the download link so both export the same rows;
                     the picker fills folder_id, and the JS copies across any ticked rows. --}}
                <form id="lm-leads-export-drive" method="POST" action="{{ route('admin.leadmax.leads.export.drive') }}" hidden data-lm-drive-include-selected data-lm-drive-sync-filters>
                    @csrf
                    {{-- A multi-select filter arrives as an array, which is_scalar() rejected --
                         so every array filter was dropped here and the Drive export quietly sent
                         an unfiltered set. The JS rebuilds these from the address bar at click
                         time too, because live filtering moves the query without re-rendering
                         this form. --}}
                    @foreach($query as $qKey => $qValue)
                        @foreach(is_array($qValue) ? $qValue : [$qValue] as $qSingle)
                            @if(is_scalar($qSingle) && trim((string) $qSingle) !== '')
                                <input type="hidden" name="{{ $qKey }}{{ is_array($qValue) ? '[]' : '' }}" value="{{ $qSingle }}" data-lm-drive-filter>
                            @endif
                        @endforeach
                    @endforeach
                    <input type="hidden" name="folder_id" value="">
                    <input type="hidden" name="folder_name" value="">
                </form>
            @else
                <a class="lm-button" data-lm-export data-lm-export-selected href="{{ route('admin.leadmax.leads.export',$query) }}"><x-leadmax-lead-icon name="export" />Export</a>
            @endif
            {{-- Deliberately not behind the Upload Leads switch: the dialog import runs here and
                 stays available when the full Upload Leads page is switched off. --}}
            <a class="lm-button lm-import-button" href="{{ $url(['dialog'=>'import']) }}"><x-leadmax-lead-icon name="export" />Import</a>
        </div>
    </div>
    {{-- Search sits on its own row under the periods, with the panel toggle beside it. The input
         is bound to the filter form with form=, so although it lives out here it is still one of
         that form's fields and live filtering needs no special case for it. --}}
    <div class="lm-leads-searchbar">
        <div class="lm-filter-search">
            <span class="lm-filter-search-icon" aria-hidden="true"><x-leadmax-lead-icon name="search" /></span>
            <input type="search" name="search" form="lm-advanced-filter" value="{{ request('search') }}" placeholder="Search by name, phone, email or ID..." aria-label="Search leads" autocomplete="off" data-lm-filter-input>
        </div>
        <button type="button" class="lm-button lm-advanced-filter {{ $activeFilterCount ? 'is-active' : '' }}"
                data-lm-filters title="Advanced Filter"
                aria-expanded="{{ $filtersOpen ? 'true' : 'false' }}" aria-controls="lm-advanced-filter">
            <x-leadmax-lead-icon name="filter" />
            <span>Advanced Filter</span>
            @if($activeFilterCount)
                <span class="lm-filter-count">{{ $activeFilterCount }}</span>
            @endif
        </button>
    </div>
    {{-- Advanced filter. Deliberately a sibling of the KPI cards rather than a child of the leads
         list: opening it pushes the cards down, and it sits outside the region the live refresh
         swaps, so it keeps its state and the cursor while results reload underneath it. --}}
    <form class="lm-filter-panel" id="lm-advanced-filter" method="GET" data-lm-filter-panel data-lm-filter-form @unless($filtersOpen) hidden @endunless>
        {{-- No submit button: changing anything re-queries straight away. These carry what the
             panel has no control over, so a refiltered URL keeps the stage, search and sort. --}}
        {!! $carryInputs(['status']) !!}
        <div class="lm-filter-grid">
            <x-leadmax-multiselect name="lead_status" label="Lead Status" :options="$leadStatusOptions" :selected="$filterValuesOf('lead_status')" :tones="$leadStatusTones" />
            <x-leadmax-multiselect name="country" label="Country" :options="$countryOptions" :selected="$filterValuesOf('country')" :flags="true" />
            <x-leadmax-multiselect name="source" label="Source" :options="$sourceOptions" :selected="$filterValuesOf('source')" />
            <x-leadmax-multiselect name="created_by" label="Affiliate" :options="$affiliateOptions" :selected="$filterValuesOf('created_by')" />
            <x-leadmax-multiselect name="manager_id" label="Agent" :options="$agentOptions" :selected="$filterValuesOf('manager_id')" />
            <x-leadmax-multiselect name="ftd" label="FTD" :options="['yes' => 'Deposited', 'no' => 'No deposit']" :selected="$filterValuesOf('ftd')" />
            <x-leadmax-multiselect name="kyc" label="KYC" :options="$kycOptions" :selected="$filterValuesOf('kyc')" />
            <div class="lm-filter-field">
                <label class="lm-filter-label" for="lm-filter-comment-from">Comment from</label>
                <input id="lm-filter-comment-from" type="date" name="comment_from" value="{{ request('comment_from') }}" data-lm-filter-date>
            </div>
            <div class="lm-filter-field">
                <label class="lm-filter-label" for="lm-filter-comment-to">Comment to</label>
                <input id="lm-filter-comment-to" type="date" name="comment_to" value="{{ request('comment_to') }}" data-lm-filter-date>
            </div>
            <div class="lm-filter-field">
                <label class="lm-filter-label" for="lm-filter-sort">Sort by</label>
                <select id="lm-filter-sort" name="sort" data-lm-filter-sort>
                    @foreach(\App\Http\Controllers\Admin\LeadMaxLeadController::SORT_LABELS as $sortValue => $sortText)
                        <option value="{{ $sortValue }}" @selected($sortBy === $sortValue)>{{ $sortText }}</option>
                    @endforeach
                </select>
            </div>
            <div class="lm-filter-field">
                <label class="lm-filter-label" for="lm-filter-direction">Order</label>
                <select id="lm-filter-direction" name="direction" data-lm-filter-direction>
                    <option value="desc" @selected($sortDir === 'desc')>Descending</option>
                    <option value="asc" @selected($sortDir === 'asc')>Ascending</option>
                </select>
            </div>
        </div>
        <div class="lm-filter-actions">
            <span class="lm-filter-busy" data-lm-filter-busy role="status" aria-live="polite"></span>
            <a class="lm-button" data-lm-filter-reset href="{{ route($listRouteName, ['period' => $period]) }}">Reset filter</a>
        </div>
    </form>
    <section class="lm-leads-kpis" aria-label="Lead statistics">
    @foreach(['total'=>'Total Leads','new'=>'New Leads','contacted'=>'Contacted','qualified'=>'Qualified','converted'=>'Converted','disqualified'=>'Disqualified'] as $key=>$label)
        @php
            $before=$previousStats[$key]; $delta=$before ? ($stats[$key]-$before)/$before*100 : null;
            // "Total Leads" is the cleared state; every other card selects its stage.
            $isActive = $key === 'total' ? $activeStage === null : $activeStage === $key;
            // Drop the page too, or switching stage can land you on a page the smaller set has not got.
            $cardHref = $url(['status' => $key === 'total' ? null : $key, 'page' => null]);
        @endphp
        <article class="{{ $isActive ? 'is-active' : '' }}">{{-- the link covers the card; see .lm-kpi-link --}}<a class="lm-kpi-link" href="{{ $cardHref }}" @if($isActive) aria-current="true" @endif><span class="sr-only">{{ $key === 'total' ? 'Show leads at every stage' : 'Show only '.$label.' leads' }}</span></a><div><x-leadmax-kpi-icon :name="$key" /><div><span>{{ $label }}</span><strong>{{ number_format($stats[$key]) }}</strong></div></div><footer><span>{{ $comparisonLabel }}</span><b class="{{ $delta===null||round($delta,1)==0?'is-flat':($delta<0?'negative':'') }}">@if($delta===null) N/A @elseif(round($delta,1)==0)0.0%@else<x-leadmax-lead-icon :name="$delta<0?'down':'up'" />{{ number_format(abs($delta),1) }}%@endif</b></footer></article>
    @endforeach
    </section>
    <section class="lm-leads-list"><header><h2>Leads List</h2><span class="lm-count" data-lm-total="{{ $leads->total() }}">{{ number_format($leads->total()) }} Leads</span><label class="lm-per-page"><span>Rows</span><select name="per_page" form="lm-advanced-filter" data-lm-filter-bound aria-label="Rows per page">@foreach(\App\Http\Controllers\Admin\LeadMaxLeadController::PER_PAGE_OPTIONS as $option)<option value="{{ $option }}" @selected($perPage === $option)>{{ $option }}</option>@endforeach</select></label><button type="button" class="lm-button lm-bulk-open" data-lm-bulk-open aria-expanded="false">Bulk action</button></header><div class="lm-leads-toprow">@include('admin.leadmax.partials.leads-pagination',['paginator'=>$leads,'placement'=>'top'])</div>
        
        <div class="lm-bulk-actions" data-lm-bulk-actions data-lm-bulk-url="{{ route('admin.leadmax.leads.bulk') }}" data-lm-total="{{ $leads->total() }}" @if($filtered) data-lm-bulk-filtered="1" @else hidden @endif><span><span data-lm-selected-count>@if($filtered)All {{ number_format($leads->total()) }} filtered {{ \Illuminate\Support\Str::plural('lead', $leads->total()) }}: choose an action, or tick rows to act on just those @else 0 leads selected @endif</span> <button type="button" class="lm-bulk-matching" data-lm-select-matching hidden>Select all {{ number_format($leads->total()) }} matching leads</button></span><div>
            @if(is_admin())<select aria-label="Assign agent" data-lm-bulk-set="assign"><option value="">Assign / reassign agent…</option>@foreach($members as $member)<option value="{{ $member->id }}">{{ $member->name }}</option>@endforeach</select>@endif
            <select aria-label="Set lead status" data-lm-bulk-set="lead_status"><option value="">Set lead status…</option>@foreach($leadStatuses as $option)<option value="{{ $option }}">{{ ucfirst($option) }}</option>@endforeach<option value="{{ \App\Http\Controllers\Admin\LeadMaxLeadController::LEAD_STATUS_NONE }}">Clear (not set)</option></select>
            <select aria-label="Set stage" data-lm-bulk-set="stage"><option value="">Set stage…</option>@foreach($statuses as $option)<option value="{{ $option }}">{{ ucfirst($option) }}</option>@endforeach</select>
            @if(is_admin())<button type="button" class="lm-button danger" data-lm-bulk-delete><x-leadmax-lead-icon name="delete" />Delete</button>@endif<button type="button" class="lm-button" data-lm-bulk-export> <x-leadmax-lead-icon name="export" />Export</button><button type="button" class="lm-bulk-deselect" data-lm-bulk-deselect aria-label="Deselect selected leads"><x-leadmax-lead-icon name="close" /></button></div></div>
        @if(is_admin())<form class="lm-bulk-delete-form" data-lm-bulk-delete-form hidden><span>Deleting leads cannot be undone. Leads with deposits, trades or a funded account are skipped.</span><input type="password" name="password" autocomplete="current-password" placeholder="Your password to confirm" aria-label="Your password to confirm" required><button class="lm-button danger">Delete leads</button><button type="button" class="lm-button" data-lm-bulk-delete-cancel>Cancel</button></form>@endif
        <p class="lm-bulk-result" role="status" data-lm-bulk-result hidden></p>
        <div class="lm-leads-scroll-top" data-lm-scroll-top aria-label="Horizontal table scrollbar"><div></div></div>
        <div class="lm-leads-scroll" data-lm-table-scroll><table class="lm-updated-leads-table" style="min-width:{{ $tableWidth }}px"><colgroup><col style="width:{{ $selectWidth }}px">@foreach($columns as $index => $column)<col data-lm-col="{{ $index }}" style="width:{{ $column[3] }}px">@endforeach<col style="width:{{ $actionsWidth }}px"></colgroup><thead><tr><th><input type="checkbox" class="lm-ftd-checkbox" aria-label="Select all leads" data-lm-select-all></th>@foreach($columns as [$heading, $sortKey, $kind, $width])
@php
    $isSorted = $sortKey && $sortBy === $sortKey;
    $firstClick = $kind === "text" ? "asc" : "desc";
    $nextDir = $isSorted ? ($sortDir === "asc" ? "desc" : "asc") : $firstClick;
@endphp
@if($sortKey)
<th class="lm-sortable{{ $isSorted ? ' is-sorted is-'.$sortDir : '' }}" data-lm-col="{{ $loop->index }}" draggable="true" aria-sort="{{ $isSorted ? ($sortDir === 'asc' ? 'ascending' : 'descending') : 'none' }}"><a href="{{ $url(['sort'=>$sortKey,'direction'=>$nextDir,'page'=>null]) }}" draggable="false" data-lm-sort><span>{{ $heading }}</span><svg class="lm-sort-arrows" viewBox="0 0 11 14" width="14" height="17.8" fill="none" stroke="currentColor" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path class="up" d="M3 9.5V2.2M1.2 4L3 2.2 4.8 4"/><path class="down" d="M8 4.5v7.3M9.8 10 8 11.8 6.2 10"/></svg><span class="sr-only">Sort by {{ $heading }} {{ $nextDir === 'asc' ? 'ascending' : 'descending' }}</span></a></th>
@else
<th data-lm-col="{{ $loop->index }}" draggable="true">{{ $heading }}</th>
@endif
@endforeach
<th>Action</th></tr></thead><tbody>
        @forelse($leads as $lead) @php $lastComment = $lead->last_comment_at ? \Carbon\Carbon::parse($lead->last_comment_at) : null; $agent = $lead->manager; $agentName = $agent?->name ?: "Unassigned"; $agentInitials = $agent ? strtoupper(substr($agent->first_name, 0, 1).substr($agent->last_name, 0, 1)) : "--"; $presence = $lead->is_online ? "online" : ($lead->last_seen && $lead->last_seen->gt(now()->subMinutes(30)) ? "away" : "offline"); $leadPhone = \App\Support\LeadMax\Phone::parts($lead->phone, $lead->country); $leadEmail = str_ends_with($lead->email ?? "", "-contact.invalid") ? "" : (string) $lead->email;$registrationDate = $lead->created_at?->format("Y-m-d H:i:s"); @endphp <tr><td><input type="checkbox" class="lm-ftd-checkbox" aria-label="Select lead {{ $leadId($lead) }}" data-lm-select value="{{ $lead->id }}"></td><td><a class="lm-id" href="{{ route("admin.leadmax.leads.show", $lead->id) }}">@if(isset($unreadLeadIds[$lead->id]))<span class="lm-unread-dot" title="New lead — not viewed"></span><span class="sr-only">New lead — not viewed: </span>@endif{{ $leadId($lead) }}</a></td><td dir="auto"><a class="lm-name-cell lm-name-link" href="{{ route("admin.leadmax.leads.show", $lead->id) }}"><i class="lm-presence {{ $presence }}"></i><span>{{ $lead->first_name }}</span></a></td><td dir="auto"><a class="lm-name-link" href="{{ route("admin.leadmax.leads.show", $lead->id) }}">{{ $lead->last_name }}</a></td><td class="lm-phone-cell"><span class="lm-cell-row" @if($leadPhone['display']) title="{{ $leadPhone['display'] }}" @endif><x-leadmax-lead-icon name="phone" /><x-leadmax-flag :country="$leadPhone['iso']" class="lm-phone-flag" :width="16" /><span data-lm-phone data-lm-copy-value="{{ $leadPhone['raw'] }}">{{ $leadPhone['display'] }}</span></span></td><td class="lm-email-cell">@if($leadEmail)<span class="lm-cell-row"><a class="lm-email-link" href="mailto:{{ $leadEmail }}" aria-label="Email {{ $leadEmail }}" title="Email {{ $leadEmail }}"><x-leadmax-lead-icon name="mail" /></a><span data-lm-copy-value="{{ $leadEmail }}">{{ $leadEmail }}</span></span>@endif</td><td>{{ $registrationDate }}</td><td><span class="lm-country"><x-leadmax-flag :country="$lead->country" class="lm-country-flag" /><span class="lm-country-name">{{ $lead->country ?: "Unknown" }}</span></span></td><td data-lm-cell="manager_id"><span class="lm-agent{{ $agent ? '' : ' is-unassigned' }}"><span class="lm-agent-avatar"><b>{{ $agentInitials }}</b></span><span class="lm-agent-name">{{ $agentName }}</span></span></td><td data-lm-cell="lead_status">@if($lead->label)<span class="lm-lead-status {{ strtolower($lead->label) === "no answer" ? "no-answer" : "" }}" data-lm-tone="{{ \App\Support\LeadMax\LeadStatusPalette::tone($lead->label) }}">{{ ucfirst($lead->label) }}</span>@else<span class="lm-lead-status is-unset" data-lm-tone="unset">Not set</span>@endif</td><td class="lm-retention-status {{ $statusName($lead->status) }}" data-lm-cell="status">{{ ucfirst($statusName($lead->status)) }}</td><td>{{ $lastComment?->format("Y-m-d H:i:s") ?: "-" }}</td><td class="lm-wrap-cell"><span>{{ $lead->affiliate?->name ?: "Direct" }}</span></td><td class="lm-wrap-cell"><span>{{ $lead->source ?: "Direct" }}</span></td><td class="lm-wrap-cell"><span>{{ $lead->campaign ?: "-" }}</span></td><td class="lm-wrap-cell"><span>{{ $lead->desk ?: "-" }}</span></td><td class="lm-wrap-cell"><span>{{ $lead->platform ?: "-" }}</span></td><td><span class="lm-status {{ $statusName($lead->status) }}">{{ ucfirst($statusName($lead->status)) }}</span></td><td class="lm-row-actions"><a aria-label="View lead {{ $leadId($lead) }}" href="{{ route("admin.leadmax.leads.show", $lead->id) }}"><x-leadmax-lead-icon name="eye" /></a><button data-lm-row-menu="lm-menu-{{ $lead->id }}" aria-label="Actions for lead {{ $leadId($lead) }}" aria-expanded="false"><x-leadmax-lead-icon name="dots" /></button></td></tr>
        @empty<tr><td colspan="11" class="lm-no-leads">No leads found for this period.</td></tr>@endforelse
        </tbody></table></div>
        <div data-lm-inline-config data-base-url="{{ url('/panel/leadmax/leads') }}" data-can-assign="{{ is_admin() ? '1' : '0' }}" data-status-tones="{{ json_encode(\App\Support\LeadMax\LeadStatusPalette::TONES) }}" data-state="{{ base64_encode(json_encode($leads->getCollection()->mapWithKeys(fn ($item) => [$item->id => ['manager_id' => $item->manager_id, 'lead_status' => $item->label, 'status' => $statusName($item->status)]])->all())) }}" hidden>
            <select data-lm-inline-proto="manager_id" aria-label="Change agent"><option value="" disabled>Unassigned</option>@foreach($members as $member)<option value="{{ $member->id }}">{{ $member->name }}</option>@endforeach</select>
            <select data-lm-inline-proto="lead_status" aria-label="Change lead status"><option value="" data-lm-tone="unset">Not set</option>@foreach($leadStatuses as $option)<option value="{{ $option }}" data-lm-tone="{{ \App\Support\LeadMax\LeadStatusPalette::tone($option) }}">{{ ucfirst($option) }}</option>@endforeach</select>
            <select data-lm-inline-proto="status" aria-label="Change retention status">@foreach($statuses as $option)<option value="{{ $option }}">{{ ucfirst($option) }}</option>@endforeach</select>
        </div>
        @include('admin.leadmax.partials.leads-pagination',['paginator'=>$leads])
    </section>
    @foreach($leads as $lead)<div class="lm-row-menu" id="lm-menu-{{ $lead->id }}" hidden>
        <a href="{{ route('admin.leadmax.leads.show', $lead->id) }}"><x-leadmax-lead-icon name="eye" />View lead</a>
        <a href="{{ $url(['lead'=>$lead->id,'dialog'=>'change-password']) }}"><x-leadmax-lead-icon name="edit" />Change Password</a>
        <a href="{{ $url(['lead'=>$lead->id,'tab'=>'comments']) }}"><x-leadmax-lead-icon name="comment" />Add comment</a>
        @if(is_admin())<a href="{{ $url(['lead'=>$lead->id,'dialog'=>'assign']) }}"><x-leadmax-lead-icon name="assign" />Assign agent</a>@endif
        <a href="{{ $url(['lead'=>$lead->id,'dialog'=>'lead-status']) }}"><x-leadmax-lead-icon name="status" />Change lead status</a>
        <a href="{{ $url(['lead'=>$lead->id,'dialog'=>'status']) }}"><x-leadmax-lead-icon name="status" />Change stage</a>
        <a href="#" data-lm-copy-link="{{ route('admin.leadmax.leads.copy-link', $lead->id) }}"><x-leadmax-lead-icon name="eye" />Copy Link</a>
        <a href="#" data-lm-send-credentials="{{ route('admin.leadmax.leads.send-credentials', $lead->id) }}"><x-leadmax-lead-icon name="assign" />Send Credentials</a>
        @if(is_admin())<a class="lm-row-menu-danger" href="{{ $url(['lead'=>$lead->id,'dialog'=>'delete']) }}"><x-leadmax-lead-icon name="delete" />Delete lead</a>@endif
    </div>@endforeach
    @if($selected || in_array($dialog, ['add', 'import'], true))@include('admin.leadmax.partials.lead-dialog')@endif
</div>
<script>
(function(){
    var csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
    function showToast(msg,type){
        var t=document.createElement('div');
        t.textContent=msg;
        t.style.cssText='position:fixed;bottom:24px;right:24px;background:'+(type==='error'?'#c0392b':'#16a34a')+';color:#fff;padding:12px 20px;border-radius:8px;font-size:.9rem;z-index:99999;box-shadow:0 4px 12px rgba(0,0,0,.3)';
        document.body.appendChild(t);
        setTimeout(function(){t.remove();},3500);
    }
    function closePhoneContactMenu(){document.querySelector('.lm-phone-contact-menu')?.remove();}
    function showPhoneContactMenu(icon,phone){
        closePhoneContactMenu();
        var digits=phone.replace(/\D/g,'');
        if(!digits)return;
        var menu=document.createElement('div');
        menu.className='lm-phone-contact-menu';
        menu.setAttribute('role','menu');
        [['☎','Call','tel:'+phone.replace(/\s/g,'')],['◉','WhatsApp','https://wa.me/'+digits],['➤','Telegram','tg://resolve?phone='+digits]].forEach(function(item){
            var link=document.createElement('a');
            link.href=item[2];
            link.setAttribute('role','menuitem');
            if(item[1]!=='Call')link.target='_blank';
            link.rel='noopener';
            var symbol=document.createElement('span');symbol.textContent=item[0];
            var label=document.createElement('strong');label.textContent=item[1];
            link.append(symbol,label);menu.appendChild(link);
        });
        document.body.appendChild(menu);
        var box=icon.getBoundingClientRect();
        menu.style.left=Math.max(8,Math.min(box.left,window.innerWidth-menu.offsetWidth-8))+'px';
        menu.style.top=Math.min(box.bottom+8,window.innerHeight-menu.offsetHeight-8)+'px';
        menu.querySelector('a')?.focus();
    }
    // Click-to-copy phone and email cells
    document.addEventListener('click',async function(e){
        var phoneIcon=e.target.closest('.lm-phone-cell .lm-lead-icon');
        if(phoneIcon){
            e.preventDefault();e.stopPropagation();
            // Skip the icon itself: it is also a <span>, and it comes first in the cell.
            showPhoneContactMenu(phoneIcon,phoneIcon.closest('.lm-phone-cell').querySelector('span:not(.lm-lead-icon)')?.textContent.trim()||'');
            return;
        }
        if(!e.target.closest('.lm-phone-contact-menu'))closePhoneContactMenu();
        // The envelope opens the mail client; only the address itself copies.
        if(e.target.closest('.lm-email-link'))return;
        var phoneSpan=e.target.closest('.lm-phone-cell span');
        var emailTd=e.target.closest('.lm-updated-leads-table tbody td:nth-child(5)');
        var target=phoneSpan||emailTd;
        if(target){
            // The phone cell shows its dial code split off with a dash; copy the plain number.
            var text=target.dataset.lmCopyValue||target.textContent.trim();
            if(text&&text!=='-'){
                try{await navigator.clipboard.writeText(text);showToast('Copied: '+text.substring(0,40));}catch(ex){}
            }
        }
    });
    document.addEventListener('click',async function(e){
        var copyBtn=e.target.closest('[data-lm-copy-link]');
        if(copyBtn){
            e.preventDefault();
            var url=copyBtn.getAttribute('data-lm-copy-link');
            try{
                var r=await fetch(url,{headers:{'X-Requested-With':'XMLHttpRequest','Accept':'application/json'}});
                var j=await r.json();
                if(j.url) await navigator.clipboard.writeText(j.url);
                showToast('Auto-login link copied to clipboard');
            }catch(ex){showToast('Failed to copy link','error');}
        }
        var sendBtn=e.target.closest('[data-lm-send-credentials]');
        if(sendBtn){
            e.preventDefault();
            var url=sendBtn.getAttribute('data-lm-send-credentials');
            try{
                var r=await fetch(url,{method:'POST',headers:{'X-Requested-With':'XMLHttpRequest','Accept':'application/json','X-CSRF-TOKEN':csrf}});
                var j=await r.json();
                showToast(j.success?'Credentials sent successfully':(j.message||'Failed to send'),'error');
            }catch(ex){showToast('Network error','error');}
        }
    });
})();
</script>

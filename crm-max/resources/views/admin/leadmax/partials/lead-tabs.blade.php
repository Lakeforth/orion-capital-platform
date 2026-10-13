{{-- Lead Details bottom tabs (Figma "Max Leads user details" *-tab-page frames). Values the platform does not record render as "—". --}}
@php
    $d = $tabData;
    $tabHelper = app(\App\Services\LeadMax\LeadDetailTabs::class);
    $dash = '—';
    $money = fn ($v) => ($v < 0 ? '-$' : '$').number_format(abs((float) $v), 2);
    $badge = fn (string $label, string $tone) => '<span class="lm-badge lm-badge-'.$tone.'">'.e($label).'</span>';
    $collapse = '<button type="button" class="lm-tab-collapse-btn" data-lm-tab-collapse aria-label="Collapse section" aria-expanded="true"><img src="'.asset('images/leadmax/details/collapse-arrow.svg').'" width="17.8004" height="17.8004" alt=""></button>';
    $kv = fn ($label, $value) => '<div class="lm-kv-row"><span>'.e($label).'</span><strong>'.($value === null || $value === '' ? $dash : $value).'</strong></div>';
    $dateTime = fn ($at, $format = 'Y-m-d H:i:s') => $at ? \Carbon\Carbon::parse($at)->format($format) : null;
@endphp
<div class="lm-tab-content lm-tab-{{ $tab }}">
@if($tab === 'finances')
    @php
        $txStatus = fn ($s) => match ($s) { 'approved', 'completed' => ['Completed', 'green'], 'pending', 'processing' => ['Pending', 'amber'], default => ['Failed', 'red'] };
        $txKind = fn ($t) => match ($t) { 'd', 'deposit', 'system_deposit' => 'Deposit', 'w', 'withdraw', 'withdrawal', 'system_withdrawal' => 'Withdrawal', default => ucfirst(str_replace('_', ' ', (string) $t)) };
    @endphp
    {{-- The money actions as buttons of their own above the summary (user, 10 Oct); admins only, as they were in the Finances menu. --}}
    <div class="lm-fin-actions" role="group" aria-label="Finance actions">
        @foreach([['payment', 'Add Payment', 'gmdi-add-card', 'is-deposit'], ['adjustment', 'Adjust Balance', 'gmdi-balance', 'is-adjust'], ['withdrawal', 'Issue Withdrawal', 'gmdi-outbox', 'is-withdraw']] as [$finDialog, $finLabel, $finIcon, $finTone])
            @if(is_admin())<a class="lm-fin-action {{ $finTone }}" href="{{ $dialogUrl($finDialog) }}">@svg($finIcon, 'lm-fin-action-icon', ['aria-hidden' => 'true']){{ $finLabel }}</a>@else<span class="lm-fin-action {{ $finTone }}" aria-disabled="true" title="Admins only">@svg($finIcon, 'lm-fin-action-icon', ['aria-hidden' => 'true']){{ $finLabel }}</span>@endif
        @endforeach
    </div>
    <div class="lm-tab-cards" id="lm-financial-summary">
        <div class="lm-tab-card"><span>Total Balance</span><strong>{{ $money($d['balance']) }}</strong></div>
        <div class="lm-tab-card"><span>Total Deposits</span><strong class="is-green">{{ $money($d['deposits']) }}</strong></div>
        <div class="lm-tab-card"><span>Total Withdrawals</span><strong class="is-red">{{ $money($d['withdrawals']) }}</strong></div>
        <div class="lm-tab-card"><span>Pending Transactions</span><strong class="is-amber">{{ $d['pending'] }}</strong></div>
    </div>
    <div class="lm-tab-table" style="--lm-min:1117px;--lm-cols:minmax(198px,198fr) minmax(221px,221fr) minmax(226px,226fr) minmax(222px,222fr) minmax(110px,110fr) minmax(140px,140fr)">
        <div class="lm-tab-thead"><span>Date</span><span>Transaction ID</span><span>Type</span><span>Amount</span><span>Status</span><span>Payment Method</span></div>
        @forelse($d['rows'] as $tx)
            @php $kind = $txKind($tx->getRawOriginal('type')); [$statusLabel, $tone] = $txStatus($tx->status); $out = $kind === 'Withdrawal'; @endphp
            <div class="lm-tab-row">
                <span class="is-muted-strong">{{ $tx->created_at?->format('Y-m-d H:i:s') }}</span>
                <span class="is-link">TXN-{{ $tx->order_id ?: strtoupper(substr(str_replace('-', '', $tx->id), -6)) }}</span>
                <span class="is-strong">{{ $kind }}</span>
                <span class="is-strong {{ $out ? 'is-red' : ($kind === 'Deposit' ? 'is-green' : 'is-muted') }}">{{ $out ? '-' : '+' }}${{ number_format((float) $tx->amount, 2) }}</span>
                <span>{!! $badge($statusLabel, $tone) !!}</span>
                <span class="is-muted">{{ $tx->method ?: ($tx->account ? ucwords(str_replace('_', ' ', $tx->account)) : $dash) }}</span>
            </div>
        @empty
            <div class="lm-tab-empty">No transactions yet.</div>
        @endforelse
        @include('admin.leadmax.partials.lead-tab-pager', ['rows' => $d['rows'], 'noun' => null])
    </div>
    @if(($invoices ?? collect())->isNotEmpty() || is_admin())
        <div class="lm-tab-table" style="--lm-min:1132px;--lm-cols:minmax(126px,126fr) minmax(216px,216fr) minmax(126px,126fr) minmax(126px,126fr) minmax(146px,146fr) minmax(146px,146fr) minmax(106px,106fr) minmax(140px,140fr)">
            <div class="lm-tab-section-head"><h3>Invoices &amp; Credit Notes</h3>{!! $collapse !!}</div>
            <div class="lm-tab-collapsible">
                <div class="lm-tab-thead"><span>Number</span><span>Type</span><span>Issued</span><span>Due</span><span>Amount</span><span>Balance</span><span>Status</span><span class="is-right">Actions</span></div>
                @forelse($invoices as $document)
                    @php $invoiceStatusUrl = route('admin.leadmax.leads.invoices.status', [$lead->id, $document->id]); $docTone = match ($document->status) { 'paid', 'issued' => 'green', 'void' => 'grey', default => 'amber' }; @endphp
                    <div class="lm-tab-row">
                        <a class="is-link" href="{{ route('admin.leadmax.leads.invoices.show', [$lead->id, $document->id]) }}" target="_blank" rel="noopener">{{ $document->number }}</a>
                        <span class="is-strong">{{ $document->isCreditNote() ? 'Credit note'.($document->invoice ? ' · '.$document->invoice->number : '') : 'Invoice' }}</span>
                        <span class="is-muted">{{ $document->issue_date?->format('Y-m-d') }}</span>
                        <span class="is-muted">{{ $document->due_date?->format('Y-m-d') ?: $dash }}</span>
                        <span class="is-strong {{ $document->isCreditNote() ? 'is-red' : '' }}">{{ $document->isCreditNote() ? '-' : '' }}{{ $document->currency }} {{ number_format((float) $document->amount, 2) }}</span>
                        <span class="is-muted">{{ $document->isCreditNote() || $document->status === 'void' ? $dash : $document->currency.' '.number_format($document->status === 'paid' ? 0 : $document->balanceDue(), 2) }}</span>
                        <span>{!! $badge(ucfirst($document->status), $docTone) !!}</span>
                        <span class="lm-tab-actions">@if(is_admin() && $document->status !== 'void')@if($hasEmail && ! $communicationsBlocked)<button type="button" data-lm-post="{{ $invoiceStatusUrl }}" data-lm-post-action="send">Email</button>@endif @if(! $document->isCreditNote() && $document->status !== 'paid')<button type="button" data-lm-post="{{ $invoiceStatusUrl }}" data-lm-post-action="paid" data-lm-confirm="Mark {{ $document->number }} as paid?">Mark paid</button>@endif @if($document->status !== 'paid')<button type="button" data-lm-post="{{ $invoiceStatusUrl }}" data-lm-post-action="void" data-lm-confirm="Void {{ $document->number }}? This cannot be undone.">Void</button>@endif @endif</span>
                    </div>
                @empty
                    <div class="lm-tab-empty">No invoices or credit notes yet.</div>
                @endforelse
            </div>
        </div>
    @endif

@elseif($tab === 'trading')
    <div class="lm-tab-cards">
        <div class="lm-tab-card"><span>Total Accounts</span><strong>{{ $d['accounts']->count() }}</strong></div>
        <div class="lm-tab-card"><span>Active Accounts</span><strong class="is-green">{{ $d['active'] }}</strong></div>
        <div class="lm-tab-card"><span>Total Equity</span><strong>{{ $money($d['equity']) }}</strong></div>
        <div class="lm-tab-card"><span>Total P&amp;L</span><strong class="{{ $d['pnl'] < 0 ? 'is-red' : 'is-green' }}">{{ $d['pnl'] < 0 ? '-' : '+' }}${{ number_format(abs($d['pnl']), 2) }}</strong></div>
    </div>
    <div class="lm-tab-table" style="--lm-min:1132px;--lm-cols:minmax(136px,136fr) minmax(116px,116fr) minmax(156px,156fr) minmax(116px,116fr) minmax(156px,156fr) minmax(156px,156fr) minmax(156px,156fr) minmax(140px,140fr)">
        <div class="lm-tab-thead"><span>Account ID</span><span>Platform</span><span>Account Type</span><span>Leverage</span><span>Balance</span><span>Equity</span><span>Free Margin</span><span>Status</span></div>
        @forelse($d['accounts'] as $account)
            @php $leverage = data_get($account->config, 'leverage'); $equity = $account->equity(); @endphp
            <div class="lm-tab-row">
                <span class="is-link">{{ $account->account_id }}@if(is_admin()) · <a class="is-link" href="{{ $dialogUrl('payment') }}&account=trading_account:{{ $account->id }}" title="Fund this trading account">Fund</a>@endif</span>
                <span>{{ $account->trading_server ?: $dash }}</span>
                <span>{{ $account->account_type ? ucfirst($account->account_type) : $dash }}</span>
                <span class="is-muted">{{ $leverage ? (str_contains((string) $leverage, ':') ? $leverage : '1:'.$leverage) : $dash }}</span>
                <span class="is-strong">{{ $money($account->balance) }}</span>
                <span class="is-strong {{ $equity > 0 ? 'is-green' : '' }}">{{ $money($equity) }}</span>
                <span class="is-strong">{{ $money($account->freeMargin()) }}</span>
                <span>{!! $account->is_active ? $badge('Active', 'green') : $badge('Inactive', 'amber') !!}</span>
            </div>
        @empty
            <div class="lm-tab-empty">This lead has no trading accounts.</div>
        @endforelse
        <div class="lm-tab-foot"><span>Showing {{ $d['accounts']->isEmpty() ? 0 : 1 }}-{{ $d['accounts']->count() }} of {{ $d['accounts']->count() }}</span><div class="lm-tab-pager"><span class="lm-pager-btn is-disabled"><img src="{{ asset('images/leadmax/details/chevron-left.svg') }}" width="10" height="10" alt="">Previous</span><span class="lm-pager-page">Page 1 of 1</span><span class="lm-pager-btn is-disabled">Next<img src="{{ asset('images/leadmax/details/chevron-right.svg') }}" width="10" height="10" alt=""></span></div></div>
    </div>
    @php
        $tradesUrl = fn (array $extra = []) => route('admin.leadmax.trades', array_merge(['lead_id' => $lead->id], $extra));
        $tradePrice = function ($trade, $value) { if ($value === null || (float) $value == 0.0) return '—'; $p = rescue(fn () => $trade->asset?->displayPrecision(), null, false) ?? ((float) $value < 10 ? 5 : 2); return number_format((float) $value, $p, '.', ','); };
    @endphp
    <div class="lm-tab-subhead"><h3>Trades <small>{{ $d['open_trades'] }} open · {{ $d['trade_count'] }} total</small></h3><a class="lm-button" href="{{ $tradesUrl() }}">Open in All Trades</a></div>
    <div class="lm-tab-table" style="--lm-min:1134px;--lm-cols:minmax(126px,126fr) minmax(86px,86fr) minmax(106px,106fr) minmax(126px,126fr) minmax(126px,126fr) minmax(106px,106fr) minmax(106px,106fr) minmax(116px,116fr) minmax(96px,96fr) minmax(140px,140fr)">
        <div class="lm-tab-thead"><span>Symbol</span><span>Type</span><span>Volume</span><span>Open price</span><span>Current / close</span><span>TP</span><span>SL</span><span>P&amp;L</span><span>Status</span><span>{{ is_admin() ? 'Actions' : 'Opened' }}</span></div>
        @forelse($d['trades'] as $trade)
            @php $tradeOpen = (int) $trade->status === 0; @endphp
            <div class="lm-tab-row">
                <span class="is-strong notranslate">{{ $trade->asset?->sym_key ?: ($trade->asset?->sym ?: $dash) }}</span>
                <span>{!! $trade->trade_type === 'buy' ? $badge('BUY', 'green') : $badge('SELL', 'red') !!}</span>
                <span>{{ number_format((float) ($trade->volume_lots ?? $trade->volume), 2) }}</span>
                <span>{{ $tradePrice($trade, $trade->opening_price) }}</span>
                <span>{{ $tradePrice($trade, $trade->closing_price) }}</span>
                <span class="is-muted">{{ $tradePrice($trade, $trade->take_profit) }}</span>
                <span class="is-muted">{{ $tradePrice($trade, $trade->stop_loss) }}</span>
                <span class="is-strong {{ (float) $trade->profit < 0 ? 'is-red' : 'is-green' }}">{{ $money($trade->profit) }}</span>
                <span>{!! $tradeOpen ? $badge('Open', 'green') : $badge('Closed', 'amber') !!}</span>
                <span>@if(is_admin())<a class="is-link" href="{{ $tradesUrl(['trade' => $trade->id, 'dialog' => 'edit']) }}">Edit</a>@if($tradeOpen) · <a class="is-link" href="{{ $tradesUrl(['trade' => $trade->id, 'dialog' => 'close']) }}">Close</a>@endif @else{{ $dateTime($trade->opened_at ?? $trade->created_at, 'Y-m-d H:i') }}@endif</span>
            </div>
        @empty
            <div class="lm-tab-empty">This lead has no trades yet.</div>
        @endforelse
    </div>
    @if(is_admin())
        <form class="lm-spread-form" data-lm-submit action="{{ route('admin.leadmax.leads.spread', $lead->id) }}" method="POST" data-lm-success="{{ route('admin.leadmax.leads.show', [$lead->id, 'tab' => 'trading']) }}">@csrf
            <label><span>Client spread</span><input name="spread_multiplier" inputmode="decimal" placeholder="Standard (1×)" value="{{ $d['spread'] === null ? '' : rtrim(rtrim(number_format((float) $d['spread'], 2, '.', ''), '0'), '.') }}"></label>
            <small>A multiplier on your markup for this client: blank = standard, 0 = no spread, 2 = double, up to 20.</small>
            <button class="lm-button primary">Save spread</button>
            <p role="alert" class="lm-form-error" hidden></p>
        </form>
    @endif

@elseif($tab === 'kyc')
    {{-- NC-23: one request-centred KYC workspace inside the existing tab. Overview cards, then
         Documents | KYC History | Audit Trail. One KYC request = one submission =
         one reference; its documents are reviewed and decided together in the request modal,
         never from a list row or a file. --}}
    @php
        $docTone = fn ($s) => match ($s) { 'approved' => 'green', 'rejected' => 'red', default => 'amber' };
        $kycHeader = $d['header'];
        $kycStateKey = $kycHeader?->state ?? 'none';
        $kycState = fn (string $key, string $label) => '<span class="lm-kyc-state lm-kyc-state-'.$key.'">'.e($label).'</span>';
        $kycOpenForReview = in_array($kycStateKey, [\App\Models\KycSubmission::PENDING_APPROVAL, \App\Models\KycSubmission::UNDER_REVIEW], true);
        $kycView = $d['view'];
        $kycViewUrl = fn (string $view) => $detailUrl(['tab' => 'kyc', 'kyc_view' => $view]);
        $requestUrl = fn (string $uuid, ?string $modalTab = null, ?string $doc = null) => $dialogUrl('kyc-request').'&request='.$uuid.($modalTab ? '&kh_tab='.$modalTab : '').($doc ? '&doc='.$doc : '');
        $active = $d['active'];
        $requests = $d['requests'];
        $cards = $requests['cards'];
        // Each card sets the Status filter to what it counts; the chosen card clears it again.
        $cardFilter = function (array $statuses) use ($requests) {
            $current = $requests['filters']['status'];
            sort($current);
            sort($statuses);
            $on = $current === $statuses;

            return ['on' => $on, 'href' => request()->fullUrlWithQuery(['tab' => 'kyc', 'kh_status' => $on ? null : ($statuses ?: null), 'kh_page' => null, 'dialog' => null, 'request' => null, 'kh_tab' => null, 'doc' => null])];
        };
        $kycExportUrl = route('admin.leadmax.leads.kyc.export', ['id' => $lead->id, ...array_filter(request()->only(['kh_search', 'kh_from', 'kh_to', 'kh_status', 'kh_type', 'kh_sort', 'kh_dir']))]);
    @endphp

    {{-- The KYC overview: four cards like the History tab's (user, 10 Oct 2026). The lead header
         above already shows who the client is; the client's verification and the request are
         different facts, shown apart. The current request opens from its card. --}}
    @php
        $kycSubmitted = $kycHeader && ($kycHeader->state !== \App\Models\KycSubmission::DRAFT || $kycHeader->submitted_at);
        $lastEvent = $d['lastEvent'];
    @endphp
    <div class="lm-tab-cards lm-kyc-overview-cards" aria-label="KYC overview">
        <div class="lm-tab-card"><span>Verification Status</span><strong>{{ ucfirst($d['status']) }}</strong><small>Risk: Not assessed</small></div>
        <div class="lm-tab-card"><span>Current Request</span>
            @if($kycSubmitted)
                <strong><a href="{{ $requestUrl($kycHeader->id) }}" title="{{ $kycOpenForReview ? 'Review this request' : 'View this request' }}">{{ $kycHeader->reference ?? 'Attempt '.$kycHeader->attempt }}</a></strong>
                <small>{{ \App\Services\LeadMax\LeadDetailTabs::kycStateLabel($kycHeader->state) }}{{ \App\Services\LeadMax\LeadDetailTabs::kycIsLegacy($kycHeader) ? ' · Legacy request' : '' }}{{ $kycOpenForReview ? ' · open to review' : '' }}</small>
            @elseif($kycHeader)
                <strong>Draft</strong><small>{{ $kycHeader->reference ? $kycHeader->reference.' is' : 'A KYC is' }} being put together; New KYC continues it.</small>
            @else
                <strong>None</strong><small>No KYC has been submitted yet.</small>
            @endif
        </div>
        <div class="lm-tab-card"><span>Documents</span>
            @if($d['bundle'])
                <strong>{{ $d['bundle']['label'] }}</strong><small>{{ $d['bundle']['present'] }}/{{ $d['bundle']['required'] }} required present · {{ $d['bundle']['reviewed'] }}/{{ $d['bundle']['required'] }} reviewed</small>
            @else
                <strong>0 documents</strong><small>Nothing uploaded yet.</small>
            @endif
        </div>
        <div class="lm-tab-card"><span>Last Activity</span><strong>{{ $lastEvent ? $lastEvent->created_at->format('Y-m-d') : $dash }}</strong><small>{{ $lastEvent ? (\App\Services\LeadMax\LeadDetailTabs::KYC_EVENT_LABELS[$lastEvent->type] ?? ucfirst(str_replace('_', ' ', $lastEvent->type))) : 'No KYC activity yet' }}</small></div>
    </div>

    {{-- One request list (user, 10 Oct 2026): a request's documents and its audit trail open from its
         KYC Request ID, in the request modal's Documents and Timeline tabs. --}}
        @php
            $filters = $requests['filters'];
            $page = $d['requestPage'];
            // Server-side sort over the whole result, like every CRM table; a click on the sorted
            // column flips it, and any new sort or filter starts again on page 1.
            $requestSort = function (string $label, string $key) use ($requests, $kycView) {
                $isSorted = $requests['sort'] === $key;
                $next = $isSorted ? ($requests['dir'] === 'desc' ? 'asc' : 'desc') : (in_array($key, ['submitted', 'reviewed', 'risk'], true) ? 'desc' : 'asc');
                $href = request()->fullUrlWithQuery(['tab' => 'kyc', 'kyc_view' => $kycView, 'kh_sort' => $key, 'kh_dir' => $next, 'kh_page' => null, 'dialog' => null, 'request' => null, 'kh_tab' => null, 'doc' => null]);

                return '<span class="lm-sortable lm-tab-sort'.($isSorted ? ' is-sorted is-'.$requests['dir'] : '').'" aria-sort="'.($isSorted ? ($requests['dir'] === 'asc' ? 'ascending' : 'descending') : 'none').'">'
                    .'<a href="'.e($href).'" data-lm-kyc-link><span>'.e($label).'</span>'
                    .'<svg class="lm-sort-arrows" viewBox="0 0 11 14" width="14" height="17.8" fill="none" stroke="currentColor" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path class="up" d="M3 9.5V2.2M1.2 4L3 2.2 4.8 4"/><path class="down" d="M8 4.5v7.3M9.8 10 8 11.8 6.2 10"/></svg>'
                    .'<span class="sr-only">Sort by '.e($label).' '.($next === 'asc' ? 'ascending' : 'descending').'</span></a></span>';
            };
            $sortOptions = ['submitted' => 'Submission Date', 'reference' => 'KYC ID', 'reviewed' => 'Reviewed Date', 'expiry' => 'Expiry Date', 'status' => 'Status', 'risk' => 'Risk Score'];
        @endphp
        <form class="lm-kyc-toolbar" method="GET" action="{{ route('admin.leadmax.leads.show', $lead->id) }}" data-lm-kyc-filter role="search" aria-label="Filter KYC requests">
            <input type="hidden" name="tab" value="kyc"><input type="hidden" name="kyc_view" value="{{ $kycView }}">
            @if(request()->filled('kh_per'))<input type="hidden" name="kh_per" value="{{ $d['requestPerPage'] }}">@endif
            <label class="lm-kyc-search"><span class="sr-only">Search KYC records</span><input type="search" name="kh_search" value="{{ $filters['search'] }}" placeholder="Search KYC records…" maxlength="100" autocomplete="off" data-lm-kyc-search></label>
            <fieldset class="lm-kyc-dates">
                <legend class="sr-only">Submission date, UTC</legend>
                <label><span>From</span><input type="date" name="kh_from" value="{{ $filters['from'] }}" aria-describedby="lm-kyc-date-zone"></label>
                <label><span>To</span><input type="date" name="kh_to" value="{{ $filters['to'] }}" aria-describedby="lm-kyc-date-zone" @if(isset($requests['errors']['kh_to'])) aria-invalid="true" @endif></label>
                <span id="lm-kyc-date-zone" class="is-muted">UTC</span>
            </fieldset>
            <x-leadmax-multiselect name="kh_status" label="Status" placeholder="All Statuses" :options="\App\Services\LeadMax\LeadDetailTabs::KYC_REQUEST_STATUSES" :selected="$filters['status']" />
            <x-leadmax-multiselect name="kh_type" label="Document Type" placeholder="All Document Types" :options="$requests['types']" :selected="$filters['type']" />
            <label class="lm-kyc-sort"><span>Sort</span>
                <select name="kh_sort">@foreach($sortOptions as $key => $label)<option value="{{ $key }}" @selected($requests['sort'] === $key)>{{ $label }}</option>@endforeach</select>
                <select name="kh_dir" aria-label="Sort order"><option value="desc" @selected($requests['dir'] === 'desc')>Descending</option><option value="asc" @selected($requests['dir'] === 'asc')>Ascending</option></select>
            </label>
            <noscript><button type="submit" class="lm-button">Apply</button></noscript>
            <span class="lm-kyc-toolbar-end">
                @if(array_filter([$filters['search'], $filters['from'], $filters['to'], $filters['status'], $filters['type']]))<a class="is-link" href="{{ $kycViewUrl($kycView) }}" data-lm-kyc-link>Clear filters</a>@endif
                <a class="lm-button" href="{{ $kycExportUrl }}" data-lm-kyc-export data-lm-kyc-export-count="{{ $requests['rows']->count() }}" @if($requests['rows']->isEmpty()) aria-disabled="true" @endif>Export</a>
                <details class="lm-kyc-bulk">
                    <summary class="lm-button">Bulk Actions <span class="lm-chip" data-lm-kyc-selected-count>0 selected</span></summary>
                    <div class="lm-kyc-bulk-menu" role="menu"><button type="button" role="menuitem" class="lm-kyc-bulk-item" data-lm-kyc-export-selected disabled>Export selected</button></div>
                </details>
            </span>
            @if(isset($requests['errors']['kh_to']))<p role="alert" class="lm-form-error">{{ $requests['errors']['kh_to'] }}</p>@endif
        </form>

        <div data-lm-kyc-results data-lm-kyc-lead="{{ $lead->id }}" data-lm-kyc-filter-key="{{ md5(json_encode($filters)) }}">
            {{-- The cards count the filtered requests before the status filter and paging, and each one
                 applies that filter. They overlap by design (a resubmission can also be approved), so
                 they are not a partition. --}}
            @php $cf = ['total' => $cardFilter([]), 'approved' => $cardFilter(['approved']), 'pending' => $cardFilter(['pending']), 'rejected' => $cardFilter(['rejected', 'resubmitted']), 'expired' => $cardFilter(['expired'])]; @endphp
            <nav class="lm-tab-cards lm-kyc-cards" aria-label="Filter KYC requests by status">
                <a class="lm-tab-card is-total{{ $cf['total']['on'] ? ' is-active' : '' }}" href="{{ $cf['total']['href'] }}" data-lm-kyc-link @if($cf['total']['on']) aria-current="true" @endif title="Distinct submitted requests in this view; drafts and file sides are not counted"><i class="lm-kyc-card-icon" aria-hidden="true"><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8z"/><path d="M14 3v5h5M9 13h6M9 17h6"/></svg></i><span>Total KYC Submissions</span><strong>{{ $cards['total'] }}</strong><small>{{ $cards['this_year'] }} submitted this year</small></a>
                <a class="lm-tab-card is-approved{{ $cf['approved']['on'] ? ' is-active' : '' }}" href="{{ $cf['approved']['href'] }}" data-lm-kyc-link @if($cf['approved']['on']) aria-current="true" @endif title="Requests with an Approved decision"><i class="lm-kyc-card-icon" aria-hidden="true"><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="m8 12 3 3 5-6"/></svg></i><span>Approved</span><strong>{{ $cards['approved'] }}</strong><small>{{ $cards['approved_pct'] }}% of total</small></a>
                <a class="lm-tab-card is-pending{{ $cf['pending']['on'] ? ' is-active' : '' }}" href="{{ $cf['pending']['href'] }}" data-lm-kyc-link @if($cf['pending']['on']) aria-current="true" @endif title="Requests pending review or under review"><i class="lm-kyc-card-icon" aria-hidden="true"><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg></i><span>Pending Review</span><strong>{{ $cards['pending'] }}</strong><small>{{ $cards['pending_pct'] }}% of total</small></a>
                <a class="lm-tab-card is-rejected{{ $cf['rejected']['on'] ? ' is-active' : '' }}" href="{{ $cf['rejected']['href'] }}" data-lm-kyc-link @if($cf['rejected']['on']) aria-current="true" @endif title="Rejected requests and resubmissions after a rejection, each request counted once"><i class="lm-kyc-card-icon" aria-hidden="true"><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="m9 9 6 6M15 9l-6 6"/></svg></i><span>Rejected / Resubmitted</span><strong>{{ $cards['rejected'] }}</strong><small>{{ $cards['rejected_pct'] }}% of total</small></a>
                <a class="lm-tab-card is-expired{{ $cf['expired']['on'] ? ' is-active' : '' }}" href="{{ $cf['expired']['href'] }}" data-lm-kyc-link @if($cf['expired']['on']) aria-current="true" @endif title="Documents with a recorded expiry date before today (UTC); {{ $cards['no_expiry'] }} {{ \Illuminate\Support\Str::plural('document', $cards['no_expiry']) }} have no expiry recorded"><i class="lm-kyc-card-icon" aria-hidden="true"><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="5" width="16" height="15" rx="2"/><path d="M8 3v4M16 3v4M4 10h16M10 14l4 4M14 14l-4 4"/></svg></i><span>Expired Documents</span><strong>{{ $cards['expired'] }}</strong><small>{{ $cards['no_expiry'] }} without an expiry date</small></a>
            </nav>
            @if($requests['excluded'] > 0)
                <p class="lm-kyc-hint">{{ $requests['excluded'] }} {{ \Illuminate\Support\Str::plural('request', $requests['excluded']) }} without a recorded submission date {{ $requests['excluded'] === 1 ? 'is' : 'are' }} left out of a date range; clear the dates to see {{ $requests['excluded'] === 1 ? 'it' : 'them' }}.</p>
            @endif

            {{-- One row per request: its document columns summarise the whole bundle. Decisions happen in the request modal only. --}}
            <div class="lm-tab-table lm-kyc-requests" style="--lm-min:1660px;--lm-cols:44px minmax(150px,150fr) minmax(160px,160fr) minmax(180px,180fr) minmax(150px,150fr) minmax(120px,120fr) minmax(160px,160fr) minmax(140px,140fr) minmax(140px,140fr) minmax(125px,125fr) minmax(120px,120fr) minmax(170px,170fr)">
                <div class="lm-tab-section-head"><h3>KYC History</h3><span class="lm-tab-head-actions">@if($kycOpenForReview)<span id="lm-kyc-blocked" class="lm-kyc-hint">A KYC submission is already awaiting review</span><button type="button" class="lm-button primary" disabled aria-describedby="lm-kyc-blocked">New KYC</button>@else<a class="lm-button primary" href="{{ $dialogUrl('kyc-new') }}">New KYC</a>@endif<span class="lm-chip">{{ $page->total() }} {{ \Illuminate\Support\Str::plural('request', $page->total()) }}</span></span></div>
                <div class="lm-tab-collapsible">
                    <div class="lm-tab-thead">
                        <span><input type="checkbox" data-lm-kyc-select-all aria-label="Select the requests on this page" @disabled($page->isEmpty())></span>
                        {!! $requestSort('KYC Request ID', 'reference') !!}
                        {!! $requestSort('Submission Date', 'submitted') !!}
                        <span>Identity Documents</span><span>Proof of Address</span><span>Country</span>
                        {!! $requestSort('Status', 'status') !!}
                        <span>Reviewed By</span>
                        {!! $requestSort('Reviewed Date', 'reviewed') !!}
                        {!! $requestSort('Expiry Date', 'expiry') !!}
                        {!! $requestSort('Risk Score', 'risk') !!}
                        <span>Notes / Issues</span>
                    </div>
                    @forelse($page as $row)
                        @php $rowRef = $row['reference'] ?? 'Attempt '.$row['attempt']; @endphp
                        <div class="lm-tab-row lm-kyc-request-row" data-lm-kyc-request="{{ $row['uuid'] }}">
                            <span><input type="checkbox" data-lm-kyc-select value="{{ $row['uuid'] }}" aria-label="Select {{ $rowRef }}"></span>
                            <a class="is-link is-strong" href="{{ $requestUrl($row['uuid']) }}" data-lm-kyc-open>{{ $rowRef }}</a>
                            <span>{{ $row['submitted_at'] ? $row['submitted_at']->format('Y-m-d H:i').' UTC' : 'Not recorded' }}</span>
                            <span class="is-ellipsis" title="{{ $row['primary'] }} · {{ $row['doc_count'] }} {{ \Illuminate\Support\Str::plural('document', $row['doc_count']) }}, {{ $row['file_count'] }} {{ \Illuminate\Support\Str::plural('file', $row['file_count']) }}">{{ $row['primary'] }} · {{ $row['file_count'] }} {{ \Illuminate\Support\Str::plural('file', $row['file_count']) }}</span>
                            <span class="is-ellipsis">{{ $row['proof'] }}</span>
                            <span class="is-ellipsis">@if($row['country_code'])<x-leadmax-flag :country="$row['country_code']" /> {{ strtoupper($row['country_code']) }}<span class="sr-only"> {{ $row['country_name'] }}</span>@else<span aria-label="Not recorded">{{ $dash }}</span>@endif</span>
                            <span class="lm-kyc-status-cell">{!! $kycState($row['state'], $row['state_label']) !!}@if($row['legacy']){!! $kycState('legacy', 'Legacy request') !!}@endif @if($row['resubmitted']){!! $kycState('resubmitted', 'Resubmitted') !!}@endif @if($row['expired']){!! $kycState('expired', 'Expired') !!}@endif</span>
                            <span class="is-ellipsis">{{ $row['reviewer'] ?? 'Not reviewed' }}</span>
                            <span>{{ $row['reviewed_at'] ? $row['reviewed_at']->format('Y-m-d') : '' }}</span>
                            <span>{!! $row['expiry'] ? e($row['expiry']->format('Y-m-d')) : '<span aria-label="Not recorded">'.$dash.'</span>' !!}</span>
                            <span>{{ $row['risk_score'] !== null ? number_format($row['risk_score'], 0).($row['risk_band'] ? ' · '.ucfirst($row['risk_band']) : '') : 'Not assessed' }}</span>
                            <span class="is-ellipsis" title="{{ $row['note'] }}">{{ $row['note'] ?? $dash }}</span>
                        </div>
                    @empty
                        @if(array_filter([$filters['search'], $filters['from'], $filters['to'], $filters['status'], $filters['type']]))
                            <div class="lm-tab-empty">No KYC requests match these filters. <a class="is-link" href="{{ $kycViewUrl($kycView) }}" data-lm-kyc-link>Clear filters</a></div>
                        @else
                            <div class="lm-tab-empty">No KYC requests have been submitted yet.</div>
                        @endif
                    @endforelse
                    <div class="lm-tab-foot">
                        <span>Showing {{ $page->firstItem() ? $page->firstItem().' to '.$page->lastItem() : '0' }} of {{ $page->total() }} {{ \Illuminate\Support\Str::plural('request', $page->total()) }}</span>
                        <div class="lm-tab-pager">
                            @if($page->onFirstPage())<span class="lm-pager-btn is-disabled">Previous</span>@else<a class="lm-pager-btn" href="{{ $page->previousPageUrl() }}" data-lm-kyc-link>Previous</a>@endif
                            <span class="lm-pager-page">Page {{ $page->currentPage() }} of {{ max(1, $page->lastPage()) }}</span>
                            @if($page->hasMorePages())<a class="lm-pager-btn is-next" href="{{ $page->nextPageUrl() }}" data-lm-kyc-link>Next</a>@else<span class="lm-pager-btn is-next is-disabled">Next</span>@endif
                        </div>
                    </div>
                    <div class="lm-kyc-per-page">Rows per page:
                        @foreach(\App\Services\LeadMax\LeadDetailTabs::KYC_REQUEST_SIZES as $size)
                            <a href="{{ request()->fullUrlWithQuery(['kh_per' => $size, 'kh_page' => null, 'dialog' => null, 'request' => null, 'kh_tab' => null, 'doc' => null]) }}" data-lm-kyc-link @class(['is-link', 'is-active' => $d['requestPerPage'] === $size]) @if($d['requestPerPage'] === $size) aria-current="true" @endif>{{ $size }}</a>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>


@elseif($tab === 'history')
    @php
        $eventTone = fn ($s) => match ($s) { 'Success', 'Completed' => 'green', 'Pending' => 'amber', default => 'red' };
    @endphp
    <div class="lm-tab-cards">
        <div class="lm-tab-card"><span>Total Events</span><strong>{{ $d['total'] }}</strong></div>
        <div class="lm-tab-card"><span>Login Events</span><strong>{{ $d['loginCount'] }}</strong></div>
        <div class="lm-tab-card"><span>Account Changes</span><strong>{{ $d['changeCount'] }}</strong></div>
        <div class="lm-tab-card"><span>Last Activity</span><strong>{{ $dateTime($d['lastActivity'], 'Y-m-d') ?? $dash }}</strong></div>
    </div>
    @php
        // Every column sorts, with the same up/down arrows as the other CRM tables; a click on the
        // sorted column flips its direction, and any new sort starts again on page 1.
        $historySort = function (string $label, string $key) use ($d) {
            $isSorted = $d['sort'] === $key;
            // As in the leads list, a first click sorts text A–Z and dates newest first.
            $next = $isSorted ? ($d['direction'] === 'desc' ? 'asc' : 'desc') : ($key === 'at' ? 'desc' : 'asc');
            $href = request()->fullUrlWithQuery(['tab' => 'history', 'hs' => $key, 'hd' => $next, 'tp' => null, 'dialog' => null]);

            return '<span class="lm-sortable lm-tab-sort'.($isSorted ? ' is-sorted is-'.$d['direction'] : '').'" aria-sort="'.($isSorted ? ($d['direction'] === 'asc' ? 'ascending' : 'descending') : 'none').'">'
                .'<a href="'.e($href).'"><span>'.e($label).'</span>'
                .'<svg class="lm-sort-arrows" viewBox="0 0 11 14" width="14" height="17.8" fill="none" stroke="currentColor" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path class="up" d="M3 9.5V2.2M1.2 4L3 2.2 4.8 4"/><path class="down" d="M8 4.5v7.3M9.8 10 8 11.8 6.2 10"/></svg>'
                .'<span class="sr-only">Sort by '.e($label).' '.($next === 'asc' ? 'ascending' : 'descending').'</span></a></span>';
        };
    @endphp
    {{-- Browser and Device live in the event details (user, 10 Oct); Country has the room for its flag.
         The Log ID opens those details -- there is no separate Details column (user, 10 Oct). --}}
    <div class="lm-tab-table" style="--lm-min:1000px;--lm-cols:minmax(110px,110fr) minmax(186px,186fr) minmax(166px,166fr) minmax(166px,166fr) minmax(106px,106fr) minmax(150px,150fr) minmax(116px,116fr)">
        <div class="lm-tab-section-head"><h3>Activity Log</h3>{!! $collapse !!}</div>
        <div class="lm-tab-collapsible">
            <div class="lm-tab-thead">{!! $historySort('Log ID', 'log') !!}{!! $historySort('Account Owner', 'owner') !!}{!! $historySort('Date/Time', 'at') !!}{!! $historySort('Event', 'event') !!}{!! $historySort('Role', 'role') !!}{!! $historySort('Country', 'country') !!}{!! $historySort('Result', 'result') !!}</div>
            @forelse($d['rows'] as $event)
                <div class="lm-tab-row">
                    <a class="is-link is-strong" href="{{ $dialogUrl('history-event') }}&kind={{ $event['kind'] }}&event={{ $event['id'] }}&tp={{ request('tp', 1) }}" title="View details of this event">{{ $event['log_id'] }}</a>
                    <span class="is-ellipsis">{{ $event['actor'] }}@if($event['actor'] !== $lead->name) <small class="is-muted">·{{ $lead->account_id ?: $lead->email }}</small>@endif</span>
                    <span class="is-muted">{{ $dateTime($event['at'], 'Y-m-d H:i') }}</span>
                    <span class="is-medium is-ellipsis" title="{{ $event['description'] }}">{{ $event['event'] }}</span>
                    <span class="is-muted">{{ $event['role'] }}</span>
                    <span class="is-muted is-ellipsis">@if($event['country_code'])<x-leadmax-flag :country="$event['country_code']" />@endif{{ $event['country'] }}</span>
                    <span>{!! $badge($event['status'], $eventTone($event['status'])) !!}</span>
                </div>
            @empty
                <div class="lm-tab-empty">No activity recorded yet.</div>
            @endforelse
            @include('admin.leadmax.partials.lead-tab-pager', ['rows' => $d['rows'], 'noun' => 'events'])
            {{-- DB-IP Lite is CC BY 4.0: credit it wherever its countries are shown. --}}
            @if(collect($d['rows']->items())->contains(fn ($event) => $event['country_code']))<p class="lm-tab-credit">Countries from IP addresses: <a href="https://db-ip.com" target="_blank" rel="noopener">IP Geolocation by DB-IP</a></p>@endif
        </div>
    </div>

@elseif($tab === 'calls')
    @php
        $last = $d['last'];
        $callTone = fn ($s) => match ($s) { 'answered' => ['Connected', 'green'], 'voicemail' => ['Voicemail', 'amber'], 'unanswered' => ['No Answer', 'amber'], default => ['Failed', 'red'] };
        $callAt = fn ($call) => $call->info['called_at'] ?? $call->created_at;
    @endphp
    <div class="lm-tab-cards">
        <div class="lm-tab-card"><span>Total Calls</span><strong>{{ $d['total'] }}</strong></div>
        <div class="lm-tab-card"><span>Inbound Calls</span><strong>{{ $d['inbound'] }}</strong></div>
        <div class="lm-tab-card"><span>Outbound Calls</span><strong>{{ $d['outbound'] }}</strong></div>
        <div class="lm-tab-card"><span>Avg Duration</span><strong>{{ $d['avgDuration'] ?? $dash }}</strong></div>
    </div>
    <div class="lm-tab-split">
        <section class="lm-kv-card"><div class="lm-tab-section-head"><h3>Last Call Details</h3>{!! $collapse !!}</div><div class="lm-tab-collapsible">
            @php [$lastLabel, $lastTone] = $last ? $callTone($last->info['call_status'] ?? null) : [null, null]; @endphp
            {!! $kv('Date/Time', $last ? $dateTime($callAt($last)) : null) !!}
            {!! $kv('Direction', $last ? e(ucfirst($last->info['call_type'] ?? '')) : null) !!}
            {!! $kv('Duration', $last ? e($tabHelper->clock($last->info['duration'] ?? null)) : null) !!}
            {!! $kv('Agent', $last ? e($last->info['actor'] ?? null) : null) !!}
            {!! $kv('Status', $last ? $badge($lastLabel, $lastTone) : null) !!}
            {!! $kv('Disposition', $last ? e($last->info['description'] ?? null) : null) !!}
            {!! $kv('Recording', null) !!}
        </div></section>
        <section class="lm-kv-card"><div class="lm-tab-section-head"><h3>Call Statistics</h3>{!! $collapse !!}</div><div class="lm-tab-collapsible">
            {!! $kv('Answered Rate', $d['answeredRate']) !!}
            {!! $kv('Avg Wait Time', null) !!}
            {!! $kv('Missed Calls', (string) $d['missed']) !!}
            {!! $kv('Voicemails', (string) $d['voicemails']) !!}
            {!! $kv('Callbacks Requested', (string) $d['callbacks']) !!}
            {!! $kv('First Call Resolution', null) !!}
            {!! $kv('Escalated Calls', null) !!}
        </div></section>
    </div>
    <div class="lm-tab-table" style="--lm-min:940px;--lm-cols:minmax(196px,196fr) minmax(126px,126fr) minmax(126px,126fr) minmax(186px,186fr) minmax(146px,146fr) minmax(160px,160fr)">
        <div class="lm-tab-section-head"><h3>Call History</h3>{!! $collapse !!}</div>
        <div class="lm-tab-collapsible">
            <div class="lm-tab-thead"><span>Date/Time</span><span>Direction</span><span>Duration</span><span>Agent</span><span>Status</span><span>Disposition</span></div>
            @forelse($d['rows'] as $call)
                @php [$label, $tone] = $callTone($call->info['call_status'] ?? null); @endphp
                <div class="lm-tab-row">
                    <span class="is-muted">{{ $dateTime($callAt($call), 'Y-m-d H:i') }}</span>
                    <span class="is-medium">{{ ucfirst($call->info['call_type'] ?? '') }}</span>
                    <span>{{ $tabHelper->clock($call->info['duration'] ?? null) ?? $dash }}</span>
                    <span>{{ $call->info['actor'] ?? $dash }}</span>
                    <span>{!! $badge($label, $tone) !!}</span>
                    <span class="is-muted is-ellipsis">{{ $call->info['description'] ?? $dash }}</span>
                </div>
            @empty
                <div class="lm-tab-empty">No calls logged yet. Use “Create call history” above.</div>
            @endforelse
            @include('admin.leadmax.partials.lead-tab-pager', ['rows' => $d['rows'], 'noun' => 'calls'])
        </div>
    </div>

@elseif($tab === 'chat')
    @php
        $last = $d['last'];
        $chatTone = fn ($s) => match ($s) { 'Active' => 'green', 'Resolved' => 'green', 'Waiting' => 'amber', 'Missed' => 'amber', default => 'red' };
    @endphp
    <div class="lm-tab-cards">
        <div class="lm-tab-card"><span>Total Chats</span><strong>{{ $d['total'] }}</strong></div>
        <div class="lm-tab-card"><span>Live Chats</span><strong>{{ $d['live'] }}</strong></div>
        <div class="lm-tab-card"><span>Resolved Chats</span><strong>{{ $d['resolved'] }}</strong></div>
        <div class="lm-tab-card"><span>Avg Response Time</span><strong>{{ $d['avgResponse'] ?? $dash }}</strong></div>
    </div>
    <div class="lm-tab-split">
        <section class="lm-kv-card"><div class="lm-tab-section-head"><h3>Last Chat Details</h3>{!! $collapse !!}</div><div class="lm-tab-collapsible">
            {!! $kv('Date/Time', $last ? $dateTime($last['end']) : null) !!}
            {!! $kv('Channel', $last ? 'Live Chat' : null) !!}
            {!! $kv('Duration', $last ? e($last['duration']) : null) !!}
            {!! $kv('Agent', $last ? e($last['agent'] ?? 'Unassigned') : null) !!}
            {!! $kv('Status', $last ? $badge($last['status'], $chatTone($last['status'])) : null) !!}
            {!! $kv('Satisfaction', null) !!}
            {!! $kv('Topic', $last ? e($last['topic']) : null) !!}
        </div></section>
        <section class="lm-kv-card"><div class="lm-tab-section-head"><h3>Chat Statistics</h3>{!! $collapse !!}</div><div class="lm-tab-collapsible">
            {!! $kv('Resolution Rate', $d['resolutionRate']) !!}
            {!! $kv('Avg First Response', $d['avgFirstResponse']) !!}
            {!! $kv('Avg Handle Time', $d['avgHandle']) !!}
            {!! $kv('Missed Chats', (string) $d['missed']) !!}
            {!! $kv('Transferred', null) !!}
            {!! $kv('CSAT Score', null) !!}
            {!! $kv('Escalated', null) !!}
        </div></section>
    </div>
    <div class="lm-tab-table" style="--lm-min:940px;--lm-cols:minmax(196px,196fr) minmax(126px,126fr) minmax(126px,126fr) minmax(186px,186fr) minmax(146px,146fr) minmax(160px,160fr)">
        <div class="lm-tab-section-head"><h3>Chat History</h3><a class="lm-tab-head-link" href="{{ route('admin.leadmax.chat.clients', ['with' => $lead->id]) }}">Open chat →</a>{!! $collapse !!}</div>
        <div class="lm-tab-collapsible">
            <div class="lm-tab-thead"><span>Date/Time</span><span>Channel</span><span>Duration</span><span>Agent</span><span>Status</span><span>Topic</span></div>
            @forelse($d['rows'] as $session)
                <div class="lm-tab-row">
                    <span class="is-muted">{{ $dateTime($session['start'], 'Y-m-d H:i') }}</span>
                    <span class="is-medium">Live Chat</span>
                    <span>{{ $session['duration'] }}</span>
                    <span>{{ $session['agent'] ?? 'Unassigned' }}</span>
                    <span>{!! $badge($session['status'], $chatTone($session['status'])) !!}</span>
                    <span class="is-muted is-ellipsis" title="{{ $session['topic'] }}">{{ $session['topic'] }}</span>
                </div>
            @empty
                <div class="lm-tab-empty">No chats with this lead yet. <a class="is-link" href="{{ route('admin.leadmax.chat.clients', ['with' => $lead->id]) }}">Start a chat</a></div>
            @endforelse
            @include('admin.leadmax.partials.lead-tab-pager', ['rows' => $d['rows'], 'noun' => 'chats'])
        </div>
    </div>

@elseif($tab === 'signals')
    @php
        $price = fn ($v, $signal) => $v === null ? '—' : number_format((float) $v, $tabHelper->precision($signal->instrument), '.', ',');
        $signalTone = fn ($s) => match ($s) { 'active' => 'blue', 'expired' => 'grey-strong', default => 'grey' };
    @endphp
    <div class="lm-signals-head"><h3>Trading Signals</h3><a class="lm-signal-send" href="{{ $dialogUrl('signal') }}"><span aria-hidden="true">+</span> Send Signal</a>{!! $collapse !!}</div>
    <div class="lm-tab-collapsible">
        <div class="lm-tab-table" style="--lm-min:1138px;--lm-cols:minmax(166px,166fr) minmax(119px,119fr) minmax(128px,128fr) minmax(119px,119fr) minmax(119px,119fr) minmax(119px,119fr) minmax(119px,119fr) minmax(119px,119fr) minmax(130px,130fr)">
            <div class="lm-tab-thead"><span>Date/Time</span><span>Signal ID</span><span>Instrument</span><span>Direction</span><span>Entry Price</span><span>Take Profit</span><span>Stop Loss</span><span>Status</span><span>Result</span></div>
            @forelse($d['rows'] as $signal)
                @php $result = (string) $signal->result; $resultTone = str_starts_with($result, '+') ? 'is-green' : (str_starts_with($result, '-') ? 'is-red' : 'is-muted'); @endphp
                <div class="lm-tab-row">
                    <span>{{ $signal->created_at?->format('Y-m-d H:i') }}</span>
                    <span class="is-link">{{ $signal->code() }}</span>
                    <span class="is-strong">{{ $signal->instrument }}</span>
                    <span>{!! $signal->direction === 'buy' ? $badge('Buy', 'buy') : $badge('Sell', 'sell') !!}</span>
                    <span>{{ $price($signal->entry_price, $signal) }}</span>
                    <span class="is-green">{{ $price($signal->take_profit, $signal) }}</span>
                    <span class="is-red">{{ $price($signal->stop_loss, $signal) }}</span>
                    <span>@if($signal->status === 'active')<a href="{{ $dialogUrl('signal-close') }}&signal={{ $signal->id }}" title="Close or expire this signal">{!! $badge('Active', 'blue') !!}</a>@else{!! $badge(ucfirst($signal->status), $signalTone($signal->status)) !!}@endif</span>
                    <span class="is-strong {{ $resultTone }}">{{ $result !== '' ? $result : ($signal->status === 'active' ? '' : '0 pips') }}</span>
                </div>
            @empty
                <div class="lm-tab-empty">No signals sent to this lead yet.</div>
            @endforelse
        </div>
        @include('admin.leadmax.partials.lead-tab-pager', ['rows' => $d['rows'], 'noun' => null, 'bare' => true])
    </div>

@elseif($tab === 'sessions')
    @php
        $yesNo = fn (bool $flag, bool $badWhenYes = true) => $badge($flag ? 'Yes' : 'No', $flag === $badWhenYes ? 'amber' : 'green');
        $device = fn ($type, $platform) => trim(ucfirst((string) ($type ?: 'Desktop')).($platform && in_array(strtolower((string) $type), ['mobile', 'tablet'], true) ? ' ('.$platform.')' : ''));
    @endphp
    <div class="lm-tab-cards">
        <div class="lm-tab-card"><span>Total Sessions</span><strong>{{ $d['total'] }}</strong></div>
        <div class="lm-tab-card"><span>Active Now</span><strong class="is-green has-dot">{{ $d['activeNow'] }}</strong></div>
        <div class="lm-tab-card"><span>Avg Session Duration</span><strong>{{ $dash }}</strong></div>
        <div class="lm-tab-card"><span>Last Login</span><strong>{{ $dateTime($d['lastLogin'], 'Y-m-d H:i') ?? $dash }}</strong></div>
    </div>
    <div class="lm-tab-table" style="--lm-min:1165px;--lm-cols:minmax(118px,118fr) minmax(118px,118fr) minmax(128px,128fr) minmax(127px,127fr) minmax(128px,128fr) minmax(155px,155fr) minmax(129px,129fr) minmax(132px,132fr) minmax(130px,130fr)">
        <div class="lm-tab-section-head"><h3>Current Active Sessions</h3>{!! $collapse !!}</div>
        <div class="lm-tab-collapsible">
            <div class="lm-tab-thead"><span>Session ID</span><span>Device</span><span>Browser</span><span>IP Address</span><span>Location</span><span>Login Time</span><span>Last Activity</span><span>Status</span><span class="is-right">Actions</span></div>
            @forelse($d['tokens'] as $token)
                <div class="lm-tab-row">
                    <span class="is-link">#SES-{{ $token['id'] }}</span>
                    <span>{{ $device($token['device_type'] ?? null, $token['platform'] ?? null) }}</span>
                    <span class="is-muted">{{ $token['browser'] ?? $dash }}</span>
                    <span>{{ $token['ip_address'] ?? $dash }}</span>
                    <span>{{ $token['location'] ?? $dash }}</span>
                    <span class="is-muted">{{ $dateTime($token['created_at'], 'Y-m-d H:i') }}</span>
                    <span>{{ $token['last_active_at'] ? \Carbon\Carbon::parse($token['last_active_at'])->diffForHumans() : $dash }}</span>
                    <span>{!! $token['active'] ? $badge('Active', 'green') : $badge('Idle', 'amber') !!}</span>
                    <span class="is-right"><button type="button" class="lm-badge lm-badge-danger-btn" data-lm-post="{{ route('admin.leadmax.leads.sessions.terminate', [$lead->id, $token['id']]) }}" data-lm-post-action="terminate" data-lm-confirm="Terminate this session? The client will be signed out on that device.">Terminate</button></span>
                </div>
            @empty
                <div class="lm-tab-empty">No active sessions.</div>
            @endforelse
        </div>
    </div>
    <div class="lm-tab-table" style="--lm-min:1180px;--lm-cols:minmax(118px,118fr) minmax(144px,144fr) minmax(127px,127fr) minmax(127px,127fr) minmax(128px,128fr) minmax(155px,155fr) minmax(155px,155fr) minmax(106px,106fr) minmax(120px,120fr)">
        <div class="lm-tab-section-head"><h3>Session History</h3>{!! $collapse !!}</div>
        <div class="lm-tab-collapsible">
            <div class="lm-tab-thead"><span>Session ID</span><span>Device</span><span>Browser</span><span>IP Address</span><span>Location</span><span>Login Time</span><span>Logout Time</span><span>Duration</span><span>Status</span></div>
            @forelse($d['rows'] as $login)
                <div class="lm-tab-row">
                    <span class="is-link">#SES-{{ $login->id }}</span>
                    <span>{{ $device($login->device_type, $login->platform) }}</span>
                    <span class="is-muted">{{ $login->browser ?: $dash }}</span>
                    <span>{{ $login->ip_address ?: $dash }}</span>
                    <span>{{ $login->location ?: $dash }}</span>
                    <span class="is-muted">{{ $login->created_at?->format('Y-m-d H:i') }}</span>
                    <span class="is-muted">{{ $dash }}</span>
                    <span>{{ $dash }}</span>
                    <span>{!! $login->status === 'failed' ? $badge('Failed', 'danger') : $badge('Expired', 'grey') !!}</span>
                </div>
            @empty
                <div class="lm-tab-empty">No login sessions recorded for this lead.</div>
            @endforelse
            @include('admin.leadmax.partials.lead-tab-pager', ['rows' => $d['rows'], 'noun' => 'sessions'])
        </div>
    </div>
    <section class="lm-kv-card"><div class="lm-tab-section-head"><h3>Security &amp; Login Alerts</h3>{!! $collapse !!}</div><div class="lm-tab-collapsible lm-kv-grid">
        {!! $kv('Failed Login Attempts (last 30 days)', (string) $d['failedAttempts']) !!}{!! $kv('Suspicious IP Detected', $yesNo($d['suspiciousIp'])) !!}
        {!! $kv('Multiple Device Login', $yesNo($d['multipleDevices'])) !!}{!! $kv('Last Password Change', $dateTime($d['lastPasswordChange'], 'Y-m-d')) !!}
        {!! $kv('Concurrent Sessions Allowed', null) !!}{!! $kv('Geolocation Anomaly', $d['geoAnomaly'] ? $badge('Detected', 'amber') : 'None') !!}
    </div></section>
@endif
</div>

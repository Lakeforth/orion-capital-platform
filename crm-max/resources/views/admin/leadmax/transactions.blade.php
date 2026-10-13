<x-leadmax-layout :central-url="$centralUrl" :active-lead-max-page="$activeLeadMaxPage">
@php
    $title = $kind === 'deposits' ? 'Deposits' : 'Withdrawals';
    $noun = $kind === 'deposits' ? 'deposit' : 'withdrawal';
    $statusClass = fn ($status) => match ($status) { 'approved' => 'lm-ftd-status-completed', 'pending' => 'lm-ftd-status-pending', default => 'lm-ftd-status-failed' };
    $listUrl = fn (array $query) => route('admin.leadmax.'.$kind, array_filter([...request()->except('page'), ...$query], fn ($value) => $value !== null && $value !== ''));
@endphp
<div class="leadmax-module-page lm-ftd-page">
    <header class="lm-ftd-header">
        <div class="lm-ftd-title-row">
            <h1>{{ $title }}</h1>
            <nav class="leadmax-breadcrumb"><a href="{{ route('admin.leadmax.dashboard') }}">Dashboard</a> <span>/</span> CRM <span>/</span> <strong>{{ $title }}</strong></nav>
        </div>
        <div class="lm-ftd-filter-row">
            <form class="leadmax-periods leadmax-dashboard-periods" method="GET">
                @foreach(['today' => 'Today', 'yesterday' => 'Yesterday', 'current_week' => 'Current week', 'current_month' => 'Current month', 'last_week' => 'Last week', 'last_month' => 'Last month'] as $val => $lbl)
                    <button type="submit" name="period" value="{{ $val }}" class="{{ $period === $val ? '' : 'leadmax-period-secondary' }}" aria-pressed="{{ $period === $val ? 'true' : 'false' }}">{{ $lbl }}</button>
                @endforeach
                @foreach(['status', 'method', 'country', 'created_by', 'manager_id', 'search'] as $keep)@if(request()->filled($keep))<input type="hidden" name="{{ $keep }}" value="{{ request($keep) }}">@endif @endforeach
                <div class="leadmax-custom-period"><label class="leadmax-date-field"><x-leadmax-reference-icon name="calendar" /><input type="text" name="from" value="{{ request('from') }}" placeholder="From date" aria-label="From date" data-leadmax-date></label><label class="leadmax-date-field"><x-leadmax-reference-icon name="calendar" /><input type="text" name="to" value="{{ request('to') }}" placeholder="To date" aria-label="To date" data-leadmax-date></label><button class="sr-only" type="submit" name="period" value="custom">Apply custom dates</button></div>
            </form>
        </div>
    </header>

    <div class="lm-ftd-kpis">
        <a class="lm-ftd-kpi" href="{{ $listUrl(['status' => 'approved']) }}">
            <div class="lm-ftd-kpi-icon" style="background:#0d3b3680"><span style="font:700 18px Inter,sans-serif;color:#34d399">$</span></div>
            <div class="lm-ftd-kpi-body"><span>Approved {{ strtolower($title) }}</span><strong>${{ number_format($approvedAmount, 0) }}</strong></div>
        </a>
        <a class="lm-ftd-kpi" href="{{ $listUrl(['status' => 'approved']) }}">
            <div class="lm-ftd-kpi-icon" style="background:#13305580"><span style="font:700 16px Inter,sans-serif;color:#2563eb">#</span></div>
            <div class="lm-ftd-kpi-body"><span>Approved count</span><strong>{{ number_format($approvedCount) }}</strong></div>
        </a>
        <a class="lm-ftd-kpi" href="{{ $listUrl(['status' => 'pending']) }}">
            <div class="lm-ftd-kpi-icon" style="background:#3b2a0d80"><span style="font:700 16px Inter,sans-serif;color:#f59e0b">⏳</span></div>
            <div class="lm-ftd-kpi-body"><span>Pending</span><strong>${{ number_format($pendingAmount, 0) }} · {{ number_format($pendingCount) }}</strong></div>
        </a>
        <div class="lm-ftd-kpi">
            <div class="lm-ftd-kpi-icon" style="background:#13305580"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#2563eb" stroke-width="2"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/></svg></div>
            <div class="lm-ftd-kpi-body"><span>Clients</span><strong>{{ number_format($clientCount) }}</strong></div>
        </div>
    </div>

    <div class="lm-ftd-table-card">
        <div class="lm-ftd-table-topbar">
            <div class="lm-ftd-table-title"><strong>{{ $title }} List</strong><span class="lm-ftd-count-badge">{{ number_format($transactions->total()) }} {{ \Illuminate\Support\Str::plural($noun, $transactions->total()) }}</span></div>
            <div class="lm-ftd-table-controls">
                <button type="button" class="lm-ftd-filter-btn" data-lm-filter-toggle aria-controls="lm-transactions-filters"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="4" y1="6" x2="20" y2="6"/><line x1="8" y1="12" x2="16" y2="12"/><line x1="11" y1="18" x2="13" y2="18"/></svg>Filters</button>
                <form method="GET" style="display:flex;align-items:center">
                    @foreach(['period', 'from', 'to', 'status', 'method', 'country', 'created_by', 'manager_id'] as $keep)@if(request()->filled($keep))<input type="hidden" name="{{ $keep }}" value="{{ request($keep) }}">@endif @endforeach
                    <div class="lm-ftd-search-wrap"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg><input type="search" name="search" value="{{ request('search') }}" placeholder="Search client..." aria-label="Search {{ strtolower($title) }}" class="lm-ftd-search-input"></div>
                </form>
            </div>
        </div>
        @include('admin.leadmax.partials.filter-panel', ['panelId' => 'lm-transactions-filters', 'keep' => ['period', 'from', 'to', 'search'], 'selects' => [
            'status' => ['Status', ['approved' => 'Approved', 'pending' => 'Pending', 'rejected' => 'Rejected', 'failed' => 'Failed']],
            'method' => ['Method', $methods->mapWithKeys(fn ($m) => [$m => $m])->all()],
            'country' => ['Country', $filterCountries->mapWithKeys(fn ($c) => [$c => $c])->all()],
            'created_by' => ['Affiliate', $filterAffiliates->mapWithKeys(fn ($u) => [$u->id => $u->name])->all()],
            'manager_id' => ['Agent', $filterOwners->mapWithKeys(fn ($u) => [$u->id => $u->name])->all()],
        ]])
        <div class="lm-ftd-table-wrap">
            <table class="lm-ftd-table">
                <thead><tr><th>ID</th><th>Client</th><th>Country</th><th>Affiliate</th><th>Agent</th><th>Amount</th><th>Method</th><th>Date</th><th>Status</th></tr></thead>
                <tbody>
                @forelse($transactions as $transaction)
                    <tr>
                        <td class="lm-ftd-id-link">@if($transaction->user)<a href="{{ route('admin.leadmax.leads.show', $transaction->user->id) }}?tab=finances">{{ $transaction->user->account_id ?: substr($transaction->user->id, 0, 8) }}</a>@else — @endif</td>
                        <td class="lm-ftd-name">{{ $transaction->user?->name ?: '—' }}</td>
                        <td>{{ $transaction->user?->country ?: '—' }}</td>
                        <td>{{ $transaction->user?->affiliate?->name ?: 'Direct' }}</td>
                        <td>{{ $transaction->user?->manager?->name ?: 'Unassigned' }}</td>
                        <td class="lm-ftd-amount">${{ number_format((float) $transaction->amount, 2) }}</td>
                        <td>{{ $transaction->method ?: '—' }}</td>
                        <td class="lm-ftd-date">{{ $transaction->created_at?->format('Y-m-d H:i') }}</td>
                        <td><span class="lm-ftd-status-badge {{ $statusClass($transaction->status) }}">{{ ucfirst((string) $transaction->status) }}</span></td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="lm-ftd-empty">No {{ strtolower($title) }} found for this period.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="lm-ftd-pagination">
            <span>Showing {{ $transactions->firstItem() ?? 0 }} to {{ $transactions->lastItem() ?? 0 }} of {{ number_format($transactions->total()) }} entries</span>
            <div class="lm-ftd-page-controls">
                @if($transactions->onFirstPage())<span class="lm-ftd-page-btn disabled">&lsaquo;</span>@else<a href="{{ $transactions->previousPageUrl() }}" class="lm-ftd-page-btn">&lsaquo;</a>@endif
                @foreach($transactions->getUrlRange(max(1, $transactions->currentPage() - 2), min($transactions->lastPage(), $transactions->currentPage() + 2)) as $page => $url)<a href="{{ $url }}" class="lm-ftd-page-btn{{ $page === $transactions->currentPage() ? ' active' : '' }}">{{ $page }}</a>@endforeach
                @if($transactions->hasMorePages())<a href="{{ $transactions->nextPageUrl() }}" class="lm-ftd-page-btn">&rsaquo;</a>@else<span class="lm-ftd-page-btn disabled">&rsaquo;</span>@endif
            </div>
        </div>
    </div>
</div>
</x-leadmax-layout>

{{-- LeadMax Open Trades / All Trades: the leads' trades, with edit / close / delete for admins. --}}
@php
    $route = $scope === 'open' ? 'admin.leadmax.trades.open' : 'admin.leadmax.trades';
    $query = request()->except(['trade', 'dialog', 'page']);
    $url = fn (array $extra = []) => route($route, array_merge($query, $extra));
    $back = $url(['page' => request('page')]);
    $money = fn ($v) => ($v < 0 ? '-$' : '$').number_format(abs((float) $v), 2);
    $price = function ($trade, $value) {
        if ($value === null || (float) $value == 0.0) return '—';
        $p = rescue(fn () => $trade->asset?->displayPrecision(), null, false) ?? ((float) $value < 10 ? 5 : 2);
        return number_format((float) $value, $p, '.', ',');
    };
    $symbol = fn ($trade) => $trade->asset?->sym_key ?: ($trade->asset?->sym ?: '—');
    $title = $scope === 'open' ? 'Open Trades' : 'All Trades';
    // Trade times can come back as strings (raw columns / accessors), so parse before formatting.
    $dt = fn ($value, string $format = 'Y-m-d H:i') => $value ? \Carbon\Carbon::parse($value)->format($format) : '';
@endphp
<x-leadmax-layout :central-url="$centralUrl" :active-lead-max-page="$activeLeadMaxPage">
<div class="lm-tools lm-trades">
    <header class="lm-tools-top">
        <div><h1>{{ $title }}@if($lead) <span class="lm-trades-for">· {{ $lead->name }}</span>@endif</h1><p>{{ $scope === 'open' ? 'Positions your clients have open right now, with live P&L.' : 'Every trade your clients have opened or closed.' }}@if($canEdit) Admins can edit, close and delete trades.@endif</p></div>
        <nav class="lm-tools-crumbs" aria-label="Breadcrumb"><a href="{{ route('admin.leadmax.dashboard') }}">Dashboard</a><i>/</i><b>{{ $title }}</b></nav>
    </header>

    <section class="lm-trades-kpis" aria-label="Totals">
        <article><span>Open positions</span><strong>{{ number_format((int) $totals->open_count) }}</strong></article>
        <article><span>Floating P&amp;L</span><strong class="{{ (float) $totals->floating < 0 ? 'is-down' : 'is-up' }}">{{ $money($totals->floating) }}</strong></article>
        <article><span>Margin in use</span><strong>{{ $money($totals->margin) }}</strong></article>
        @if($scope === 'all')
            <article><span>Closed trades</span><strong>{{ number_format((int) $totals->closed_count) }}</strong></article>
            <article><span>Realised P&amp;L</span><strong class="{{ (float) $totals->realised < 0 ? 'is-down' : 'is-up' }}">{{ $money($totals->realised) }}</strong></article>
        @endif
    </section>

    <section class="lm-tools-card">
        <header class="lm-tools-card-head">
            <div><h2>{{ $title }}</h2><span>{{ number_format($trades->total()) }} trades</span></div>
            <form method="GET" class="lm-tools-search" role="search">
                @if($lead)<input type="hidden" name="lead_id" value="{{ $lead->id }}">@endif
                <input type="search" name="search" value="{{ $search }}" placeholder="Client, email, trade ID or symbol" aria-label="Search trades">
                <select name="type" class="lm-tools-select" aria-label="Direction"><option value="">Buy &amp; sell</option><option value="buy" @selected($type === 'buy')>Buy</option><option value="sell" @selected($type === 'sell')>Sell</option></select>
                @if($scope === 'all')<select name="state" class="lm-tools-select" aria-label="Status"><option value="">Open &amp; closed</option><option value="open" @selected($state === 'open')>Open</option><option value="closed" @selected($state === 'closed')>Closed</option></select>@endif
                <button class="lm-tools-btn">Search</button>
                @if($search !== '' || $type || ($scope === 'all' && $state) || $lead)<a class="lm-tools-btn" href="{{ route($route) }}">Reset</a>@endif
            </form>
        </header>
        <div class="lm-tools-table-wrap">
            <table class="lm-tools-table">
                <thead><tr><th>Client</th><th>Trade</th><th>Symbol</th><th>Type</th><th class="num">Volume</th><th class="num">Open price</th><th class="num">{{ $scope === 'open' ? 'Current' : 'Current / close' }}</th><th class="num">TP</th><th class="num">SL</th><th class="num">Leverage</th><th class="num">Margin</th><th class="num">P&amp;L</th><th>Opened</th>@if($scope === 'all')<th>Closed</th>@endif<th>Status</th>@if($canEdit)<th>Actions</th>@endif</tr></thead>
                <tbody>
                @forelse($trades as $trade)
                    @php $isOpen = (int) $trade->status === 0; @endphp
                    <tr>
                        <td>@if($trade->user)<a class="lm-trades-client" href="{{ $trade->user->hasRole('lead') ? route('admin.leadmax.leads.show', [$trade->user_id, 'tab' => 'trading']) : route('admin.users.show', $trade->user_id) }}"><strong>{{ $trade->user->name }}</strong><small>#{{ $trade->tradingAccount?->account_id ?? ($trade->user->account_id ?: '—') }}</small></a>@else — @endif</td>
                        <td class="mono"><small>{{ \Illuminate\Support\Str::limit((string) $trade->trade_id, 10, '…') }}</small></td>
                        <td><strong class="notranslate">{{ $symbol($trade) }}</strong></td>
                        <td><span class="lm-tools-badge {{ $trade->trade_type === 'buy' ? 'is-live' : 'is-off' }}">{{ strtoupper($trade->trade_type) }}</span></td>
                        <td class="num mono">{{ number_format((float) ($trade->volume_lots ?? $trade->volume), 2) }}</td>
                        <td class="num mono">{{ $price($trade, $trade->opening_price) }}</td>
                        <td class="num mono">{{ $price($trade, $trade->closing_price) }}</td>
                        <td class="num mono">{{ $price($trade, $trade->take_profit) }}</td>
                        <td class="num mono">{{ $price($trade, $trade->stop_loss) }}</td>
                        <td class="num">1:{{ (int) $trade->leverage }}</td>
                        <td class="num mono">{{ $money($trade->margin) }}</td>
                        <td class="num mono {{ (float) $trade->profit < 0 ? 'is-down' : 'is-up' }}">{{ $money($trade->profit) }}</td>
                        <td>{{ $dt($trade->opened_at ?? $trade->created_at) }}</td>
                        @if($scope === 'all')<td>{{ $isOpen ? '—' : $dt($trade->close_at ?? $trade->updated_at) }}</td>@endif
                        <td><span class="lm-tools-badge {{ $isOpen ? 'is-live' : 'is-low' }}">{{ $isOpen ? 'Open' : 'Closed' }}</span></td>
                        @if($canEdit)
                            <td class="lm-trades-actions">
                                <a class="lm-tools-btn" href="{{ $url(['trade' => $trade->id, 'dialog' => 'edit', 'page' => request('page')]) }}">Edit</a>
                                @if($isOpen)<a class="lm-tools-btn" href="{{ $url(['trade' => $trade->id, 'dialog' => 'close', 'page' => request('page')]) }}">Close</a>@endif
                                <a class="lm-tools-btn is-danger" href="{{ $url(['trade' => $trade->id, 'dialog' => 'delete', 'page' => request('page')]) }}">Delete</a>
                            </td>
                        @endif
                    </tr>
                @empty
                    <tr><td colspan="{{ ($scope === 'all' ? 15 : 14) + ($canEdit ? 1 : 0) }}" class="lm-tools-empty">{{ $scope === 'open' ? 'No open trades.' : 'No trades match.' }}</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        @if($trades->hasPages())<footer class="lm-tools-pager">@include('admin.leadmax.partials.leads-pagination', ['paginator' => $trades])</footer>@endif
    </section>

    @if($editing && $canEdit)
        @php $isOpen = (int) $editing->status === 0; $val = fn ($v) => $v === null || (float) $v == 0.0 ? '' : rtrim(rtrim(number_format((float) $v, 6, '.', ''), '0'), '.'); @endphp
        <div class="lm-modal-backdrop">
        <section role="dialog" aria-modal="true" aria-labelledby="lm-trade-dialog-title" tabindex="-1" class="lm-tools-dialog" data-lm-dialog>
            <header><div><h2 id="lm-trade-dialog-title">{{ ['edit' => 'Edit trade', 'close' => 'Close trade', 'delete' => 'Delete trade'][$dialog] ?? 'Edit trade' }}</h2><p>{{ $editing->user?->name }} · <span class="notranslate">{{ $symbol($editing) }}</span> {{ strtoupper($editing->trade_type) }} · {{ $isOpen ? 'open' : 'closed' }} · {{ $editing->trade_id }}</p></div><a class="lm-tools-close" href="{{ $back }}" aria-label="Close dialog">×</a></header>
            @if($dialog === 'close' && $isOpen)
                <form data-lm-submit action="{{ route('admin.leadmax.trades.close', $editing->id) }}" method="POST" data-lm-success="{{ $back }}">@csrf
                    <p class="lm-tools-dialog-text">Close this position at the current market price? The P&amp;L (now {{ $money($editing->profit) }}) is settled into the client's account, exactly as if the client closed it.</p>
                    <p role="alert" class="lm-form-error" hidden></p>
                    <footer><a class="lm-tools-btn" href="{{ $back }}">Cancel</a><button class="lm-tools-btn is-primary">Close trade</button></footer>
                </form>
            @elseif($dialog === 'delete')
                <form data-lm-submit action="{{ route('admin.leadmax.trades.destroy', $editing->id) }}" method="POST" data-lm-success="{{ $back }}">@csrf @method('DELETE')
                    <p class="lm-tools-dialog-text">Deleting removes the trade from the client's history. This cannot be undone.</p>
                    @unless($isOpen)<label class="lm-tools-check"><input type="checkbox" name="reverse_pnl" value="1"> Also reverse its P&amp;L ({{ $money($editing->profit) }}) on the client's account</label>@endunless
                    <label class="lm-tools-field">Your password to confirm<input type="password" name="password" required autocomplete="current-password"></label>
                    <p role="alert" class="lm-form-error" hidden></p>
                    <footer><a class="lm-tools-btn" href="{{ $back }}">Cancel</a><button class="lm-tools-btn is-danger">Delete trade</button></footer>
                </form>
            @else
                <form data-lm-submit action="{{ route('admin.leadmax.trades.update', $editing->id) }}" method="POST" data-lm-success="{{ $back }}">@csrf
                    <div class="lm-tools-grid">
                        <label class="lm-tools-field">Opening price<input name="opening_price" inputmode="decimal" required value="{{ $val($editing->opening_price) }}"></label>
                        @if($isOpen)
                            <label class="lm-tools-field">Current price<input value="{{ $val($editing->closing_price) }}" disabled></label>
                        @else
                            <label class="lm-tools-field">Closing price<input name="closing_price" inputmode="decimal" required value="{{ $val($editing->closing_price) }}"></label>
                        @endif
                        <label class="lm-tools-field">Volume (units)<input name="volume" inputmode="decimal" required value="{{ $val($editing->volume) }}"></label>
                        <label class="lm-tools-field">Leverage (1:x)<input name="leverage" inputmode="numeric" required value="{{ (int) $editing->leverage }}"></label>
                        <label class="lm-tools-field">Take profit<input name="take_profit" inputmode="decimal" placeholder="Not set" value="{{ $val($editing->take_profit) }}"></label>
                        <label class="lm-tools-field">Stop loss<input name="stop_loss" inputmode="decimal" placeholder="Not set" value="{{ $val($editing->stop_loss) }}"></label>
                        <label class="lm-tools-field">P&amp;L ($)<input name="profit" inputmode="decimal" value="{{ round((float) $editing->profit, 2) }}"></label>
                        <label class="lm-tools-field">Opened at<input type="datetime-local" name="opened_at" value="{{ $dt($editing->opened_at ?? $editing->created_at, 'Y-m-d\TH:i') }}"></label>
                        @unless($isOpen)<label class="lm-tools-field">Closed at<input type="datetime-local" name="close_at" value="{{ $dt($editing->close_at, 'Y-m-d\TH:i') }}"></label>@endunless
                    </div>
                    <p class="lm-tools-dialog-text is-small">{{ $isOpen ? 'Open trade: its P&L follows the market. Change the P&L to set it directly (the opening price is recalculated to match), or change the opening price, volume or leverage and the P&L is recalculated.' : 'Closed trade: its P&L was already paid into the client\'s account. Changing the P&L credits or debits the client\'s trading account by the difference.' }}</p>
                    <label class="lm-tools-field">Reason / note<textarea name="note" maxlength="500" rows="2" placeholder="Why this trade was changed (kept in the lead's history)"></textarea></label>
                    <p role="alert" class="lm-form-error" hidden></p>
                    <footer><a class="lm-tools-btn" href="{{ $back }}">Cancel</a><button class="lm-tools-btn is-primary">Save changes</button></footer>
                </form>
            @endif
        </section>
        </div>
    @endif
</div>
</x-leadmax-layout>

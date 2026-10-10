{{-- Global search results (top bar search). --}}
@php
    use App\Http\Controllers\Admin\LeadMaxController as LM;
    $sections = [
        'leads' => ['Leads', fn ($u) => route('admin.leadmax.leads.show', $u->id), route('admin.leadmax.leads', ['period' => 'all_time', 'search' => $q])],
        'affiliates' => ['Affiliates', fn ($u) => route('admin.leadmax.affiliates.show', $u->id), route('admin.leadmax.affiliates', ['search' => $q])],
        'agents' => ['Agents', fn ($u) => route('admin.leadmax.agents.show', $u->id), route('admin.leadmax.agents', ['search' => $q])],
        'managers' => ['Managers', fn ($u) => route('admin.leadmax.managers.show', $u->id), route('admin.leadmax.managers', ['search' => $q])],
    ];
@endphp
<x-leadmax-layout :central-url="$centralUrl" :active-lead-max-page="$activeLeadMaxPage">
<div class="lm-search">
    <header class="lm-search-top">
        <div><h1>Search</h1><p>@if($q === '')Search leads, affiliates, agents, managers and transactions.@elseif(mb_strlen($q) < 2)Type at least 2 characters.@else{{ $total }} {{ \Illuminate\Support\Str::plural('result', $total) }} for “{{ $q }}”@endif</p></div>
        <form method="GET" action="{{ route('admin.leadmax.search') }}" class="lm-search-form" role="search"><input type="search" name="q" value="{{ $q }}" placeholder="Name, email, phone, account or transaction ID" aria-label="Search" minlength="2" required autofocus><button type="submit">Search</button></form>
    </header>

    @if($groups)
        <div class="lm-search-grid">
            @foreach($sections as $key => [$title, $link, $more])
                @if($key !== 'managers' || is_admin())
                <section class="lm-search-card">
                    <header><h2>{{ $title }}</h2>@if($groups[$key]->count() > \App\Http\Controllers\Admin\LeadMaxSearchController::LIMIT)<a href="{{ $more }}">View all</a>@endif</header>
                    @forelse($groups[$key]->take(\App\Http\Controllers\Admin\LeadMaxSearchController::LIMIT) as $user)
                        <a class="lm-search-row" href="{{ $link($user) }}"><b>{{ $user->name }}</b><span>{{ $user->email }}@if($user->account_id) · #{{ $user->account_id }}@endif</span></a>
                    @empty
                        <p class="lm-search-empty">No {{ strtolower($title) }} match.</p>
                    @endforelse
                </section>
                @endif
            @endforeach
            <section class="lm-search-card">
                <header><h2>Transactions</h2></header>
                @forelse($groups['transactions']->take(\App\Http\Controllers\Admin\LeadMaxSearchController::LIMIT) as $tx)
                    @php $deposit = in_array($tx->getRawOriginal('type'), LM::DEPOSIT_TYPES, true); @endphp
                    <a class="lm-search-row" href="{{ $tx->user ? route('admin.leadmax.leads.show', [$tx->user_id, 'tab' => 'finances']) : route('admin.leadmax.'.($deposit ? 'deposits' : 'withdrawals')) }}"><b>{{ $deposit ? 'Deposit' : 'Withdrawal' }} · ${{ number_format((float) $tx->amount, 2) }}</b><span>@if($tx->order_id)#{{ $tx->order_id }} · @endif{{ $tx->user?->name ?? 'Unknown lead' }} · {{ ucfirst((string) $tx->status) }} · {{ $tx->created_at?->format('Y-m-d') }}</span></a>
                @empty
                    <p class="lm-search-empty">No transactions match.</p>
                @endforelse
            </section>
        </div>
    @endif
</div>
</x-leadmax-layout>

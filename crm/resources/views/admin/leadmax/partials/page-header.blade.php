<div class="leadmax-page-header">
    <div class="leadmax-title-row"><h1>{{ $title }}</h1><div class="leadmax-breadcrumb">Dashboard / <strong>CRM</strong> / {{ $title }}</div></div>
    <form method="GET" class="leadmax-toolbar">
        <div class="leadmax-periods">
            @foreach(['today'=>'Today','yesterday'=>'Yesterday','current_week'=>'Current week','current_month'=>'Current month','last_week'=>'Last week','last_month'=>'Last month'] as $value => $label)
                <button name="period" value="{{ $value }}" class="{{ request('period', 'current_month') === $value ? 'selected' : '' }}">{{ $label }}</button>
            @endforeach
            <input type="date" name="from" value="{{ request('from') }}" aria-label="From date"><input type="date" name="to" value="{{ request('to') }}" aria-label="To date">
            <button name="period" value="custom">Apply</button>
        </div>
        @if($search ?? true)<div class="leadmax-search"><input name="search" value="{{ request('search') }}" placeholder="Search by name, phone, email..."><button aria-label="Search">{{ svg('heroicon-o-magnifying-glass','h-4 w-4') }}</button></div>@endif
    </form>
</div>

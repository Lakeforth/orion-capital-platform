{{-- LeadMax Markups: this company's spread markup and leverage per asset category (same keys as Settings > Trade). --}}
@php
    $categories = \App\Http\Controllers\Admin\LeadMaxToolsController::CATEGORIES;
    $lock = $canEdit ? '' : 'disabled';
@endphp
<x-leadmax-layout :central-url="$centralUrl" :active-lead-max-page="$activeLeadMaxPage">
<div class="lm-tools">
    <header class="lm-tools-top">
        <div><h1>Markups</h1><p>Spread markup and leverage for each asset category. Changes apply to new trades.</p></div>
        <nav class="lm-tools-crumbs" aria-label="Breadcrumb"><a href="{{ route('admin.leadmax.dashboard') }}">Dashboard</a><i>/</i><b>Markups</b></nav>
    </header>

    @if(session('success'))<p class="lm-tools-flash" role="status">{{ session('success') }}</p>@endif
    @if($errors->any())<p class="lm-tools-flash is-warn" role="alert">{{ $errors->first() }}</p>@endif
    @unless($canEdit)<p class="lm-tools-flash is-info">Only an admin can change markups. You can view them here.</p>@endunless

    <form method="POST" action="{{ route('admin.leadmax.markups.save') }}" class="lm-tools-card">
        @csrf
        <header class="lm-tools-card-head">
            <div><h2>Pricing</h2><span>{{ $twoSided ? 'Two-sided pricing: buy markup raises the ask, sell markup lowers the bid.' : 'One-sided pricing: the buy markup is added to the ask; the sell markup is not used.' }}</span></div>
            <label class="lm-tools-inline">Leverage
                <select name="leverage_system" {{ $lock }}>
                    @foreach($leverageSystems as $value => $label)<option value="{{ $value }}" @selected(old('leverage_system', $leverageSystem) === $value)>{{ $label }}</option>@endforeach
                </select>
            </label>
        </header>
        <div class="lm-tools-table-wrap">
            <table class="lm-tools-table">
                <thead><tr><th>Category</th><th>Leverage (1:x)</th><th>Buy markup (%)</th><th>Sell markup (%)</th></tr></thead>
                <tbody>
                @foreach($categories as $key => [$label])
                    <tr>
                        <td><strong>{{ $label }}</strong></td>
                        <td><input class="lm-tools-input" type="number" name="{{ $key }}_leverage" min="1" max="1000" step="any" value="{{ old($key.'_leverage', $values[$key]['leverage']) }}" aria-label="{{ $label }} leverage" {{ $lock }}></td>
                        <td><input class="lm-tools-input" type="number" name="{{ $key }}_buy_spread" min="0" max="2" step="any" required value="{{ old($key.'_buy_spread', $values[$key]['buy']) }}" aria-label="{{ $label }} buy markup" {{ $lock }}></td>
                        <td><input class="lm-tools-input" type="number" name="{{ $key }}_sell_spread" min="0" max="2" step="any" required value="{{ old($key.'_sell_spread', $values[$key]['sell']) }}" aria-label="{{ $label }} sell markup" {{ $lock }} @unless($twoSided) title="Not used with one-sided pricing" @endunless></td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        <footer class="lm-tools-card-foot">
            <span>Leverage per category is used when Leverage is set to "Asset Category Based". Markups are capped at 2%.</span>
            @if($canEdit)<button class="lm-tools-btn is-primary">Save markups</button>@endif
        </footer>
    </form>

    @if($history->isNotEmpty())
    <section class="lm-tools-card">
        <header class="lm-tools-card-head"><div><h2>Recent changes</h2></div></header>
        <ul class="lm-tools-history">
            @foreach($history as $log)
                <li><b>{{ $log->created_at?->format('Y-m-d H:i') }}</b> {{ $log->info['actor'] ?? 'Admin' }}:
                    {{ collect($log->info['changes'] ?? [])->map(fn ($c, $k) => str_replace('_', ' ', $k).' '.($c['from'] ?? '—').' → '.$c['to'])->implode(', ') }}</li>
            @endforeach
        </ul>
    </section>
    @endif
</div>
</x-leadmax-layout>

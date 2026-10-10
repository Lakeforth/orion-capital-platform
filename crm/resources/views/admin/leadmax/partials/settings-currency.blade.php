{{-- Settings > Currency Management (Figma 2551:21745). Selects save on change; rates come from the shared currencies table. --}}
@php
    use App\Http\Controllers\Admin\LeadMaxSettingsController as Settings;
    $icon = fn (string $name, $w, $h = null) => '<img src="'.asset('images/leadmax/settings/'.$name.'.svg').'" width="'.$w.'" height="'.($h ?? $w).'" alt="">';
    $chevron = $icon('cur-chevron', 8, 5);
    $canEdit = is_admin();
    $base = $cur['base'];
    $select = function (string $name, array $options, $value) use ($chevron, $canEdit) {
        $html = '<span class="lm-cur-select"><select name="'.$name.'" data-lm-autosubmit '.($canEdit ? '' : 'disabled').'>';
        foreach ($options as $key => $label) {
            $html .= '<option value="'.e($key).'"'.((string) $key === (string) $value ? ' selected' : '').'>'.e($label).'</option>';
        }

        return $html.'</select>'.$chevron.'</span>';
    };
    $currencyOptions = $allCurrencies->mapWithKeys(fn ($c) => [$c->code => $c->code.($c->name ? ' - '.$c->name : '')])->all();
    $decimals = collect(range(0, 6))->mapWithKeys(fn ($n) => [$n => $n === 1 ? '1 decimal place' : $n.' decimal places'])->all();
    $stateLabel = ['connected' => 'Connected', 'delayed' => 'Delayed', 'manual' => 'Manual'][$provider['state']];
@endphp
<div class="lm-set-grid lm-cur">
    <div class="lm-set-main">
        <form method="POST" action="{{ route('admin.leadmax.settings.currency') }}" class="lm-cur-card">
            @csrf
            <header><h2>Base Currency Configuration</h2><p>Set your platform's default currency and display preferences.</p></header>
            <div class="lm-cur-fields">
                <label><span>Base Currency</span>{!! $select('base', $currencyOptions ?: ['USD' => 'USD - US Dollar'], $base) !!}</label>
                <label><span>Currency Display Format</span>{!! $select('display', Settings::DISPLAY_FORMATS, $cur['display']) !!}</label>
                <label><span>Decimal Precision</span>{!! $select('decimals', $decimals, $cur['decimals']) !!}</label>
                <label><span>Thousands Separator</span>{!! $select('separator', Settings::SEPARATORS, $cur['separator']) !!}</label>
            </div>
        </form>

        <section class="lm-cur-card">
            <header class="is-row">
                <div><h2>Supported Currencies</h2><p>Manage which currencies are available on your platform.</p></div>
                <form method="GET" class="lm-cur-search" role="search"><input type="hidden" name="tab" value="currency">{!! $icon('cur-search', 12) !!}<input type="search" name="q" value="{{ request('q') }}" placeholder="Search currencies..." aria-label="Search currencies"></form>
            </header>
            <div class="lm-cur-tablewrap">
                <table class="lm-cur-table">
                    <thead><tr><th>Currency</th><th>Symbol</th><th>Exchange Rate</th><th>Status</th><th>Last Updated</th></tr></thead>
                    <tbody>
                        @forelse($currencyRows as $row)
                            <tr>
                                <td><span class="lm-cur-name">@if($row['flag'])<img src="{{ $row['flag'] }}" width="20" height="14" alt="">@else<i></i>@endif<b>{{ $row['code'] }}</b>@if($row['name'])<em>- {{ $row['name'] }}</em>@endif</span></td>
                                <td>{{ $row['symbol'] ?: '—' }}</td>
                                <td class="is-rate">{{ number_format($row['rate'], 6, '.', '') }}</td>
                                <td>
                                    <form method="POST" action="{{ route('admin.leadmax.settings.currency.toggle', $row['code']) }}">@csrf<input type="hidden" name="q" value="{{ request('q') }}">
                                        <button type="submit" class="lm-cur-switch {{ $row['enabled'] ? 'is-on' : '' }}" role="switch" aria-checked="{{ $row['enabled'] ? 'true' : 'false' }}" aria-label="{{ $row['enabled'] ? 'Disable' : 'Enable' }} {{ $row['code'] }}" @disabled(! $canEdit || $row['base'])><i></i></button>
                                    </form>
                                </td>
                                <td class="{{ $row['base'] ? 'is-base' : 'is-muted' }}">{{ $row['base'] ? 'Base Currency' : $row['updated'] }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="lm-cur-empty">{{ request('q') ? 'No currencies match your search.' : 'No currencies are configured.' }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <form method="POST" action="{{ route('admin.leadmax.settings.currency') }}" class="lm-cur-card">
            @csrf
            <header><h2>Conversion Fee Settings</h2><p>Configure fees applied to currency conversions.</p></header>
            <div class="lm-cur-fields">
                <label><span>Default Conversion Fee %</span><span class="lm-cur-input is-suffix" data-affix="%"><input type="number" name="fee_percent" value="{{ number_format($cur['fee_percent'], 2, '.', '') }}" min="0" max="100" step="0.01" data-lm-autosubmit @disabled(! $canEdit)></span></label>
                <label><span>Minimum Fee ($)</span><span class="lm-cur-input is-prefix" data-affix="$"><input type="number" name="fee_min" value="{{ number_format($cur['fee_min'], 2, '.', '') }}" min="0" step="0.01" data-lm-autosubmit @disabled(! $canEdit)></span></label>
                <label><span>Maximum Fee ($)</span><span class="lm-cur-input is-prefix" data-affix="$"><input type="number" name="fee_max" value="{{ number_format($cur['fee_max'], 2, '.', '') }}" min="0" step="0.01" data-lm-autosubmit @disabled(! $canEdit)></span></label>
                <label><span>Fee Rounding</span>{!! $select('fee_rounding', Settings::ROUNDING, $cur['fee_rounding']) !!}</label>
            </div>
        </form>
    </div>

    <aside class="lm-set-side">
        <section class="lm-cur-card is-side">
            <h2>Exchange Rate Provider</h2>
            <div class="lm-cur-provider"><b>{{ $provider['label'] }}</b><span class="is-{{ $provider['state'] }}"><i></i>{{ $stateLabel }}</span></div>
            <form method="POST" action="{{ route('admin.leadmax.settings.currency') }}">@csrf<label class="lm-cur-label"><span>Auto-Update Frequency</span>{!! $select('frequency', Settings::FREQUENCIES, $cur['frequency']) !!}</label></form>
            @if($canEdit)
                <div class="lm-cur-provider-actions">
                    <form method="POST" action="{{ route('admin.leadmax.settings.currency.test') }}">@csrf<button type="submit" class="lm-cur-outline">Test Connection</button></form>
                    <details class="lm-cur-more"><summary>Change Provider</summary><p role="note">Rates come from the platform price feed that also prices trading assets. Other providers are connected by the platform administrator.</p></details>
                </div>
            @endif
        </section>

        <section class="lm-cur-card is-side">
            <header><h2>Rate Alert Rules</h2><p>Get notified on high exchange rate variance.</p></header>
            <div class="lm-cur-alerts">
                @forelse($cur['alerts'] as $i => $alert)
                    <div class="lm-cur-alert">
                        <div><b>{{ $alert['code'] }}/{{ $base }}</b><small>±{{ rtrim(rtrim(number_format($alert['threshold'], 2), '0'), '.') }}% change</small></div>
                        @if($canEdit)
                            <form method="POST" action="{{ route('admin.leadmax.settings.currency.alerts.destroy', $i) }}">@csrf @method('DELETE')<button type="submit" class="lm-cur-remove" aria-label="Remove {{ $alert['code'] }} alert" data-lm-confirm-submit="Remove the {{ $alert['code'] }}/{{ $base }} alert?">×</button></form>
                        @endif
                        <form method="POST" action="{{ route('admin.leadmax.settings.currency.alerts.toggle', $i) }}">@csrf<button type="submit" class="lm-cur-switch {{ ($alert['enabled'] ?? false) ? 'is-on' : '' }}" role="switch" aria-checked="{{ ($alert['enabled'] ?? false) ? 'true' : 'false' }}" aria-label="Toggle {{ $alert['code'] }} alert" @disabled(! $canEdit)><i></i></button></form>
                    </div>
                @empty
                    <p class="lm-cur-empty">No alert rules yet.</p>
                @endforelse
            </div>
            @if($canEdit)
                <details class="lm-cur-add">
                    <summary class="lm-cur-outline is-accent">+ Add Alert Rule</summary>
                    <form method="POST" action="{{ route('admin.leadmax.settings.currency.alerts.store') }}" class="lm-cur-add-form">
                        @csrf
                        <label><span>Currency</span><span class="lm-cur-select"><select name="code" required>@foreach($allCurrencies->where('code', '!=', $base) as $c)<option value="{{ $c->code }}">{{ $c->code }}/{{ $base }}</option>@endforeach</select>{!! $chevron !!}</span></label>
                        <label><span>Alert at change (%)</span><span class="lm-cur-input is-suffix" data-affix="%"><input type="number" name="threshold" value="2" min="0.1" max="100" step="0.1" required></span></label>
                        <button type="submit" class="lm-set-save">Add Rule</button>
                    </form>
                </details>
            @endif
        </section>

        @if($canEdit)
        <section class="lm-cur-card is-side">
            <h2>Quick Actions</h2>
            <div class="lm-cur-actions">
                <form method="POST" action="{{ route('admin.leadmax.settings.currency.refresh') }}">@csrf<button type="submit" class="lm-cur-action"><span class="lm-set-action-icon">{!! $icon('cur-refresh', 11) !!}</span><span><b>Force Rate Refresh</b><small>Manually update all exchange rates</small></span>{!! $icon('cur-arrow', 5, 8) !!}</button></form>
                <a href="{{ route('admin.leadmax.settings.currency.history') }}" class="lm-cur-action"><span class="lm-set-action-icon">{!! $icon('cur-download', 11) !!}</span><span><b>Export Rate History</b><small>Download historical rate data as CSV</small></span>{!! $icon('cur-arrow', 5, 8) !!}</a>
                <a href="{{ route('admin.leadmax.settings', ['tab' => 'currency', 'panel' => 'audit']) }}" class="lm-cur-action"><span class="lm-set-action-icon">{!! $icon('cur-history', 11) !!}</span><span><b>Rate Audit Log</b><small>View all rate change history</small></span>{!! $icon('cur-arrow', 5, 8) !!}</a>
            </div>
        </section>
        @endif
    </aside>
</div>

@if($audit !== null)
    <div class="lm-cur-overlay" role="dialog" aria-modal="true" aria-labelledby="lm-cur-audit-title">
        <div class="lm-cur-dialog">
            <header><h2 id="lm-cur-audit-title">Rate Audit Log</h2><a href="{{ route('admin.leadmax.settings', ['tab' => 'currency']) }}" aria-label="Close">×</a></header>
            <p>Latest exchange rate changes (units per 1 USD). Rates are recorded hourly, or sooner when they move {{ \App\Services\LeadMax\CurrencyRates::LOG_MOVE }}% or more.</p>
            <div class="lm-cur-tablewrap">
                <table class="lm-cur-table">
                    <thead><tr><th>Date</th><th>Currency</th><th>Previous</th><th>New</th><th>Change</th></tr></thead>
                    <tbody>
                        @forelse($audit as $log)
                            <tr><td>{{ $log->created_at->format('Y-m-d H:i') }}</td><td><b>{{ $log->code }}</b></td><td>{{ $log->old_rate !== null ? number_format($log->old_rate, 6, '.', '') : '—' }}</td><td class="is-rate">{{ number_format($log->new_rate, 6, '.', '') }}</td><td class="{{ $log->change_percent > 0 ? 'is-up' : ($log->change_percent < 0 ? 'is-down' : 'is-muted') }}">{{ $log->old_rate !== null ? sprintf('%+.2f%%', $log->change_percent) : 'First record' }}</td></tr>
                        @empty
                            <tr><td colspan="5" class="lm-cur-empty">No rate changes recorded yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endif

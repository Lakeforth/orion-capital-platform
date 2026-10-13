{{-- Settings > General (Figma 2524:20234). One form covers the three left cards; Save Changes sits in the first card. --}}
@php
    $icon = fn (string $name, $w, $h = null) => '<img src="'.asset('images/leadmax/settings/'.$name.'.svg').'" width="'.$w.'" height="'.($h ?? $w).'" alt="">';
    $v = fn (string $key) => old($key, $general[$key]);
    $chevron = $icon('select-chevron', 8.25, 4.5);
    $canEdit = is_admin();
    $nameParts = preg_split('/\s+/', trim((string) $general['company_name']));
    $nameTail = count($nameParts) > 1 ? array_pop($nameParts) : '';
    $currencyLabel = fn ($c) => $c->code.($c->symbol ? ' ('.$c->symbol.')' : '');
@endphp
<div class="lm-set-grid">
    <div class="lm-set-main">
        <form method="POST" action="{{ route('admin.leadmax.settings.general') }}" enctype="multipart/form-data" id="lm-set-general" class="lm-set-stack">
            @csrf
            <fieldset @disabled(! $canEdit)>
            <section class="lm-set-card">
                <header class="lm-set-card-head">
                    <div><h2>Company Information</h2><p>Update your company details and branding information.</p></div>
                    @if($canEdit)<button type="submit" class="lm-set-save">{!! $icon('save', 10.5) !!}<span>Save Changes</span></button>@endif
                </header>
                <div class="lm-set-company">
                    <div class="lm-set-logo">
                        <span class="lm-set-label">Logo</span>
                        <div class="lm-set-logo-box" data-lm-logo-preview>
                            @if($general['logo'])
                                <img src="{{ route('admin.leadmax.settings.logo') }}" alt="{{ $general['company_name'] }} logo">
                            @else
                                <span class="lm-set-logo-text">{!! $icon('logo-mark', 24) !!}<b>{{ implode(' ', $nameParts) }}@if($nameTail !== '') <em>{{ $nameTail }}</em>@endif</b></span>
                            @endif
                        </div>
                        <small>Recommended size: 200 x 60px<br>PNG, JPG or SVG (Max 2MB)</small>
                        @if($canEdit)
                            <div class="lm-set-logo-actions">
                                <label class="lm-set-btn is-upload">{!! $icon('upload', 9) !!}<span>Upload<br>Logo</span><input type="file" name="logo" accept=".png,.jpg,.jpeg,.svg,image/png,image/jpeg,image/svg+xml" data-lm-logo-input></label>
                                <button type="submit" form="lm-set-logo-remove" class="lm-set-btn" @disabled(! $general['logo']) data-lm-confirm-submit="Remove the logo?">{!! $icon('remove', 9, 9.75) !!}<span>Remove</span></button>
                            </div>
                        @endif
                        {{-- The logo above sits on dark surfaces; light mode's top bar is white, so a logo with
                             white lettering needs a variant drawn for light backgrounds. --}}
                        <div class="lm-set-logo is-light">
                            <span class="lm-set-label">Logo for light mode</span>
                            <div class="lm-set-logo-box is-light" data-lm-logo-preview>
                                @if($general['logo_light'])
                                    <img src="{{ route('admin.leadmax.settings.logo', ['variant' => 'light']) }}" alt="{{ $general['company_name'] }} logo for light mode">
                                @else
                                    <span class="lm-set-logo-text">Uses the logo above</span>
                                @endif
                            </div>
                            @if($canEdit)
                                <div class="lm-set-logo-actions">
                                    <label class="lm-set-btn is-upload">{!! $icon('upload', 9) !!}<span>Upload<br>Logo</span><input type="file" name="logo_light" accept=".png,.jpg,.jpeg,.svg,image/png,image/jpeg,image/svg+xml" data-lm-logo-input></label>
                                    <button type="submit" form="lm-set-logo-light-remove" class="lm-set-btn" @disabled(! $general['logo_light']) data-lm-confirm-submit="Remove the light-mode logo?">{!! $icon('remove', 9, 9.75) !!}<span>Remove</span></button>
                                </div>
                            @endif
                        </div>
                    </div>
                    <div class="lm-set-fields">
                        <label class="lm-set-field"><span>Company Name</span><input type="text" name="company_name" value="{{ $v('company_name') }}" required maxlength="120"></label>
                        <label class="lm-set-field"><span>Website</span><input type="text" inputmode="url" name="website" value="{{ $v('website') }}" placeholder="https://" maxlength="255" autocomplete="url" aria-describedby="lm-set-website-hint"><small id="lm-set-website-hint">The logo in the client area and WebTrader opens this site in a new tab. The platform owner can also set it.</small></label>
                        <label class="lm-set-field is-wide"><span>Support Email</span><input type="email" name="support_email" value="{{ $v('support_email') }}" maxlength="255"></label>
                        <label class="lm-set-field is-wide"><span>Support Phone</span><input type="tel" name="support_phone" value="{{ $v('support_phone') }}" maxlength="40"></label>
                        <label class="lm-set-field is-wide"><span>Time Zone</span><span class="lm-set-select"><select name="timezone">@foreach($timezones as $id => $label)<option value="{{ $id }}" @selected($v('timezone') === $id)>{{ $label }}</option>@endforeach</select>{!! $chevron !!}</span></label>
                        <label class="lm-set-field is-wide"><span>Default Language</span><span class="lm-set-select"><select name="language">@foreach(\App\Http\Controllers\Admin\LeadMaxSettingsController::LANGUAGES as $code => $label)<option value="{{ $code }}" @selected($v('language') === $code)>{{ $label }}</option>@endforeach</select>{!! $chevron !!}</span></label>
                    </div>
                </div>
            </section>

            <section class="lm-set-card">
                <header class="lm-set-card-head"><div><h2>Address Information</h2><p>Set your company address and location details.</p></div></header>
                <div class="lm-set-fields is-half">
                    <label class="lm-set-field"><span>Country</span><span class="lm-set-select"><select name="country"><option value="">Not selected</option>@foreach($countries as $country)<option value="{{ $country }}" @selected($v('country') === $country)>{{ $country }}</option>@endforeach</select>{!! $chevron !!}</span></label>
                    <label class="lm-set-field"><span>State / Region</span><input type="text" name="state" value="{{ $v('state') }}" placeholder="Not selected" maxlength="120"></label>
                    <label class="lm-set-field"><span>City</span><input type="text" name="city" value="{{ $v('city') }}" maxlength="120"></label>
                    <label class="lm-set-field"><span>Postal Code</span><input type="text" name="postal_code" value="{{ $v('postal_code') }}" maxlength="20"></label>
                    <label class="lm-set-field is-wide"><span>Address</span><input type="text" name="address" value="{{ $v('address') }}" maxlength="255"></label>
                </div>
            </section>

            <section class="lm-set-card">
                <header class="lm-set-card-head"><div><h2>Business Settings</h2><p>Configure your business preferences.</p></div></header>
                <div class="lm-set-fields is-half">
                    <label class="lm-set-field"><span>Default Currency</span><span class="lm-set-select"><select name="currency">@foreach($currencies as $currency)<option value="{{ $currency->code }}" @selected($v('currency') === $currency->code)>{{ $currencyLabel($currency) }}</option>@endforeach @if(! $currencies->contains('code', $v('currency')))<option value="{{ $v('currency') }}" selected>{{ $v('currency') }}</option>@endif</select>{!! $chevron !!}</span></label>
                    <label class="lm-set-field"><span>Lead Expiry (Days)</span><input type="number" name="lead_expiry_days" value="{{ $v('lead_expiry_days') }}" min="0" max="3650" required></label>
                    <label class="lm-set-field"><span>Date Format</span><span class="lm-set-select"><select name="date_format">@foreach(\App\Http\Controllers\Admin\LeadMaxSettingsController::DATE_FORMATS as $format => $label)<option value="{{ $format }}" @selected($v('date_format') === $format)>{{ $label }}</option>@endforeach</select>{!! $chevron !!}</span></label>
                    <label class="lm-set-field"><span>Default Account Type</span><span class="lm-set-select"><select name="account_type">@foreach(\App\Http\Controllers\Admin\LeadMaxSettingsController::ACCOUNT_TYPES as $type => $label)<option value="{{ $type }}" @selected($v('account_type') === $type)>{{ $label }}</option>@endforeach</select>{!! $chevron !!}</span></label>
                    <label class="lm-set-field"><span>Time Format</span><span class="lm-set-select"><select name="time_format">@foreach(\App\Http\Controllers\Admin\LeadMaxSettingsController::TIME_FORMATS as $format => $label)<option value="{{ $format }}" @selected($v('time_format') === $format)>{{ $label }}</option>@endforeach</select>{!! $chevron !!}</span></label>
                    <label class="lm-set-field"><span>Default Trading Group</span><span class="lm-set-select"><select name="trading_group">@foreach($tradingGroups as $group => $label)<option value="{{ $group }}" @selected($v('trading_group') === (string) $group)>{{ $label }}</option>@endforeach</select>{!! $chevron !!}</span></label>
                </div>
            </section>
            </fieldset>
            {{-- The header button saves every card on this page; this one sits where the form ends. --}}
            @if($canEdit)<div class="lm-set-form-foot"><button type="submit" class="lm-set-save">{!! $icon('save', 10.5) !!}<span>Save Changes</span></button></div>@endif
        </form>
        <form method="POST" action="{{ route('admin.leadmax.settings.logo.remove') }}" id="lm-set-logo-remove" hidden>@csrf @method('DELETE')</form>
        <form method="POST" action="{{ route('admin.leadmax.settings.logo.remove') }}" id="lm-set-logo-light-remove" hidden>@csrf @method('DELETE')<input type="hidden" name="variant" value="light"></form>
    </div>

    <aside class="lm-set-side">
        <section class="lm-set-card is-side">
            <h3>Platform Status</h3>
            <p class="lm-set-status {{ $status['ok'] ? '' : 'is-warn' }}"><i></i><b>{{ $status['title'] }}</b></p>
            <p class="lm-set-status-note">{{ $status['note'] }}</p>
        </section>

        @if($canEdit)
        <section class="lm-set-card is-side">
            <h3>Quick Actions</h3>
            <div class="lm-set-actions">
                <form method="POST" action="{{ route('admin.leadmax.settings.cache') }}">@csrf<button type="submit" class="lm-set-action"><span class="lm-set-action-icon">{!! $icon('qa-cache', 10.5, 11.375) !!}</span><span><b>Clear Cache</b><small>Refresh system cache</small></span>{!! $icon('qa-chevron', 4.5, 8.25) !!}</button></form>
                <a href="{{ route('admin.leadmax.settings.export') }}" class="lm-set-action"><span class="lm-set-action-icon">{!! $icon('qa-export', 9.625, 11.375) !!}</span><span><b>Export Settings</b><small>Download your configuration</small></span>{!! $icon('qa-chevron', 4.5, 8.25) !!}</a>
                <form method="POST" action="{{ route('admin.leadmax.settings.import') }}" enctype="multipart/form-data">@csrf<label class="lm-set-action"><span class="lm-set-action-icon">{!! $icon('qa-import', 9.625, 11.375) !!}</span><span><b>Import Settings</b><small>Upload configuration file</small></span>{!! $icon('qa-chevron', 4.5, 8.25) !!}<input type="file" name="file" accept=".json,application/json" data-lm-autosubmit></label></form>
                <form method="POST" action="{{ route('admin.leadmax.settings.reset') }}">@csrf<input type="hidden" name="scope" value="general"><button type="submit" class="lm-set-action" data-lm-confirm-submit="Restore the default settings? Your logo is kept."><span class="lm-set-action-icon">{!! $icon('qa-reset', 11.375, 10.5) !!}</span><span><b>Reset to Default</b><small>Restore default settings</small></span>{!! $icon('qa-chevron', 4.5, 8.25) !!}</button></form>
            </div>
        </section>

        <section class="lm-set-card is-side is-danger">
            <h3>Danger Zone</h3>
            <p>These actions are irreversible. Please proceed with caution.</p>
            <form method="POST" action="{{ route('admin.leadmax.settings.reset') }}">@csrf<input type="hidden" name="scope" value="all"><button type="submit" class="lm-set-danger" data-lm-confirm-submit="Reset ALL LeadMax settings, including the logo? This cannot be undone.">{!! $icon('dz-reset', 11.375, 10.5) !!}<span>Reset All Settings</span></button></form>
            <details class="lm-set-danger-more"><summary class="lm-set-danger">{!! $icon('dz-delete', 10.5, 11.375) !!}<span>Delete Account</span></summary><p role="note">Deleting a company account removes every user, lead and transaction. It is done by the platform administrator on request, not from this page.</p></details>        </section>
        @endif
    </aside>
</div>

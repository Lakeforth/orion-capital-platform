{{-- Account settings for the signed-in user, inside the CRM-Max shell. Reuses the Settings page's
     card and field styles so it reads as part of the same section. --}}
@php
    $icon = fn (string $name, $w, $h = null) => '<img src="'.asset('images/leadmax/settings/'.$name.'.svg').'" width="'.$w.'" height="'.($h ?? $w).'" alt="">';
    $v = fn (string $key, $fallback = '') => old($key, $fallback);
    $roles = $account->roles->pluck('display_name')->filter()->implode(', ') ?: $account->roles->pluck('name')->implode(', ');
@endphp
<x-leadmax-layout :central-url="$centralUrl" :active-lead-max-page="$activeLeadMaxPage">
<div class="lm-set">
    <header class="lm-set-top">
        <div><h1>Account Settings</h1><p>Update your own name, contact details and profile picture.</p></div>
        <nav class="lm-set-crumbs" aria-label="Breadcrumb"><a href="{{ route('admin.leadmax.dashboard') }}">Dashboard</a><i>/</i><b>Account Settings</b></nav>
    </header>
    <nav class="lm-fin-tabs lm-account-sections" aria-label="Account sections">
        <a href="#profile">Profile</a><a href="#password">Password Updates</a><a href="#security">Security</a><a href="#notifications">Notifications</a><a href="#preferences">Preferences</a>
    </nav>

    @if(session('success'))<aside class="lm-owner-toast" role="status"><x-leadmax-lead-icon name="success" /><div><strong>Saved</strong><p>{{ session('success') }}</p></div><button type="button" aria-label="Dismiss notification" data-lm-toast-close><x-leadmax-lead-icon name="close" /></button></aside>@endif
    @if($errors->any())<aside class="lm-owner-toast is-error" role="alert"><x-leadmax-lead-icon name="close" /><div><strong>Check the form</strong><p>{{ $errors->first() }}</p></div></aside>@endif

    <div class="lm-set-grid">
        <div class="lm-set-main">
            <form method="POST" action="{{ route('admin.leadmax.account.save') }}" enctype="multipart/form-data" class="lm-set-stack">
                @csrf
                <section class="lm-set-card" id="profile">
                    <header class="lm-set-card-head">
                        <div><h2>Profile Information</h2><p>This is how your name appears across CRM-Max.</p></div>
                        <button type="submit" class="lm-set-save">{!! $icon('save', 10.5) !!}<span>Save Changes</span></button>
                    </header>
                    <div class="lm-set-company">
                        <div class="lm-set-logo">
                            <img class="lm-account-avatar" src="{{ $account->avatar }}" width="72" height="72" alt="">
                            <label class="lm-set-action">
                                <span class="lm-set-action-icon">{!! $icon('qa-import', 9.625, 11.375) !!}</span>
                                <span><b>Change picture</b><small>PNG, JPG or WEBP, up to 2&nbsp;MB</small></span>
                                <input type="file" name="avatar" accept="image/png,image/jpeg,image/webp">
                            </label>
                        </div>
                        <div class="lm-set-fields">
                            <label class="lm-set-field"><span>First Name</span><input type="text" name="first_name" value="{{ $v('first_name', $account->first_name) }}" required maxlength="120"></label>
                            <label class="lm-set-field"><span>Last Name</span><input type="text" name="last_name" value="{{ $v('last_name', $account->last_name) }}" maxlength="120"></label>
                            <label class="lm-set-field"><span>Phone</span><input type="tel" name="phone" value="{{ $v('phone', $account->phone) }}" maxlength="40"></label>
                            {{-- Email identifies the account for sign-in, so it is shown but not editable here,
                                 exactly as the central panel's profile page has it. --}}
                            <label class="lm-set-field"><span>Email</span><input type="email" value="{{ $account->email }}" disabled></label>
                        </div>
                    </div>
                </section>
            </form>

            <form method="POST" action="{{ route('admin.leadmax.account.password') }}" class="lm-set-stack">
                @csrf
                <section class="lm-set-card" id="password">
                    <header class="lm-set-card-head">
                        <div><h2>Password Updates</h2><p>Confirm your current password to set a new one. Your other sessions are signed out afterwards.</p></div>
                        <button type="submit" class="lm-set-save">{!! $icon('save', 10.5) !!}<span>Update Password</span></button>
                    </header>
                    <div class="lm-set-fields lm-set-password-fields">
                        <label class="lm-set-field is-wide"><span>Current Password</span><input type="password" name="current_password" autocomplete="current-password" required></label>
                        <label class="lm-set-field"><span>New Password</span><input type="password" name="password" autocomplete="new-password" minlength="8" required data-lm-pw-generate></label>
                        <label class="lm-set-field"><span>Confirm New Password</span><input type="password" name="password_confirmation" autocomplete="new-password" minlength="8" required></label>
                    </div>
                </section>
            </form>
        </div>

        <aside class="lm-set-side">
            <section class="lm-set-card">
                <header class="lm-set-card-head"><div><h2>Account</h2><p>Read-only details for this sign-in.</p></div></header>
                <dl class="lm-account-meta">
                    <div><dt>Role</dt><dd>{{ $roles ?: 'None' }}</dd></div>
                    <div><dt>Brand</dt><dd>{{ company_settings('site_name') }}</dd></div>
                    <div><dt>Member since</dt><dd>{{ $account->created_at?->format('Y-m-d') ?: '-' }}</dd></div>
                    <div><dt>Last seen</dt><dd>{{ $account->last_seen?->diffForHumans() ?: '-' }}</dd></div>
                </dl>
            </section>
            <section class="lm-set-card" id="security">
                <header class="lm-set-card-head"><div><h2>Security</h2><p>You sign in with your email and password.</p></div></header>
                <dl class="lm-account-meta">
                    @forelse($recentLogins as $login)
                        <div><dt>{{ $login->created_at?->format('Y-m-d H:i') }}</dt><dd>{{ $login->status === 'failed' ? 'Failed attempt' : 'Signed in' }} · {{ $login->browser ?: 'Unknown browser' }} · {{ implode(' · ', array_filter([$login->device_type ? ucfirst($login->device_type) : null, $login->platform])) ?: 'Unknown device' }} · {{ $login->ip_address ?: 'IP not recorded' }}</dd></div>
                    @empty
                        <div><dt>Recent sign-ins</dt><dd>None recorded yet.</dd></div>
                    @endforelse
                </dl>
            </section>
            @php($prefs = data_get($account->settings, 'leadmax', []))
            <section class="lm-set-card" id="preferences">
                <header class="lm-set-card-head"><div><h2>Preferences</h2><p>Saved on your account, so they follow you to another browser. The header shortcuts change the same settings.</p></div></header>
                <form method="POST" action="{{ route('admin.leadmax.account.preferences') }}" class="lm-set-stack">
                    @csrf
                    {{-- The same label-above, themed select as Settings > General. --}}
                    <label class="lm-set-field"><span>Language</span><span class="lm-set-select"><select name="locale">@foreach(\App\Http\Controllers\Admin\LeadMaxAccountController::LANGUAGES as $code => $name)<option value="{{ $code }}" @selected(($prefs['locale'] ?? 'en') === $code)>{{ $name }}</option>@endforeach</select>{!! $icon('select-chevron', 8.25, 4.5) !!}</span></label>
                    <label class="lm-set-field"><span>Display currency</span><span class="lm-set-select"><select name="currency"><option value="">Default ({{ \App\Support\LeadMax\DisplayCurrency::options()->firstWhere('is_default', true)?->code ?? 'USD' }})</option>@foreach(\App\Support\LeadMax\DisplayCurrency::options() as $currency)<option value="{{ $currency->code }}" @selected(\App\Support\LeadMax\DisplayCurrency::code() === $currency->code)>{{ $currency->code }}{{ $currency->symbol ? ' · '.$currency->symbol : '' }}{{ $currency->name ? ' · '.$currency->name : '' }}</option>@endforeach</select>{!! $icon('select-chevron', 8.25, 4.5) !!}</span></label>
                    <label class="lm-set-field"><span>Timezone</span><span class="lm-set-select"><select name="timezone">@foreach(timezone_identifiers_list() as $zone)<option value="{{ $zone }}" @selected(($prefs['timezone'] ?? 'UTC') === $zone)>{{ $zone }}</option>@endforeach</select>{!! $icon('select-chevron', 8.25, 4.5) !!}</span><small class="lm-set-hint">Saved for your account; timestamps are not converted yet.</small></label>
                    <label class="lm-set-field"><span>Theme</span><span class="lm-set-select"><select name="theme">@foreach(['dark' => 'Dark', 'light' => 'Light'] as $value => $label)<option value="{{ $value }}" @selected(($prefs['theme'] ?? 'dark') === $value)>{{ $label }}</option>@endforeach</select>{!! $icon('select-chevron', 8.25, 4.5) !!}</span></label>
                    <button type="submit" class="lm-set-save">Save preferences</button>
                </form>
            </section>
            <section class="lm-set-card" id="notifications">
                <header class="lm-set-card-head"><div><h2>Notifications</h2><p>{{ \App\Http\Controllers\Admin\LeadMaxChatController::visibleNotifications($account)->whereNull('read_at')->count() }} unread. New leads, finance and system notices arrive in the bell.</p></div></header>
                <p><a class="lm-set-link" href="{{ route('admin.leadmax.notifications') }}">Open notifications</a></p>
            </section>
        </aside>
    </div>
</div>
</x-leadmax-layout>

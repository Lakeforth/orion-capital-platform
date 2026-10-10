{{-- LeadMax Settings (Figma 2524:20234 General, 2551:21745 Currency Management). --}}
@php
    $icon = fn (string $name, $w, $h = null) => '<img src="'.asset('images/leadmax/settings/'.$name.'.svg').'" width="'.$w.'" height="'.($h ?? $w).'" alt="">';
    // Figma NavigationTabs: [label, icon, width, height]; only General and Currency Management have designs.
    $tabItems = [
        'general' => ['General', 'tab-general', 11.457, 11.457], 'currency' => ['Currency Management', 'tab-currency', 11.375, 10.5], 'users' => ['Users & Roles', 'tab-users', 13.7539, 8.75],
        'channels' => ['Channels', 'tab-channels', 12.25, 10.5], 'campaigns' => ['Campaigns', 'tab-campaigns', 12.25, 12.25], 'lead' => ['Lead Settings', 'tab-lead', 10.5, 10.5],
        'integrations' => ['Integrations', 'tab-integrations', 10.5, 11.375], 'notifications' => ['Notifications', 'tab-notifications', 10.5, 11.375], 'security' => ['Security', 'tab-security', 10.5, 10.9375], 'billing' => ['Billing', 'tab-billing', 12.25, 8.75],
    ];
@endphp
<x-leadmax-layout :central-url="$centralUrl" :active-lead-max-page="$activeLeadMaxPage">
<div class="lm-set">
    <header class="lm-set-top">
        <div><h1>Settings</h1><p>Manage your platform settings, preferences and configurations.</p></div>
        <nav class="lm-set-crumbs" aria-label="Breadcrumb"><a href="{{ route('admin.leadmax.dashboard') }}">Dashboard</a><i>/</i><b>Settings</b></nav>
    </header>

    <nav class="lm-set-tabs" aria-label="Settings sections">
        @foreach($tabItems as $key => [$label, $tabIcon, $w, $h])
            @if(isset($tabs[$key]))
                <a href="{{ route('admin.leadmax.settings', $key === 'general' ? [] : ['tab' => $key]) }}" class="{{ $tab === $key ? 'active' : '' }}" @if($tab === $key) aria-current="page" @endif>{!! $icon($tabIcon.($tab === $key ? '-active' : ''), $w, $h) !!}<span>{{ $label }}</span></a>
            @else
                <span aria-disabled="true" title="Not available yet">{!! $icon($tabIcon, $w, $h) !!}<span>{{ $label }}</span></span>
            @endif
        @endforeach
    </nav>

    @if(session('success'))<p class="lm-detail-flash" role="status">{{ session('success') }}</p>@endif
    @if(session('warning'))<p class="lm-detail-flash lm-detail-flash-warning" role="status">{{ session('warning') }}</p>@endif
    @if($errors->any())<p class="lm-detail-flash lm-detail-flash-warning" role="alert">{{ $errors->first() }}</p>@endif

    @include('admin.leadmax.partials.settings-'.$tab)
</div>
</x-leadmax-layout>

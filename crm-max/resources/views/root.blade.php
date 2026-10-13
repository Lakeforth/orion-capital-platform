<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    @php
        // A page may set its own title (e.g. "Leads | Orion Capital CRM") by sharing
        // spladePageTitle; this layout renders after the page, so it must not overwrite it.
        $spladePageTitle = \Illuminate\Support\Facades\View::shared('spladePageTitle') ?? 'Back Office - '.get_site_name();
        \ProtoneMedia\Splade\Facades\SEO::title($spladePageTitle);
    @endphp
    <title>{{ $spladePageTitle }}</title>
    <link rel="icon" href="{{ company_settings('favicon') ? getFullImage(company_settings('favicon')) : asset('favicon.ico') }}">

    <!-- Fonts -->
    <link rel="stylesheet" href="https://fonts.bunny.net/css2?family=Nunito:wght@400;600;700&display=swap">


    <link href="https://cdn.jsdelivr.net/npm/@splidejs/splide@4.1.4/dist/css/splide.min.css" rel="stylesheet">

    <!-- SweetAlert2 (plan-limit upgrade prompts, etc.) -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- Scripts -->
    @vite(['resources/js/app.js'])

    @spladeHead

    {{-- 'Modern' admin theme tokens -- kept out of the Splade-templated body so the
         client-side Vue render pass never has to parse a <style> block. --}}
    <style>
        .qs-modern {
            --bg:#f1f5f9; --surface:#ffffff; --border:#e2e8f0; --text:#1c2434; --muted:#64748b; --muted2:#94a3b8;
            --soft:#f1f5f9; --grid:#eef1f6; --icon-soft:#eef1ff; --side:#1c2434; --accent:#6366f1; --accent2:#22d3ee;
        }
        .qs-modern[data-theme="dark"] {
            --bg:#0f1524; --surface:#182135; --border:#2a3350; --text:#e7ecf6; --muted:#9aa6be; --muted2:#6b7791;
            --soft:#222c44; --grid:#26314c; --icon-soft:#232f52; --side:#12182a;
        }
        .qs-modern { font-family: 'Inter', system-ui, sans-serif; color: var(--text); background: var(--bg); }
        .qs-modern a { text-decoration: none; }
        .qs-modern-card { background: var(--surface); border: 1px solid var(--border); border-radius: 12px; padding: 22px 24px; transition: background .2s ease, border-color .2s ease; }
        .qs-nav-link { display: flex; align-items: center; gap: 12px; padding: 11px 12px; border-radius: 9px; font-size: 14px; font-weight: 500; color: #8a99af; transition: color .15s ease; }
        .qs-nav-link:hover { color: #fff; }
        .qs-nav-active, .qs-nav-active:hover { background: rgba(255,119,0,0.12); color: #ff8a24; box-shadow: inset 3px 0 0 #ff7700; }
        .qs-kpi-card:hover { transform: translateY(-4px); box-shadow: 0 14px 34px rgba(28,36,52,0.14); }
        .qs-modern[data-theme="dark"] .qs-theme-icon-light { display: none; }
        .qs-modern[data-theme="dark"] .qs-theme-icon-dark { display: inline-flex; }
        .qs-theme-icon-dark { display: none; }
        .qs-modern select.qs-lang-select { background: var(--soft); color: var(--text); border: none; }
        .qs-modern, .qs-modern > aside { transition: grid-template-columns .22s ease, transform .22s ease, opacity .18s ease; }
        .qs-modern.qs-sidebar-collapsed { grid-template-columns: 0 minmax(0, 1fr) !important; }
        .qs-modern.qs-sidebar-collapsed > #qsModernSidebar { transform: translateX(-100%); opacity: 0; pointer-events: none; }
        .mini-customer-table table { width: 100%; table-layout: auto; }
        .mini-customer-table th, .mini-customer-table td { padding-left: 8px !important; padding-right: 8px !important; }
        .mini-customer-table th, .mini-customer-table th * { white-space: pre-line !important; line-height: 1.15 !important; }
        .mini-customer-table th:nth-child(7), .mini-customer-table th:nth-child(8), .mini-customer-table th:nth-child(9) { width: 92px; }
        .mini-customer-table td:nth-child(1) > div { min-width: 170px !important; }
        .mini-customer-table td:nth-child(2) > div { min-width: 145px !important; }
        .mini-customer-table td:nth-child(7) > div,
        .mini-customer-table td:nth-child(8) > div,
        .mini-customer-table td:nth-child(9) > div { min-width: 105px !important; }
        .mini-customer-table td:nth-child(10) > div { min-width: 112px !important; }
    </style>

{{--    <script--}}
{{--        src='//fw-cdn.com/11600335/4251348.js'--}}
{{--        chat='true'>--}}
{{--    </script>--}}

</head>
<body class="h-screen antialiased  dark:text-bodydark bg-gray-50 dark:bg-dark">

<div class="flex-col flex min-h-dvh">
    @splade
</div>

{{--@splade--}}


{{--<script src="/vendor/laravel-filemanager/js/stand-alone-button.js"></script>--}}
{{--<script src="/vendor/laravel-filemanager/js/script.js"></script>--}}


</body>
</html>

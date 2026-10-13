{{-- Page not found. Replaces Laravel's bare "404 | NOT FOUND", which offered no way back. Standalone on
     purpose: a 404 can be raised before the tenant or the signed-in user is known. --}}
@php
    $siteName = rescue(fn () => company_settings('site_name'), null, false) ?: config('app.name');
    $home = request()->is('panel/leadmax*') ? url('/panel/leadmax') : (request()->is('panel*') ? url('/panel') : url('/'));
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Page not found | {{ $siteName }}</title>
    <style>
        body{margin:0;min-height:100vh;display:grid;place-items:center;background:#07111e;color:#e2e8f0;font:14px/1.5 Inter,"Segoe UI",Arial,sans-serif}
        main{max-width:440px;padding:32px 24px;text-align:center}
        p.code{margin:0;color:#60a5fa;font-size:13px;font-weight:600;letter-spacing:.08em}
        h1{margin:8px 0 10px;color:#fff;font-size:26px;line-height:1.25}
        p{margin:0;color:#94a3b8}
        nav{display:flex;justify-content:center;gap:10px;margin-top:24px}
        a{display:inline-flex;align-items:center;height:38px;padding:0 16px;border:1px solid #2f4a76;border-radius:8px;color:#e2e8f0;text-decoration:none}
        a.primary{border-color:#2563eb;background:#2563eb;color:#fff}
        a:focus-visible{outline:2px solid #60a5fa;outline-offset:2px}
    </style>
</head>
<body>
<main>
    <p class="code">404</p>
    <h1>Page not found</h1>
    <p>The page you were looking for doesn't exist or has moved. Check the address, or head back.</p>
    <nav>
        <a href="{{ $home }}" class="primary">Go to {{ $siteName }}</a>
        <a href="javascript:history.back()">Go back</a>
    </nav>
</main>
</body>
</html>

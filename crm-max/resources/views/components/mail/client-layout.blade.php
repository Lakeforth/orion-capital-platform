@props([
    'siteName'    => null,
    'logoUrl'     => null,
    'supportUrl'  => null,
    'message'     => null,
    'showHero'    => false,
    'heroTitle'   => '',
    'showBadge'   => false,
    'badgeText'   => '',
    'badgeColor'  => 'green',
    'showSupport' => true,
    'title'       => null,
])

@php
    $siteName   = $siteName   ?? get_site_name() ?? config('app.name');
    $logoUrl    = $logoUrl    ?? asset('images/logo.png');
    $supportUrl = $supportUrl ?? config('app.url');
    $title      = $title      ?? $siteName;

    $badgeStyles = [
        'green' => ['bg' => '#e6f9f0', 'text' => '#0E9F64'],
        'amber' => ['bg' => '#fef3c7', 'text' => '#654703'],
        'teal'  => ['bg' => '#ccf7f7', 'text' => '#026b6b'],
    ];
    $badge = $badgeStyles[$badgeColor] ?? $badgeStyles['green'];

    $footerIcons = collect(range(1, 8))->map(function (int $i) use ($message) {
        $path = public_path("default/footer/{$i}.png");
        if ($message && method_exists($message, 'embed') && file_exists($path)) {
            return $message->embed($path);
        }
        return asset("default/footer/{$i}.png");
    })->all();
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title }}</title>
</head>
<body style="margin:0;padding:0;background-color:#FAF8FF;font-family:Arial,Helvetica,sans-serif;color:#323232;">
{{-- The font is set on the tables too: Gmail and others drop <body> styles, and the text fell back to a serif. --}}
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#FAF8FF;font-family:Arial,Helvetica,sans-serif;color:#323232;">
    <tr>
        <td align="center" style="padding:28px 12px;">
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:600px;background-color:#FFFFFF;font-family:Arial,Helvetica,sans-serif;color:#323232;">

                {{-- HEADER: logo --}}
                <tr>
                    <td style="padding:24px 36px 20px;">
                        <img src="{{ $logoUrl }}" alt="{{ $siteName }}" style="display:block;max-height:40px;max-width:160px;">
                    </td>
                </tr>

                {{-- HERO BANNER --}}
                @if($showHero)
                <tr>
                    <td style="background-color:#3B82F6;padding:40px 36px 36px;background-image:linear-gradient(135deg,#1d4ed8 0%,#3B82F6 60%,#60a5fa 100%);">
                        <div style="font-size:30px;font-weight:700;color:#FFFFFF;line-height:1.25;">{!! nl2br(e($heroTitle)) !!}</div>
                    </td>
                </tr>
                @endif

                {{-- STATUS BADGE --}}
                @if($showBadge)
                <tr>
                    <td style="padding:28px 36px 0;">
                        <span style="display:inline-block;padding:6px 18px;border-radius:999px;background-color:{{ $badge['bg'] }};color:{{ $badge['text'] }};font-size:14px;font-weight:600;">
                            {{ $badgeText }}
                        </span>
                    </td>
                </tr>
                @endif

                {{-- MAIN CONTENT --}}
                <tr>
                    <td style="padding:28px 36px 0;">
                        {{ $slot }}
                    </td>
                </tr>

                {{-- SUPPORT BOX --}}
                @if($showSupport)
                <tr>
                    <td style="padding:28px 36px 36px;">
                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#FAF8FF;border-radius:12px;">
                            <tr>
                                <td style="padding:24px;">
                                    <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                        <tr>
                                            <td style="width:56px;vertical-align:top;">
                                                <div style="width:40px;height:40px;background-color:#e6f9f0;border-radius:50%;text-align:center;line-height:40px;font-size:20px;">&#128172;</div>
                                            </td>
                                            <td style="vertical-align:top;">
                                                <div style="font-size:16px;font-weight:700;color:#001313;margin-bottom:6px;">Need Help getting started?</div>
                                                <div style="font-size:14px;line-height:1.6;color:#323232;">Our team is online and is ready to walk you through your first deposit</div>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td colspan="2" style="padding-top:18px;">
                                                <a href="{{ $supportUrl }}" style="display:block;text-align:center;padding:14px 20px;border-radius:999px;border:1px solid #0E9F64;color:#0E9F64;font-size:14px;font-weight:500;text-decoration:none;">
                                                    Contact Support
                                                </a>
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
                @endif

                {{-- FOOTER --}}
                <tr>
                    <td style="background-color:#F5FFFB;padding:28px 24px 32px;text-align:center;">
                        <div style="font-size:18px;font-weight:700;color:#0E9F64;margin-bottom:10px;">{{ $siteName }}</div>
                        <div style="font-size:14px;line-height:1.7;color:#323232;max-width:400px;margin:0 auto 16px;">Never miss a beat on our latest product updates and events</div>
                        <table role="presentation" cellpadding="0" cellspacing="0" style="margin:0 auto 16px;">
                            <tr>
                                @foreach($footerIcons as $icon)
                                <td style="padding:0 3px;">
                                    <img src="{{ $icon }}" alt="" style="display:block;width:28px;height:28px;">
                                </td>
                                @endforeach
                            </tr>
                        </table>
                        <div style="font-size:14px;color:#323232;margin-bottom:12px;">Trade on the go with {{ $siteName }}</div>
                        <div style="margin-bottom:10px;">
                            <span style="font-size:12px;color:#323232;margin-right:16px;">Latest promotions</span>
                            <a href="{{ $supportUrl }}" style="text-decoration:none;display:inline-table;">
                                <table role="presentation" cellpadding="0" cellspacing="0" style="display:inline-table;">
                                    <tr>
                                        <td style="vertical-align:middle;padding-right:4px;">
                                            <div style="width:16px;height:16px;background-color:#25D366;border-radius:3px;text-align:center;line-height:16px;font-size:10px;color:#ffffff;">&#128172;</div>
                                        </td>
                                        <td style="vertical-align:middle;font-size:12px;font-weight:500;color:#0E9F64;">Support</td>
                                    </tr>
                                </table>
                            </a>
                        </div>
                        <div style="font-size:12px;color:#323232;">Copyright &copy; 2018&ndash;{{ date('Y') }} {{ $siteName }}. All rights reserved.</div>
                    </td>
                </tr>

            </table>
        </td>
    </tr>
</table>
</body>
</html>

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $data['title'] ?? $heading }}</title>
</head>
<body style="margin:0;padding:0;background-color:#f4f4f4;font-family:'Satoshi',Helvetica,Arial,sans-serif;">
<table border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color:#f4f4f4;">
    <tr>
        <td align="center" style="padding:24px 16px;">
            <table border="0" cellpadding="0" cellspacing="0" width="100%" style="max-width:500px;background-color:#ffffff;border-radius:12px;">
                <tr>
                    <td style="padding:40px;">

                        {{-- Logo --}}
                        <table border="0" cellpadding="0" cellspacing="0">
                            <tr>
                                @if($logoUrl)
                                    <td style="padding-bottom:40px;">
                                        <img src="{{ $logoUrl }}" alt="{{ $siteName }}" height="28" style="height:28px;max-height:28px;width:auto;display:block;">
                                    </td>
                                @else
                                    <td style="padding-bottom:40px;font-family:'Satoshi',Helvetica,Arial,sans-serif;font-size:16px;font-weight:700;color:#001313;">
                                        {{ $siteName }}
                                    </td>
                                @endif
                            </tr>
                        </table>

                        {{-- Heading --}}
                        <p style="margin:0 0 28px;font-family:'Satoshi',Helvetica,Arial,sans-serif;font-size:24px;line-height:1.4;font-weight:700;color:#001313;">
                            {{ $heading }}
                        </p>

                        {{-- Greeting + body --}}
                        <p style="margin:0 0 20px;font-family:'Satoshi',Helvetica,Arial,sans-serif;font-size:16px;line-height:1.5;color:#323232;">
                            {{ $greeting }}
                        </p>
                        <p style="margin:0 0 28px;font-family:'Satoshi',Helvetica,Arial,sans-serif;font-size:16px;line-height:1.5;color:#001313;">
                            {{ $bodyText }}
                        </p>

                        {{-- Credentials box --}}
                        <table border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color:#faf8ff;border-radius:16px;margin-bottom:24px;">
                            <tr>
                                <td style="padding:16px 16px 12px;">
                                    <table border="0" cellpadding="0" cellspacing="0" width="100%">
                                        <tr>
                                            <td style="padding-bottom:16px;border-bottom:1px solid #e5e2f5;font-family:'Satoshi',Helvetica,Arial,sans-serif;font-size:16px;color:#001313;">
                                                <span style="font-weight:500;">{{ $usernameLabel }}</span>
                                                <span style="font-style:italic;color:#02cccc;">{{ $email }}</span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td style="padding-top:16px;font-family:'Satoshi',Helvetica,Arial,sans-serif;font-size:16px;color:#001313;">
                                                <span style="font-weight:500;">{{ $passwordLabel }}</span>
                                                <span style="font-style:italic;">{{ $plainPassword }}</span>
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                        </table>

                        {{-- CTA button --}}
                        <table border="0" cellpadding="0" cellspacing="0">
                            <tr>
                                <td style="border-radius:32px;background-color:#0e9f64;">
                                    <a href="{{ $ctaUrl }}" target="_blank" style="display:inline-block;padding:12px 48px;font-family:'Satoshi',Helvetica,Arial,sans-serif;font-size:16px;font-weight:500;color:#fafffd;text-decoration:none;">
                                        {{ $ctaText }}
                                    </a>
                                </td>
                            </tr>
                        </table>

                    </td>
                </tr>
            </table>

            {{-- Footer (Figma "Footer desktop" component) --}}
            <table border="0" cellpadding="0" cellspacing="0" width="100%" style="max-width:500px;background-color:#f5fffb;">
                <tr>
                    <td align="center" style="padding:28px 40px;">

                        @if($logoUrl)
                            <img src="{{ $logoUrl }}" alt="{{ $siteName }}" height="20" style="height:20px;max-height:20px;width:auto;display:block;margin:0 auto 18px;">
                        @else
                            <p style="margin:0 0 18px;font-family:'Satoshi',Helvetica,Arial,sans-serif;font-size:16px;font-weight:700;color:#0e9f64;">
                                {{ $siteName }}
                            </p>
                        @endif

                        <p style="margin:0 0 20px;font-family:'Satoshi',Helvetica,Arial,sans-serif;font-size:14px;line-height:1.4;color:#323232;">
                            Never miss a beat on our latest product updates and events
                        </p>

                        <table border="0" cellpadding="0" cellspacing="0" style="margin:0 auto 20px;">
                            <tr>
                                @foreach (range(1, 8) as $i)
                                    <td style="padding:0 4px;">
                                        <img src="{{ asset('images/mail/campaign-welcome/coin-'.$i.'.png') }}" width="28" height="28" style="width:28px;height:28px;border-radius:50%;display:block;" alt="">
                                    </td>
                                @endforeach
                            </tr>
                        </table>

                        <p style="margin:0 0 20px;font-family:'Satoshi',Helvetica,Arial,sans-serif;font-size:14px;color:#323232;">
                            Trade on the go with <strong style="color:#0e9f64;">{{ $siteName }}</strong>
                        </p>

                        <p style="margin:0 0 12px;">
                            <a href="{{ $frontendUrl }}" style="font-family:'Satoshi',Helvetica,Arial,sans-serif;font-size:12px;color:#323232;text-decoration:underline;">Latest promotions</a>
                        </p>

                        <table border="0" cellpadding="0" cellspacing="0" style="margin:0 auto 20px;">
                            <tr>
                                <td style="padding-right:6px;">
                                    <img src="{{ asset('images/mail/campaign-welcome/whatsapp.png') }}" width="20" height="20" style="width:20px;height:20px;display:block;" alt="">
                                </td>
                                <td>
                                    <a href="{{ $supportUrl }}" style="font-family:'Satoshi',Helvetica,Arial,sans-serif;font-size:12px;font-weight:500;color:#323232;text-decoration:none;">Support</a>
                                </td>
                            </tr>
                        </table>

                        <p style="margin:0;font-family:'Satoshi',Helvetica,Arial,sans-serif;font-size:12px;color:#323232;">
                            Copyright @2018 - {{ date('Y') }} <strong style="color:#0e9f64;">{{ $siteName }}.</strong> All rights reserved
                        </p>

                    </td>
                </tr>
            </table>

        </td>
    </tr>
</table>
</body>
</html>

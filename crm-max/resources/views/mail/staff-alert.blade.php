{{-- A staff alert about a lead (StaffLeadAlerts): plain and internal, without the client
     layouts' promotions, support and "first deposit" panels. --}}
@php
    // The brand logo travels inside the email (a CID attachment) when it is one of our own public
    // files, so it shows even when the reader's mail app blocks remote images or the site's host is
    // not reachable; a logo hosted elsewhere stays a link. Only files under public/ are embedded.
    // First choice: the logo uploaded in CRM-Max Settings › General (private storage, so it can only
    // travel embedded); otherwise the brand's public logo.
    $logoSrc = $logoUrl ?? null;
    $canEmbed = isset($message) && $message instanceof \Illuminate\Mail\Message;
    $crmLogo = ($data['crm_logo_path'] ?? null) ? realpath($data['crm_logo_path']) : false;
    $logoFile = $logoSrc ? realpath(public_path(ltrim((string) parse_url($logoSrc, PHP_URL_PATH), '/'))) : false;
    if ($canEmbed && $crmLogo && str_starts_with($crmLogo, realpath(storage_path('app'))) && is_file($crmLogo)) {
        $logoSrc = $message->embed($crmLogo);
    } elseif ($canEmbed && $logoFile && str_starts_with($logoFile, realpath(public_path())) && is_file($logoFile)) {
        $logoSrc = $message->embed($logoFile);
    }
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }}</title>
</head>
<body style="margin:0;padding:0;background:#f1f5f9;font-family:Arial,Helvetica,sans-serif;color:#0f172a">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f1f5f9;padding:24px 12px">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:560px;background:#ffffff;border:1px solid #e2e8f0;border-radius:8px">
                    <tr>
                        <td style="padding:20px 24px;border-bottom:1px solid #e2e8f0">
                            @if($logoSrc)<img src="{{ $logoSrc }}" alt="{{ $siteName }}" style="display:inline-block;max-height:40px;max-width:160px;vertical-align:middle;margin-right:10px">@endif
                            <strong style="font-size:15px;vertical-align:middle">{{ $siteName }} CRM</strong>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:24px;font-size:14px;line-height:21px">
                            <h1 style="margin:0 0 16px;font-size:18px;line-height:24px">{{ $title }}</h1>
                            {!! $content !!}
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:16px 24px;border-top:1px solid #e2e8f0;color:#64748b;font-size:12px;line-height:18px">
                            An automatic alert from the {{ $siteName }} CRM. Please do not reply to this email.
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>

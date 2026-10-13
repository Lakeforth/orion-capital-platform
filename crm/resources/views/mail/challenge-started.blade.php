<x-mail.client-layout
    :message="$message ?? null"
    :siteName="$siteName ?? get_site_name()"
    :logoUrl="$logoUrl ?? null"
    title="Your Challenge Is Ready"
    :showHero="true"
    heroTitle="Your Challenge Is Ready"
>
    <h1 style="margin:0 0 16px;font-size:24px;font-weight:700;color:#001313;">Hi, {{ $customerName }}</h1>

    <p style="margin:0 0 20px;font-size:16px;line-height:1.6;color:#001313;">
        Your {{ $data['planTitle'] ?? 'PropFirm' }} challenge has started. Here's a summary of your account:
    </p>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#FAF8FF;border-radius:8px;margin-bottom:20px;">
        <tr>
            <td style="padding:0 24px 16px;">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                    <tr>
                        <td style="padding:16px 0;font-size:14px;font-weight:700;color:#323232;border-bottom:1px solid #e5e7eb;width:40%;">Plan:</td>
                        <td align="right" style="padding:16px 0;font-size:14px;color:#001313;border-bottom:1px solid #e5e7eb;">{{ $data['planTitle'] ?? '' }}</td>
                    </tr>
                    <tr>
                        <td style="padding:16px 0;font-size:14px;font-weight:700;color:#323232;border-bottom:1px solid #e5e7eb;">Account Size:</td>
                        <td align="right" style="padding:16px 0;font-size:14px;color:#001313;border-bottom:1px solid #e5e7eb;">{{ $data['accountSize'] ?? '' }}</td>
                    </tr>
                    <tr>
                        <td style="padding:16px 0;font-size:14px;font-weight:700;color:#323232;">Phase:</td>
                        <td align="right" style="padding:16px 0;font-size:14px;color:#001313;">{{ $data['phaseLabel'] ?? '' }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <p style="margin:0 0 12px;font-size:16px;line-height:1.6;color:#001313;font-weight:700;">
        Your Trading Credentials
    </p>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#FAF8FF;border-radius:16px;margin-bottom:24px;">
        <tr>
            <td style="padding:16px 16px 12px;">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                    <tr>
                        <td style="padding-bottom:16px;border-bottom:1px solid #e5e2f5;font-size:16px;color:#001313;">
                            <span style="font-weight:500;">Account:</span>
                            <span style="font-style:italic;color:#02cccc;">{{ $data['accountId'] ?? 'Not yet issued' }}</span>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:16px 0;border-bottom:1px solid #e5e2f5;font-size:16px;color:#001313;">
                            <span style="font-weight:500;">Server:</span>
                            <span style="font-style:italic;">{{ $data['tradingServer'] ?? 'Not yet issued' }}</span>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding-top:16px;font-size:16px;color:#001313;">
                            <span style="font-weight:500;">Password:</span>
                            <span style="font-style:italic;">{{ $data['password'] ?? 'Not yet issued' }}</span>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <p style="margin:0 0 28px;font-size:13px;line-height:1.6;color:#64748B;">
        Save these details now -- for your security, this password won't be shown again in the client area.
    </p>

    <a href="{{ $startUrl ?? config('app.url') }}" style="display:block;text-align:center;background-color:#0E9F64;color:#FAFFFD;text-decoration:none;padding:16px 24px;border-radius:999px;font-size:16px;font-weight:500;margin-bottom:28px;">
        View Your Challenge &rarr;
    </a>
</x-mail.client-layout>

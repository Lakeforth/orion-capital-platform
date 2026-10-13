<x-mail.client-layout
    :message="$message ?? null"
    :siteName="$siteName ?? get_site_name()"
    :logoUrl="$logoUrl ?? null"
    :title="'Welcome to ' . ($siteName ?? get_site_name())"
>
    <h1 style="margin:0 0 20px;font-size:24px;font-weight:700;color:#001313;line-height:1.3;">
        Welcome, We're pleased to meet you
    </h1>

    <p style="margin:0 0 12px;font-size:16px;color:#323232;">Hi {{ $customerName ?? 'Trader' }},</p>
    <p style="margin:0 0 28px;font-size:16px;line-height:1.6;color:#001313;">
        Your account has been successfully created and you're ready to get started.
        Fund your account, explore live markets, and access powerful trading tools
        designed to help you trade with confidence.
    </p>

    @if(!empty($accountUsername) || !empty($accountPassword))
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#FAF8FF;border-radius:8px;margin-bottom:24px;">
        <tr>
            <td style="padding:0 24px 16px;">
                @if(!empty($accountUsername))
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                    <tr>
                        <td style="padding:14px 0;font-size:14px;color:#323232;border-bottom:1px solid #e5e7eb;">Username</td>
                        <td align="right" style="padding:14px 0;font-size:14px;font-weight:500;color:#001313;border-bottom:1px solid #e5e7eb;">{{ $accountUsername }}</td>
                    </tr>
                </table>
                @endif
                @if(!empty($accountPassword))
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                    <tr>
                        <td style="padding:14px 0;font-size:14px;color:#323232;">Password</td>
                        <td align="right" style="padding:14px 0;font-size:14px;font-weight:500;color:#001313;">{{ $accountPassword }}</td>
                    </tr>
                </table>
                @endif
            </td>
        </tr>
    </table>
    @endif

    <a href="{{ $ctaUrl ?? config('app.url') }}" style="display:block;text-align:center;background-color:#0E9F64;color:#FAFFFD;text-decoration:none;padding:16px 24px;border-radius:999px;font-size:16px;font-weight:500;">
        Start Trading &rarr;
    </a>
</x-mail.client-layout>

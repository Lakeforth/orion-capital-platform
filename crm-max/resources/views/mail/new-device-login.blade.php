<x-mail.client-layout
    :message="$message ?? null"
    :siteName="$siteName ?? get_site_name()"
    :logoUrl="$logoUrl ?? null"
    :title="($siteName ?? get_site_name()) . ' — New Device Login Alert'"
>
    <h1 style="margin:0 0 16px;font-size:24px;font-weight:700;color:#001313;">Hi, {{ $siteName ?? get_site_name() }}</h1>

    <p style="margin:0 0 20px;font-size:16px;line-height:1.6;color:#001313;">
        We're writing to inform you that we detected a new login to your account from a new device.
    </p>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#FAF8FF;border-radius:8px;margin-bottom:20px;">
        <tr>
            <td style="padding:0 24px 16px;">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                    <tr>
                        <td style="padding:16px 0;font-size:14px;font-weight:700;color:#323232;border-bottom:1px solid #e5e7eb;width:40%;">When:</td>
                        <td align="right" style="padding:16px 0;font-size:14px;color:#001313;border-bottom:1px solid #e5e7eb;">{{ $loginTime ?? '' }}</td>
                    </tr>
                    <tr>
                        <td style="padding:16px 0;font-size:14px;font-weight:700;color:#323232;border-bottom:1px solid #e5e7eb;">Near:</td>
                        <td align="right" style="padding:16px 0;font-size:14px;color:#001313;border-bottom:1px solid #e5e7eb;">{{ $loginLocation ?? '' }}</td>
                    </tr>
                    <tr>
                        <td style="padding:16px 0;font-size:14px;font-weight:700;color:#323232;">Device:</td>
                        <td align="right" style="padding:16px 0;font-size:14px;color:#001313;">{{ $loginDevice ?? '' }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#FAF8FF;border-radius:8px;">
        <tr>
            <td style="padding:20px 24px;">
                <p style="margin:0 0 12px;font-size:16px;line-height:1.6;color:#323232;">
                    If this was you, you can ignore this message.
                </p>
                <p style="margin:0 0 16px;font-size:16px;line-height:1.6;color:#323232;">
                    If this wasn't you, open your {{ $siteName ?? get_site_name() }} dashboard and go to
                    <strong>"settings and privacy"</strong> &gt; <strong>"Security and login"</strong> &gt;
                    <strong>"Security alerts"</strong> and review Unauthorized logins.
                    If you're unable to access your account, contact support.
                </p>
                <hr style="border:none;border-top:1px solid #e5e7eb;margin:16px 0;">
                <p style="margin:0 0 10px;font-size:16px;line-height:1.6;color:#323232;">
                    You can also set up 2-step verification to secure your account — go to
                    <strong>"settings and privacy"</strong> &gt; <strong>"Security and login"</strong> &gt;
                    <strong>"2-step verification"</strong>.
                </p>
                <a href="{{ $securityUrl ?? config('app.url') }}" style="font-size:16px;font-weight:700;color:#970303;text-decoration:none;">
                    Learn more about 2-step verification
                </a>
            </td>
        </tr>
    </table>
</x-mail.client-layout>

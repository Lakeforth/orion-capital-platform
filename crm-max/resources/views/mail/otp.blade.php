<x-mail.client-layout
    :message="$message ?? null"
    :siteName="$siteName ?? get_site_name()"
    :logoUrl="$logoUrl ?? null"
    :showHero="true"
    heroTitle="Your login - Verification Code"
    :title="($siteName ?? get_site_name()) . ' — Login Verification Code'"
>
    <p style="margin:0 0 12px;font-size:16px;color:#323232;">Dear {{ $customerName ?? 'Trader' }},</p>
    <p style="margin:0 0 20px;font-size:16px;line-height:1.6;color:#001313;">
        We kindly request you to use the following code for email verification purposes.
    </p>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#FAF8FF;border-radius:8px;margin-bottom:20px;">
        <tr>
            <td style="padding:20px 24px;">
                <div style="font-size:16px;font-weight:700;color:#001313;margin-bottom:10px;">Verification Code:</div>
                <div style="font-size:36px;font-weight:900;color:#001313;letter-spacing:10px;">{{ $otp ?? '' }}</div>
            </td>
        </tr>
    </table>

    <p style="margin:0 0 20px;font-size:16px;line-height:1.6;color:#970303;">
        Please note that this code expires in 30 minutes and can only be used once.
        If you did not request an email verification code for {{ $siteName ?? get_site_name() }}, please disregard this email.
    </p>

    <p style="margin:0 0 6px;font-size:16px;color:#323232;">Thank you for choosing {{ $siteName ?? get_site_name() }}</p>
    <p style="margin:0 0 6px;font-size:16px;color:#323232;">Best Regards,</p>
    <p style="margin:0 0 0;font-size:16px;color:#323232;">{{ $siteName ?? get_site_name() }} Team.</p>
</x-mail.client-layout>

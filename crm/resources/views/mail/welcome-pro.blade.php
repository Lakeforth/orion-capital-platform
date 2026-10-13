<x-mail.client-layout
    :message="$message ?? null"
    :siteName="$siteName ?? get_site_name()"
    :logoUrl="$logoUrl ?? null"
    :showHero="true"
    heroTitle="You're now in&#10;Now let's grow"
    :title="'Welcome to ' . ($siteName ?? get_site_name())"
>
    <p style="margin:0 0 12px;font-size:16px;color:#323232;">Hi {{ $customerName ?? 'Trader' }},</p>
    <p style="margin:0 0 28px;font-size:16px;line-height:1.6;color:#001313;">
        Your account is verified and ready. Make your first deposit to unlock full trading
        access and your welcome bonus.
    </p>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#FAF8FF;border-radius:8px;margin-bottom:24px;">
        <tr>
            <td width="50%" style="padding:20px 24px;border-right:1px solid #e5e7eb;">
                <div style="font-size:20px;font-weight:700;color:#001313;margin-bottom:4px;">${{ $minDeposit ?? '20' }}</div>
                <div style="font-size:14px;color:#323232;">Min. Deposit</div>
            </td>
            <td width="50%" style="padding:20px 24px;">
                <div style="font-size:20px;font-weight:700;color:#001313;margin-bottom:4px;">${{ $minBonus ?? '500' }}</div>
                <div style="font-size:14px;color:#323232;">Min. Bonus</div>
            </td>
        </tr>
    </table>

    <a href="{{ $ctaUrl ?? config('app.url') }}" style="display:block;text-align:center;background-color:#0E9F64;color:#FAFFFD;text-decoration:none;padding:16px 24px;border-radius:999px;font-size:16px;font-weight:500;margin-bottom:28px;">
        Deposit Now &rarr;
    </a>

    <div style="font-size:20px;font-weight:700;color:#001313;margin-bottom:20px;">Fund your account in 3 steps</div>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:8px;">
        <tr>
            <td style="width:36px;vertical-align:top;padding-top:2px;">
                <div style="width:28px;height:28px;background-color:#323232;border-radius:50%;text-align:center;line-height:28px;font-size:14px;font-weight:700;color:#FFFFFF;">1</div>
            </td>
            <td style="vertical-align:top;padding-left:12px;padding-bottom:16px;">
                <div style="font-size:16px;font-weight:700;color:#001313;margin-bottom:4px;">Create your account</div>
                <div style="font-size:14px;color:#323232;line-height:1.5;">Sign up and complete your profile details to get started.</div>
            </td>
        </tr>
        <tr>
            <td style="width:36px;vertical-align:top;padding-top:2px;">
                <div style="width:28px;height:28px;background-color:#323232;border-radius:50%;text-align:center;line-height:28px;font-size:14px;font-weight:700;color:#FFFFFF;">2</div>
            </td>
            <td style="vertical-align:top;padding-left:12px;padding-bottom:16px;">
                <div style="font-size:16px;font-weight:700;color:#001313;margin-bottom:4px;">Verify your identity</div>
                <div style="font-size:14px;color:#323232;line-height:1.5;">Submit your KYC documents to unlock full account access.</div>
            </td>
        </tr>
        <tr>
            <td style="width:36px;vertical-align:top;padding-top:2px;">
                <div style="width:28px;height:28px;background-color:#323232;border-radius:50%;text-align:center;line-height:28px;font-size:14px;font-weight:700;color:#FFFFFF;">3</div>
            </td>
            <td style="vertical-align:top;padding-left:12px;">
                <div style="font-size:16px;font-weight:700;color:#001313;margin-bottom:4px;">Fund &amp; start trading</div>
                <div style="font-size:14px;color:#323232;line-height:1.5;">Make your first deposit and start exploring live markets.</div>
            </td>
        </tr>
    </table>
</x-mail.client-layout>

<x-mail.client-layout
    :message="$message ?? null"
    :siteName="$siteName ?? get_site_name()"
    :logoUrl="$logoUrl ?? null"
    :showBadge="true"
    badgeText="Action is Required"
    badgeColor="amber"
    :title="($siteName ?? get_site_name()) . ' — Identity Verification Required'"
>
    <h1 style="margin:0 0 16px;font-size:24px;font-weight:700;color:#001313;">Identity Verification Required</h1>

    <p style="margin:0 0 12px;font-size:16px;color:#323232;">Hi {{ $customerName ?? 'Trader' }},</p>
    <p style="margin:0 0 24px;font-size:16px;line-height:1.6;color:#001313;">
        We need to verify your identity before you can access full trading features.
        This will help us keep your account safe against fraudulent activities.
    </p>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#FAF8FF;border-radius:8px;margin-bottom:20px;">
        <tr>
            <td style="padding:20px 24px;">
                <div style="font-size:20px;font-weight:700;color:#001313;margin-bottom:16px;">Documents Required</div>

                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:12px;">
                    <tr>
                        <td style="width:36px;vertical-align:top;padding-top:2px;">
                            <div style="width:28px;height:28px;background-color:#323232;border-radius:50%;text-align:center;line-height:28px;font-size:14px;font-weight:700;color:#FFFFFF;">1</div>
                        </td>
                        <td style="vertical-align:top;padding-left:12px;">
                            <div style="font-size:16px;font-weight:700;color:#001313;margin-bottom:2px;">Government-issued Photo ID</div>
                            <div style="font-size:14px;color:#323232;">(passport, driver's license, or national ID)</div>
                        </td>
                    </tr>
                </table>

                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:12px;">
                    <tr>
                        <td style="width:36px;vertical-align:top;padding-top:2px;">
                            <div style="width:28px;height:28px;background-color:#323232;border-radius:50%;text-align:center;line-height:28px;font-size:14px;font-weight:700;color:#FFFFFF;">2</div>
                        </td>
                        <td style="vertical-align:top;padding-left:12px;">
                            <div style="font-size:16px;font-weight:700;color:#001313;margin-bottom:2px;">Proof of Address</div>
                            <div style="font-size:14px;color:#323232;">(utility bill or bank statement; last 3 months)</div>
                        </td>
                    </tr>
                </table>

                <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                    <tr>
                        <td style="width:36px;vertical-align:top;padding-top:2px;">
                            <div style="width:28px;height:28px;background-color:#323232;border-radius:50%;text-align:center;line-height:28px;font-size:14px;font-weight:700;color:#FFFFFF;">3</div>
                        </td>
                        <td style="vertical-align:top;padding-left:12px;">
                            <div style="font-size:16px;font-weight:700;color:#001313;margin-bottom:2px;">Passport photo</div>
                            <div style="font-size:14px;color:#323232;">Clear headshot photograph, with proper lighting</div>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <p style="margin:0 0 24px;font-size:12px;line-height:1.6;color:#323232;">
        Please ensure all documents are clear, valid, and not expired.<br>
        Files accepted: JPG, PNG, or PDF up to 10MB each.<br>
        Verification usually takes 1–2 business days.
    </p>

    <a href="{{ $ctaUrl ?? config('app.url') }}" style="display:block;text-align:center;background-color:#0E9F64;color:#FAFFFD;text-decoration:none;padding:16px 24px;border-radius:999px;font-size:16px;font-weight:500;">
        Complete Verification &rarr;
    </a>
</x-mail.client-layout>

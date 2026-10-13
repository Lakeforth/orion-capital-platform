<x-mail.client-layout
    :message="$message ?? null"
    :siteName="$siteName ?? get_site_name()"
    :logoUrl="$logoUrl ?? null"
    :showBadge="true"
    badgeText="Verification is needed"
    badgeColor="amber"
    :title="($siteName ?? get_site_name()) . ' — Your Account is Under Review'"
>
    <h1 style="margin:0 0 16px;font-size:24px;font-weight:700;color:#001313;">Your Account is Under Review</h1>

    <p style="margin:0 0 12px;font-size:16px;color:#323232;">Hi {{ $customerName ?? 'Trader' }},</p>
    <p style="margin:0 0 24px;font-size:16px;line-height:1.6;color:#001313;">
        We have noticed some unusual activities in your account. Our compliance team needs to
        verify your identity before granting full access.
    </p>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#FAF8FF;border-radius:8px;margin-bottom:20px;">
        <tr>
            <td style="padding:20px 24px;">
                <div style="font-size:20px;font-weight:700;color:#001313;margin-bottom:16px;">What happens next?</div>

                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:12px;">
                    <tr>
                        <td style="width:36px;vertical-align:top;padding-top:2px;">
                            <div style="width:28px;height:28px;background-color:#323232;border-radius:50%;text-align:center;line-height:28px;font-size:14px;font-weight:700;color:#FFFFFF;">1</div>
                        </td>
                        <td style="vertical-align:top;padding-left:12px;">
                            <div style="font-size:16px;color:#323232;">Our accounts team will contact you at <strong>{{ $clientEmail ?? '' }}</strong></div>
                        </td>
                    </tr>
                </table>

                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:12px;">
                    <tr>
                        <td style="width:36px;vertical-align:top;padding-top:2px;">
                            <div style="width:28px;height:28px;background-color:#323232;border-radius:50%;text-align:center;line-height:28px;font-size:14px;font-weight:700;color:#FFFFFF;">2</div>
                        </td>
                        <td style="vertical-align:top;padding-left:12px;">
                            <div style="font-size:16px;color:#323232;">You may be asked to provide supporting documents.</div>
                        </td>
                    </tr>
                </table>

                <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                    <tr>
                        <td style="width:36px;vertical-align:top;padding-top:2px;">
                            <div style="width:28px;height:28px;background-color:#323232;border-radius:50%;text-align:center;line-height:28px;font-size:14px;font-weight:700;color:#FFFFFF;">3</div>
                        </td>
                        <td style="vertical-align:top;padding-left:12px;">
                            <div style="font-size:16px;color:#323232;">Once verified, full account access will be restored.</div>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <p style="margin:0 0 24px;font-size:12px;line-height:1.6;color:#323232;">
        You do not need to take any action right now. A member of our team will contact you within 1–2 business days.
    </p>

    <a href="{{ $supportUrl ?? config('app.url') }}" style="display:block;text-align:center;background-color:#FFFFFF;color:#0E9F64;text-decoration:none;padding:14px 24px;border-radius:999px;font-size:14px;font-weight:500;border:1px solid #0E9F64;">
        Contact Support
    </a>
</x-mail.client-layout>

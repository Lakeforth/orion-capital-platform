<x-mail.client-layout
    :message="$message ?? null"
    :siteName="$siteName ?? get_site_name()"
    :logoUrl="$logoUrl ?? null"
    :showBadge="true"
    badgeText="Action is Required"
    badgeColor="amber"
    :title="($siteName ?? get_site_name()) . ' — Verification Could Not Be Completed'"
>
    <h1 style="margin:0 0 20px;font-size:24px;font-weight:700;color:#001313;">Verification Could Not Be Completed</h1>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#FAF8FF;border-radius:8px;margin-bottom:24px;">
        <tr>
            <td style="padding:24px;">
                <p style="margin:0 0 12px;font-size:16px;color:#323232;">Dear Trader,</p>
                <p style="margin:0 0 12px;font-size:16px;line-height:1.6;color:#323232;">
                    We regret to inform you that your Identity Verification could not be approved at this time.
                </p>
                <p style="margin:0 0 12px;font-size:16px;line-height:1.6;color:#323232;">
                    This may be due to unclear documents, missing information, or details that could not be
                    verified successfully. Please review your submitted documents and ensure that all information
                    provided is accurate and clearly visible before resubmitting.
                </p>
                <p style="margin:0 0 12px;font-size:16px;line-height:1.6;color:#323232;">
                    You can log in to your account and upload a new set of verification documents to continue
                    the verification process.
                </p>
                <p style="margin:0 0 12px;font-size:16px;line-height:1.6;color:#323232;">
                    If you require any assistance, our support team is available to help guide you through the process.
                </p>
                <p style="margin:0 0 6px;font-size:16px;color:#323232;">Best Regards,</p>
                <p style="margin:0;font-size:16px;color:#323232;">{{ $siteName ?? get_site_name() }} Team.</p>
            </td>
        </tr>
    </table>

    <a href="{{ $ctaUrl ?? config('app.url') }}" style="display:block;text-align:center;background-color:#0E9F64;color:#FAFFFD;text-decoration:none;padding:16px 24px;border-radius:999px;font-size:16px;font-weight:500;">
        Resubmit Documents &rarr;
    </a>
</x-mail.client-layout>

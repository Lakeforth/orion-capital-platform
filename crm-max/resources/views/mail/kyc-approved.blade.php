<x-mail.client-layout
    :message="$message ?? null"
    :siteName="$siteName ?? get_site_name()"
    :logoUrl="$logoUrl ?? null"
    :showBadge="true"
    badgeText="Identity Verified"
    badgeColor="green"
    :title="($siteName ?? get_site_name()) . ' — Identity Verified'"
>
    <h1 style="margin:0 0 20px;font-size:24px;font-weight:700;color:#001313;">Your Identity has been Verified</h1>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#FAF8FF;border-radius:8px;">
        <tr>
            <td style="padding:24px;">
                <p style="margin:0 0 12px;font-size:16px;color:#323232;">Dear Trader,</p>
                <p style="margin:0 0 12px;font-size:16px;line-height:1.6;color:#323232;">
                    We are pleased to inform you that your Identity Verification has been successfully reviewed
                    and approved. We appreciate your patience and cooperation during the process.
                </p>
                <p style="margin:0 0 12px;font-size:16px;line-height:1.6;color:#323232;">
                    Your account is now fully activated and you have full access to our trading platform.
                    You can now log into your account and begin exploring all the possibilities our platform offers.
                </p>
                <p style="margin:0 0 12px;font-size:16px;color:#323232;">Happy trading!</p>
                <p style="margin:0;font-size:16px;color:#323232;">{{ $siteName ?? get_site_name() }} Team.</p>
            </td>
        </tr>
    </table>
</x-mail.client-layout>

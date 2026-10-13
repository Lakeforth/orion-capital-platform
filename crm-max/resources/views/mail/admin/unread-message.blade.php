<x-mail.client-layout
    :message="$message ?? null"
    :siteName="$siteName ?? get_site_name()"
    :showBadge="true"
    badgeText="New Message"
    badgeColor="teal"
    :title="($siteName ?? get_site_name()) . ' — New Client Message'"
>
    <h1 style="margin:0 0 16px;font-size:24px;font-weight:700;color:#001313;">Admin, you have a new message</h1>

    <p style="margin:0 0 12px;font-size:16px;color:#323232;">Hi {{ $adminName ?? 'Admin' }},</p>
    <p style="margin:0 0 20px;font-size:16px;line-height:1.6;color:#001313;">
        A client is waiting for your response.
    </p>

    {{-- Message Card --}}
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-left:3px solid #0E9F6E;background-color:#FFFFFF;box-shadow:0 1px 4px rgba(0,0,0,0.06);border-radius:0 8px 8px 0;margin-bottom:24px;">
        <tr>
            <td style="padding:16px 20px;">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:10px;">
                    <tr>
                        <td style="font-size:14px;font-weight:600;color:#323232;">{{ $clientName ?? 'Client' }}</td>
                        <td align="right" style="font-size:10px;font-weight:500;color:#323232;">{{ $messageTime ?? '' }}</td>
                    </tr>
                </table>
                <hr style="border:none;border-top:1px solid #e5e7eb;margin:0 0 10px;">
                <p style="margin:0;font-size:13px;line-height:1.6;color:#48566A;">{{ $messagePreview ?? '' }}</p>
            </td>
        </tr>
    </table>

    <a href="{{ $ctaUrl ?? config('app.url') }}" style="display:block;text-align:center;background-color:#0E9F64;color:#FAFFFD;text-decoration:none;padding:16px 24px;border-radius:999px;font-size:16px;font-weight:500;margin-bottom:12px;">
        Reply to Client &rarr;
    </a>
    <a href="{{ $ctaUrl ?? config('app.url') }}" style="display:block;text-align:center;background-color:#FFFFFF;color:#0E9F64;text-decoration:none;padding:14px 24px;border-radius:999px;font-size:14px;font-weight:500;border:1px solid #0E9F64;">
        View All Messages
    </a>

    <p style="margin:20px 0 0;font-size:12px;color:#323232;text-align:center;">
        Clients expect a reply within 24 hours. Responding quickly improves satisfaction and trust.
    </p>
</x-mail.client-layout>

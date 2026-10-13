<x-mail.client-layout
    :message="$message ?? null"
    :siteName="$siteName ?? get_site_name()"
    :showHero="true"
    heroTitle="New Withdrawal Request"
    :title="($siteName ?? get_site_name()) . ' — New Withdrawal Request'"
>
    <p style="margin:0 0 12px;font-size:16px;color:#323232;">Hi {{ $adminName ?? 'Admin' }},</p>
    <p style="margin:0 0 24px;font-size:16px;line-height:1.6;color:#001313;">
        A client has submitted a withdrawal request that requires your approval.
    </p>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#FAF8FF;border-radius:8px;margin-bottom:24px;">
        <tr>
            <td style="padding:16px 24px 4px;">
                <div style="font-size:14px;color:#323232;">Withdrawal Details</div>
            </td>
        </tr>
        <tr>
            <td style="padding:0 24px 16px;">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                    @if(!empty($clientName))
                    <tr>
                        <td style="padding:14px 0;font-size:14px;color:#323232;border-bottom:1px solid #e5e7eb;">Client Name</td>
                        <td align="right" style="padding:14px 0;font-size:14px;font-weight:500;color:#001313;border-bottom:1px solid #e5e7eb;">{{ $clientName }}</td>
                    </tr>
                    @endif
                    @if(!empty($amount))
                    <tr>
                        <td style="padding:14px 0;font-size:14px;color:#323232;border-bottom:1px solid #e5e7eb;">Amount</td>
                        <td align="right" style="padding:14px 0;font-size:14px;font-weight:500;color:#001313;border-bottom:1px solid #e5e7eb;">{{ $amount }}</td>
                    </tr>
                    @endif
                    @if(!empty($method))
                    <tr>
                        <td style="padding:14px 0;font-size:14px;color:#323232;border-bottom:1px solid #e5e7eb;">Method</td>
                        <td align="right" style="padding:14px 0;font-size:14px;font-weight:500;color:#001313;border-bottom:1px solid #e5e7eb;">{{ $method }}</td>
                    </tr>
                    @endif
                    @if(!empty($transactionDate))
                    <tr>
                        <td style="padding:14px 0;font-size:14px;color:#323232;border-bottom:1px solid #e5e7eb;">Date</td>
                        <td align="right" style="padding:14px 0;font-size:14px;font-weight:500;color:#001313;border-bottom:1px solid #e5e7eb;">{{ $transactionDate }}</td>
                    </tr>
                    @endif
                    <tr>
                        <td style="padding:14px 0;font-size:14px;color:#323232;">Status</td>
                        <td align="right" style="padding:14px 0;">
                            <span style="display:inline-block;padding:4px 14px;border-radius:999px;background-color:#fef3c7;color:#654703;font-size:13px;font-weight:600;">Pending</span>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <a href="{{ $ctaUrl ?? config('app.url') }}" style="display:block;text-align:center;background-color:#0E9F64;color:#FAFFFD;text-decoration:none;padding:16px 24px;border-radius:999px;font-size:16px;font-weight:500;margin-bottom:12px;">
        Review Withdrawal &rarr;
    </a>

    <p style="margin:16px 0 0;font-size:12px;color:#94A5B8;text-align:center;">
        Log in to approve or reject this withdrawal from your admin panel.
    </p>
</x-mail.client-layout>

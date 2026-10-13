<x-mail.client-layout
    :message="$message ?? null"
    :siteName="$siteName ?? get_site_name()"
    :logoUrl="$logoUrl ?? null"
    :showHero="true"
    heroTitle="Withdrawal Approved"
    :title="($siteName ?? get_site_name()) . ' — Withdrawal Approved'"
>
    <p style="margin:0 0 12px;font-size:16px;color:#323232;">Hi {{ $customerName ?? 'Trader' }},</p>
    <p style="margin:0 0 24px;font-size:16px;line-height:1.6;color:#001313;">
        Good news! your withdrawal request has been approved and your funds are on their way.
        Please allow some time for the transfer to complete depending on your chosen method.
    </p>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#FAF8FF;border-radius:8px;margin-bottom:16px;">
        <tr>
            <td style="padding:20px 24px;">
                <div style="font-size:20px;font-weight:700;color:#0E9F64;margin-bottom:6px;">Amount Withdrawn</div>
                <div style="font-size:20px;font-weight:700;color:#02CCCC;">{{ $amount ?? '' }}</div>
            </td>
        </tr>
    </table>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#FAF8FF;border-radius:8px;margin-bottom:16px;">
        <tr>
            <td style="padding:16px 24px 4px;">
                <div style="font-size:14px;color:#323232;">Transaction Details</div>
            </td>
        </tr>
        <tr>
            <td style="padding:0 24px 16px;">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                    <tr>
                        <td style="padding:14px 0;font-size:14px;color:#323232;border-bottom:1px solid #e5e7eb;">Initial Balance</td>
                        <td align="right" style="padding:14px 0;font-size:14px;font-weight:500;color:#001313;border-bottom:1px solid #e5e7eb;">{{ $initialBalance ?? '' }}</td>
                    </tr>
                    <tr>
                        <td style="padding:14px 0;font-size:14px;color:#323232;border-bottom:1px solid #e5e7eb;">Amount Withdrawn</td>
                        <td align="right" style="padding:14px 0;font-size:14px;font-weight:500;color:#001313;border-bottom:1px solid #e5e7eb;">{{ $amount ?? '' }}</td>
                    </tr>
                    <tr>
                        <td style="padding:14px 0;font-size:14px;color:#323232;">New Balance</td>
                        <td align="right" style="padding:14px 0;font-size:14px;font-weight:500;color:#001313;">{{ $newBalance ?? '' }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    @if(!empty($txHash) || !empty($network) || !empty($transactionDate))
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#FAF8FF;border-radius:8px;margin-bottom:24px;">
        <tr>
            <td style="padding:16px 24px 4px;">
                <div style="font-size:14px;color:#323232;">Onchain Details</div>
            </td>
        </tr>
        <tr>
            <td style="padding:0 24px 16px;">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                    @if(!empty($txHash))
                    <tr>
                        <td style="padding:14px 0;font-size:14px;color:#323232;border-bottom:1px solid #e5e7eb;width:40%;">Transaction Hash</td>
                        <td align="right" style="padding:14px 0;font-size:13px;font-weight:500;color:#001313;border-bottom:1px solid #e5e7eb;word-break:break-all;">{{ $txHash }}</td>
                    </tr>
                    @endif
                    @if(!empty($network))
                    <tr>
                        <td style="padding:14px 0;font-size:14px;color:#323232;border-bottom:1px solid #e5e7eb;">Network</td>
                        <td align="right" style="padding:14px 0;font-size:14px;font-weight:500;color:#001313;border-bottom:1px solid #e5e7eb;">{{ $network }}</td>
                    </tr>
                    @endif
                    @if(!empty($transactionDate))
                    <tr>
                        <td style="padding:14px 0;font-size:14px;color:#323232;">Time Stamp</td>
                        <td align="right" style="padding:14px 0;font-size:14px;font-weight:500;color:#001313;">{{ $transactionDate }}</td>
                    </tr>
                    @endif
                </table>
            </td>
        </tr>
    </table>
    @endif

    <a href="{{ $ctaUrl ?? config('app.url') }}" style="display:block;text-align:center;background-color:#0E9F64;color:#FAFFFD;text-decoration:none;padding:16px 24px;border-radius:999px;font-size:16px;font-weight:500;margin-bottom:12px;">
        View Dashboard &rarr;
    </a>
    <a href="{{ $ctaUrl ?? config('app.url') }}" style="display:block;text-align:center;background-color:#FFFFFF;color:#0E9F64;text-decoration:none;padding:14px 24px;border-radius:999px;font-size:14px;font-weight:500;border:1px solid #0E9F64;">
        Go to Dashboard
    </a>
</x-mail.client-layout>

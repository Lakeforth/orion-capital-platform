<x-mail.client-layout
    :message="$message ?? null"
    :siteName="$siteName ?? get_site_name()"
    :logoUrl="$logoUrl ?? null"
    :showHero="true"
    heroTitle="Deposit Pending"
    :title="($siteName ?? get_site_name()) . ' — Deposit Pending'"
>
    <p style="margin:0 0 12px;font-size:16px;color:#323232;">Hi {{ $customerName ?? 'Trader' }},</p>
    <p style="margin:0 0 16px;font-size:16px;line-height:1.6;color:#001313;">
        We wish to inform you that we have received your request and are processing your funds.
    </p>
    <p style="margin:0 0 24px;font-size:16px;line-height:1.6;font-weight:700;color:#001313;">
        Do not resend Funds: your transaction is securely in queue. Initiating another transaction
        may result in duplicate charges and processing delays.
    </p>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#FAF8FF;border-radius:8px;margin-bottom:16px;">
        <tr>
            <td style="padding:20px 24px;">
                <div style="font-size:20px;font-weight:700;color:#0E9F64;margin-bottom:6px;">Pending Deposit</div>
                <div style="font-size:20px;font-weight:700;color:#02CCCC;">{{ $amount ?? '' }}</div>
            </td>
        </tr>
    </table>

    @if(!empty($txHash) || !empty($network) || !empty($senderAddress) || !empty($transactionDate))
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
                    @if(!empty($senderAddress))
                    <tr>
                        <td style="padding:14px 0;font-size:14px;color:#323232;border-bottom:1px solid #e5e7eb;">From wallet</td>
                        <td align="right" style="padding:14px 0;font-size:13px;font-weight:500;color:#001313;border-bottom:1px solid #e5e7eb;word-break:break-all;">{{ $senderAddress }}</td>
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
    <a href="{{ $walletUrl ?? config('app.url') }}" style="display:block;text-align:center;background-color:#FFFFFF;color:#0E9F64;text-decoration:none;padding:14px 24px;border-radius:999px;font-size:14px;font-weight:500;border:1px solid #0E9F64;">
        View Wallet
    </a>
</x-mail.client-layout>

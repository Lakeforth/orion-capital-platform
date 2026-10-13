<x-mail.client-layout
    :message="$message ?? null"
    :siteName="$siteName ?? get_site_name()"
    :logoUrl="$logoUrl ?? null"
    :showHero="true"
    heroTitle="Deposit Approved"
    :title="($siteName ?? get_site_name()) . ' — Deposit Approved'"
>
    <p style="margin:0 0 12px;font-size:16px;color:#323232;">Hi {{ $customerName ?? 'Trader' }},</p>
    <p style="margin:0 0 24px;font-size:16px;line-height:1.6;color:#001313;">
        Good news! your funds have been successfully credited to your {{ $siteName ?? get_site_name() }} wallet
        and are now available in your account.
    </p>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#FAF8FF;border-radius:8px;margin-bottom:16px;">
        <tr>
            <td style="padding:20px 24px;">
                <div style="font-size:20px;font-weight:700;color:#0E9F64;margin-bottom:6px;">+ Deposit Amount</div>
                <div style="font-size:20px;font-weight:700;color:#02CCCC;">{{ $amount ?? '' }}</div>
            </td>
        </tr>
    </table>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#FAF8FF;border-radius:8px;margin-bottom:24px;">
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
                        <td align="right" style="padding:14px 0;font-size:14px;font-weight:500;color:#001313;border-bottom:1px solid #e5e7eb;">{{ $wallet_balance ?? '' }}</td>
                    </tr>
                    <tr>
                        <td style="padding:14px 0;font-size:14px;color:#323232;border-bottom:1px solid #e5e7eb;">Amount Credited</td>
                        <td align="right" style="padding:14px 0;font-size:14px;font-weight:500;color:#001313;border-bottom:1px solid #e5e7eb;">{{ $amount ?? '' }}</td>
                    </tr>
                    <tr>
                        <td style="padding:14px 0;font-size:14px;color:#323232;">New Balance</td>
                        <td align="right" style="padding:14px 0;font-size:14px;font-weight:500;color:#001313;">{{ $new_balance ?? '' }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    {{-- Onchain Details --}}
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
                        <td style="padding:14px 0;font-size:14px;color:#323232;border-bottom:1px solid #e5e7eb;width:45%;">Transaction Hash</td>
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
                        <td style="padding:14px 0;font-size:14px;color:#323232;border-bottom:1px solid #e5e7eb;">Time Stamp</td>
                        <td align="right" style="padding:14px 0;font-size:14px;font-weight:500;color:#001313;border-bottom:1px solid #e5e7eb;">{{ $transactionDate }}</td>
                    </tr>
                    @endif
                    <tr>
                        <td style="padding:14px 0;font-size:14px;color:#323232;">Narration</td>
                        <td align="right" style="padding:14px 0;font-size:14px;font-weight:500;color:#001313;">{{ $narration ?? '---' }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
    @endif

    <a href="{{ $ctaUrl ?? config('app.url') }}" style="display:block;text-align:center;background-color:#0E9F64;color:#FAFFFD;text-decoration:none;padding:16px 24px;border-radius:999px;font-size:16px;font-weight:500;margin-bottom:12px;">
        Start Trading &rarr;
    </a>
    <a href="{{ $ctaUrl ?? config('app.url') }}" style="display:block;text-align:center;background-color:#FFFFFF;color:#0E9F64;text-decoration:none;padding:14px 24px;border-radius:999px;font-size:14px;font-weight:500;border:1px solid #0E9F64;">
        View Wallet
    </a>
</x-mail.client-layout>

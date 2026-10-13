<x-mail.client-layout
    :message="$message ?? null"
    :siteName="$siteName ?? get_site_name()"
    :logoUrl="$logoUrl ?? null"
    :showHero="true"
    heroTitle="Bonus Alert"
    :title="($siteName ?? get_site_name()) . ' — Bonus Credited'"
>
    <p style="margin:0 0 12px;font-size:16px;color:#323232;">Hi {{ $customerName ?? 'Trader' }},</p>
    <p style="margin:0 0 12px;font-size:16px;line-height:1.6;color:#001313;">
        Great work! You've achieved a new account milestone.
    </p>
    <p style="margin:0 0 24px;font-size:16px;line-height:1.6;color:#001313;">
        As part of your progress with {{ $siteName ?? get_site_name() }}, a bonus reward has been
        successfully credited to your trading wallet and is now available in your account.
    </p>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#FAF8FF;border-radius:8px;margin-bottom:20px;">
        <tr>
            <td style="padding:20px 24px;">
                <div style="font-size:20px;font-weight:700;color:#0E9F64;margin-bottom:6px;">+ Bonus Amount</div>
                <div style="font-size:20px;font-weight:700;color:#02CCCC;">{{ $amount ?? '' }}</div>
            </td>
        </tr>
    </table>

    <p style="margin:0 0 24px;font-size:16px;line-height:1.6;color:#001313;">
        Stay active and keep trading to qualify for future rewards and exclusive promotions.
    </p>

    <a href="{{ $ctaUrl ?? config('app.url') }}" style="display:block;text-align:center;background-color:#0E9F64;color:#FAFFFD;text-decoration:none;padding:16px 24px;border-radius:999px;font-size:16px;font-weight:500;margin-bottom:12px;">
        Start Trading &rarr;
    </a>

    <p style="margin:16px 0 0;font-size:12px;color:#323232;text-align:center;">
        Bonus terms and conditions apply. See your dashboard for details.
    </p>
</x-mail.client-layout>

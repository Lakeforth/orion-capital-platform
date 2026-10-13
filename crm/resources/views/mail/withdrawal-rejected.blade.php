<x-mail.client-layout
    :message="$message ?? null"
    :siteName="$siteName ?? get_site_name()"
    :logoUrl="$logoUrl ?? null"
    :showHero="true"
    heroTitle="Withdrawal Failed"
    :title="($siteName ?? get_site_name()) . ' — Withdrawal Failed'"
>
    <h2 style="margin:0 0 16px;font-size:24px;font-weight:700;color:#001313;">Your Withdrawal Could Not Be Processed</h2>

    <p style="margin:0 0 12px;font-size:16px;color:#323232;">Hi {{ $customerName ?? 'Trader' }},</p>
    <p style="margin:0 0 16px;font-size:16px;line-height:1.6;color:#001313;">
        We were unable to process your withdrawal request. Your funds have been returned to your
        trading balance. If you believe this is an error or need assistance, please contact our support team.
    </p>
    <p style="margin:0 0 28px;font-size:16px;line-height:1.6;color:#001313;">
        Your funds remain safe in your account.
    </p>

    <a href="{{ $ctaUrl ?? config('app.url') }}" style="display:block;text-align:center;background-color:#0E9F64;color:#FAFFFD;text-decoration:none;padding:16px 24px;border-radius:999px;font-size:16px;font-weight:500;margin-bottom:12px;">
        Try Again &rarr;
    </a>
    <a href="{{ $ctaUrl ?? config('app.url') }}" style="display:block;text-align:center;background-color:#FFFFFF;color:#323232;text-decoration:none;padding:14px 24px;border-radius:999px;font-size:14px;font-weight:500;border:1px solid #e5e7eb;">
        View Dashboard
    </a>
</x-mail.client-layout>

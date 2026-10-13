<x-mail.client-layout
    :message="$message ?? null"
    :siteName="$siteName ?? get_site_name()"
    :logoUrl="$logoUrl ?? null"
    :title="($siteName ?? get_site_name()) . ' — Account Suspended'"
>
    <h1 style="margin:0 0 16px;font-size:24px;font-weight:700;color:#001313;">Your Account Has been Suspended</h1>

    <p style="margin:0 0 12px;font-size:16px;color:#323232;">Hi {{ $customerName ?? 'Trader' }},</p>
    <p style="margin:0 0 16px;font-size:16px;line-height:1.6;color:#001313;">
        Your account has been suspended due to a violation of our terms of service.
    </p>
    <p style="margin:0 0 16px;font-size:16px;line-height:1.6;color:#001313;">
        If you believe this is a mistake or would like more information, please review our
        <a href="{{ $policyUrl ?? config('app.url') }}" style="color:#0E9F64;text-decoration:none;font-weight:700;">suspension policy</a>.
    </p>
    <p style="margin:0 0 28px;font-size:16px;line-height:1.6;color:#001313;">
        To appeal this decision, click the button below.
    </p>

    <a href="{{ $ctaUrl ?? config('app.url') }}" style="display:block;text-align:center;background-color:#0E9F64;color:#FAFFFD;text-decoration:none;padding:16px 24px;border-radius:999px;font-size:16px;font-weight:500;margin-bottom:12px;">
        Appeal Suspension &rarr;
    </a>

    <p style="margin:16px 0 0;font-size:12px;color:#323232;text-align:center;">
        If you have questions, contact our support team for assistance.
    </p>
</x-mail.client-layout>

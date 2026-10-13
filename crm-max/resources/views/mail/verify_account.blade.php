<x-mail.client-layout
    :message="$message ?? null"
    :siteName="$siteName ?? get_site_name()"
    :title="($siteName ?? get_site_name()) . ' — Verify Your Email Address'"
>
    <h1 style="margin:0 0 24px;font-size:26px;font-weight:700;color:#001313;">Verify Your Email Address</h1>

    <p style="margin:0 0 12px;font-size:16px;color:#323232;">Hi {{ $customerName ?? 'there' }},</p>
    <p style="margin:0 0 20px;font-size:16px;line-height:1.6;color:#001313;">
        Thank you for signing up with {{ $siteName ?? get_site_name() }}. Please use the confirmation code below to verify your email address and activate your account.
    </p>

    <p style="margin:0 0 24px;font-size:16px;line-height:1.6;color:#001313;">
        <strong>Confirmation Code:</strong>&nbsp;&nbsp;<span style="font-size:18px;font-weight:700;letter-spacing:6px;">{{ $otp ?? '' }}</span>
    </p>

    <p style="margin:0 0 24px;font-size:16px;line-height:1.6;color:#970303;">
        This code expires in <strong>30 minutes</strong> and can only be used once.
        If you did not create an account with {{ $siteName ?? get_site_name() }}, you can safely ignore this email.
    </p>

    <p style="margin:0 0 4px;font-size:16px;color:#323232;">Thank you for choosing {{ $siteName ?? get_site_name() }}</p>
    <p style="margin:0 0 4px;font-size:16px;color:#323232;">Best Regards,</p>
    <p style="margin:0 0 0;font-size:16px;color:#323232;">{{ $siteName ?? get_site_name() }} Team.</p>
</x-mail.client-layout>

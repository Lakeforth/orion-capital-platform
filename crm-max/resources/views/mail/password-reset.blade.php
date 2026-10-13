<x-mail.client-layout
    :siteName="$siteName ?? get_site_name()"
    :title="($siteName ?? get_site_name()) . ' — Reset Your Password'"
>
    <h1 style="margin:0 0 16px;font-size:24px;font-weight:700;color:#001313;">Hi, {{ $siteName ?? get_site_name() }}</h1>

    <p style="margin:0 0 12px;font-size:16px;line-height:1.6;color:#001313;">
        You are receiving this email because you have forgotten your password and want to get back into your account.
    </p>
    <p style="margin:0 0 20px;font-size:16px;line-height:1.6;color:#001313;">
        Here is your reset code below:
    </p>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#FAF8FF;border-radius:8px;margin-bottom:16px;">
        <tr>
            <td style="padding:20px 24px;">
                <div style="font-size:16px;font-weight:700;color:#001313;margin-bottom:10px;">Verification Code:</div>
                <div style="font-size:36px;font-weight:900;color:#001313;letter-spacing:10px;">{{ $token ?? '' }}</div>
            </td>
        </tr>
    </table>

    <p style="margin:0 0 16px;font-size:16px;line-height:1.6;color:#001313;">
        Copy the code above and use it to reset your password.
    </p>

    @if(!empty($resetUrl))
    <a href="{{ $resetUrl }}" style="display:block;text-align:center;background-color:#0E9F64;color:#FAFFFD;text-decoration:none;padding:16px 24px;border-radius:999px;font-size:16px;font-weight:500;margin-bottom:20px;">
        Reset Password &rarr;
    </a>
    @endif

    <p style="margin:0 0 20px;font-size:16px;line-height:1.6;color:#970303;">
        Please note that this code expires in {{ $expireMinutes ?? 30 }} minutes and can only be used once.
        If you did not request a password reset, please ignore this email — your account remains secure.
    </p>

    <p style="margin:0 0 6px;font-size:16px;color:#323232;">Thank you for choosing {{ $siteName ?? get_site_name() }}</p>
    <p style="margin:0 0 6px;font-size:16px;color:#323232;">Best Regards,</p>
    <p style="margin:0 0 0;font-size:16px;color:#323232;">{{ $siteName ?? get_site_name() }} Team.</p>
</x-mail.client-layout>

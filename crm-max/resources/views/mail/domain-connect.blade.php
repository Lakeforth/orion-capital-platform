<x-mail.client-layout
    :message="$message ?? null"
    :siteName="$siteName ?? get_site_name()"
    :title="($siteName ?? get_site_name()) . ' — Domain Connection Alert'"
    :showHero="true"
    heroTitle="Your Domain Connection Alert"
>
    <h1 style="margin:0 0 16px;font-size:24px;font-weight:700;color:#001313;">Hi, {{ $customerName }}</h1>

    <p style="margin:0 0 20px;font-size:16px;line-height:1.6;color:#001313;">
        Congratulations, your domain has been connected.
    </p>

    <p style="margin:0 0 20px;font-size:16px;line-height:1.6;color:#001313;">
        Please see the various URLs of your domain below:
    </p>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#FAF8FF;border-radius:8px;margin-bottom:20px;">
        <tr>
            <td style="padding:0 24px 16px;">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                    <tr>
                        <td style="padding:16px 0;font-size:14px;font-weight:700;color:#323232;border-bottom:1px solid #e5e7eb;width:40%;">Client Area::</td>
                        <td align="right" style="padding:16px 0;font-size:14px;color:#001313;border-bottom:1px solid #e5e7eb;">{{ $data['clientAreaUrl'] ?? '' }}</td>
                    </tr>
                    <tr>
                        <td style="padding:16px 0;font-size:14px;font-weight:700;color:#323232;border-bottom:1px solid #e5e7eb;">Backoffice Admin:</td>
                        <td align="right" style="padding:16px 0;font-size:14px;color:#001313;border-bottom:1px solid #e5e7eb;">{{ $data['backOfficeUrl'] ?? '' }}</td>
                    </tr>
                    <tr>
                        <td style="padding:16px 0;font-size:14px;font-weight:700;color:#323232;">Trade Room:</td>
                        <td align="right" style="padding:16px 0;font-size:14px;color:#001313;">{{ $data['tradeRoomUrl'] ?? '' }}</td>
                    </tr>
                    <tr>
                        <td style="padding:16px 0;font-size:14px;font-weight:700;color:#323232;">Landing Page:</td>
                        <td align="right" style="padding:16px 0;font-size:14px;color:#001313;">{{ $data['landingPageUrl'] ?? '' }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <a href="{{ $data['startUrl'] ?? config('app.url') }}" style="display:block;text-align:center;background-color:#0E9F64;color:#FAFFFD;text-decoration:none;padding:16px 24px;border-radius:999px;font-size:16px;font-weight:500;margin-bottom:28px;">
        Start Trading &rarr;
    </a>
</x-mail.client-layout>

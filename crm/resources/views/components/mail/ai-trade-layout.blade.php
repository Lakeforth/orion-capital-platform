@props(['data' => [], 'message' => null])

@php
    $siteName = (string) ($data['site_name'] ?? get_site_name() ?? config('app.name'));
    $logoUrl = (string) ($data['logo_url'] ?? asset('images/logo.png'));
    $supportUrl = (string) ($data['support_url'] ?? config('app.url'));
    $supportEmail = (string) ($data['support_email'] ?? '');
    $variant = (string) ($data['variant'] ?? 'success');
    $heroAccent = (string) ($data['hero_accent'] ?? '');
    $heroTitle = (string) ($data['hero_title'] ?? 'AI Trading');
    $kind = (string) ($data['kind'] ?? 'toggle');
    $greeting = (string) ($data['greeting'] ?? 'Hi there');
    $intro = (string) ($data['intro'] ?? '');
    $detailsHeading = (string) ($data['details_heading'] ?? 'Client Details');
    $detailsRows = is_array($data['details_rows'] ?? null) ? $data['details_rows'] : [];
    $summary = (string) ($data['summary'] ?? '');
    $ctaText = (string) ($data['cta_text'] ?? 'View Details');
    $ctaUrl = (string) ($data['cta_url'] ?? config('app.url'));
    $footerNote = (string) ($data['footer_note'] ?? "Trade on the go with {$siteName}");
    $accentColor = $variant === 'warning' ? '#c3b221' : '#16b66a';
    $statusPillBackground = $variant === 'warning' ? '#f8f3d7' : '#dff6eb';
    $statusPillColor = $variant === 'warning' ? '#a68708' : '#1b8f57';
    $performanceHeading = (string) ($data['performance_heading'] ?? "Today's performance AI");
    $performanceValue = (string) ($data['performance_value'] ?? '$0.00');
    $performanceValueColor = (string) ($data['performance_value_color'] ?? '#111827');
    $performanceDelta = (string) ($data['performance_delta'] ?? '');
    $performanceDeltaColor = (string) ($data['performance_delta_color'] ?? '#16b66a');
    $performanceBadge = (string) ($data['performance_badge'] ?? 'Positive day');
    $performanceBadgeBackground = (string) ($data['performance_badge_background'] ?? '#e6f8ef');
    $performanceBadgeColor = (string) ($data['performance_badge_color'] ?? '#16a765');
    $statsCards = is_array($data['stats_cards'] ?? null) ? $data['stats_cards'] : [];
    $insightTitle = (string) ($data['insight_title'] ?? 'AI Insight');
    $insightBody = (string) ($data['insight_body'] ?? '');
    $transactionsHeading = (string) ($data['transactions_heading'] ?? 'Transactions');
    $transactions = is_array($data['transactions'] ?? null) ? $data['transactions'] : [];
    $recommendationHeading = (string) ($data['recommendation_heading'] ?? 'AI Recommendation');
    $recommendationSource = (string) ($data['recommendation_source'] ?? 'AI Trading Assistant');
    $recommendationBody = (string) ($data['recommendation_body'] ?? $summary);
    $mailMessage = $message;

    $embedAsset = function (string $relativePath) use ($mailMessage): string {
        $absolutePath = public_path($relativePath);

        if ($mailMessage && method_exists($mailMessage, 'embed') && file_exists($absolutePath)) {
            return $mailMessage->embed($absolutePath);
        }

        return asset($relativePath);
    };

    $bannerImage = $embedAsset('default/header/banner.png');
    $footerIcons = collect(range(1, 8))
        ->map(fn (int $index) => $embedAsset("default/footer/{$index}.png"))
        ->all();
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $data['title'] ?? $siteName }}</title>
</head>
<body style="margin:0;padding:0;background-color:#f7f3f8;font-family:Arial,sans-serif;color:#1f2937;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f7f3f8;margin:0;padding:0;width:100%;">
    <tr>
        <td align="center" style="padding:28px 12px;">
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:640px;background-color:#ffffff;border-radius:0;overflow:hidden;">
                <tr>
                    <td style="padding:24px 26px 14px;">
                        <table role="presentation" cellpadding="0" cellspacing="0" width="100%">
                            <tr>
                                <td align="left">
                                    <table role="presentation" cellpadding="0" cellspacing="0">
                                        <tr>
                                            <td style="vertical-align:middle;">
                                                <img src="{{ $logoUrl }}" alt="{{ $siteName }}" style="display:block;max-height:32px;max-width:168px;width:auto;">
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
                <tr>
                    <td style="padding:0 18px 0;">
                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-radius:18px;overflow:hidden;background-color:#16214c;">
                            <tr>
                                <td style="padding:0;position:relative;">
                                    <div style="position:relative;">
                                        <img src="{{ $bannerImage }}" alt="{{ $heroTitle }} {{ $heroAccent }}" style="display:block;width:100%;height:auto;">
                                        <div style="position:absolute;left:24px;top:26px;color:#ffffff;letter-spacing:-0.03em;font-family:Arial,sans-serif;">
                                            <div style="font-size:23px;line-height:1.02;font-weight:700;color:#ffffff;margin:0 0 4px;">
                                                {{ $heroTitle }}
                                            </div>
                                            <div style="font-size:25px;line-height:1.02;font-weight:700;color:{{ $accentColor }};margin:0;">
                                                {{ $heroAccent }}
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
                <tr>
                    <td style="padding:24px 36px 0;font-size:17px;line-height:1.8;color:#374151;">
                        <div style="margin:0 0 12px;font-size:17px;color:#374151;">{{ $greeting }}</div>
                        @if($intro !== '')
                            <div style="margin:0;color:#374151;">{{ $intro }}</div>
                        @endif
                    </td>
                </tr>
                @if($kind === 'daily_summary')
                    <tr>
                        <td style="padding:22px 36px 0;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#ffffff;border:1px solid #eef0f5;border-radius:18px;box-shadow:0 2px 10px rgba(16,24,40,0.03);">
                                <tr>
                                    <td style="padding:20px 22px;">
                                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                            <tr>
                                                <td style="font-size:15px;font-weight:600;color:#374151;padding-bottom:12px;">
                                                    {{ $performanceHeading }}
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>
                                                    <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                                        <tr>
                                                            <td style="width:58%;vertical-align:middle;">
                                                                <div style="font-size:32px;line-height:1;font-weight:700;color:{{ $performanceValueColor }};letter-spacing:-0.03em;">
                                                                    {{ $performanceValue }}
                                                                </div>
                                                                @if($performanceDelta !== '')
                                                                    <div style="font-size:14px;line-height:1.4;color:{{ $performanceDeltaColor }};margin-top:10px;">
                                                                        {{ $performanceDelta }}
                                                                    </div>
                                                                @endif
                                                            </td>
                                                            <td align="right" style="width:42%;vertical-align:middle;">
                                                                <span style="display:inline-block;padding:12px 18px;border-radius:999px;background-color:{{ $performanceBadgeBackground }};color:{{ $performanceBadgeColor }};font-size:16px;font-weight:600;">
                                                                    {{ $performanceBadge }}
                                                                </span>
                                                            </td>
                                                        </tr>
                                                    </table>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    @if(count($statsCards))
                        <tr>
                            <td style="padding:14px 36px 0;">
                                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border:1px solid #eef0f5;border-radius:14px;background-color:#ffffff;overflow:hidden;">
                                    <tr>
                                        @foreach($statsCards as $index => $card)
                                            <td align="center" style="padding:16px 10px;{{ $index + 1 < count($statsCards) ? 'border-right:1px solid #eef0f5;' : '' }}">
                                                <div style="font-size:24px;line-height:1;font-weight:700;color:#111827;letter-spacing:-0.03em;margin-bottom:8px;">
                                                    {{ $card['value'] ?? '--' }}
                                                </div>
                                                <div style="font-size:12px;line-height:1.4;color:#6b7280;">
                                                    {{ $card['label'] ?? '' }}
                                                </div>
                                            </td>
                                        @endforeach
                                    </tr>
                                </table>
                            </td>
                        </tr>
                    @endif
                    <tr>
                        <td style="padding:14px 36px 0;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#ffffff;border:1px solid #eef0f5;border-radius:18px;">
                                <tr>
                                    <td style="padding:18px 20px;">
                                        <div style="font-size:18px;font-weight:700;color:#16b66a;margin-bottom:10px;">
                                            ✦ {{ $insightTitle }}
                                        </div>
                                        <div style="font-size:16px;line-height:1.7;color:#374151;">
                                            {{ $insightBody }}
                                        </div>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:14px 36px 0;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#ffffff;border:1px solid #eef0f5;border-radius:18px;">
                                <tr>
                                    <td style="padding:20px;">
                                        <div style="font-size:15px;font-weight:700;color:#111827;margin-bottom:12px;">{{ $transactionsHeading }}</div>
                                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                            <tr>
                                                <td style="padding:0 0 10px;font-size:13px;font-weight:600;color:#6b7280;">Pair</td>
                                                <td style="padding:0 0 10px;font-size:13px;font-weight:600;color:#6b7280;">Type</td>
                                                <td style="padding:0 0 10px;font-size:13px;font-weight:600;color:#6b7280;">Entry price</td>
                                                <td align="right" style="padding:0 0 10px;font-size:13px;font-weight:600;color:#6b7280;">P/l</td>
                                            </tr>
                                            @forelse($transactions as $transaction)
                                                <tr>
                                                    <td style="padding:12px 0;font-size:15px;color:#111827;border-top:1px solid #f1f3f8;">
                                                        {{ $transaction['pair'] ?? 'N/A' }}
                                                    </td>
                                                    <td style="padding:12px 0;font-size:15px;color:{{ $transaction['type_color'] ?? '#111827' }};border-top:1px solid #f1f3f8;">
                                                        {{ $transaction['type'] ?? 'Buy' }}
                                                    </td>
                                                    <td style="padding:12px 0;font-size:15px;color:#111827;border-top:1px solid #f1f3f8;">
                                                        {{ $transaction['entry_price'] ?? '0.00' }}
                                                    </td>
                                                    <td align="right" style="padding:12px 0;font-size:15px;color:{{ $transaction['pnl_color'] ?? '#16b66a' }};border-top:1px solid #f1f3f8;">
                                                        {{ $transaction['pnl'] ?? '0.00' }}
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="4" style="padding:18px 0;font-size:15px;color:#6b7280;border-top:1px solid #f1f3f8;">
                                                        No AI trades were recorded for this summary period.
                                                    </td>
                                                </tr>
                                            @endforelse
                                        </table>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                @elseif($kind === 'recommendation')
                    <tr>
                        <td style="padding:22px 36px 0;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#ffffff;border:1px solid #eef0f5;border-radius:18px;box-shadow:0 2px 10px rgba(16,24,40,0.03);">
                                <tr>
                                    <td style="padding:22px;">
                                        <div style="font-size:13px;font-weight:700;letter-spacing:0.08em;text-transform:uppercase;color:#16b66a;margin-bottom:10px;">
                                            {{ $recommendationSource }}
                                        </div>
                                        <div style="font-size:22px;line-height:1.25;font-weight:700;color:#111827;margin-bottom:14px;">
                                            {{ $recommendationHeading }}
                                        </div>
                                        <div style="font-size:16px;line-height:1.8;color:#374151;">
                                            {!! nl2br(e($recommendationBody)) !!}
                                        </div>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                @else
                    <tr>
                        <td style="padding:22px 36px 0;">
                            <div style="font-size:15px;font-weight:700;color:#111827;margin-bottom:14px;">{{ $detailsHeading }}</div>
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#fcfbff;border:1px solid #eef0f5;border-radius:18px;">
                                @foreach($detailsRows as $index => $row)
                                    @php
                                        $valueColor = !empty($row['accent']) ? $statusPillColor : '#111827';
                                    @endphp
                                    <tr>
                                        <td style="padding:18px 20px;font-size:13px;color:#6b7280;{{ $index + 1 < count($detailsRows) ? 'border-bottom:1px solid #eceff5;' : '' }}">
                                            {{ $row['label'] ?? '' }}
                                        </td>
                                        <td align="right" style="padding:18px 20px;font-size:16px;font-weight:600;color:{{ $valueColor }};{{ $index + 1 < count($detailsRows) ? 'border-bottom:1px solid #eceff5;' : '' }}">
                                            @if(!empty($row['accent']))
                                                <span style="display:inline-block;padding:9px 18px;border-radius:999px;background-color:{{ $statusPillBackground }};color:{{ $statusPillColor }};font-size:14px;font-weight:600;">
                                                    {{ $row['value'] ?? '' }}
                                                </span>
                                            @else
                                                {{ $row['value'] ?? '' }}
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:24px 36px 0;font-size:16px;line-height:1.7;color:#374151;">
                            {{ $summary }}
                        </td>
                    </tr>
                @endif
                <tr>
                    <td style="padding:24px 36px 0;">
                        <a href="{{ $ctaUrl }}" style="display:block;text-align:center;background-color:#17b267;color:#ffffff;text-decoration:none;padding:16px 24px;border-radius:999px;font-size:16px;font-weight:600;">
                            {{ $ctaText }} &nbsp; →
                        </a>
                    </td>
                </tr>
                <tr>
                    <td style="padding:28px 36px 34px;">
                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#fbf9fe;border-radius:0;border:1px solid #f0edf7;">
                            <tr>
                                <td style="padding:24px;">
                                    <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                        <tr>
                                            <td style="width:74px;vertical-align:top;">
                                                <div style="width:60px;height:60px;border-radius:50%;background-color:#eefbf4;text-align:center;line-height:60px;font-size:28px;color:#16b66a;font-weight:700;">
                                                    ☏
                                                </div>
                                            </td>
                                            <td style="vertical-align:top;">
                                                <div style="font-size:16px;font-weight:700;color:#111827;margin-bottom:8px;">Need Help getting started?</div>
                                                <div style="font-size:15px;line-height:1.7;color:#6b7280;">Our team is online and is ready to walk you through your first deposit</div>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td colspan="2" style="padding-top:22px;">
                                                <a href="{{ $supportUrl }}" style="display:block;text-align:center;text-decoration:none;padding:14px 20px;border-radius:999px;border:1px solid #b7e7cf;color:#0f9a57;font-size:15px;font-weight:600;">
                                                    Contact Support
                                                </a>
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
                <tr>
                    <td style="background-color:#eefaf5;padding:26px 24px 32px;text-align:center;">
                        <div style="font-size:24px;font-weight:700;color:#17a161;margin-bottom:12px;">{{ $siteName }}</div>
                        <div style="font-size:14px;line-height:1.7;color:#5b6472;max-width:420px;margin:0 auto 18px;">Never miss a beat on our latest product updates and events</div>
                        <table role="presentation" cellpadding="0" cellspacing="0" style="margin:0 auto 16px;">
                            <tr>
                                @foreach($footerIcons as $icon)
                                    <td style="padding:0 4px;">
                                        <img src="{{ $icon }}" alt="" style="display:block;width:24px;height:24px;">
                                    </td>
                                @endforeach
                            </tr>
                        </table>
                        <div style="font-size:16px;line-height:1.7;color:#374151;margin-bottom:10px;">{!! nl2br(e($footerNote)) !!}</div>
                        <div style="font-size:14px;color:#6b7280;margin-bottom:8px;">Latest promotions</div>
                        @if($supportEmail !== '')
                            <div style="font-size:14px;color:#17a161;margin-bottom:8px;">{{ $supportEmail }}</div>
                        @endif
                        <div style="font-size:13px;color:#6b7280;">Copyright &copy; 2018 - {{ date('Y') }} {{ $siteName }}. All rights reserved</div>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>

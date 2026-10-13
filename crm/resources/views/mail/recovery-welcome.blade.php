<x-mail.basic-layout :message="$message" :title="$title ?? 'Welcome to Funds Recovery'">
    <table border="0" cellpadding="0" cellspacing="0" width="100%">
        <tr>
            <td style="padding: 10px 20px;">
                <p style="margin:0 0 12px;font-size:16px;color:#323232;">
                    Hi {{ $customerName ?? ($user->first_name ?? 'there') }},
                </p>
                <div style="font-size:15px;line-height:1.6;color:#323232;margin-bottom:24px;">
                    {!! $content ?? ($data['message'] ?? '') !!}
                </div>
                @if (!empty($data['casesUrl'] ?? null))
                    <p style="margin:0 0 8px;">
                        <a href="{{ $data['casesUrl'] }}" style="display:inline-block;background-color:#2563EB;color:#ffffff;text-decoration:none;padding:12px 24px;border-radius:8px;font-size:14px;font-weight:600;">
                            View your recovery case
                        </a>
                    </p>
                @endif
                @if (!empty($data['case']?->case_number))
                    <p style="margin:16px 0 0;font-size:13px;color:#6B7280;">
                        Case reference: <strong>{{ $data['case']->case_number }}</strong>
                    </p>
                @endif
            </td>
        </tr>
    </table>
</x-mail.basic-layout>

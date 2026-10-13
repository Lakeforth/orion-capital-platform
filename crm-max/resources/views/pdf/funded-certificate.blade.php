<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Certificate of Funding</title>
    <style>
        @page {
            margin: 0;
        }

        body {
            color: #0f172a;
            font-family: DejaVu Sans, sans-serif;
            margin: 0;
            padding: 0;
        }

        .frame {
            border: 3px solid #0f172a;
            margin: 24px;
            padding: 48px 56px;
        }

        .frame-inner {
            border: 1px solid #cbd5e1;
            padding: 40px 48px;
            text-align: center;
        }

        .site-name {
            color: #64748b;
            font-size: 13px;
            font-weight: 700;
            letter-spacing: 2px;
            text-transform: uppercase;
        }

        .title {
            color: #0f172a;
            font-size: 30px;
            font-weight: 700;
            letter-spacing: 3px;
            margin: 18px 0 4px;
            text-transform: uppercase;
        }

        .subtitle {
            color: #475569;
            font-size: 12px;
            margin: 0 0 28px;
        }

        .presented-to {
            color: #94a3b8;
            font-size: 11px;
            letter-spacing: 1px;
            text-transform: uppercase;
        }

        .trader-name {
            color: #0f172a;
            font-size: 34px;
            font-weight: 700;
            margin: 8px 0 24px;
        }

        .body-text {
            color: #334155;
            font-size: 13px;
            line-height: 1.7;
            margin: 0 auto 28px;
            max-width: 480px;
        }

        .plan-highlight {
            color: #16a34a;
            font-weight: 700;
        }

        .details {
            border-collapse: collapse;
            margin: 0 auto 32px;
            width: 420px;
        }

        .details td {
            border-top: 1px solid #e2e8f0;
            font-size: 11px;
            padding: 10px 4px;
        }

        .details td.label {
            color: #64748b;
            text-align: left;
            text-transform: uppercase;
        }

        .details td.value {
            color: #0f172a;
            font-weight: 700;
            text-align: right;
        }

        .footer {
            color: #94a3b8;
            font-size: 10px;
            margin-top: 8px;
        }
    </style>
</head>
<body>
<div class="frame">
    <div class="frame-inner">
        <p class="site-name">{{ $siteName }}</p>
        <p class="title">Certificate of Funding</p>
        <p class="subtitle">Prop Trading Challenge Program</p>

        <p class="presented-to">This certifies that</p>
        <p class="trader-name">{{ $traderName }}</p>

        <p class="body-text">
            has successfully completed all evaluation phases of the
            <span class="plan-highlight">{{ $planTitle }}</span> challenge and has been awarded
            a live funded trading account in the amount of <strong>{{ $accountSize }}</strong>.
        </p>

        <table class="details">
            <tr>
                <td class="label">Certificate No.</td>
                <td class="value">{{ $certificateNumber }}</td>
            </tr>
            <tr>
                <td class="label">Date Funded</td>
                <td class="value">{{ $fundedDate }}</td>
            </tr>
            <tr>
                <td class="label">Profit Split</td>
                <td class="value">{{ $profitSplit }}</td>
            </tr>
        </table>

        <p class="footer">Issued by {{ $siteName }} on {{ $fundedDate }} &middot; Certificate {{ $certificateNumber }}</p>
    </div>
</div>
</body>
</html>

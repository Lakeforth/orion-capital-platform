<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Account Statement</title>
    <style>
        @page {
            margin: 24px 24px 36px;
        }

        body {
            color: #0f172a;
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            line-height: 1.45;
        }

        .header {
            background: #0f172a;
            border-radius: 10px;
            color: #ffffff;
            padding: 20px 24px;
        }

        .header-title {
            font-size: 24px;
            font-weight: 700;
            margin: 0 0 4px;
        }

        .header-subtitle {
            color: #cbd5e1;
            font-size: 11px;
            margin: 0;
        }

        .meta {
            margin-top: 18px;
            width: 100%;
        }

        .meta td {
            vertical-align: top;
            width: 50%;
        }

        .meta-card {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 14px 16px;
        }

        .meta-label {
            color: #64748b;
            font-size: 10px;
            margin-bottom: 4px;
            text-transform: uppercase;
        }

        .meta-value {
            color: #0f172a;
            font-size: 12px;
            font-weight: 600;
        }

        .section {
            margin-top: 22px;
        }

        .section-title {
            border-left: 4px solid #0f766e;
            color: #0f172a;
            font-size: 14px;
            font-weight: 700;
            margin: 0 0 12px;
            padding-left: 10px;
        }

        .summary-grid {
            width: 100%;
        }

        .summary-grid td {
            padding: 0 8px 10px 0;
            vertical-align: top;
            width: 25%;
        }

        .summary-card {
            background: #ffffff;
            border: 1px solid #dbe4ee;
            border-radius: 10px;
            min-height: 72px;
            padding: 12px;
        }

        .summary-card .label {
            color: #64748b;
            font-size: 10px;
            margin-bottom: 6px;
            text-transform: uppercase;
        }

        .summary-card .value {
            color: #0f172a;
            font-size: 15px;
            font-weight: 700;
        }

        .table {
            border-collapse: collapse;
            width: 100%;
        }

        .table thead th {
            background: #e2e8f0;
            border: 1px solid #cbd5e1;
            color: #334155;
            font-size: 10px;
            padding: 8px 6px;
            text-align: left;
            text-transform: uppercase;
        }

        .table tbody td {
            border: 1px solid #e2e8f0;
            padding: 7px 6px;
            vertical-align: top;
        }

        .muted {
            color: #64748b;
        }

        .align-right {
            text-align: right;
        }

        .badge {
            border-radius: 999px;
            display: inline-block;
            font-size: 10px;
            font-weight: 700;
            padding: 3px 8px;
        }

        .badge-open,
        .badge-approved,
        .badge-deposit {
            background: #dcfce7;
            color: #166534;
        }

        .badge-closed,
        .badge-withdrawal {
            background: #dbeafe;
            color: #1d4ed8;
        }

        .badge-pending {
            background: #fef3c7;
            color: #92400e;
        }

        .badge-rejected,
        .negative {
            background: #fee2e2;
            color: #b91c1c;
        }

        .footer {
            color: #94a3b8;
            font-size: 10px;
            margin-top: 16px;
            text-align: center;
        }

        .empty-state {
            background: #f8fafc;
            border: 1px dashed #cbd5e1;
            border-radius: 10px;
            color: #64748b;
            padding: 18px;
            text-align: center;
        }
    </style>
</head>
<body>
@php
    $summary = $statement['summary'];
    $period = $statement['period'];
    $customer = $statement['customer'];

    $money = fn (float $amount) => currency() . number_format($amount, 2);
    $price = fn (float $amount) => number_format($amount, 4);
@endphp

<div class="header">
    <div class="header-title">Account Statement</div>
    <p class="header-subtitle">
        {{ $statement['company_name'] }} |
        Reporting Period: {{ $period['label'] }}
    </p>
</div>

<table class="meta" cellspacing="0" cellpadding="0">
    <tr>
        <td style="padding-right: 8px;">
            <div class="meta-card">
                <div class="meta-label">Account Holder</div>
                <div class="meta-value">{{ $customer['name'] }}</div>
                <div class="muted">{{ $customer['email'] }}</div>
                <div class="muted">Account ID: {{ $customer['account_id'] }}</div>
                @if(!empty($customer['plan']))
                    <div class="muted">Plan: {{ $customer['plan'] }}</div>
                @endif
            </div>
        </td>
        <td style="padding-left: 8px;">
            <div class="meta-card">
                <div class="meta-label">Report Details</div>
                <div class="meta-value">{{ $period['start']->format('M d, Y') }} - {{ $period['end']->format('M d, Y') }}</div>
                <div class="muted">Generated: {{ $statement['generated_at']->format('M d, Y H:i') }}</div>
                @if(!empty($statement['company_email']))
                    <div class="muted">Support: {{ $statement['company_email'] }}</div>
                @endif
                @if(!empty($statement['company_website']))
                    <div class="muted">Website: {{ $statement['company_website'] }}</div>
                @endif
            </div>
        </td>
    </tr>
</table>

<div class="section">
    <h2 class="section-title">Account Summary</h2>

    <table class="summary-grid" cellspacing="0" cellpadding="0">
        <tr>
            <td><div class="summary-card"><div class="label">Total Balance</div><div class="value">{{ $money($summary['total_balance']) }}</div></div></td>
            <td><div class="summary-card"><div class="label">Equity</div><div class="value">{{ $money($summary['equity']) }}</div></div></td>
            <td><div class="summary-card"><div class="label">Margin</div><div class="value">{{ $money($summary['margin']) }}</div></div></td>
            <td><div class="summary-card"><div class="label">Free Margin</div><div class="value">{{ $money($summary['free_margin']) }}</div></div></td>
        </tr>
        <tr>
            <td><div class="summary-card"><div class="label">Main Balance</div><div class="value">{{ $money($summary['balance']) }}</div></div></td>
            <td><div class="summary-card"><div class="label">Credit Wallet</div><div class="value">{{ $money($summary['credit_balance']) }}</div></div></td>
            <td><div class="summary-card"><div class="label">Bonus Wallet</div><div class="value">{{ $money($summary['bonus_balance']) }}</div></div></td>
            <td><div class="summary-card"><div class="label">Floating P/L</div><div class="value {{ $summary['floating_pnl'] < 0 ? 'negative' : '' }}">{{ $money($summary['floating_pnl']) }}</div></div></td>
        </tr>
        <tr>
            <td><div class="summary-card"><div class="label">Trades in Period</div><div class="value">{{ $summary['period_trades'] }}</div></div></td>
            <td><div class="summary-card"><div class="label">Closed Trades</div><div class="value">{{ $summary['period_closed_trades'] }}</div></div></td>
            <td><div class="summary-card"><div class="label">Open Trades</div><div class="value">{{ $summary['period_open_trades'] }}</div></div></td>
            <td><div class="summary-card"><div class="label">Realized P/L</div><div class="value {{ $summary['period_realized_pnl'] < 0 ? 'negative' : '' }}">{{ $money($summary['period_realized_pnl']) }}</div></div></td>
        </tr>
        <tr>
            <td><div class="summary-card"><div class="label">Trade Volume</div><div class="value">{{ number_format($summary['period_trade_volume'], 2) }}</div></div></td>
            <td><div class="summary-card"><div class="label">Margin Used</div><div class="value">{{ $money($summary['period_margin_used']) }}</div></div></td>
            <td><div class="summary-card"><div class="label">Deposits</div><div class="value">{{ $money($summary['period_deposits']) }}</div></div></td>
            <td><div class="summary-card"><div class="label">Withdrawals</div><div class="value">{{ $money($summary['period_withdrawals']) }}</div></div></td>
        </tr>
    </table>
</div>

<div class="section">
    <h2 class="section-title">Trade History</h2>

    @if($statement['trades']->isEmpty())
        <div class="empty-state">No trades were recorded for the selected period.</div>
    @else
        <table class="table">
            <thead>
            <tr>
                <th>Trade Ref</th>
                <th>Instrument</th>
                <th>Side</th>
                <th>Status</th>
                <th>Opened</th>
                <th>Closed</th>
                <th class="align-right">Amount</th>
                <th class="align-right">Volume</th>
                <th class="align-right">Margin</th>
                <th class="align-right">Leverage</th>
                <th class="align-right">Open Price</th>
                <th class="align-right">Close Price</th>
                <th class="align-right">P/L</th>
            </tr>
            </thead>
            <tbody>
            @foreach($statement['trades'] as $trade)
                <tr>
                    <td>{{ $trade['reference'] }}</td>
                    <td>
                        <strong>{{ $trade['instrument'] }}</strong><br>
                        <span class="muted">{{ $trade['symbol'] }}</span>
                    </td>
                    <td>{{ $trade['side'] }}</td>
                    <td>
                        <span class="badge {{ $trade['status'] === 'Closed' ? 'badge-closed' : 'badge-open' }}">
                            {{ $trade['status'] }}
                        </span>
                    </td>
                    <td>{{ $trade['opened_at'] }}</td>
                    <td>{{ $trade['closed_at'] }}</td>
                    <td class="align-right">{{ $money($trade['amount']) }}</td>
                    <td class="align-right">{{ number_format($trade['volume'], 2) }}</td>
                    <td class="align-right">{{ $money($trade['margin']) }}</td>
                    <td class="align-right">{{ $trade['leverage'] }}x</td>
                    <td class="align-right">{{ $price($trade['opening_price']) }}</td>
                    <td class="align-right">{{ $price($trade['closing_price']) }}</td>
                    <td class="align-right {{ $trade['pnl'] < 0 ? 'negative' : '' }}">{{ $money($trade['pnl']) }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @endif
</div>

<div class="section">
    <h2 class="section-title">Cash Activity</h2>

    @if($statement['transactions']->isEmpty())
        <div class="empty-state">No deposits or withdrawals were recorded for the selected period.</div>
    @else
        <table class="table">
            <thead>
            <tr>
                <th>Reference</th>
                <th>Date</th>
                <th>Type</th>
                <th>Method</th>
                <th>Account</th>
                <th>Status</th>
                <th class="align-right">Amount</th>
            </tr>
            </thead>
            <tbody>
            @foreach($statement['transactions'] as $transaction)
                <tr>
                    <td>{{ $transaction['reference'] }}</td>
                    <td>{{ $transaction['date'] }}</td>
                    <td>
                        <span class="badge {{ $transaction['type'] === 'Deposit' ? 'badge-deposit' : 'badge-withdrawal' }}">
                            {{ $transaction['type'] }}
                        </span>
                    </td>
                    <td>{{ $transaction['method'] }}</td>
                    <td>{{ $transaction['account'] }}</td>
                    <td>
                        @php
                            $statusClass = match (strtolower($transaction['status'])) {
                                'approved', 'completed', 'success' => 'badge-approved',
                                'rejected', 'declined', 'failed', 'cancelled' => 'badge-rejected',
                                default => 'badge-pending',
                            };
                        @endphp
                        <span class="badge {{ $statusClass }}">{{ $transaction['status'] }}</span>
                    </td>
                    <td class="align-right">{{ $money($transaction['amount']) }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @endif
</div>

<div class="footer">
    Generated by {{ $statement['company_name'] }} for record keeping, compliance, and personal account tracking.
</div>
</body>
</html>

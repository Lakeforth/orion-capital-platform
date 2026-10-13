@php
    $label = $document->isCreditNote() ? 'Credit note' : 'Invoice';
    $money = fn ($value) => $document->currency.' '.number_format((float) $value, 2);
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>{{ $label }} {{ $document->number }}</title>
{{-- Standalone printable document (opened in a new tab, outside the Splade shell). --}}
<style>
    body{margin:0;padding:40px 16px;background:#f1f5f9;color:#0f172a;font:14px/1.5 Inter,Arial,sans-serif}
    .sheet{max-width:760px;margin:0 auto;padding:40px;background:#fff;border:1px solid #e2e8f0;border-radius:8px}
    header{display:flex;justify-content:space-between;gap:24px;flex-wrap:wrap;margin-bottom:32px}
    h1{margin:0;font-size:24px}.muted{color:#64748b}.void{color:#dc2626;font-weight:700}
    table{width:100%;border-collapse:collapse;margin:24px 0}th,td{padding:10px 8px;border-bottom:1px solid #e2e8f0;text-align:left}th{color:#64748b;font-weight:500}
    .num{text-align:right;white-space:nowrap}.total td{font-weight:700;border-bottom:0}
    .actions{max-width:760px;margin:0 auto 16px;text-align:right}.actions button{padding:8px 16px;border:0;border-radius:4px;background:#2563eb;color:#fff;cursor:pointer}
    @media print{body{padding:0;background:#fff}.sheet{border:0}.actions{display:none}}
</style>
</head>
<body>
<div class="actions"><button type="button" onclick="window.print()">Print / Save as PDF</button></div>
<main class="sheet">
    <header>
        <div><h1>{{ $label }}</h1><div class="muted">{{ $document->number }}</div>@if($document->status === 'void')<div class="void">VOID</div>@endif</div>
        <div><strong>{{ company_settings('site_name') ?: app('company')->name }}</strong><div class="muted">Issued {{ $document->issue_date->format('Y-m-d') }}</div>@if($document->due_date)<div class="muted">Due {{ $document->due_date->format('Y-m-d') }}</div>@endif @if($document->invoice)<div class="muted">Against {{ $document->invoice->number }}</div>@endif</div>
    </header>
    <div><div class="muted">Billed to</div><strong>{{ $lead->name }}</strong>@if($lead->account_id)<div class="muted">Client ID {{ $lead->account_id }}</div>@endif @unless(str_ends_with((string) $lead->email, '.invalid'))<div class="muted">{{ $lead->email }}</div>@endunless @if($lead->country)<div class="muted">{{ $lead->country }}</div>@endif</div>
    <table>
        <thead><tr><th>Description</th><th class="num">Qty</th><th class="num">Unit price</th><th class="num">Amount</th></tr></thead>
        <tbody>
        @foreach($document->items as $item)<tr><td>{{ $item['description'] }}</td><td class="num">{{ rtrim(rtrim(number_format($item['quantity'], 2), '0'), '.') }}</td><td class="num">{{ $money($item['unit_price']) }}</td><td class="num">{{ $money($item['quantity'] * $item['unit_price']) }}</td></tr>@endforeach
        <tr class="total"><td colspan="3" class="num">Total</td><td class="num">{{ $money($document->amount) }}</td></tr>
        @if(! $document->isCreditNote() && $document->creditNotes->isNotEmpty())<tr><td colspan="3" class="num muted">Credited</td><td class="num muted">−{{ $money($document->creditNotes->sum('amount')) }}</td></tr><tr class="total"><td colspan="3" class="num">Balance due</td><td class="num">{{ $money($document->status === 'paid' ? 0 : $document->balanceDue()) }}</td></tr>@endif
        </tbody>
    </table>
    <p class="muted">Status: {{ ucfirst($document->status) }}{{ $document->paid_at ? ' on '.$document->paid_at->format('Y-m-d') : '' }}</p>
    @if($document->notes)<p>{!! nl2br(e($document->notes)) !!}</p>@endif
</main>
</body>
</html>

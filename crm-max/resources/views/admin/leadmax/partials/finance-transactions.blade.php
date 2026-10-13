{{-- Finances > All Transactions / Deposits / Withdrawals / Adjustments tables (Figma 3294:25532, 3297:26245, 3300:29563, 3301:30532). --}}
@php
    use App\Http\Controllers\Admin\LeadMaxFinanceController as Finance;
    $fi = fn (string $name, $w, $h = null) => '<img src="'.asset('images/leadmax/finance/'.$name.'.svg').'" width="'.$w.'" height="'.($h ?? $w).'" alt="">';
    $palette = ['#2563eb', '#db2777', '#7c3aed', '#0891b2', '#16a34a', '#ea580c', '#0d9488', '#9333ea', '#dc2626', '#65a30d'];
    $initials = fn ($u) => $u ? strtoupper(mb_substr((string) $u->first_name, 0, 1).mb_substr((string) $u->last_name, 0, 1)) : '?';
    $color = fn ($u) => $palette[$u ? (crc32((string) $u->id) % count($palette)) : 0];
    $shortName = fn ($u) => $u ? trim($u->first_name.' '.($u->last_name ? mb_substr($u->last_name, 0, 1).'.' : '')) : 'Unknown';
    $statusLabel = fn ($s) => ['approved' => 'Completed', 'declined' => 'Rejected'][$s] ?? ucfirst((string) $s);
    $statusClass = fn ($s) => ['approved' => 'is-completed', 'declined' => 'is-rejected'][$s] ?? 'is-'.$s;
    $adjust = $tab === 'adjustments';
    $showType = in_array($tab, ['all', 'withdrawals', 'adjustments'], true);
@endphp
<div class="lm-fin-table-wrap" data-lm-bulk-page data-lm-bulk-noun="transaction">
    <table class="lm-fin-table">
        <thead><tr>
            <th><input type="checkbox" aria-label="Select all" data-lm-select-all></th><th>Transaction ID</th><th>User</th>
            @if($showType)<th>Type</th>@endif
            <th>Amount</th>
            <th>{{ $adjust ? 'Reason' : 'Payment Method' }}</th><th>Status</th><th>Date</th><th>Reference</th><th>Actions</th>
        </tr></thead>
        <tbody>
            @forelse($rows as $t)
                @php $kind = Finance::kind($t); $in = in_array($kind, ['deposit', 'credit'], true); $leadUrl = $t->user ? route('admin.leadmax.leads.show', [$t->user_id, 'tab' => 'finances', 'from' => 'finances']) : null; @endphp
                <tr>
                    <td><input type="checkbox" aria-label="Select {{ Finance::txId($t) }}" data-lm-select></td>
                    <td class="lm-fin-mono">{{ Finance::txId($t) }}</td>
                    <td><span class="lm-fin-user"><i style="--c:{{ $color($t->user) }}">{{ $initials($t->user) }}</i>{{ $shortName($t->user) }}</span></td>
                    @if($showType)
                        <td>
                            @if($adjust)<em class="lm-fin-badge {{ $in ? 'is-credit' : 'is-debit' }}">{{ $in ? 'Credit' : 'Debit' }}</em>
                            @elseif($tab === 'withdrawals')<em class="lm-fin-badge is-pending">Withdrawal</em>
                            @else<em class="lm-fin-badge is-{{ in_array($kind, ['credit', 'debit'], true) ? 'adjustment' : $kind }}">{{ Finance::typeLabel($t) }}</em>@endif
                        </td>
                    @endif
                    <td class="lm-fin-amount {{ $adjust ? ($in ? 'is-plus' : 'is-minus') : '' }}">
                        {{ $adjust ? ($in ? '+' : '-') : '' }}${{ number_format((float) $t->amount, (float) $t->amount == floor((float) $t->amount) ? 0 : 2) }}
                        @if($tab === 'deposits')<em class="lm-fin-badge is-deposit" style="margin-left:6px;padding:0 4px;font-size:8px">DEP</em>@endif
                    </td>
                    <td>{{ $adjust ? Finance::reason($t) : Finance::methodLabel($t) }}</td>
                    <td><em class="lm-fin-badge {{ $statusClass($t->status) }}">{{ $statusLabel($t->status) }}</em></td>
                    <td class="lm-fin-mono is-muted">{{ $t->created_at?->format('Y-m-d H:i:s') }}</td>
                    <td class="lm-fin-mono is-muted" title="{{ Finance::reference($t) }}">{{ \Illuminate\Support\Str::limit(Finance::reference($t), 10) ?: '—' }}</td>
                    <td>
                        <span class="lm-fin-row-actions">
                            @if($leadUrl)<a href="{{ $leadUrl }}" title="View on the lead's finances" aria-label="View {{ Finance::txId($t) }}">{!! $fi('eye', 14) !!}</a>@endif
                            <details class="lm-fin-menu"><summary aria-label="More actions for {{ Finance::txId($t) }}">{!! $fi('row-more', 14) !!}</summary><div>
                                @if($leadUrl)<a href="{{ $leadUrl }}">Open lead finances</a>@endif
                                @if(is_admin() && $t->status === 'pending')<a href="{{ route('admin.all_'.($in ? 'deposits' : 'withdrawals')) }}">Review in back office</a>@endif
                                <button type="button" data-lm-copy-text="{{ Finance::txId($t) }}">Copy transaction ID</button>
                            </div></details>
                        </span>
                    </td>
                </tr>
            @empty
                <tr><td colspan="10" class="lm-fin-empty">No {{ $tab === 'all' ? 'transactions' : $tab }} match these filters.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

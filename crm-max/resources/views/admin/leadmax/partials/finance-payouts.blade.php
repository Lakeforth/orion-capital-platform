{{-- Finances > Payouts (Figma 3317:34133): prop-firm challenge payouts of visible leads. --}}
@php
    use App\Http\Controllers\Admin\LeadMaxFinanceController as Finance;
    $fi = fn (string $name, $w, $h = null) => '<img src="'.asset('images/leadmax/finance/'.$name.'.svg').'" width="'.$w.'" height="'.($h ?? $w).'" alt="">';
    $palette = ['#2563eb', '#db2777', '#7c3aed', '#0891b2', '#16a34a', '#ea580c', '#0d9488', '#9333ea', '#dc2626', '#65a30d'];
    $initials = fn ($u) => $u ? strtoupper(mb_substr((string) $u->first_name, 0, 1).mb_substr((string) $u->last_name, 0, 1)) : '?';
    $color = fn ($u) => $palette[$u ? (crc32((string) $u->id) % count($palette)) : 0];
    $shortName = fn ($u) => $u ? trim($u->first_name.' '.($u->last_name ? mb_substr($u->last_name, 0, 1).'.' : '')) : 'Unknown';
    $statusClass = ['paid' => 'is-completed', 'approved' => 'is-processing', 'pending' => 'is-pending', 'rejected' => 'is-failed'];
    $statusLabel = ['paid' => 'Completed', 'approved' => 'Processing', 'pending' => 'Pending', 'rejected' => 'Rejected'];
@endphp
<div class="lm-fin-table-wrap" data-lm-bulk-page data-lm-bulk-noun="payout">
    <table class="lm-fin-table">
        <thead><tr><th><input type="checkbox" aria-label="Select all" data-lm-select-all></th><th>Payout ID</th><th>User</th><th>Method</th><th>Amount</th><th>Fee</th><th>Net Amount</th><th>Status</th><th>Date</th><th>Actions</th></tr></thead>
        <tbody>
            @if(! $rows)
                <tr><td colspan="10" class="lm-fin-empty">Payouts are part of the prop-firm module, which is not set up on this platform.</td></tr>
            @else
                @forelse($rows as $p)
                    <tr>
                        <td><input type="checkbox" aria-label="Select {{ Finance::payoutId($p) }}" data-lm-select></td>
                        <td class="lm-fin-mono">{{ Finance::payoutId($p) }}</td>
                        <td><span class="lm-fin-user"><i style="--c:{{ $color($p->user) }}">{{ $initials($p->user) }}</i>{{ $shortName($p->user) }}</span></td>
                        <td>{{ ucwords(str_replace('_', ' ', (string) $p->method)) ?: '—' }}</td>
                        <td class="lm-fin-amount">${{ number_format((float) $p->amount, 0) }}</td>
                        <td class="lm-fin-mono is-muted" title="No payout fee is charged">$0.00</td>
                        <td class="lm-fin-amount">${{ number_format((float) $p->amount, 0) }}</td>
                        <td><em class="lm-fin-badge {{ $statusClass[$p->status] ?? 'is-other' }}">{{ $statusLabel[$p->status] ?? ucfirst((string) $p->status) }}</em></td>
                        <td class="lm-fin-mono is-muted">{{ ($p->processed_at ?? $p->created_at)?->format('Y-m-d H:i') }}</td>
                        <td><span class="lm-fin-row-actions">
                            <a href="{{ route('admin.leadmax.leads.show', [$p->user_id, 'tab' => 'finances', 'from' => 'finances']) }}" aria-label="View {{ Finance::payoutId($p) }}">{!! $fi('eye', 14) !!}</a>
                            <details class="lm-fin-menu"><summary aria-label="More actions">{!! $fi('row-more', 14) !!}</summary><div>
                                @if(is_admin() && \Illuminate\Support\Facades\Route::has('admin.propfirm.payouts.index'))<a href="{{ route('admin.propfirm.payouts.index') }}">Review in prop-firm payouts</a>@endif
                                <button type="button" data-lm-copy-text="{{ Finance::payoutId($p) }}">Copy payout ID</button>
                            </div></details>
                        </span></td>
                    </tr>
                @empty
                    <tr><td colspan="10" class="lm-fin-empty">No payouts match these filters.</td></tr>
                @endforelse
            @endif
        </tbody>
    </table>
</div>

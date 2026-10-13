{{-- Finances > Wallets (Figma 3318:36564): clients' Spot wallets (fiat balance) and crypto deposit addresses. --}}
@php
    $fi = fn (string $name, $w, $h = null) => '<img src="'.asset('images/leadmax/finance/'.$name.'.svg').'" width="'.$w.'" height="'.($h ?? $w).'" alt="">';
    $palette = ['#2563eb', '#db2777', '#7c3aed', '#0891b2', '#16a34a', '#ea580c', '#0d9488', '#9333ea', '#dc2626', '#65a30d'];
    $initials = fn ($u) => $u ? strtoupper(mb_substr((string) $u->first_name, 0, 1).mb_substr((string) $u->last_name, 0, 1)) : 'CO';
    $color = fn ($u) => $palette[$u ? (crc32((string) $u->id) % count($palette)) : 0];
    $shortName = fn ($u) => $u ? trim($u->first_name.' '.($u->last_name ? mb_substr($u->last_name, 0, 1).'.' : '')) : 'Company (general)';
@endphp
<div class="lm-fin-table-wrap" data-lm-bulk-page data-lm-bulk-noun="wallet">
    <table class="lm-fin-table">
        <thead><tr><th><input type="checkbox" aria-label="Select all" data-lm-select-all></th><th>Wallet ID</th><th>User</th><th>Type</th><th>Currency</th><th>Balance</th><th>Status</th><th>Created Date</th><th>Last Activity</th><th>Actions</th></tr></thead>
        <tbody>
            @forelse($rows as $w)
                <tr>
                    <td><input type="checkbox" aria-label="Select {{ $w['id'] }}" data-lm-select></td>
                    <td class="lm-fin-mono">{{ $w['id'] }}</td>
                    <td><span class="lm-fin-user"><i style="--c:{{ $color($w['user']) }}">{{ $initials($w['user']) }}</i>{{ $shortName($w['user']) }}</span></td>
                    <td>{{ $w['type'] }}</td>
                    <td>{{ $w['currency'] }}</td>
                    <td class="lm-fin-amount" @if($w['kind'] === 'crypto') title="Total received at this address" @endif>{{ $w['balance'] }}</td>
                    <td><em class="lm-fin-badge is-{{ $w['status'] }}">{{ ucfirst($w['status']) }}</em></td>
                    <td class="lm-fin-mono is-muted">{{ $w['created']?->format('Y-m-d H:i:s') }}</td>
                    <td class="lm-fin-mono is-muted">{{ $w['activity']?->format('Y-m-d H:i') }}</td>
                    <td><span class="lm-fin-row-actions">
                        @if($w['link'])<a href="{{ $w['link'] }}" aria-label="View {{ $w['id'] }}">{!! $fi('eye', 14) !!}</a>@endif
                        @if(! empty($w['address']))<button type="button" class="lm-fin-copy" data-lm-copy-text="{{ $w['address'] }}" title="Copy address" aria-label="Copy wallet address">{!! $fi('row-more', 14) !!}</button>@endif
                    </span></td>
                </tr>
            @empty
                <tr><td colspan="10" class="lm-fin-empty">No wallets match these filters.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

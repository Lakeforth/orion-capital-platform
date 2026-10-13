{{-- API Key Details modal (Figma 3236:24083). Usage is not metered yet, so the usage tiles show "—". --}}
@php
    $isActive = ($key['status'] ?? 'active') === 'active';
    $closeUrl = route('admin.leadmax.affiliates.show', [$affiliate->id, 'tab' => 'api-keys']);
    $permissions = str_contains(strtolower((string) ($key['permissions'] ?? '')), 'write') ? ['Read', 'Write'] : ['Read'];
    $when = fn ($value) => $value ? \Carbon\Carbon::parse($value) : null;
@endphp
<div class="lm-modal-backdrop lm-apikey-backdrop" data-lm-dismiss-url="{{ $closeUrl }}">
<section class="lm-apikey-modal" role="dialog" aria-modal="true" aria-labelledby="lm-apikey-title" tabindex="-1" data-lm-dialog>
    <header class="lm-apikey-head">
        <div><h2 id="lm-apikey-title">API Key Details</h2><span class="lm-apikey-badge {{ $isActive ? 'is-active' : 'is-revoked' }}">{{ $isActive ? 'Active' : 'Revoked' }}</span></div>
        <a class="lm-apikey-close" href="{{ $closeUrl }}" aria-label="Close"><img src="{{ asset('images/leadmax/affiliates/x-circle.svg') }}" width="14" height="14" alt=""></a>
    </header>
    <div class="lm-apikey-section">
        <div class="lm-apikey-field"><small>Key Name</small><strong>{{ $key['name'] }}</strong></div>
        <div class="lm-apikey-field"><small>API Key</small>
            <div class="lm-apikey-value"><code>{{ $key['key'] }}</code><button type="button" class="lm-apikey-copy" data-lm-copy-text="{{ $key['key'] }}" aria-label="Copy API key"><img src="{{ asset('images/leadmax/affiliates/copy.svg') }}" width="14" height="14" alt=""></button></div>
        </div>
        <div class="lm-apikey-dates">
            <div class="lm-apikey-field"><small>Created Date</small><strong>{{ $when($key['created_at'] ?? null)?->format('F j, Y') ?? '—' }}</strong></div>
            <div class="lm-apikey-field"><small>Last Used</small><strong>{{ $when($key['last_used_at'] ?? null)?->diffForHumans() ?? 'Never' }}</strong></div>
        </div>
        <div class="lm-apikey-field"><small>Environment</small><div><span class="lm-apikey-pill">{{ $key['environment'] ?? 'Production' }}</span></div></div>
    </div>
    <div class="lm-apikey-section lm-apikey-perms">
        <h3>Permissions</h3>
        <div>@foreach($permissions as $permission)<span class="lm-apikey-pill">{{ $permission }}</span>@endforeach</div>
    </div>
    <div class="lm-apikey-usage">
        <h3>Usage (Last 30 days)</h3>
        <div>
            <div><strong>{{ $key['usage_requests'] ?? '—' }}</strong><small>Total Requests</small></div>
            <div><strong class="is-green">{{ $key['usage_success_rate'] ?? '—' }}</strong><small>Success Rate</small></div>
            <div><strong>{{ $key['usage_avg_ms'] ?? '—' }}</strong><small>Avg Response</small></div>
        </div>
    </div>
    <footer class="lm-apikey-foot">
        @if($isActive)
            <form method="POST" action="{{ route('admin.leadmax.affiliates.api-keys.destroy', [$affiliate->id, $key['id']]) }}">@csrf @method('DELETE')<button type="submit" class="lm-apikey-revoke" data-lm-confirm-submit="Revoke {{ $key['name'] }}? Integrations using it will stop working.">Revoke Key</button></form>
        @endif
        <a class="lm-apikey-close-btn" href="{{ $closeUrl }}">Close</a>
    </footer>
</section>
</div>

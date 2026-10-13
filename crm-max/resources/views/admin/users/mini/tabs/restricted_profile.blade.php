<div class="mini-panel p-5">
    <h3 class="font-semibold mb-4">Customer information</h3>
    <dl class="grid grid-cols-2 gap-3 text-sm">
        <dt>Name</dt><dd>{{ $user->name }}</dd>
        <dt>Email</dt><dd>{{ $user->email }}</dd>
        <dt>Phone</dt><dd>{{ $user->phone }}</dd>
        <dt>Country</dt><dd>{{ $user->country }}</dd>
        <dt>Status</dt><dd>{{ ucfirst($user->user_status ?? $user->status) }}</dd>
        <dt>Verification</dt><dd>{{ ucfirst($user->verification_status ?? 'unverified') }}</dd>
        <dt>Plan</dt><dd>{{ $user->plan?->title ?? '-' }}</dd>
        <dt>Registered</dt><dd>{{ $user->created_at }}</dd>
    </dl>
</div>

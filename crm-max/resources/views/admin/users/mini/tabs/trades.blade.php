<div class="space-y-4">
    <div class="mini-panel p-4">
        @include('admin.users.partials.position', ['user' => $user])
    </div>

    <div class="mini-panel p-4">
        @include('admin.users.partials.closed_positions', ['user' => $user])
    </div>
</div>

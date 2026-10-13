<div class="mini-panel p-4">
    <h2 class="font-semibold text-lg text-gray-700 leading-tight mb-3">Auto Profit Settings</h2>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="mini-stat-card">
            <p class="mini-stat-title mb-3">Trading Controls</p>
            <div class="flex flex-wrap gap-2">
                <Link method="POST" href="{{ route('admin.user.toggle',['id' => $user->id,'field' => 'can_trade']) }}"
                      class="mini-action-btn {{ $user->can_trade ? 'mini-action-note' : 'mini-action-edit' }}">
                    <span>{{ $user->can_trade ? 'Disable Trade' : 'Enable Trade' }}</span>
                </Link>

                <Link method="POST" href="{{ route('admin.user.toggle',['id' => $user->id,'field' => 'copy_trader']) }}"
                      class="mini-action-btn {{ $user->copy_trader ? 'mini-action-note' : 'mini-action-edit' }}">
                    <span>{{ $user->copy_trader ? 'Disable Auto Trader' : 'Enable Auto Trader' }}</span>
                </Link>

                <Link method="POST" href="{{ route('admin.user.toggle',['id' => $user->id,'field' => 'ai_trade']) }}"
                      class="mini-action-btn {{ $user->ai_trade ? 'mini-action-note' : 'mini-action-edit' }}">
                    <span>{{ $user->ai_trade ? 'Disable AI Trading' : 'Enable AI Trading' }}</span>
                </Link>
            </div>
        </div>
    </div>
</div>

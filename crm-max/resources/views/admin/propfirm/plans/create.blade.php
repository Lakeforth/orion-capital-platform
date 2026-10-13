<x-admin-layout>
    <x-slot name="header">Add Challenge Package</x-slot>
    <div class="max-w-5xl mx-auto">
        <x-card>
            @php
                $emptyPhase = ['name' => '', 'profit_target_pct' => '', 'max_daily_loss_pct' => '', 'max_total_drawdown_pct' => '', 'min_trading_days' => '', 'max_duration_days' => '', 'drawdown_type' => 'static', 'max_lot_size' => '', 'allow_weekend_holding' => 1];
            @endphp
            <x-splade-form :default="['is_active' => 1, 'profit_split_pct' => 80, 'currency' => 'usd', 'scaling_min_profit_pct' => 10, 'scaling_min_trading_days' => 5, 'scaling_required_months' => 3, 'scaling_multiplier' => 2.00, 'phase_input' => [$emptyPhase, $emptyPhase, $emptyPhase]]" class="space-y-2" action="{{ route('admin.propfirm.plans.store') }}">
                @php $phases = []; $submitLabel = 'Create Package'; @endphp
                @include('admin.propfirm.plans.form')
            </x-splade-form>
        </x-card>
    </div>
</x-admin-layout>

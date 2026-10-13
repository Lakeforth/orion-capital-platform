<x-admin-layout>
    <x-slot name="header">Edit Challenge Package</x-slot>
    <div class="max-w-5xl mx-auto">
        <x-card>
            @php
                $emptyPhase = ['name' => '', 'profit_target_pct' => '', 'max_daily_loss_pct' => '', 'max_total_drawdown_pct' => '', 'min_trading_days' => '', 'max_duration_days' => '', 'drawdown_type' => 'static', 'max_lot_size' => '', 'allow_weekend_holding' => 1];
                $phaseInputDefaults = [];
                for ($i = 0; $i < 3; $i++) {
                    $existingPhase = $phases[$i] ?? null;
                    $phaseInputDefaults[] = $existingPhase ? [
                        'name' => $existingPhase->name ?? '',
                        'profit_target_pct' => $existingPhase->profit_target_pct ?? '',
                        'max_daily_loss_pct' => $existingPhase->max_daily_loss_pct ?? '',
                        'max_total_drawdown_pct' => $existingPhase->max_total_drawdown_pct ?? '',
                        'min_trading_days' => $existingPhase->min_trading_days ?? '',
                        'max_duration_days' => $existingPhase->max_duration_days ?? '',
                        'drawdown_type' => $existingPhase->drawdown_type ?? 'static',
                        'max_lot_size' => $existingPhase->max_lot_size ?? '',
                        'allow_weekend_holding' => ($existingPhase->allow_weekend_holding ?? true) ? 1 : 0,
                    ] : $emptyPhase;
                }
                $formDefaults = array_merge($plan->toArray(), ['phase_input' => $phaseInputDefaults]);
                $submitLabel = 'Save Changes';
            @endphp
            <x-splade-form :default="$formDefaults" method="PUT" class="space-y-2" action="{{ route('admin.propfirm.plans.update', $plan->id) }}">
                @include('admin.propfirm.plans.form')
            </x-splade-form>
        </x-card>
    </div>
</x-admin-layout>

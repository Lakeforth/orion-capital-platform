@php $submitLabel = $submitLabel ?? 'Save'; @endphp

<div class="plan-form-wizard" data-plan-form-wizard>
    {{-- Step indicator --}}
    <div class="flex items-center justify-center mb-6" aria-hidden="true">
        @foreach (['Plan Details', 'Rules & Scaling', 'Evaluation Phases'] as $index => $label)
            @php $stepNum = $index + 1; @endphp
            <div class="flex items-center {{ $index < 2 ? 'flex-1' : '' }}">
                <div class="flex flex-col items-center">
                    <div
                        data-step-dot="{{ $stepNum }}"
                        class="step-dot flex items-center justify-center w-8 h-8 rounded-full border-2 text-sm font-bold transition-colors {{ $stepNum === 1 ? 'bg-orange-500 border-orange-500 text-white' : 'border-gray-300 text-gray-400' }}"
                    >{{ $stepNum }}</div>
                    <span data-step-label="{{ $stepNum }}" class="mt-1 text-xs font-medium {{ $stepNum === 1 ? 'text-gray-800' : 'text-gray-400' }}">{{ $label }}</span>
                </div>
                @if ($index < 2)
                    <div data-step-connector="{{ $stepNum }}" class="step-connector flex-1 h-0.5 mx-2 bg-gray-200"></div>
                @endif
            </div>
        @endforeach
    </div>

    {{-- Step 1: Plan Details --}}
    <div data-plan-form-step="1">
        <x-splade-input name="title" label="Package Title" required placeholder="e.g. $10K Challenge" />
        <x-splade-textarea name="description" label="Description" class="mt-4" />

        <div class="grid grid-cols-2 gap-4 mt-4">
            <x-splade-input type="number" name="account_size" label="Account Size ($)" required step="0.01" />
            <x-splade-input type="number" name="price" label="Fee Charged to Client ($)" required step="0.01" />
        </div>

        <div class="grid grid-cols-2 gap-4 mt-4">
            <x-splade-input type="number" name="profit_split_pct" label="Trader Profit Split (%)" step="0.01" placeholder="80" />
            <x-splade-input name="currency" label="Currency" placeholder="usd" />
        </div>

        <div class="mt-4 rounded-xl border border-blue-100 bg-blue-50 p-5">
            <x-splade-checkbox name="is_active" value="1" label="Active (visible to clients)" />
        </div>

        <div class="mt-4 rounded-xl border border-green-100 bg-green-50 p-5">
            @php $freeTrialLabel = 'Free Trial (client pays $0 for this package and it\'s badged "Free Trial" in the client area)'; @endphp
            <x-splade-checkbox name="free_trial_enabled" value="1" :label="$freeTrialLabel" />
        </div>
    </div>

    {{-- Step 2: Rules & Scaling --}}
    <div data-plan-form-step="2" class="hidden">
        <div class="rounded-xl border border-blue-100 bg-blue-50 p-5">
            <x-splade-checkbox name="refund_fee_on_funded" value="1" label="Refund the challenge fee on the first payout after funding" />
        </div>

        <div class="mt-4 rounded-xl border border-amber-100 bg-amber-50 p-5 space-y-3">
            <x-splade-checkbox name="scaling_enabled" value="1" label="Allow funded accounts on this plan to request scaling to a larger account" />
            <div class="grid grid-cols-2 gap-4">
                <x-splade-input type="number" name="scaling_min_profit_pct" label="Min Profit per Qualifying Month (%)" step="0.01" placeholder="10" />
                <x-splade-input type="number" name="scaling_min_trading_days" label="Min Trading Days per Month" placeholder="5" />
                <x-splade-input type="number" name="scaling_required_months" label="Consecutive Qualifying Months Required" placeholder="3" />
                <x-splade-input type="number" name="scaling_multiplier" label="New Account Size Multiplier" step="0.01" placeholder="2.00" />
            </div>
        </div>
    </div>

    {{-- Step 3: Evaluation Phases --}}
    <div data-plan-form-step="3" class="hidden">
        <p class="text-sm text-gray-500 mb-4">Fill in Phase 1 for a one-step challenge. Add Phase 2 / Phase 3 for multi-step. Leave a phase's name and profit target blank to skip it.</p>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
            @for ($i = 0; $i < 3; $i++)
                @php $existing = $phases[$i] ?? null; @endphp
                <div class="p-4 rounded-lg border bg-white">
                    <p class="text-xs font-bold text-gray-500 uppercase mb-3">Phase {{ $i + 1 }}</p>
                    <div class="space-y-3">
                        <x-splade-input name="phase_input[{{ $i }}][name]" label="Phase Name" placeholder="{{ $i === 0 ? 'Evaluation' : ($i === 1 ? 'Verification' : 'Optional') }}" :default="$existing->name ?? ''" />
                        <x-splade-input type="number" name="phase_input[{{ $i }}][profit_target_pct]" label="Profit Target (%)" step="0.01" placeholder="8" :default="$existing->profit_target_pct ?? ''" />
                        <x-splade-input type="number" name="phase_input[{{ $i }}][max_daily_loss_pct]" label="Max Daily Loss (%)" step="0.01" placeholder="5" :default="$existing->max_daily_loss_pct ?? ''" />
                        <x-splade-input type="number" name="phase_input[{{ $i }}][max_total_drawdown_pct]" label="Max Total Drawdown (%)" step="0.01" placeholder="10" :default="$existing->max_total_drawdown_pct ?? ''" />
                        <x-splade-input type="number" name="phase_input[{{ $i }}][min_trading_days]" label="Min Trading Days" placeholder="0" :default="$existing->min_trading_days ?? ''" />
                        <x-splade-input type="number" name="phase_input[{{ $i }}][max_duration_days]" label="Max Duration Days (blank = unlimited)" :default="$existing->max_duration_days ?? ''" />
                        <x-splade-select name="phase_input[{{ $i }}][drawdown_type]" label="Drawdown Type" :options="['static' => 'Static', 'trailing' => 'Trailing']" :default="$existing->drawdown_type ?? 'static'" />
                        <x-splade-input type="number" name="phase_input[{{ $i }}][max_lot_size]" label="Max Lot Size (blank = unlimited)" step="0.01" :default="$existing->max_lot_size ?? ''" />
                        <x-splade-select name="phase_input[{{ $i }}][allow_weekend_holding]" label="Weekend Holding" :options="[1 => 'Allowed', 0 => 'Not Allowed (fails the challenge)']" :default="($existing?->allow_weekend_holding ?? true) ? 1 : 0" />
                    </div>
                </div>
            @endfor
        </div>
    </div>

    {{-- Wizard nav --}}
    <div class="flex items-center justify-between mt-6 pt-4 border-t">
        <button type="button" data-plan-form-back class="hidden px-4 py-2 rounded-lg border text-sm font-semibold text-gray-700 hover:bg-gray-50">
            Back
        </button>
        <div class="flex-1"></div>
        <button type="button" data-plan-form-next class="px-5 py-2 rounded-lg bg-orange-500 text-white text-sm font-bold hover:bg-orange-600">
            Next
        </button>
        <div data-plan-form-submit class="hidden">
            <x-splade-submit :label="$submitLabel" />
        </div>
    </div>
</div>

<x-splade-script>
(function () {
    function initPlanFormWizard(root) {
        if (!root || root.dataset.wizardInitialized) return;
        root.dataset.wizardInitialized = '1';

        var steps = Array.prototype.slice.call(root.querySelectorAll('[data-plan-form-step]'));
        var dots = Array.prototype.slice.call(root.querySelectorAll('[data-step-dot]'));
        var labels = Array.prototype.slice.call(root.querySelectorAll('[data-step-label]'));
        var connectors = Array.prototype.slice.call(root.querySelectorAll('[data-step-connector]'));
        var backBtn = root.querySelector('[data-plan-form-back]');
        var nextBtn = root.querySelector('[data-plan-form-next]');
        var submitWrap = root.querySelector('[data-plan-form-submit]');
        var current = 1;
        var total = steps.length;

        function fieldsOf(step) {
            return Array.prototype.slice.call(steps[step - 1].querySelectorAll('input, select, textarea'));
        }

        function clearFieldError(el) {
            el.classList.remove('plan-form-field-invalid');
            el.style.removeProperty('border-color');
            el.style.removeProperty('box-shadow');
            var msg = el.parentElement && el.parentElement.querySelector('[data-plan-form-error-for="' + el.name + '"]');
            if (msg) msg.remove();
        }

        function markFieldError(el) {
            el.classList.add('plan-form-field-invalid');
            el.style.borderColor = '#dc2626';
            el.style.boxShadow = '0 0 0 1px #dc2626';
            var existing = el.parentElement && el.parentElement.querySelector('[data-plan-form-error-for="' + el.name + '"]');
            if (!existing) {
                var msg = document.createElement('p');
                msg.setAttribute('data-plan-form-error-for', el.name);
                msg.style.color = '#dc2626';
                msg.style.fontSize = '12px';
                msg.style.marginTop = '4px';
                msg.textContent = el.validationMessage || 'This field is required.';
                el.insertAdjacentElement('afterend', msg);
            }
            if (!el.dataset.planFormErrorBound) {
                el.dataset.planFormErrorBound = '1';
                el.addEventListener('input', function () {
                    if (el.checkValidity()) clearFieldError(el);
                });
                el.addEventListener('change', function () {
                    if (el.checkValidity()) clearFieldError(el);
                });
            }
        }

        function stepIsValid(step) {
            var invalid = fieldsOf(step).filter(function (el) { return typeof el.checkValidity === 'function' && !el.checkValidity(); });
            if (invalid.length) {
                invalid.forEach(markFieldError);
                invalid[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
                invalid[0].focus({ preventScroll: true });
                invalid[0].reportValidity();
                return false;
            }
            return true;
        }

        function render() {
            steps.forEach(function (el, i) {
                el.classList.toggle('hidden', (i + 1) !== current);
            });
            dots.forEach(function (el) {
                var n = parseInt(el.getAttribute('data-step-dot'), 10);
                var active = n === current;
                var done = n < current;
                el.classList.toggle('bg-orange-500', active);
                el.classList.toggle('border-orange-500', active || done);
                el.classList.toggle('text-white', active);
                el.classList.toggle('border-gray-300', !active && !done);
                el.classList.toggle('text-gray-400', !active && !done);
                el.classList.toggle('bg-white', done && !active);
                el.classList.toggle('text-orange-500', done && !active);
            });
            labels.forEach(function (el) {
                var n = parseInt(el.getAttribute('data-step-label'), 10);
                el.classList.toggle('text-gray-800', n <= current);
                el.classList.toggle('text-gray-400', n > current);
            });
            connectors.forEach(function (el) {
                var n = parseInt(el.getAttribute('data-step-connector'), 10);
                el.classList.toggle('bg-orange-500', n < current);
                el.classList.toggle('bg-gray-200', n >= current);
            });
            backBtn.classList.toggle('hidden', current === 1);
            nextBtn.classList.toggle('hidden', current === total);
            submitWrap.classList.toggle('hidden', current !== total);
        }

        backBtn.addEventListener('click', function () {
            if (current > 1) { current -= 1; render(); }
        });

        nextBtn.addEventListener('click', function () {
            if (!stepIsValid(current)) return;
            if (current < total) { current += 1; render(); }
        });

        // If a hidden required field further down is invalid at submit time,
        // jump to its step and block submission rather than let the browser
        // silently skip validating a display:none input.
        var form = root.closest('form');
        if (form) {
            form.addEventListener('submit', function (e) {
                for (var i = 1; i <= total; i++) {
                    if (!stepIsValid(i)) {
                        if (i !== current) { current = i; render(); }
                        e.preventDefault();
                        e.stopPropagation();
                        return;
                    }
                }
            }, true);
        }

        render();
    }

    function init() {
        document.querySelectorAll('[data-plan-form-wizard]').forEach(initPlanFormWizard);
    }

    init();
})();
</x-splade-script>

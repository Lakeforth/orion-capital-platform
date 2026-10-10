{{-- Add Manual Transaction (Figma 3334:38599). Creates a real, approved entry through the admin deposit flow. --}}
@php
    use App\Http\Controllers\Admin\LeadMaxFinanceController as Finance;
    $fi = fn (string $name, $w, $h = null) => '<img src="'.asset('images/leadmax/finance/'.$name.'.svg').'" width="'.$w.'" height="'.($h ?? $w).'" alt="">';
    $close = request()->fullUrlWithQuery(['dialog' => null]);
@endphp
<div class="lm-fin-overlay" role="dialog" aria-modal="true" aria-labelledby="lm-fin-modal-title">
    <form method="POST" action="{{ route('admin.leadmax.finances.transactions.store') }}" class="lm-fin-modal">
        @csrf
        <header>
            <div><h2 id="lm-fin-modal-title">Add Manual Transaction</h2><p>Create a new transaction record</p></div>
            <a class="lm-fin-modal-close" href="{{ $close }}" aria-label="Close">×</a>
        </header>
        <div class="lm-fin-modal-body">
            @if($errors->any())<p class="lm-detail-flash lm-detail-flash-warning" role="alert">{{ $errors->first() }}</p>@endif
            <input type="hidden" name="idempotency_key" value="{{ old('idempotency_key', (string) \Illuminate\Support\Str::uuid()) }}">
            <label>Transaction Type<select name="kind" required>
                @foreach(['deposit' => 'Deposit', 'withdrawal' => 'Withdrawal', 'adjustment' => 'Adjustment (+ adds, − deducts)'] as $v => $l)<option value="{{ $v }}" @selected(old('kind', request('kind', 'deposit')) === $v)>{{ $l }}</option>@endforeach
            </select></label>
            <label>User / Account<span class="lm-fin-prefix" style="display:block;position:relative"><input type="text" name="user" value="{{ old('user') }}" placeholder="Lead ID or email" required autocomplete="off"></span></label>
            <label>Amount<span class="lm-fin-prefix"><span>$</span><input type="text" inputmode="decimal" name="amount" value="{{ old('amount') }}" placeholder="0.00, or +25.00 / -25.00 for an adjustment" pattern="[+\-]?\d{1,9}(\.\d{1,2})?" required></span></label>
            <label>Currency<select disabled aria-describedby="lm-fin-currency-note"><option>USD</option></select><small id="lm-fin-currency-note" hidden>Balances are kept in USD.</small></label>
            <label>Payment Method<select name="method"><option value="">Select method...</option>@foreach(Finance::METHODS as $v => $l)<option value="{{ $v }}" @selected(old('method') === $v)>{{ $l }}</option>@endforeach</select></label>
            <label>Status<select disabled title="Manual entries are recorded as completed and move the balance straight away"><option>Completed</option></select></label>
            <label>Date &amp; Time<input type="datetime-local" name="date" value="{{ old('date', now()->format('Y-m-d\TH:i')) }}" max="{{ now()->format('Y-m-d\TH:i') }}"></label>
            <label>Reference ID<input type="text" name="reference" value="{{ old('reference') }}" placeholder="Enter reference..." maxlength="120"></label>
            <label class="is-wide">Notes / Reason<textarea name="notes" placeholder="Add notes or description... (a reason is required for adjustments)" maxlength="500">{{ old('notes') }}</textarea></label>
            <p class="lm-fin-note is-wide" style="grid-column:1/-1">The amount moves the lead's Spot wallet balance at once. An adjustment takes a signed amount (+25.00 adds, -25.00 deducts), cannot take the balance below zero, and is kept out of the client's transaction history and deposit totals.</p>
        </div>
        <footer>
            <a class="lm-fin-btn" href="{{ $close }}">Cancel</a>
            <button type="submit" class="lm-fin-btn is-primary" data-lm-confirm-submit="Record this transaction? It changes the lead's balance immediately.">{!! $fi('plus', 14) !!}<span>Submit Transaction</span></button>
        </footer>
    </form>
</div>

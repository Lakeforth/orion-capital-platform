<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Challenge;
use App\Models\ChallengePayout;
use App\Models\ChallengePlan;
use App\Models\ChallengePlanPhase;
use App\Models\ChallengeScalingRequest;
use App\Mail\ChallengeStartedMail;
use App\Models\PromoCode;
use App\Models\Transaction;
use App\Models\User;
use App\Services\NotificationService;
use App\Tables\ChallengeTradeTable;
use App\Services\PropFirmProvisioningService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;
use ProtoneMedia\Splade\Facades\Toast;

class PropFirmController extends Controller
{
    public function plansIndex(Request $request): View
    {
        // Archived packages stay out of the way by default but are never gone --
        // "Show archived" brings them back so they can be restored.
        $showArchived = $request->boolean('archived');

        $plans = ChallengePlan::withoutGlobalScope('company')
            ->where('company_id', auth()->user()->company_id)
            ->when($showArchived, fn ($q) => $q->archived(), fn ($q) => $q->notArchived())
            ->withCount('challenges')
            ->with('phases')
            ->orderBy('sort_order')
            ->get();

        $archivedCount = ChallengePlan::withoutGlobalScope('company')
            ->where('company_id', auth()->user()->company_id)
            ->archived()
            ->count();

        return view('admin.propfirm.plans.index', compact('plans', 'showArchived', 'archivedCount'));
    }

    public function plansCreate(): View
    {
        return view('admin.propfirm.plans.create');
    }

    public function plansStore(Request $request): RedirectResponse
    {
        $plan = ChallengePlan::create($this->validatePlan($request) + [
            'company_id' => auth()->user()->company_id,
        ]);
        $this->syncPhases($plan, $request);

        Toast::title('Challenge plan created.');

        return redirect()->route('admin.propfirm.plans.index');
    }

    public function plansEdit(string $id): View
    {
        $plan = $this->findPlan($id);
        $phases = $plan->phases()->orderBy('phase_number')->get()->keyBy(fn ($p) => $p->phase_number - 1);

        return view('admin.propfirm.plans.edit', compact('plan', 'phases'));
    }

    public function plansUpdate(Request $request, string $id): RedirectResponse
    {
        $plan = $this->findPlan($id);
        $plan->update($this->validatePlan($request));
        $this->syncPhases($plan, $request);

        Toast::title('Challenge plan updated.');

        return redirect()->route('admin.propfirm.plans.index');
    }

    public function plansDestroy(string $id): RedirectResponse
    {
        $plan = $this->findPlan($id);

        // A plan with challenges on it can never be hard-deleted: Challenge rows
        // keep a challenge_plan_id, and dropping the parent would break every
        // view that reads $challenge->plan. Archiving is the way out -- the list
        // only offers Delete when the count is genuinely zero, so this is a
        // backstop for a stale page or a hand-crafted request.
        if ($plan->challenges()->exists()) {
            Toast::danger('This package has challenges on it, so it can\'t be deleted. Archive it instead to take it off sale and out of this list.');

            return redirect()->back();
        }

        $plan->delete();
        Toast::title('Challenge package deleted.');

        return redirect()->route('admin.propfirm.plans.index');
    }

    /**
     * Retire a package that can't be deleted. Also forces it inactive, which is
     * what actually takes it off the client-facing list.
     */
    public function plansArchive(string $id): RedirectResponse
    {
        $plan = $this->findPlan($id);

        if ($plan->isArchived()) {
            return redirect()->back();
        }

        $plan->update([
            'archived_at' => now(),
            'is_active' => false,
        ]);

        Toast::title('Package archived. It is now off sale and hidden from this list.');

        return redirect()->route('admin.propfirm.plans.index');
    }

    /**
     * Bring an archived package back. Left inactive on purpose so it can be
     * reviewed before it goes back on sale.
     */
    public function plansUnarchive(string $id): RedirectResponse
    {
        $plan = $this->findPlan($id);

        $plan->update(['archived_at' => null]);

        Toast::title('Package restored. Switch it Active when you\'re ready to sell it.');

        return redirect()->back();
    }

    public function plansToggle(string $id): RedirectResponse
    {
        $plan = $this->findPlan($id);

        // Never let an archived package be switched back on sale -- the client
        // endpoints filter on is_active, so this would silently re-expose it.
        if ($plan->isArchived() && ! $plan->is_active) {
            Toast::danger('This package is archived. Restore it first, then switch it Active.');

            return redirect()->back();
        }

        $plan->update(['is_active' => ! $plan->is_active]);

        Toast::title('Challenge plan updated.');

        return redirect()->back();
    }

    public function promoCodesIndex(): View
    {
        $promoCodes = PromoCode::withoutGlobalScope('company')
            ->where('company_id', auth()->user()->company_id)
            ->latest()
            ->paginate(25);

        return view('admin.propfirm.promo-codes.index', compact('promoCodes'));
    }

    public function promoCodesCreate(): View
    {
        return view('admin.propfirm.promo-codes.create');
    }

    public function promoCodesStore(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', 'alpha_dash'],
            'discount_pct' => ['required', 'numeric', 'min:0.01', 'max:100'],
            'max_uses' => ['required', 'integer', 'min:1'],
            'expires_at' => ['nullable', 'date', 'after:now'],
        ]);

        $exists = PromoCode::withoutGlobalScope('company')
            ->where('company_id', auth()->user()->company_id)
            ->where('code', strtoupper($validated['code']))
            ->exists();

        if ($exists) {
            Toast::danger('A discount code with that name already exists.');

            return redirect()->back()->withInput();
        }

        PromoCode::create([
            'company_id' => auth()->user()->company_id,
            'code' => strtoupper($validated['code']),
            'discount_pct' => $validated['discount_pct'],
            'is_active' => true,
            'expires_at' => $validated['expires_at'] ?? null,
            'max_uses' => $validated['max_uses'],
            'source' => 'admin',
        ]);

        Toast::title('Discount code created.');

        return redirect()->route('admin.propfirm.promo-codes.index');
    }

    public function promoCodesToggle(string $id): RedirectResponse
    {
        $promoCode = $this->findPromoCode($id);
        $promoCode->update(['is_active' => ! $promoCode->is_active]);

        Toast::title('Discount code updated.');

        return redirect()->back();
    }

    public function promoCodesDestroy(string $id): RedirectResponse
    {
        $this->findPromoCode($id)->delete();

        Toast::title('Discount code deleted.');

        return redirect()->back();
    }

    public function challengesIndex(Request $request): View
    {
        $status = $request->query('status');

        $challenges = Challenge::withoutGlobalScope('company')
            ->where('company_id', auth()->user()->company_id)
            ->with(['user:id,first_name,last_name,email', 'plan'])
            ->when($status, fn ($q) => $q->where('status', $status))
            ->latest()
            ->paginate(25);

        return view('admin.propfirm.challenges.index', compact('challenges', 'status'));
    }

    /**
     * Deliberately mirrors the client area's own "Buy a Challenge" wizard
     * (type -> size -> drawdown -> add-ons -> confirm) rather than a plain
     * dropdown form -- admins kept finding the old two-dropdown version
     * confusing because it didn't match the mental model they already have
     * from the client side. See AdminGrantChallengeWizard.vue.
     */
    public function challengesCreate(Request $request): View
    {
        $selectedUserId = $request->query('user_id');

        $customers = User::query()
            ->whereHasRole(['customer', 'user'])
            ->when(! is_admin(), fn ($query) => $query->where('manager_id', auth()->id()))
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get(['id', 'first_name', 'last_name', 'email'])
            ->map(fn (User $user) => [
                'id' => $user->id,
                'name' => trim("{$user->first_name} {$user->last_name}"),
                'email' => $user->email,
            ])
            ->values();

        // Only honour the pre-select if it's actually one of this admin's visible clients --
        // otherwise it just falls through to the client picker, no error.
        if (! $customers->firstWhere('id', $selectedUserId)) {
            $selectedUserId = null;
        }

        $plans = ChallengePlan::withoutGlobalScope('company')
            ->where('company_id', auth()->user()->company_id)
            ->where('is_active', true)
            ->with('phases')
            ->orderBy('sort_order')
            ->get();

        $addonCatalog = collect(Challenge::ADDON_CATALOG)
            ->map(fn ($addon, $key) => ['key' => $key, 'label' => $addon['label'], 'price' => $addon['price']])
            ->values();

        return view('admin.propfirm.challenges.create', [
            'customers' => $customers,
            'plans' => $plans,
            'addonCatalog' => $addonCatalog,
            'selectedUserId' => $selectedUserId,
        ]);
    }

    /**
     * Grant a challenge to a client who hasn't bought or paid for one --
     * bypasses the balance check entirely (fee_paid is always 0 here,
     * unlike the client purchase flow), but deliberately keeps the same
     * guardrails a real purchase has: the plan must currently be
     * active/on-sale, and the client's propfirm_max_active_challenges cap
     * still applies. An admin who wants to bypass those too should disable
     * the cap or reactivate the plan first, rather than this route silently
     * allowing an inconsistent state a normal purchase never could reach.
     */
    public function challengesStore(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'challenge_plan_id' => ['required', 'string'],
            'addons' => ['nullable', 'array'],
            'addons.*' => ['string', 'in:'.implode(',', array_keys(Challenge::ADDON_CATALOG))],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        $user = User::withoutGlobalScope('company')
            ->where('id', $validated['user_id'])
            ->where('company_id', auth()->user()->company_id)
            ->firstOrFail();

        $plan = ChallengePlan::withoutGlobalScope('company')
            ->where('id', $validated['challenge_plan_id'])
            ->where('company_id', auth()->user()->company_id)
            ->where('is_active', true)
            ->first();

        if (! $plan) {
            $message = 'That plan is no longer active -- reactivate it first, or pick a different one.';

            if ($request->wantsJson()) {
                return response()->json(['message' => $message], 422);
            }

            Toast::danger($message);

            return redirect()->back()->withInput();
        }

        $maxActiveChallenges = (int) company_settings('propfirm_max_active_challenges', 0);
        if ($maxActiveChallenges > 0) {
            $activeChallengeCount = Challenge::withoutGlobalScope('company')
                ->where('user_id', $user->id)
                ->where('status', 'active')
                ->count();

            if ($activeChallengeCount >= $maxActiveChallenges) {
                $message = "This client already has the maximum number of open challenges ({$maxActiveChallenges}).";

                if ($request->wantsJson()) {
                    return response()->json(['message' => $message], 422);
                }

                Toast::danger($message);

                return redirect()->back()->withInput();
            }
        }

        $admin = auth()->user();
        $addonKeys = array_unique($validated['addons'] ?? []);

        $challenge = DB::transaction(function () use ($user, $plan, $admin, $addonKeys, $validated) {
            $challenge = app(PropFirmProvisioningService::class)->createChallenge(
                $user, $plan, $addonKeys, null, null, 0.0
            );

            $reason = "Admin-granted {$plan->title} prop firm challenge (no charge) by {$admin->first_name} {$admin->last_name}";
            if ($addonKeys) {
                $reason .= ' + '.count($addonKeys).' add-on(s)';
            }
            if (! empty($validated['note'])) {
                $reason .= ' -- '.$validated['note'];
            }

            Transaction::create([
                'user_id' => $user->id,
                'type' => 'withdrawal',
                'method' => 'wallet',
                'status' => 'approved',
                'amount' => 0,
                'reason' => $reason,
            ]);

            return $challenge;
        });

        Mail::to($user->email)->send(new ChallengeStartedMail([
            'customerName' => trim("{$user->first_name} {$user->last_name}"),
            'planTitle' => $plan->title,
            'accountSize' => format_price($plan->account_size),
            'phaseLabel' => 'Phase '.($challenge->current_phase ?? 1).' of '.$challenge->plan->phases->count(),
            'accountId' => $challenge->tradingAccount?->account_id,
            'tradingServer' => $challenge->tradingAccount?->trading_server,
            'password' => $challenge->tradingAccount?->password,
            'startUrl' => config('app.url'),
        ], $user->company_id));

        if ($request->wantsJson()) {
            return response()->json([
                'data' => [
                    'id' => $challenge->id,
                    'show_url' => route('admin.propfirm.challenges.show', $challenge->id),
                    'account_id' => $challenge->tradingAccount?->account_id,
                    'password' => $challenge->tradingAccount?->password,
                ],
            ]);
        }

        Toast::title('Challenge granted -- the client has been emailed their trading credentials.');

        return redirect()->route('admin.propfirm.challenges.show', $challenge->id);
    }

    public function challengesShow(string $id): View
    {
        $challenge = $this->findChallenge($id);
        $challenge->load(['user:id,first_name,last_name,email', 'plan.phases', 'tradingAccount', 'dailyStats', 'payouts']);

        $trades = new ChallengeTradeTable($challenge->trading_account_id);

        return view('admin.propfirm.challenges.show', compact('challenge', 'trades'));
    }

    public function challengesUpdate(Request $request, string $id): RedirectResponse
    {
        $challenge = $this->findChallenge($id);

        $validated = $request->validate([
            'status' => ['required', 'in:active,passed,failed,funded,withdrawn'],
            'failed_reason' => ['nullable', 'string', 'max:255'],
        ]);

        $updates = ['status' => $validated['status']];

        $becomingFunded = $validated['status'] === 'funded';

        if ($validated['status'] === 'failed') {
            $updates['failed_reason'] = $validated['failed_reason'] ?? 'admin_override';
            $updates['ended_at'] = now();
        } elseif ($becomingFunded) {
            $updates['funded_at'] = now();
        } elseif (in_array($validated['status'], ['passed', 'withdrawn'], true)) {
            $updates['ended_at'] = now();
        }

        $challenge->update($updates);

        if ($becomingFunded) {
            // Same provisioning the automatic evaluator uses -- a fresh,
            // distinct funded-account login, not a balance reset on the
            // existing account. Matches app(NotificationService)'s
            // sendChallengePassedNotification below so a manual admin
            // funding gets the exact same certificate + credentials mail
            // an automatic pass would have sent, instead of silently
            // skipping both as it did before.
            app(PropFirmProvisioningService::class)->provisionPhaseAccount(
                $challenge->fresh(['plan.phases', 'tradingAccount', 'user']),
                (float) $challenge->starting_balance,
                'Funded Account'
            );

            if ($challenge->user) {
                app(NotificationService::class)->sendChallengePassedNotification($challenge->user, $challenge->fresh(), true);
            }
        }

        Toast::title('Challenge updated.');

        return redirect()->back();
    }

    public function challengesProvisionAccount(Request $request, string $id): RedirectResponse
    {
        $challenge = $this->findChallenge($id);

        if (! $challenge->tradingAccount) {
            Toast::danger('This challenge has no trading account to provision.');

            return redirect()->back();
        }

        $validated = $request->validate([
            'trading_server' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', 'min:6', 'max:255'],
        ]);

        $challenge->tradingAccount->update($validated);

        Toast::title('Trading account credentials issued.');

        return redirect()->back();
    }

    public function payoutsIndex(Request $request): View
    {
        $status = $request->query('status');

        $payouts = ChallengePayout::withoutGlobalScope('company')
            ->where('company_id', auth()->user()->company_id)
            ->with(['user:id,first_name,last_name,email', 'challenge.plan'])
            ->when($status, fn ($q) => $q->where('status', $status))
            ->latest()
            ->paginate(25);

        return view('admin.propfirm.payouts.index', compact('payouts', 'status'));
    }

    public function payoutsApprove(string $id): RedirectResponse
    {
        $payout = $this->findPayout($id);
        $payout->update(['status' => 'approved', 'processed_at' => now()]);

        Toast::title('Payout approved.');

        return redirect()->back();
    }

    public function payoutsReject(Request $request, string $id): RedirectResponse
    {
        $validated = $request->validate(['admin_note' => ['nullable', 'string', 'max:1000']]);

        $payout = $this->findPayout($id);
        $payout->update([
            'status' => 'rejected',
            'admin_note' => $validated['admin_note'] ?? null,
            'processed_at' => now(),
        ]);

        Toast::title('Payout rejected.');

        return redirect()->back();
    }

    public function payoutsMarkPaid(string $id): RedirectResponse
    {
        $payout = $this->findPayout($id);

        if ($payout->status !== 'approved') {
            Toast::danger('Only approved payouts can be marked as paid.');

            return redirect()->back();
        }

        $payout->update(['status' => 'paid']);

        if ($payout->user) {
            app(NotificationService::class)->sendPayoutProcessedNotification($payout->user, $payout);
        }

        Toast::title('Payout marked as paid.');

        return redirect()->back();
    }

    public function scalingRequestsIndex(Request $request): View
    {
        $status = $request->query('status');

        $scalingRequests = ChallengeScalingRequest::withoutGlobalScope('company')
            ->where('company_id', auth()->user()->company_id)
            ->with(['user:id,first_name,last_name,email', 'challenge.plan'])
            ->when($status, fn ($q) => $q->where('status', $status))
            ->latest()
            ->paginate(25);

        return view('admin.propfirm.scaling-requests.index', compact('scalingRequests', 'status'));
    }

    public function scalingRequestsApprove(string $id): RedirectResponse
    {
        $scalingRequest = $this->findScalingRequest($id);
        $challenge = $scalingRequest->challenge;

        $scalingRequest->update(['status' => 'approved', 'processed_at' => now()]);

        $challenge->update(['starting_balance' => $scalingRequest->requested_account_size]);
        if ($challenge->tradingAccount) {
            $challenge->tradingAccount->update(['balance' => $scalingRequest->requested_account_size]);
        }

        if ($scalingRequest->user) {
            app(NotificationService::class)->sendScalingUpdateNotification($scalingRequest->user, $scalingRequest, 'approved');
        }

        Toast::title('Scaling request approved -- account resized to '.$scalingRequest->requested_account_size);

        return redirect()->back();
    }

    public function scalingRequestsReject(Request $request, string $id): RedirectResponse
    {
        $validated = $request->validate(['admin_note' => ['nullable', 'string', 'max:1000']]);

        $scalingRequest = $this->findScalingRequest($id);
        $scalingRequest->update([
            'status' => 'rejected',
            'admin_note' => $validated['admin_note'] ?? null,
            'processed_at' => now(),
        ]);

        if ($scalingRequest->user) {
            app(NotificationService::class)->sendScalingUpdateNotification($scalingRequest->user, $scalingRequest, 'rejected');
        }

        Toast::title('Scaling request rejected.');

        return redirect()->back();
    }

    private function validatePlan(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'account_size' => ['required', 'numeric', 'min:0'],
            'price' => ['required', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'max:10'],
            'profit_split_pct' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'refund_fee_on_funded' => ['nullable'],
            'is_active' => ['nullable'],
            'free_trial_enabled' => ['nullable'],
            'sort_order' => ['nullable', 'integer'],
            'scaling_enabled' => ['nullable'],
            'scaling_min_profit_pct' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'scaling_min_trading_days' => ['nullable', 'integer', 'min:0'],
            'scaling_required_months' => ['nullable', 'integer', 'min:1'],
            'scaling_multiplier' => ['nullable', 'numeric', 'min:1'],
        ]) + [
            'currency' => $request->input('currency', 'usd'),
            'profit_split_pct' => $request->input('profit_split_pct', 80),
            'refund_fee_on_funded' => (bool) $request->input('refund_fee_on_funded'),
            'is_active' => (bool) $request->input('is_active', true),
            'free_trial_enabled' => (bool) $request->input('free_trial_enabled'),
            'sort_order' => (int) $request->input('sort_order', 0),
            'scaling_enabled' => (bool) $request->input('scaling_enabled'),
            'scaling_min_profit_pct' => $request->input('scaling_min_profit_pct', 10),
            'scaling_min_trading_days' => (int) $request->input('scaling_min_trading_days', 5),
            'scaling_required_months' => (int) $request->input('scaling_required_months', 3),
            'scaling_multiplier' => $request->input('scaling_multiplier', 2.00),
        ];
    }

    private function syncPhases(ChallengePlan $plan, Request $request): void
    {
        $phases = $request->input('phase_input', []);

        $plan->phases()->delete();

        $number = 1;
        foreach ($phases as $phase) {
            if (empty($phase['name']) && empty($phase['profit_target_pct'])) {
                continue;
            }

            ChallengePlanPhase::create([
                'challenge_plan_id' => $plan->id,
                'phase_number' => $number,
                'name' => ($phase['name'] ?? '') ?: "Phase {$number}",
                'profit_target_pct' => $phase['profit_target_pct'] ?? 0,
                'max_daily_loss_pct' => $phase['max_daily_loss_pct'] ?? 0,
                'max_total_drawdown_pct' => $phase['max_total_drawdown_pct'] ?? 0,
                'drawdown_type' => ($phase['drawdown_type'] ?? '') ?: 'static',
                'max_lot_size' => ($phase['max_lot_size'] ?? '') ?: null,
                'allow_weekend_holding' => ($phase['allow_weekend_holding'] ?? '1') !== '0',
                'min_trading_days' => $phase['min_trading_days'] ?? 0,
                'max_duration_days' => ($phase['max_duration_days'] ?? '') ?: null,
            ]);
            $number++;
        }
    }

    private function findPlan(string $id): ChallengePlan
    {
        return ChallengePlan::withoutGlobalScope('company')
            ->where('id', $id)
            ->where('company_id', auth()->user()->company_id)
            ->firstOrFail();
    }

    private function findPromoCode(string $id): PromoCode
    {
        return PromoCode::withoutGlobalScope('company')
            ->where('id', $id)
            ->where('company_id', auth()->user()->company_id)
            ->firstOrFail();
    }

    private function findChallenge(string $id): Challenge
    {
        return Challenge::withoutGlobalScope('company')
            ->where('id', $id)
            ->where('company_id', auth()->user()->company_id)
            ->firstOrFail();
    }

    private function findPayout(string $id): ChallengePayout
    {
        return ChallengePayout::withoutGlobalScope('company')
            ->where('id', $id)
            ->where('company_id', auth()->user()->company_id)
            ->firstOrFail();
    }

    private function findScalingRequest(string $id): ChallengeScalingRequest
    {
        return ChallengeScalingRequest::withoutGlobalScope('company')
            ->where('id', $id)
            ->where('company_id', auth()->user()->company_id)
            ->firstOrFail();
    }
}

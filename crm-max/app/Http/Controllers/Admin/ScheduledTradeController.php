<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Models\ScheduledTrade;
use App\Models\User;
use App\Services\ScheduledTradeService;
use App\Services\TradeService;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use ProtoneMedia\Splade\Facades\Toast;

class ScheduledTradeController extends Controller
{
    public function create(): View
    {
        $default = [
            'direction' => 'buy',
            'duration' => 60,
            'profit_target' => 0,
            'volume' => 1,
            'leverage' => 10,
            'scheduled_at' => now()->addMinutes(5)->format('Y-m-d\TH:i'),
            'redirect_to' => 'customers_ai_trading',
        ];

        return view('admin.scheduled-trades.create', [
            'default' => $default,
            'customerOptions' => $this->scheduleCustomerOptions(),
            'assetOptions' => $this->assetOptions(),
            'directionOptions' => $this->directionOptions(),
        ]);
    }

    public function storeFromListing(Request $request): RedirectResponse
    {
        $request->validate([
            'user_id' => ['required', Rule::exists('users', 'id')],
        ]);

        return $this->store($request, (string) $request->input('user_id'));
    }

    public function store(Request $request, string $user): RedirectResponse
    {
        $user = User::query()->findOrFail($user);
        $validated = $this->validatedData($request);
        $asset = Asset::query()->findOrFail($validated['asset_id']);

        ScheduledTrade::query()->create($this->payload($user, $asset, $validated));

        Toast::success('AI trade queued successfully');

        return $this->redirectBackToSchedule($user);
    }

    public function edit(string $user, string $scheduledTrade): View
    {
        $user = User::query()->findOrFail($user);
        $scheduledTrade = ScheduledTrade::query()
            ->with(['asset', 'user'])
            ->findOrFail($scheduledTrade);

        $this->authorizeScheduledTrade($user, $scheduledTrade);
        $this->ensurePending($scheduledTrade);

        return view('admin.scheduled-trades.edit', [
            'scheduledTrade' => $scheduledTrade,
            'default' => [
                'asset_id' => $scheduledTrade->asset_id,
                'direction' => $scheduledTrade->direction,
                'duration' => $scheduledTrade->duration,
                'profit_target' => $scheduledTrade->profit_target,
                'volume' => $scheduledTrade->volume,
                'leverage' => $scheduledTrade->leverage,
                'scheduled_at' => $scheduledTrade->scheduled_at?->format('Y-m-d\TH:i'),
                'redirect_to' => 'customers_ai_trading',
            ],
            'assetOptions' => $this->assetOptions(),
            'directionOptions' => $this->directionOptions(),
        ]);
    }

    public function update(
        Request $request,
        string $user,
        string $scheduledTrade
    ): RedirectResponse {
        $user = User::query()->findOrFail($user);
        $scheduledTrade = ScheduledTrade::query()->findOrFail($scheduledTrade);

        $this->authorizeScheduledTrade($user, $scheduledTrade);
        $this->ensurePending($scheduledTrade);

        $validated = $this->validatedData($request);
        $asset = Asset::query()->findOrFail($validated['asset_id']);

        $scheduledTrade->update($this->payload($user, $asset, $validated));

        Toast::success('AI trade schedule updated');

        return $this->redirectBackToSchedule($user);
    }

    public function cancel(string $user, string $scheduledTrade): RedirectResponse
    {
        $user = User::query()->findOrFail($user);
        $scheduledTrade = ScheduledTrade::query()->findOrFail($scheduledTrade);

        $this->authorizeScheduledTrade($user, $scheduledTrade);
        $this->ensurePending($scheduledTrade);

        $scheduledTrade->update([
            'status' => ScheduledTrade::STATUS_CANCELLED,
            'cancelled_at' => now(),
            'failure_reason' => null,
        ]);

        Toast::success('AI trade schedule cancelled');

        return $this->redirectBackToSchedule($user);
    }

    public function trigger(
        string $user,
        string $scheduledTrade,
        ScheduledTradeService $scheduledTradeService,
        TradeService $tradeService
    ): RedirectResponse {
        $user = User::query()->findOrFail($user);
        $scheduledTrade = ScheduledTrade::query()
            ->with(['asset', 'user'])
            ->findOrFail($scheduledTrade);

        $this->authorizeScheduledTrade($user, $scheduledTrade);
        $this->ensurePending($scheduledTrade);

        $result = $scheduledTradeService->openPendingTrade($scheduledTrade, $tradeService);

        if ($result['opened'] ?? false) {
            Toast::success('Scheduled AI trade opened now');
        } else {
            Toast::danger((string) ($result['message'] ?? 'Scheduled AI trade could not be opened.'));
        }

        return $this->redirectBackToSchedule($user);
    }

    public function destroy(string $user, string $scheduledTrade): RedirectResponse
    {
        $user = User::query()->findOrFail($user);
        $scheduledTrade = ScheduledTrade::query()->findOrFail($scheduledTrade);

        $this->authorizeScheduledTrade($user, $scheduledTrade);
        $this->ensureDeletable($scheduledTrade);

        $scheduledTrade->delete();

        Toast::success('AI trade schedule deleted');

        return $this->redirectBackToSchedule($user);
    }

    private function validatedData(Request $request): array
    {
        $validated = $request->validate([
            'asset_id' => ['required', Rule::exists('assets', 'id')],
            'direction' => ['required', Rule::in(['buy', 'sell'])],
            'duration' => ['required', 'integer', 'min:1', 'max:43200'],
            'profit_target' => ['nullable', 'numeric', 'min:0'],
            'volume' => ['required', 'numeric', 'min:0.01'],
            'leverage' => ['required', 'integer', 'min:1', 'max:1000'],
            'scheduled_at' => ['required', 'date'],
        ]);

        $scheduledAt = CarbonImmutable::parse($validated['scheduled_at']);

        if ($scheduledAt->lt(now()->subMinute())) {
            throw ValidationException::withMessages([
                'scheduled_at' => 'Scheduled time must be now or later.',
            ]);
        }

        if ($scheduledAt->gt(now()->addDays(30))) {
            throw ValidationException::withMessages([
                'scheduled_at' => 'Scheduled trades can only be queued up to 30 days ahead.',
            ]);
        }

        return $validated;
    }

    private function payload(User $user, Asset $asset, array $validated): array
    {
        $volume = (float) $validated['volume'];
        $leverage = (int) $validated['leverage'];
        $margin = $leverage > 0 ? ($volume * (float) $asset->rate) / $leverage : 0;

        return [
            'company_id' => $user->company_id,
            'user_id' => $user->id,
            'asset_id' => $asset->id,
            'direction' => $validated['direction'],
            'duration' => (int) $validated['duration'],
            'profit_target' => (float) ($validated['profit_target'] ?? 0),
            'volume' => $volume,
            'leverage' => $leverage,
            'margin' => round($margin, 2),
            'scheduled_at' => CarbonImmutable::parse($validated['scheduled_at']),
            'status' => ScheduledTrade::STATUS_PENDING,
            'failure_reason' => null,
        ];
    }

    private function authorizeScheduledTrade(User $user, ScheduledTrade $scheduledTrade): void
    {
        abort_unless($scheduledTrade->user_id === $user->id, 404);
    }

    private function ensurePending(ScheduledTrade $scheduledTrade): void
    {
        abort_unless($scheduledTrade->status === ScheduledTrade::STATUS_PENDING, 422, 'Only pending scheduled trades can be changed.');
    }

    private function ensureDeletable(ScheduledTrade $scheduledTrade): void
    {
        abort_unless(
            in_array($scheduledTrade->status, [
                ScheduledTrade::STATUS_CANCELLED,
                ScheduledTrade::STATUS_COMPLETED,
            ], true),
            422,
            'Only cancelled or completed scheduled trades can be deleted.'
        );
    }

    private function scheduleCustomerOptions(): array
    {
        return User::query()
            ->whereHasRole(['customer', 'user'])
            ->when(! is_admin(), fn ($query) => $query->where('manager_id', auth()->id()))
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get(['id', 'first_name', 'last_name', 'email'])
            ->mapWithKeys(fn (User $user) => [
                $user->id => trim($user->first_name.' '.$user->last_name).' - '.$user->email,
            ])
            ->toArray();
    }

    private function assetOptions(): array
    {
        return Asset::query()
            ->tradable()
            ->orderBy('sym')
            ->get()
            ->mapWithKeys(fn (Asset $asset) => [
                $asset->id => $asset->sy.' - '.$asset->name,
            ])
            ->toArray();
    }

    private function directionOptions(): array
    {
        return [
            'buy' => 'Buy',
            'sell' => 'Sell',
        ];
    }

    private function redirectBackToSchedule(User $user): RedirectResponse
    {
        if (request()->input('redirect_to') === 'customers_ai_trading') {
            return redirect()->route('admin.users.customers', ['tab' => 'ai_trading']);
        }

        if (request()->input('redirect_to') === 'user_scheduled_trades') {
            return redirect()->route('admin.users.show', ['user' => $user->id, 'tab' => 'scheduled_trades']);
        }

        return redirect()->route('admin.users.show', ['user' => $user->id, 'tab' => 'scheduled_trades']);
    }
}

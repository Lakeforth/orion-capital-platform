<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiRecommendation;
use App\Models\User;
use App\Services\AiRecommendationService;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use ProtoneMedia\Splade\Facades\Toast;

class AiRecommendationController extends Controller
{
    public function create(): View
    {
        return view('admin.ai-recommendations.create', [
            'default' => [
                'schedule_type' => AiRecommendation::SCHEDULE_INSTANT,
                'recurrence' => 'daily',
                'send_email' => 'yes',
                'source_name' => 'Gemini AI Recommendation',
                'scheduled_at' => now()->addMinutes(5)->format('Y-m-d\TH:i'),
                'redirect_to' => 'customers_ai_recommendations',
            ],
            'customerOptions' => $this->customerOptions(),
            'sourceOptions' => $this->sourceOptions(),
            'actionRoute' => route('admin.ai-recommendations.store_listing'),
            'includeCustomer' => true,
        ]);
    }

    public function createForUser(string $user): View
    {
        $user = User::query()->findOrFail($user);

        return view('admin.ai-recommendations.create', [
            'targetUser' => $user,
            'default' => [
                'heading' => $user->company_name ?: $user->name,
                'schedule_type' => AiRecommendation::SCHEDULE_INSTANT,
                'recurrence' => 'daily',
                'send_email' => 'yes',
                'source_name' => 'Gemini AI Recommendation',
                'scheduled_at' => now()->addMinutes(5)->format('Y-m-d\TH:i'),
                'redirect_to' => 'user_ai_recommendations',
            ],
            'sourceOptions' => $this->sourceOptions(),
            'actionRoute' => route('admin.ai-recommendations.store_for_user', $user->id),
            'includeCustomer' => false,
            'redirectTo' => 'user_ai_recommendations',
        ]);
    }

    public function storeFromListing(Request $request, AiRecommendationService $service): RedirectResponse
    {
        $validated = $this->validatedData($request);
        $user = User::query()->findOrFail($validated['user_id']);

        return $this->storeForResolvedUser($request, $service, $user, $validated);
    }

    public function storeForUser(Request $request, string $user, AiRecommendationService $service): RedirectResponse
    {
        $user = User::query()->findOrFail($user);
        $validated = $this->validatedData($request, false);

        return $this->storeForResolvedUser($request, $service, $user, array_merge($validated, ['user_id' => $user->id]));
    }

    private function storeForResolvedUser(
        Request $request,
        AiRecommendationService $service,
        User $user,
        array $validated
    ): RedirectResponse {
        $recommendation = AiRecommendation::query()->create($this->payload($user, $validated));

        if ($recommendation->schedule_type === AiRecommendation::SCHEDULE_INSTANT && empty($validated['draft'])) {
            $service->deliver($recommendation);
            Toast::success('AI recommendation sent successfully');
        } else {
            Toast::success('AI recommendation queued successfully');
        }

        return $this->redirectBack($user);
    }

    public function edit(string $user, string $aiRecommendation): View
    {
        $user = User::query()->findOrFail($user);
        $recommendation = AiRecommendation::query()->with('user')->findOrFail($aiRecommendation);

        $this->authorizeRecommendation($user, $recommendation);

        return view('admin.ai-recommendations.edit', [
            'recommendation' => $recommendation,
            'default' => [
                'heading' => $recommendation->heading,
                'message' => $recommendation->message,
                'source_name' => $recommendation->source_name,
                'schedule_type' => $recommendation->schedule_type,
                'recurrence' => $recommendation->recurrence ?? 'daily',
                'scheduled_at' => $recommendation->scheduled_at?->format('Y-m-d\TH:i'),
                'send_email' => $recommendation->send_email ? 'yes' : 'no',
                'redirect_to' => request('redirect_to', 'customers_ai_recommendations'),
            ],
            'sourceOptions' => $this->sourceOptions($recommendation->source_name),
        ]);
    }

    public function update(Request $request, string $user, string $aiRecommendation): RedirectResponse
    {
        $user = User::query()->findOrFail($user);
        $recommendation = AiRecommendation::query()->findOrFail($aiRecommendation);

        $this->authorizeRecommendation($user, $recommendation);

        $validated = $this->validatedData($request, false);
        $recommendation->update($this->payload($user, array_merge($validated, ['user_id' => $user->id])));

        Toast::success('AI recommendation updated');

        return $this->redirectBack($user);
    }

    public function trigger(string $user, string $aiRecommendation, AiRecommendationService $service): RedirectResponse
    {
        $user = User::query()->findOrFail($user);
        $recommendation = AiRecommendation::query()->findOrFail($aiRecommendation);

        $this->authorizeRecommendation($user, $recommendation);
        $service->deliver($recommendation);

        Toast::success('AI recommendation delivered now');

        return $this->redirectBack($user);
    }

    public function destroy(string $user, string $aiRecommendation): RedirectResponse
    {
        $user = User::query()->findOrFail($user);
        $recommendation = AiRecommendation::query()->findOrFail($aiRecommendation);

        $this->authorizeRecommendation($user, $recommendation);
        $recommendation->delete();

        Toast::success('AI recommendation deleted');

        return $this->redirectBack($user);
    }

    private function validatedData(Request $request, bool $requireUser = true): array
    {
        $rules = [
            'heading' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string', 'max:2000'],
            'source_name' => ['required', 'string', 'max:255'],
            'custom_source_name' => ['nullable', 'required_if:source_name,__custom__', 'string', 'max:255'],
            'schedule_type' => ['required', Rule::in([
                AiRecommendation::SCHEDULE_INSTANT,
                AiRecommendation::SCHEDULE_SPECIFIC_DATE,
                AiRecommendation::SCHEDULE_RECURRING,
            ])],
            'recurrence' => ['nullable', Rule::in(['daily', 'weekly', 'monthly'])],
            'scheduled_at' => ['nullable', 'date'],
            'send_email' => ['nullable', Rule::in(['yes', 'no'])],
        ];

        if ($requireUser) {
            $rules['user_id'] = ['required', Rule::exists('users', 'id')];
        }

        $validated = $request->validate($rules);
        $validated['draft'] = $request->input('action') === 'draft';

        if (! $validated['draft'] && in_array($validated['schedule_type'], [
            AiRecommendation::SCHEDULE_SPECIFIC_DATE,
            AiRecommendation::SCHEDULE_RECURRING,
        ], true) && blank($validated['scheduled_at'] ?? null)) {
            throw ValidationException::withMessages([
                'scheduled_at' => 'Scheduled time is required for scheduled recommendations.',
            ]);
        }

        if (! $validated['draft'] && ($validated['schedule_type'] ?? null) === AiRecommendation::SCHEDULE_RECURRING && blank($validated['recurrence'] ?? null)) {
            throw ValidationException::withMessages([
                'recurrence' => 'Recurring recommendations require an interval.',
            ]);
        }

        if (filled($validated['scheduled_at'] ?? null) && CarbonImmutable::parse($validated['scheduled_at'])->lt(now()->subMinute())) {
            throw ValidationException::withMessages([
                'scheduled_at' => 'Scheduled time must be now or later.',
            ]);
        }

        return $validated;
    }

    private function payload(User $user, array $validated): array
    {
        $scheduleType = $validated['schedule_type'];
        $scheduledAt = filled($validated['scheduled_at'] ?? null)
            ? CarbonImmutable::parse($validated['scheduled_at'])
            : null;

        $status = ! empty($validated['draft'])
            ? AiRecommendation::STATUS_QUEUED
            : match ($scheduleType) {
                AiRecommendation::SCHEDULE_INSTANT => AiRecommendation::STATUS_QUEUED,
                AiRecommendation::SCHEDULE_RECURRING => AiRecommendation::STATUS_RECURRING,
                default => AiRecommendation::STATUS_SCHEDULED,
            };

        $sourceName = ($validated['source_name'] ?? null) === '__custom__'
            ? trim((string) ($validated['custom_source_name'] ?? ''))
            : trim((string) $validated['source_name']);

        return [
            'company_id' => $user->company_id,
            'user_id' => $user->id,
            'heading' => trim((string) $validated['heading']),
            'message' => trim((string) $validated['message']),
            'source_name' => $sourceName,
            'schedule_type' => $scheduleType,
            'recurrence' => $scheduleType === AiRecommendation::SCHEDULE_RECURRING ? ($validated['recurrence'] ?? 'daily') : null,
            'scheduled_at' => $scheduleType === AiRecommendation::SCHEDULE_INSTANT ? null : $scheduledAt,
            'is_active' => false,
            'send_email' => ($validated['send_email'] ?? 'no') === 'yes',
            'status' => $status,
        ];
    }

    private function authorizeRecommendation(User $user, AiRecommendation $recommendation): void
    {
        abort_unless($recommendation->user_id === $user->id, 404);
    }

    private function customerOptions(): array
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

    private function sourceOptions(?string $current = null): array
    {
        $options = [
            'Gemini AI Recommendation' => 'Gemini AI Recommendation',
            'Claude AI Analysis' => 'Claude AI Analysis',
            'OpenAI Market Insight' => 'OpenAI Market Insight',
            'AI Trading Assistant' => 'AI Trading Assistant',
            '__custom__' => 'Custom source name',
        ];

        if ($current && ! array_key_exists($current, $options)) {
            $options = [$current => $current] + $options;
        }

        return $options;
    }

    private function redirectBack(User $user): RedirectResponse
    {
        if (request()->input('redirect_to') === 'user_ai_recommendations') {
            return redirect()->route('admin.users.show', ['user' => $user->id, 'tab' => 'ai_recommendations']);
        }

        return redirect()->route('admin.users.customers', ['tab' => 'ai_recommendations']);
    }
}

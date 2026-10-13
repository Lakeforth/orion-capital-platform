@php
    $statusClasses = [
        'queued' => 'bg-rose-50 text-rose-700 ring-rose-200',
        'scheduled' => 'bg-cyan-50 text-cyan-700 ring-cyan-200',
        'sent' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
        'recurring' => 'bg-slate-100 text-slate-700 ring-slate-300',
    ];
@endphp
@php($isCustomerView = isset($user))

<div class="space-y-6">
    <div class="flex flex-col gap-4 border-b border-gray-200 pb-6 md:flex-row md:items-center md:justify-between">
        <div>
            <h2 class="text-2xl font-bold tracking-tight text-gray-950">AI Recommendations</h2>
            <p class="mt-1 text-sm text-gray-500">Manage AI delivery and client-level recommendation logic.</p>
        </div>

        <Link
            modal
            href="{{ $isCustomerView ? route('admin.ai-recommendations.create_for_user', $user->id) : route('admin.ai-recommendations.create') }}"
            class="inline-flex items-center justify-center gap-2 bg-orange-500 px-6 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-orange-600"
        >
            {{ svg('heroicon-o-plus', 'w-5 h-5') }}
            <span>Create New AI Recommendation</span>
        </Link>
    </div>

    <div class="mini-panel p-4">
        <div class="mb-3 flex items-center justify-between gap-3">
            <div>
                <h3 class="font-semibold text-gray-700">Recommendations History</h3>
                <p class="text-sm text-gray-500">Search, filter, and manage queued AI recommendations.</p>
            </div>
        </div>

        <x-splade-table class="mt-3" :for="$aiRecommendationTable">
            @unless($isCustomerView)
                <x-splade-cell user_id as="$recommendation">
                    @if($recommendation->user)
                        <span class="block font-semibold leading-snug text-gray-900">{{ $recommendation->user->name }}</span>
                        <span class="block text-xs leading-snug text-gray-500">{{ $recommendation->user->email }}</span>
                    @else
                        <span class="font-semibold text-gray-700">Customer unavailable</span>
                    @endif
                </x-splade-cell>
            @endunless

            <x-splade-cell message as="$recommendation">
                <div class="max-w-xs">
                    <p class="font-semibold text-gray-900">{{ $recommendation->heading }}</p>
                    <p class="mt-1 italic leading-relaxed text-gray-600">"{{ \Illuminate\Support\Str::limit($recommendation->message, 90) }}"</p>
                </div>
            </x-splade-cell>

            <x-splade-cell source_name as="$recommendation">
                <span class="font-medium text-gray-800">{{ $recommendation->source_name }}</span>
            </x-splade-cell>

            <x-splade-cell scheduled_at as="$recommendation">
                @if($recommendation->last_delivered_at)
                    <span class="block text-gray-800">{{ $recommendation->last_delivered_at->isToday() ? 'Today' : $recommendation->last_delivered_at->format('M j, Y') }}</span>
                    <span class="block text-xs text-gray-500">{{ $recommendation->last_delivered_at->format('g:i A') }}</span>
                @elseif($recommendation->scheduled_at)
                    <span class="block text-gray-800">{{ $recommendation->scheduled_at->isTomorrow() ? 'Tomorrow' : $recommendation->scheduled_at->format('M j, Y') }}</span>
                    <span class="block text-xs text-gray-500">{{ $recommendation->scheduled_at->format('g:i A') }}</span>
                @else
                    <span class="text-gray-500">Pending approval</span>
                @endif
            </x-splade-cell>

            <x-splade-cell status as="$recommendation">
                <span class="inline-flex rounded-full px-3 py-1 text-xs font-bold uppercase ring-1 {{ $statusClasses[$recommendation->status] ?? 'bg-gray-100 text-gray-700 ring-gray-200' }}">
                    {{ $recommendation->status }}
                </span>
            </x-splade-cell>

            <x-splade-cell actions as="$recommendation">
                @include('admin.ai-recommendations.actions', ['recommendation' => $recommendation, 'redirectTo' => $redirectTo ?? 'customers_ai_recommendations'])
            </x-splade-cell>
        </x-splade-table>
    </div>
</div>

@php($planUsage = $planUsage ?? [])
@php($hasCaps = collect($planUsage)->contains(fn ($row) => ! ($row['unlimited'] ?? true)))

@if (! empty($planUsage) && $hasCaps)
    <div class="mb-6 rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
        <div class="mb-4 flex items-center justify-between">
            <div>
                <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">Plan usage</h3>
                <p class="text-xs text-gray-500 dark:text-gray-400">
                    Your current usage against your
                    <span class="font-medium">{{ app('company')->plan?->title ?? 'plan' }}</span> limits.
                </p>
            </div>
            @if (collect($planUsage)->contains(fn ($row) => $row['near_limit'] ?? false))
                <span class="inline-flex items-center rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-medium text-amber-800 dark:bg-amber-900/40 dark:text-amber-300">
                    Approaching a limit
                </span>
            @endif
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ($planUsage as $row)
                @php($unlimited = $row['unlimited'] ?? true)
                @php($limit = $row['limit'] ?? null)
                @php($used = $row['used'] ?? 0)
                @php($pct = (! $unlimited && $limit > 0) ? min(100, (int) round(($used / $limit) * 100)) : 0)
                @php($near = $row['near_limit'] ?? false)
                <div class="rounded-xl border border-gray-100 p-4 dark:border-gray-700">
                    <div class="flex items-baseline justify-between">
                        <span class="text-xs font-medium text-gray-500 dark:text-gray-400">{{ $row['label'] }}</span>
                        <span class="text-sm font-semibold {{ $near ? 'text-amber-600 dark:text-amber-400' : 'text-gray-900 dark:text-gray-100' }}">
                            {{ $used }}<span class="text-gray-400">/{{ $unlimited ? '∞' : $limit }}</span>
                        </span>
                    </div>
                    <div class="mt-3 h-1.5 w-full overflow-hidden rounded-full bg-gray-100 dark:bg-gray-700">
                        <div class="h-full rounded-full {{ $near ? 'bg-amber-500' : 'bg-blue-600' }}"
                             style="width: {{ $unlimited ? 0 : $pct }}%"></div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@endif

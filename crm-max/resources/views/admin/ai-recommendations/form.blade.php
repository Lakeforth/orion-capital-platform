<input type="hidden" name="redirect_to" value="{{ $redirectTo ?? 'customers_ai_recommendations' }}">

<div class="space-y-6">
    @if($includeCustomer ?? false)
        <x-splade-select
            name="user_id"
            label="Recipient"
            :options="$customerOptions"
            placeholder="Select customer"
            choices
            required
        />
    @elseif(isset($recommendation) || isset($targetUser))
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Recipient</label>
            <div class="rounded border border-gray-200 bg-gray-50 px-3 py-2 text-sm text-gray-700">
                {{ isset($recommendation) ? $recommendation->user?->name : $targetUser->name }}
                <span class="block text-xs text-gray-500">{{ isset($recommendation) ? $recommendation->user?->email : $targetUser->email }}</span>
            </div>
        </div>
    @endif

    <x-splade-input name="heading" label="Heading" placeholder="Blackwood Asset Mgmt" required />

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <x-splade-select
            name="source_name"
            label="AI Source Name"
            :options="$sourceOptions"
            placeholder="Select source"
            choices
            required
        />

        <x-splade-checkbox name="send_email" value="yes" false-value="no" label="Send email notification" />
    </div>

    <div v-if="form.source_name === '__custom__'">
        <x-splade-input
            name="custom_source_name"
            label="Custom Source Name"
            placeholder="AI Trading Assistant"
        />
    </div>

    <div>
        <div class="mb-2 flex items-center justify-between gap-3">
            <label class="block text-sm font-medium text-gray-700">Message Content</label>
            <span class="text-xs text-gray-500">@{{ (form.message || '').length }} / 2000 characters</span>
        </div>
        <div class="rounded border border-slate-300 bg-slate-50 p-3">
            <x-splade-textarea
                name="message"
                rows="7"
                class="w-full border-0 bg-transparent p-0 shadow-none focus:ring-0"
                placeholder="Enter recommendation details here..."
                required
            />
        </div>
    </div>

    <div>
        <p class="mb-3 text-xs font-bold uppercase tracking-wider text-gray-600">Delivery Schedule</p>
        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
            <label
                class="relative cursor-pointer rounded border px-4 py-5 text-center transition"
                v-bind:class="form.schedule_type === 'instant' ? 'border-slate-950 bg-slate-50 ring-2 ring-slate-950 ring-offset-2 shadow-sm' : 'border-gray-200 bg-white hover:border-slate-400'"
            >
                <input type="radio" name="schedule_type" value="instant" v-model="form.schedule_type" class="sr-only">
                <span v-if="form.schedule_type === 'instant'" class="absolute right-3 top-3 inline-flex h-5 w-5 items-center justify-center rounded-full bg-slate-950 text-white">
                    {{ svg('heroicon-o-check', 'h-3.5 w-3.5') }}
                </span>
                <span class="mx-auto mb-3 block" v-bind:class="form.schedule_type === 'instant' ? 'text-teal-700' : 'text-gray-500'">{{ svg('heroicon-o-bolt', 'h-5 w-5 inline') }}</span>
                <span class="block text-lg font-semibold" v-bind:class="form.schedule_type === 'instant' ? 'text-slate-950' : 'text-gray-900'">Instant</span>
                <span class="mt-1 block text-xs text-gray-500">Send as soon as deployed</span>
            </label>

            <label
                class="relative cursor-pointer rounded border px-4 py-5 text-center transition"
                v-bind:class="form.schedule_type === 'specific_date' ? 'border-slate-950 bg-slate-50 ring-2 ring-slate-950 ring-offset-2 shadow-sm' : 'border-gray-200 bg-white hover:border-slate-400'"
            >
                <input type="radio" name="schedule_type" value="specific_date" v-model="form.schedule_type" class="sr-only">
                <span v-if="form.schedule_type === 'specific_date'" class="absolute right-3 top-3 inline-flex h-5 w-5 items-center justify-center rounded-full bg-slate-950 text-white">
                    {{ svg('heroicon-o-check', 'h-3.5 w-3.5') }}
                </span>
                <span class="mx-auto mb-3 block" v-bind:class="form.schedule_type === 'specific_date' ? 'text-teal-700' : 'text-gray-500'">{{ svg('heroicon-o-calendar-days', 'h-5 w-5 inline') }}</span>
                <span class="block text-lg font-semibold" v-bind:class="form.schedule_type === 'specific_date' ? 'text-slate-950' : 'text-gray-900'">Specific Date</span>
                <span class="mt-1 block text-xs text-gray-500">Pick a future moment</span>
            </label>

            <label
                class="relative cursor-pointer rounded border px-4 py-5 text-center transition"
                v-bind:class="form.schedule_type === 'recurring' ? 'border-slate-950 bg-slate-50 ring-2 ring-slate-950 ring-offset-2 shadow-sm' : 'border-gray-200 bg-white hover:border-slate-400'"
            >
                <input type="radio" name="schedule_type" value="recurring" v-model="form.schedule_type" class="sr-only">
                <span v-if="form.schedule_type === 'recurring'" class="absolute right-3 top-3 inline-flex h-5 w-5 items-center justify-center rounded-full bg-slate-950 text-white">
                    {{ svg('heroicon-o-check', 'h-3.5 w-3.5') }}
                </span>
                <span class="mx-auto mb-3 block" v-bind:class="form.schedule_type === 'recurring' ? 'text-teal-700' : 'text-gray-500'">{{ svg('heroicon-o-arrow-path', 'h-5 w-5 inline') }}</span>
                <span class="block text-lg font-semibold" v-bind:class="form.schedule_type === 'recurring' ? 'text-slate-950' : 'text-gray-900'">Recurring</span>
                <span class="mt-1 block text-xs text-gray-500">Systematic interval</span>
            </label>
        </div>

        <div v-if="form.schedule_type === 'specific_date' || form.schedule_type === 'recurring'" class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
            <x-splade-input name="scheduled_at" type="datetime-local" label="Scheduled Time" />
            <x-splade-select
                v-if="form.schedule_type === 'recurring'"
                name="recurrence"
                label="Recurring Interval"
                :options="['daily' => 'Daily', 'weekly' => 'Weekly', 'monthly' => 'Monthly']"
            />
        </div>

        <div class="mt-5 rounded border border-dashed border-slate-300 bg-slate-50 px-4 py-4 text-sm text-slate-600">
            <span v-if="form.schedule_type === 'instant'">Recommendation will be sent immediately to the client dashboard and email when enabled.</span>
            <span v-if="form.schedule_type === 'specific_date'">Recommendation will be queued until the scheduled time is reached.</span>
            <span v-if="form.schedule_type === 'recurring'">Recommendation will repeat on the selected interval after each delivery.</span>
        </div>
    </div>
</div>

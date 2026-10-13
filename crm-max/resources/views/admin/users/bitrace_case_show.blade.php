<x-admin-layout>
    <x-slot name="header"></x-slot>

    @php
        $orderedPhases = $case->phases->sortBy('order')->values();
        $displayPhase = $activePhase ?? $orderedPhases->firstWhere('is_active', true) ?? $orderedPhases->first();
        $currentStage = $displayPhase->name ?? ($case->current_stage ?? \App\Services\RecoveryCaseStageService::CASE_OPENED);
        $activeOrder = $displayPhase->order ?? 0;
        $nextPhase = $orderedPhases->first(fn ($p) => $p->order > $activeOrder);
        $isWithdrawn = $case->status === 'withdrawn';
        $managerOptions = $managers->mapWithKeys(fn ($m) => [$m->id => trim($m->first_name.' '.$m->last_name)])->all();
        $actionTypeOptions = $actionTypes->mapWithKeys(fn ($t) => [$t->id => $t->name])->all();
    @endphp

    <div class="max-w-7xl mx-auto py-6 px-4 space-y-6">
        <nav class="text-sm text-gray-500 flex items-center gap-2 flex-wrap">
            <Link href="{{ route('admin.users.show', $user->id) }}?tab=recovery" class="hover:text-blue-600">{{ $user->name }}</Link>
            <span>/</span>
            <span class="text-gray-700">Recovery</span>
            <span>/</span>
            <span class="text-gray-900 font-medium">{{ $case->case_number }}</span>
        </nav>

        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
            <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-4">
                <div>
                    <p class="text-xs uppercase tracking-wide text-gray-400 font-semibold">Recovery Module</p>
                    <h1 class="text-2xl font-bold text-gray-900 mt-1">{{ $case->case_number }}</h1>
                    <p class="text-gray-700 mt-1">{{ $case->title }}</p>
                    <div class="flex flex-wrap items-center gap-3 mt-3 text-sm">
                        <span class="inline-flex px-2.5 py-0.5 rounded-full bg-blue-100 text-blue-800 font-medium">{{ ucfirst(str_replace('_', ' ', $case->status)) }}</span>
                        <span class="text-gray-600">Step: <strong>{{ $currentStage }}</strong></span>
                        @if($case->eth_wallet_address)
                            <span class="text-gray-500 font-mono text-xs break-all">Wallet: {{ $case->eth_wallet_address }}</span>
                        @endif
                    </div>
                </div>

                <div class="w-full lg:w-80">
                    <x-splade-form
                        method="PUT"
                        preserve-scroll
                        :default="['assigned_manager_id' => $case->assigned_manager_id]"
                        action="{{ route('admin.recovery.cases.assign', $case->id) }}"
                        class="space-y-2"
                    >
                        <x-splade-select name="assigned_manager_id" label="Assigned staff" :options="$managerOptions" />
                        <x-splade-submit class="px-4 py-2 bg-gray-900 text-white rounded-lg text-sm" label="Reassign" />
                    </x-splade-form>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-semibold text-gray-900">Case Progress</h2>
            </div>

            @if($orderedPhases->isEmpty())
                <p class="text-sm text-gray-500">No phases yet.</p>
            @else
                <div class="flex flex-wrap gap-2">
                    @foreach($orderedPhases as $phase)
                        @php
                            $isDone = $phase->status === 'completed';
                            $isActive = $phase->is_active;
                            $stepDate = $phase->activated_at ?? $phase->completed_at;
                        @endphp
                        <div class="flex items-center gap-2 px-3 py-2 rounded-lg text-sm border {{ $isActive ? 'bg-blue-600 text-white border-blue-600' : ($isDone ? 'bg-blue-50 text-blue-800 border-blue-200' : 'bg-gray-50 text-gray-500 border-gray-200') }}">
                            <span class="font-semibold">{{ $loop->iteration }}.</span>
                            <span>{{ $phase->name }}</span>
                            @if($stepDate)
                                <span class="text-xs opacity-75">· {{ $stepDate->format('M d') }}</span>
                            @endif
                        </div>
                    @endforeach
                </div>

                <div class="mt-6 pt-5 border-t border-gray-100 flex flex-wrap items-center gap-3">
                    @if($isWithdrawn)
                        <span class="text-sm text-red-600">This case has been withdrawn. Progression is disabled.</span>
                    @elseif($nextPhase)
                        <x-splade-form
                            preserve-scroll
                            method="POST"
                            action="{{ route('admin.recovery.cases.advance', $case->id) }}"
                            confirm="Advance to {{ $nextPhase->name }}?"
                            confirm-text="This moves the case forward one step and notifies the client."
                            class="inline"
                        >
                            <x-splade-submit class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium" label="Advance to {{ $nextPhase->name }}" />
                        </x-splade-form>
                        <span class="text-xs text-gray-500">Progression is sequential — the case moves one step at a time.</span>
                    @else
                        <span class="text-sm font-medium text-green-700">Final phase reached.</span>
                    @endif
                </div>
            @endif
        </div>

        <div class="grid grid-cols-1 xl:grid-cols-12 gap-6">
            <div class="xl:col-span-8 space-y-6">
                @if($displayPhase)
                    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
                        <h2 class="text-lg font-semibold text-gray-900 mb-1">{{ $displayPhase->name }} — Actions</h2>
                        <p class="text-sm text-gray-500 mb-4">Review the tasks the client must complete for the current step.</p>

                        <div class="overflow-x-auto border border-gray-200 rounded-lg mb-5">
                            <table class="min-w-full text-sm">
                                <thead class="bg-gray-50 text-left text-gray-600">
                                    <tr>
                                        <th class="px-3 py-2">Action</th>
                                        <th class="px-3 py-2">Status</th>
                                        <th class="px-3 py-2 text-right">Review</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    @forelse($displayPhase->actions as $action)
                                        @php
                                            $statusClass = match ($action->status) {
                                                'completed' => 'bg-green-100 text-green-800',
                                                'skipped' => 'bg-gray-200 text-gray-700',
                                                default => 'bg-yellow-100 text-yellow-800',
                                            };
                                        @endphp
                                        <tr>
                                            <td class="px-3 py-3 font-medium text-gray-900">{{ $action->actionType->name ?? 'Action' }}</td>
                                            <td class="px-3 py-3">
                                                <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium capitalize {{ $statusClass }}">{{ $action->status }}</span>
                                            </td>
                                            <td class="px-3 py-3">
                                                <div class="flex justify-end gap-2 flex-wrap">
                                                    @if($action->status !== 'completed')
                                                        <x-splade-form preserve-scroll method="PUT" :default="['status' => 'completed']" action="{{ route('admin.recovery.phase_actions.status', [$case->id, $displayPhase->id, $action->id]) }}" class="inline">
                                                            <x-splade-submit class="text-xs px-2 py-1 bg-green-600 text-white rounded" label="Approve" />
                                                        </x-splade-form>
                                                    @endif
                                                    @if($action->status !== 'skipped')
                                                        <x-splade-form preserve-scroll method="PUT" :default="['status' => 'skipped']" action="{{ route('admin.recovery.phase_actions.status', [$case->id, $displayPhase->id, $action->id]) }}" class="inline">
                                                            <x-splade-submit class="text-xs px-2 py-1 bg-gray-500 text-white rounded" label="Skip" />
                                                        </x-splade-form>
                                                    @endif
                                                    @if($action->status !== 'pending')
                                                        <x-splade-form preserve-scroll method="PUT" :default="['status' => 'pending']" action="{{ route('admin.recovery.phase_actions.status', [$case->id, $displayPhase->id, $action->id]) }}" class="inline">
                                                            <x-splade-submit class="text-xs px-2 py-1 bg-yellow-500 text-white rounded" label="Reset to pending" />
                                                        </x-splade-form>
                                                    @endif
                                                    <Link
                                                        href="{{ route('admin.recovery.phase_actions.destroy', [$case->id, $displayPhase->id, $action->id]) }}"
                                                        method="delete"
                                                        confirm="Remove this action?"
                                                        class="text-xs text-red-600 hover:underline self-center"
                                                    >Remove</Link>
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="3" class="px-3 py-6 text-center text-gray-500">No actions assigned for this step.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <div class="border-t border-gray-200 pt-4">
                            <h3 class="text-sm font-semibold text-gray-700 mb-3">Add action</h3>
                            <x-splade-form
                                preserve-scroll
                                reset-on-success
                                :default="['action_type_id' => '']"
                                method="POST"
                                action="{{ route('admin.recovery.phase_actions.store', [$case->id, $displayPhase->id]) }}"
                                class="flex flex-col sm:flex-row gap-3 items-end"
                            >
                                <div class="flex-1 w-full">
                                    <x-splade-select name="action_type_id" label="Action type" :options="$actionTypeOptions" placeholder="Select action..." required />
                                </div>
                                <x-splade-submit class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm mb-0.5" label="Add action" />
                            </x-splade-form>
                        </div>
                    </div>
                @else
                    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6 text-gray-500 text-sm">
                        No active phase for this case.
                    </div>
                @endif

                @if($case->transferRequests->isNotEmpty())
                    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
                        <h2 class="text-lg font-semibold text-gray-900 mb-4">Transfer Requests</h2>
                        <div class="space-y-4">
                            @foreach($case->transferRequests as $transfer)
                                <div class="border border-gray-200 rounded-lg p-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                                    <div>
                                        <p class="font-medium text-gray-900">{{ $transfer->phaseAction->actionType->name ?? 'Test transfer' }}</p>
                                        <p class="text-xs text-gray-500 mt-1">Submitted {{ $transfer->created_at?->format('M d, Y H:i') }}</p>
                                        <p class="text-sm capitalize text-gray-700 mt-1">Status: {{ $transfer->status }}</p>
                                    </div>
                                    <x-splade-form
                                        preserve-scroll
                                        method="PUT"
                                        :default="['status' => $transfer->status]"
                                        action="{{ route('admin.recovery.transfer_requests.update', [$case->id, $transfer->id]) }}"
                                        class="flex items-end gap-2"
                                    >
                                        <x-splade-select name="status" label="Update status" :options="['pending' => 'Pending', 'initiated' => 'Initiated', 'completed' => 'Completed', 'rejected' => 'Rejected']" />
                                        <x-splade-submit class="px-3 py-2 bg-blue-600 text-white rounded-lg text-sm" label="Save" />
                                    </x-splade-form>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>

            <div class="xl:col-span-4 space-y-6">
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Activity Log</h2>
                    <div class="space-y-3 max-h-56 overflow-y-auto">
                        @forelse($case->activities->take(30) as $activity)
                            <div class="border-l-2 border-gray-200 pl-3 py-1">
                                <p class="text-sm text-gray-800">{{ $activity->description }}</p>
                                <p class="text-xs text-gray-500 mt-0.5">{{ $activity->actor }} · {{ $activity->created_at?->format('M d, Y H:i') }}</p>
                            </div>
                        @empty
                            <p class="text-sm text-gray-500">No activity recorded yet.</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-admin-layout>

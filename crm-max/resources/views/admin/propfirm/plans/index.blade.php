<x-admin-layout>
    <x-slot name="header">
        Prop Firm — Challenge Packages
    </x-slot>
    <x-slot name="header_right">
        <div class="p-1- flex justify-between gap-2">
            <a href="{{ route('admin.propfirm.plans.create') }}" class="bg-blue-900 text-white items-center rounded flex gap-2 px-2 py-2">
                <span>{{ svg('heroicon-s-plus-small','h-5') }}</span>
                <span>Add New</span>
            </a>
        </div>
    </x-slot>

    <div class="mb-4 flex gap-2">
        <a href="{{ route('admin.propfirm.plans.index') }}" class="px-3 py-1.5 rounded-md text-sm font-medium bg-blue-900 text-white">Packages</a>
        <a href="{{ route('admin.propfirm.challenges.index') }}" class="px-3 py-1.5 rounded-md text-sm font-medium bg-gray-100 text-gray-700">Challenges</a>
        <a href="{{ route('admin.propfirm.payouts.index') }}" class="px-3 py-1.5 rounded-md text-sm font-medium bg-gray-100 text-gray-700">Payouts</a>
        <a href="{{ route('admin.propfirm.scaling-requests.index') }}" class="px-3 py-1.5 rounded-md text-sm font-medium bg-gray-100 text-gray-700">Scaling Requests</a>
        <a href="{{ route('admin.propfirm.promo-codes.index') }}" class="px-3 py-1.5 rounded-md text-sm font-medium bg-gray-100 text-gray-700">Discount Codes</a>
    </div>

    <div class="mb-4 flex items-center justify-between">
        <p class="text-sm text-gray-500">
            @if($showArchived)
                Archived packages. These are off sale and hidden from the main list, but their existing challenges are untouched.
            @else
                Packages with challenges on them can't be deleted, because their challenge history points at them. Archive those instead.
            @endif
        </p>

        @if($showArchived)
            <a href="{{ route('admin.propfirm.plans.index') }}" class="px-3 py-1.5 rounded-md text-sm font-medium bg-blue-900 text-white whitespace-nowrap">
                &larr; Back to active packages
            </a>
        @elseif($archivedCount > 0)
            <a href="{{ route('admin.propfirm.plans.index', ['archived' => 1]) }}" class="px-3 py-1.5 rounded-md text-sm font-medium bg-gray-100 text-gray-700 whitespace-nowrap">
                Show archived ({{ $archivedCount }})
            </a>
        @endif
    </div>

    <x-card>
        <table class="min-w-full divide-y divide-gray-200">
            <thead>
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Title</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Free Trial</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Account Size</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Price</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Profit Split</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Phases</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Challenges</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Active</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($plans as $plan)
                    <tr>
                        <td class="px-6 py-4 font-medium text-gray-800">
                            {{ $plan->title }}
                            @if($plan->isArchived())
                                <span class="ml-2 px-2 py-1 rounded-full text-xs font-semibold bg-gray-200 text-gray-600">Archived</span>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            @if($plan->free_trial_enabled)
                                <span class="px-2 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-700">Free Trial</span>
                            @else
                                <span class="text-gray-300">—</span>
                            @endif
                        </td>
                        <td class="px-6 py-4">${{ number_format($plan->account_size, 2) }}</td>
                        <td class="px-6 py-4">${{ number_format($plan->price, 2) }}</td>
                        <td class="px-6 py-4">{{ $plan->profit_split_pct }}%</td>
                        <td class="px-6 py-4">{{ $plan->phases->count() }}</td>
                        <td class="px-6 py-4">{{ $plan->challenges_count }}</td>
                        <td class="px-6 py-4">
                            @if($plan->isArchived())
                                {{-- An archived package must not be switchable back on sale;
                                     restore it first. --}}
                                <span class="text-xs text-gray-400">Off sale</span>
                            @else
                                <Link method="POST" href="{{ route('admin.propfirm.plans.toggle', $plan->id) }}">
                                    <label class="relative inline-flex items-center mb-5 cursor-pointer">
                                        <input @if($plan->is_active) checked @endif type="checkbox" value="" class="sr-only peer">
                                        <div class="w-9 h-5 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-blue-600"></div>
                                    </label>
                                </Link>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-2">
                                <a class="text-white bg-yellow-300 rounded-md px-2 py-2 inline-block" href="{{ route('admin.propfirm.plans.edit', $plan->id) }}">
                                    {{ svg('heroicon-s-pencil-square', 'h-4') }}
                                </a>

                                @if($plan->isArchived())
                                    <Link
                                        class="text-white bg-blue-900 rounded-md px-3 py-2 inline-block text-xs font-medium"
                                        confirm="Restore package"
                                        confirm-text="Restore this package? It comes back inactive, so you can review it before putting it back on sale."
                                        confirm-button="Restore"
                                        cancel-button="Cancel"
                                        href="{{ route('admin.propfirm.plans.unarchive', $plan->id) }}" method="POST">
                                        Restore
                                    </Link>
                                @elseif($plan->challenges_count > 0)
                                    {{-- Delete is impossible here (challenge history points at this
                                         plan), so don't offer it -- a dead button reads as "delete
                                         is broken". Archive is the real action. --}}
                                    <Link
                                        class="text-white bg-gray-600 rounded-md px-3 py-2 inline-block text-xs font-medium"
                                        confirm="Archive package"
                                        confirm-text="This package has {{ $plan->challenges_count }} challenge(s) on it, so it can't be deleted without breaking their history. Archiving takes it off sale and hides it from this list. Continue?"
                                        confirm-button="Archive"
                                        cancel-button="Cancel"
                                        href="{{ route('admin.propfirm.plans.archive', $plan->id) }}" method="POST">
                                        Archive
                                    </Link>
                                @else
                                    <Link
                                        class="text-white bg-red-600 rounded-md px-2 py-2 inline-block"
                                        confirm="Delete"
                                        confirm-text="Are you sure you want to delete this package?"
                                        confirm-button="Delete"
                                        cancel-button="Cancel"
                                        href="{{ route('admin.propfirm.plans.destroy', $plan->id) }}" method="DELETE">
                                        {{ svg('heroicon-o-trash','h-4') }}
                                    </Link>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="px-6 py-8 text-center text-gray-400">
                            @if($showArchived)
                                No archived packages.
                            @else
                                No challenge packages yet. Click "Add New" to create one.
                            @endif
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </x-card>
</x-admin-layout>

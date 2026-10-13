<x-admin-layout>
    <x-slot name="header">
        <div class="w-full bg-white shadow">
            <div class="flex flex-col gap-3 px-6 py-4 md:flex-row md:items-center md:justify-between">
                <Link href="{{ $profileType === 'affiliate' ? route('admin.users.affiliates') : (module_enabled('propfirm_enabled') ? route('admin.users.agents') : route('admin.users.managers')) }}"
                      class="inline-flex items-center gap-2 text-sm font-medium text-gray-600 hover:text-gray-900">
                    {{ svg('heroicon-o-arrow-left', 'w-4 h-4') }}
                    <span>{{ $profileType === 'affiliate' ? 'Affiliates' : (module_enabled('propfirm_enabled') ? 'Agents' : 'Managers') }}</span>
                </Link>

                <div class="flex gap-2">
                    <Link modal href="{{ route('admin.users.edit', $user->id) }}"
                          class="inline-flex items-center gap-2 rounded-md bg-yellow-300 px-3 py-2 text-sm font-medium text-gray-900">
                        {{ svg('heroicon-o-pencil-square', 'w-4 h-4') }}
                        <span>Edit</span>
                    </Link>

                    <Link href="{{ route('admin.notes.index', ['user_id' => $user->id]) }}" modal
                          class="inline-flex items-center gap-2 rounded-md bg-blue-600 px-3 py-2 text-sm font-medium text-white">
                        {{ svg('heroicon-o-chat-bubble-left-right', 'w-4 h-4') }}
                        <span>Add Note</span>
                    </Link>
                </div>
            </div>
        </div>
    </x-slot>

    <div class="space-y-6 py-6">
        <section class="rounded-xl bg-white p-6 shadow-sm">
            <div class="flex flex-col gap-6 lg:flex-row lg:items-start lg:justify-between">
                <div class="flex items-start gap-4">
                    <img class="h-16 w-16 rounded-full object-cover" src="{{ $user->avatar }}" alt="{{ $user->name }}" />

                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-orange-500">{{ ucfirst($profileType) }}</p>
                        <h1 class="mt-1 text-2xl font-semibold text-gray-900">{{ $user->name }}</h1>
                        <div class="mt-2 space-y-1 text-sm text-gray-600">
                            <p>{{ $user->email }}</p>
                            <p>{{ $user->phone ?: 'No phone number' }}</p>
                            <p>{{ $user->country ?: 'No country set' }}</p>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3 md:min-w-[360px]">
                    <div class="rounded-lg border border-gray-200 bg-gray-50 p-4">
                        <p class="text-xs uppercase tracking-wide text-gray-500">Customers</p>
                        <p class="mt-2 text-2xl font-semibold text-gray-900">{{ $tabCounts['customers'] }}</p>
                    </div>
                    <div class="rounded-lg border border-gray-200 bg-gray-50 p-4">
                        <p class="text-xs uppercase tracking-wide text-gray-500">Leads</p>
                        <p class="mt-2 text-2xl font-semibold text-gray-900">{{ $tabCounts['leads'] }}</p>
                    </div>
                    <div class="rounded-lg border border-gray-200 bg-gray-50 p-4">
                        <p class="text-xs uppercase tracking-wide text-gray-500">Notes</p>
                        <p class="mt-2 text-2xl font-semibold text-gray-900">{{ $tabCounts['notes'] }}</p>
                    </div>
                    <div class="rounded-lg border border-gray-200 bg-gray-50 p-4">
                        <p class="text-xs uppercase tracking-wide text-gray-500">Last Seen</p>
                        <p class="mt-2 text-sm font-medium text-gray-900">{{ $user->is_online ? 'Online' : ($user->last_seen ? $user->last_seen->diffForHumans() : 'Never') }}</p>
                    </div>
                </div>
            </div>
        </section>

        <section class="overflow-hidden rounded-xl bg-white shadow-sm">
            <div class="border-b border-gray-200 px-4">
                <nav class="flex gap-2 overflow-x-auto py-3">
                    @foreach($tabs as $tab)
                        @php $isActive = $activeTab === $tab['id']; @endphp
                        <Link
                            href="{{ route('admin.users.managed_profile', $user->id) }}?tab={{ $tab['id'] }}"
                            class="inline-flex items-center gap-2 rounded-full px-4 py-2 text-sm font-medium {{ $isActive ? 'bg-gray-900 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}"
                        >
                            <span>{{ $tab['title'] }}</span>
                            <span class="rounded-full {{ $isActive ? 'bg-white/20 text-white' : 'bg-white text-gray-600' }} px-2 py-0.5 text-xs">
                                {{ $tabCounts[$tab['id']] }}
                            </span>
                        </Link>
                    @endforeach
                </nav>
            </div>

            <div class="p-4 md:p-6">
                @include('admin.users.managed_tabs.'.$activeTab, ['user' => $user, 'records' => $records, 'profileType' => $profileType])
            </div>
        </section>
    </div>
</x-admin-layout>

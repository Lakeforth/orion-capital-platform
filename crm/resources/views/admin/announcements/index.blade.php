@php
    $maxActive = \App\Http\Controllers\Admin\AnnouncementController::MAX_ACTIVE;
    $atLimit = $activeCount >= $maxActive;
@endphp

<x-admin-layout>
    <x-slot name="header">
        News &amp; Announcement
    </x-slot>

    <x-slot name="header_right">
        <div>
            <Link class="bg-orange-500 hover:bg-orange-600 text-white rounded-xl px-4 py-3" modal
                  href="{{ route('admin.announcements.create') }}">
                + Create
            </Link>
        </div>
    </x-slot>

    {{-- Section on/off toggle --}}
    <div class="mb-4 rounded-lg border p-4 {{ $sectionEnabled ? 'border-gray-200 bg-white' : 'border-yellow-300 bg-yellow-50' }}">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <p class="font-semibold text-gray-900">Announcements Section</p>
                <p class="text-sm text-gray-500">
                    {{ $sectionEnabled ? 'The section is visible in the client trading area.' : 'The section is hidden from the client trading area.' }}
                </p>
            </div>
            <Link
                method="POST"
                href="{{ route('admin.announcements.section.toggle') }}"
                class="{{ 'inline-flex items-center gap-2 rounded-lg px-4 py-2 text-sm font-medium transition-colors ' . ($sectionEnabled ? 'bg-gray-100 hover:bg-gray-200 text-gray-700' : 'bg-green-500 hover:bg-green-600 text-white') }}">
                {{ svg($sectionEnabled ? 'heroicon-o-eye-slash' : 'heroicon-o-eye', 'h-4 w-4') }}
                {{ $sectionEnabled ? 'Disable Section' : 'Enable Section' }}
            </Link>
        </div>
        @if(! $sectionEnabled)
            <p class="mt-2 text-sm font-medium text-yellow-700">
                Client apps are receiving an "enabled: false" signal — the section will not be rendered.
            </p>
        @endif
    </div>

    <div class="mb-4 rounded-lg border p-4 {{ $atLimit ? 'border-red-300 bg-red-50' : 'border-orange-200 bg-orange-50' }}">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <div>
                <p class="font-semibold text-gray-900">Active Announcement Limit</p>
                <p class="text-sm text-gray-600">
                    Your platform can have up to {{ $maxActive }} simultaneous active announcements on the client terminal.
                </p>
            </div>
            <span class="rounded-full px-3 py-1 text-sm font-medium {{ $atLimit ? 'bg-red-600 text-white' : 'bg-orange-500 text-white' }}">
                {{ $activeCount }} / {{ $maxActive }} Items Active
            </span>
        </div>
        <div class="mt-3 h-2 w-full overflow-hidden rounded-full bg-gray-200">
            <div class="h-full rounded-full {{ $atLimit ? 'bg-red-500' : 'bg-orange-500' }}"
                 style="width: {{ min(100, ($activeCount / $maxActive) * 100) }}%"></div>
        </div>
        @if($atLimit)
            <p class="mt-2 text-sm font-medium text-red-700">
                Active limit reached — deactivate an item before adding more.
            </p>
        @endif
    </div>

    <x-card>
        <div class="mx-auto">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-2">
                <x-splade-table class="mt-5" :for="$data">

                    <x-splade-cell title as="$item">
                        <div>
                            <p class="font-semibold text-gray-900">{{ $item->title }}</p>
                            @if($item->body)
                                <p class="text-sm text-gray-500 line-clamp-1">{{ Str::limit(strip_tags($item->body), 80) }}</p>
                            @endif
                        </div>
                    </x-splade-cell>

                    <x-splade-cell is_active as="$item">
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium {{ $item->is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' }}">
                            <span class="w-1.5 h-1.5 rounded-full {{ $item->is_active ? 'bg-green-500' : 'bg-gray-400' }}"></span>
                            {{ $item->is_active ? 'Live' : 'Draft' }}
                        </span>
                    </x-splade-cell>

                    <x-splade-cell created_at as="$item">
                        {{ $item->created_at?->format('M d, Y') }}
                    </x-splade-cell>

                    <x-splade-cell actions as="$item">
                        <div class="flex items-center gap-2">
                            <Link
                                class="transition-colors {{ $item->is_active ? 'text-green-500 hover:text-gray-400' : 'text-gray-400 hover:text-green-500' }}"
                                href="{{ route('admin.announcements.toggle', $item->id) }}"
                                method="POST"
                                title="{{ $item->is_active ? 'Deactivate' : 'Activate' }}">
                                {{ svg('heroicon-o-power', 'h-4 w-4') }}
                            </Link>

                            <Link
                                class="text-gray-500 hover:text-orange-500 transition-colors"
                                modal
                                href="{{ route('admin.announcements.edit', $item->id) }}">
                                {{ svg('heroicon-o-pencil', 'h-4 w-4') }}
                            </Link>

                            <Link
                                class="text-gray-500 hover:text-red-500 transition-colors"
                                confirm="Delete announcement"
                                confirm-text="Are you sure you want to delete this announcement?"
                                confirm-button="Delete"
                                cancel-button="Cancel"
                                href="{{ route('admin.announcements.destroy', $item->id) }}"
                                method="DELETE">
                                {{ svg('heroicon-o-trash', 'h-4 w-4') }}
                            </Link>
                        </div>
                    </x-splade-cell>
                </x-splade-table>
            </div>
        </div>
    </x-card>
</x-admin-layout>

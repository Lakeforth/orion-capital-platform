<x-admin-layout>

    <x-slot name="header">

        <div class="w-full bg-white shadow">
            <div class="flex justify-between items-center px-6 py-2">
                <h1 class="text-lg font-bold text-gray-800">Leads</h1>

                <div class="flex gap-3">

                    <Link class="border-orange-500 border items-center rounded flex gap-2 px-4 py-2 text-sm text-orange-600 hover:bg-orange-50 transition-colors"
                          modal href="{{ route('admin.data.labels') }}">
                    <span>{{ svg('heroicon-s-cog-8-tooth','h-4') }}</span>
                    <span>Labels Setup</span>
                    </Link>

                    <Link class="bg-orange-500 hover:bg-orange-600 text-white items-center rounded flex gap-2 px-4 py-2 text-sm transition-colors"
                          modal href="{{ route('admin.users.create') }}?role=lead">
                    <span>{{ svg('heroicon-s-plus-small','h-4') }}</span>
                    <span>Add Lead</span>
                    </Link>

                    <Link class="bg-green-500 hover:bg-green-600 text-white items-center rounded flex gap-2 px-3 py-2 text-sm transition-colors" modal
                          href="{{ route('admin.users.csv_upload') }}?role=lead">
                    <span>Import Leads</span>
                    </Link>
                </div>

            </div>
        </div>

    </x-slot>


    <x-card>
        <x-splade-table class="mt-5" :for="$data">

            <x-splade-cell phone as="$item">
                <a class="text-blue-600 hover:text-blue-800 text-sm" href="tel:{{$item->phone}}">{{ mask_phone($item->phone) }}</a>
            </x-splade-cell>

            <x-splade-cell ip_address as="$item">
                @if($item->ip_address)
                    <div class="space-y-1">
                        <span class="font-mono text-xs text-gray-500 block truncate max-w-[130px]" title="{{ $item->ip_address }}">
                            {{ $item->ip_address }}
                        </span>
                        @if($item->country)
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-50 text-blue-700 border border-blue-100">
                                {{ $item->country }}
                            </span>
                        @endif
                    </div>
                @else
                    <span class="text-gray-300 text-sm">—</span>
                @endif
            </x-splade-cell>

            <x-splade-cell device_type as="$item">
                @if($item->device_type)
                    @php
                        $deviceIcon = match(strtolower($item->device_type)) {
                            'mobile'  => '📱',
                            'tablet'  => '📟',
                            default   => '🖥️',
                        };
                    @endphp
                    <span class="inline-flex items-center gap-1 text-xs text-gray-600 capitalize">
                        {{ $deviceIcon }} {{ $item->device_type }}
                    </span>
                @else
                    <span class="text-gray-300 text-sm">—</span>
                @endif
            </x-splade-cell>

            <x-splade-cell browser_type as="$item">
                @if($item->browser_type)
                    <span class="text-xs text-gray-600">{{ $item->browser_type }}</span>
                @else
                    <span class="text-gray-300 text-sm">—</span>
                @endif
            </x-splade-cell>

            <x-splade-cell notes_count as="$item">
                <Link modal class="inline-flex items-center gap-1 text-sm text-blue-600 hover:text-blue-800" href="{{ route('admin.notes.index') }}?user_id={{ $item->id }}">
                    <span class="font-medium">{{ $item->notes_count }}</span>
                    <span class="text-gray-400 text-xs">notes</span>
                    {{ svg('heroicon-s-pencil-square', 'h-3 text-gray-400') }}
                </Link>
            </x-splade-cell>

            <x-splade-cell manager_id as="$item">
                @if(is_admin())
                    @if($item->manager)
                        <Link modal href="{{ route('admin.users.assign_manager',$item->id) }}" class="inline-flex items-center gap-1 text-sm text-gray-800 hover:text-blue-600">
                            {{ $item->manager->name }}
                            {{ svg('heroicon-s-pencil-square', 'h-3 text-gray-400') }}
                        </Link>
                    @else
                        <Link modal href="{{ route('admin.users.assign_manager',$item->id) }}" class="inline-flex items-center gap-1 text-xs text-gray-400 hover:text-gray-600 italic">
                            Unassigned
                            {{ svg('heroicon-s-pencil-square', 'h-3') }}
                        </Link>
                    @endif
                @else
                    <span class="{{ $item->manager ? 'text-sm text-gray-800' : 'text-xs text-gray-400 italic' }}">
                        {{ $item->manager?->name ?? 'Unassigned' }}
                    </span>
                @endif
            </x-splade-cell>

            <x-splade-cell created_by as="$item">
                @if($item->creator)
                    <span class="text-sm text-gray-700">{{ $item->creator->name }}</span>
                @else
                    <span class="text-gray-300 text-sm">—</span>
                @endif
            </x-splade-cell>

            <x-splade-cell actions as="$item">
                @include('admin.users.leads.mini_actions',['item' => $item])
            </x-splade-cell>

            <x-splade-cell convert as="$item">
                <Link
                    class="inline-flex items-center px-3 py-1.5 text-xs font-semibold text-white bg-green-600 hover:bg-green-700 rounded-md transition-colors"
                    confirm="Convert to Customer"
                    confirm-text="Are you sure you want to convert this lead to a customer account?"
                    confirm-button="Yes, Convert"
                    cancel-button="Cancel"
                    href="{{ route('admin.users.convert', $item->id) }}" method="POST">
                    Convert
                </Link>
            </x-splade-cell>

        </x-splade-table>

    </x-card>
</x-admin-layout>

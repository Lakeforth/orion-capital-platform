<x-admin-layout>
    <x-slot name="header">
        Email Templates
    </x-slot>
    <x-slot name="header_right">
        <div class="p-1- flex justify-between gap-2">
            <Link class="bg-blue-900 text-white items-center rounded flex gap-2 px-2 py-2"
                  href="{{ route('admin.welcome-messages.create') }}">
                <span>{{ svg('heroicon-s-plus-small','h-5') }}</span>
                <span>New Template</span>
            </Link>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    @if ($messages->isEmpty())
                        <p class="text-gray-500 text-center py-10">No email templates yet. Create one to get started.</p>
                    @else
                        <table class="w-full text-left">
                            <thead>
                                <tr class="border-b text-gray-600 text-sm uppercase tracking-wide">
                                    <th class="pb-3 pr-6">Name</th>
                                    <th class="pb-3 pr-6">Language</th>
                                    <th class="pb-3 pr-6">Subject</th>
                                    <th class="pb-3">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($messages as $message)
                                    <tr class="border-b last:border-0">
                                        <td class="py-4 pr-6 font-medium text-gray-900">{{ $message->name }}</td>
                                        <td class="py-4 pr-6">
                                            <span class="rounded-full px-3 py-1 text-sm font-medium {{ $message->language === 'French' ? 'bg-blue-100 text-blue-700' : 'bg-green-100 text-green-700' }}">
                                                {{ $message->language }}
                                            </span>
                                        </td>
                                        <td class="py-4 pr-6 text-gray-600 text-sm max-w-xs truncate">{{ $message->subject }}</td>
                                        <td class="py-4">
                                            <div class="flex items-center gap-3">
                                                <x-splade-link href="{{ route('admin.welcome-messages.edit', $message) }}" class="text-blue-600 hover:underline text-sm">Edit</x-splade-link>
                                                <x-splade-form method="DELETE" action="{{ route('admin.welcome-messages.destroy', $message) }}" confirm="Delete this template?" confirm-text="This cannot be undone.">
                                                    <button type="submit" class="text-red-500 hover:underline text-sm">Delete</button>
                                                </x-splade-form>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-admin-layout>

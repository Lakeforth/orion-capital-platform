<x-admin-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Edit Template: {{ $message->name }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-8">
                <x-splade-form :default="$message" method="PUT" class="space-y-4" action="{{ route('admin.welcome-messages.update', $message) }}">
                    <x-splade-input name="name" label="Template Name" required />
                    <x-splade-select name="language" label="Language" required :options="['English' => 'English', 'French' => 'French']" />
                    <x-splade-input name="subject" label="Email Subject" required />

                    <div>
                        <x-splade-textarea name="body" label="Email Body (HTML)" rows="16" required />
                        <p class="mt-2 text-sm text-gray-500">
                            Available placeholders:
                            <code class="bg-gray-100 px-1 rounded">@{{first_name}}</code>
                            <code class="bg-gray-100 px-1 rounded">@{{email}}</code>
                            <code class="bg-gray-100 px-1 rounded">@{{password}}</code>
                            <code class="bg-gray-100 px-1 rounded">@{{login_url}}</code>
                            <code class="bg-gray-100 px-1 rounded">@{{site_name}}</code>
                            <code class="bg-gray-100 px-1 rounded">@{{support_email}}</code>
                        </p>
                    </div>

                    <div class="flex items-center justify-end gap-4 pt-4">
                        <x-splade-link href="{{ route('admin.welcome-messages.index') }}" class="text-gray-500 hover:text-gray-700">Cancel</x-splade-link>
                        <x-splade-submit label="Update Template" />
                    </div>
                </x-splade-form>
            </div>
        </div>
    </div>
</x-admin-layout>

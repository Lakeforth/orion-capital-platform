<x-admin-layout>
    <x-slot name="header">
        Live Chat
    </x-slot>

    <div style="height: calc(100vh - 170px); min-height: 480px; border-radius: 12px; overflow: hidden; border: 1px solid #e5e7eb;">
        <iframe src="{{ $chatUrl }}" title="Live Chat" style="width: 100%; height: 100%; border: 0;"></iframe>
    </div>
</x-admin-layout>

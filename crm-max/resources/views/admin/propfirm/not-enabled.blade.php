<x-admin-layout>
    <x-slot name="header">
        Prop Firm
    </x-slot>

    <x-card>
        <div class="flex flex-col items-center text-center py-16 px-6">
            <div class="w-16 h-16 rounded-full bg-blue-50 flex items-center justify-center mb-4 text-3xl">
                🏆
            </div>
            <h2 class="text-xl font-bold text-gray-800">Prop Firm module is not enabled</h2>
            <p class="text-gray-500 text-sm mt-2 max-w-md">
                Turn on the Prop Firm module for this tenant to start creating challenge plans, and to
                manage challenges, payouts, scaling requests, and support tickets from here.
            </p>
            <a href="{{ route('admin.settings.modules') }}" class="mt-6 px-5 py-2.5 rounded-lg bg-blue-900 text-white text-sm font-semibold">
                Enable Prop Firm as a Module
            </a>
        </div>
    </x-card>
</x-admin-layout>

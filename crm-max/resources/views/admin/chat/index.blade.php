<x-admin-layout>

    <x-card>
        <div class="">


            <a
                @if(! module_enabled('propfirm_enabled')) target="_blank" @endif
                class="text-white bg-green-700 rounded-md  px-2 py-2 mr-2"
                href="{{ module_enabled('propfirm_enabled') ? route('admin.live_chat') : route('admin.chat_login') }}">
                Chat Page
            </a>

        </div>
    </x-card>
</x-admin-layout>

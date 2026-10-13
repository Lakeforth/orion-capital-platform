<div class="">
    <div class="flex h-screen flex-1 flex-col justify-between border-e bg-white">
        <div class="px-4 py-6">
            <ul class="mt-14 space-y-1">
                <li>
                    <Link

                        href="{{ route('admin.settings.index') }}"
                        class="block rounded-lg {{ is_active('admin.settings.index') ? 'bg-gray-100' : '' }} px-4 py-2 text-sm font-medium text-gray-700"
                    >
                        Website Settings
                    </Link>
                </li>

                <li>
                    <Link

                        href="{{ route('admin.settings.trade') }}"
                        class="block rounded-lg {{ is_active('admin.settings.trade') ? 'bg-gray-100' : '' }} px-4 py-2 text-sm font-medium text-gray-700"
                    >
                        Trade Settings
                    </Link>
                </li>


                <li>
                    <Link

                        href="{{ route('admin.settings.email') }}"
                        class="block rounded-lg {{ is_active('admin.settings.email') ? 'bg-gray-100' : '' }} px-4 py-2 text-sm font-medium text-gray-700"
                    >
                        Email Settings
                    </Link>
                </li>




            </ul>
        </div>
    </div>
</div>

<x-admin-layout>


    <x-slot name="header">

        <div class="w-full bg-white shadow">
            <div class="flex justify-between items-center px-6 py-2">
                <h1 class="text-lg capitalize -font-bold text-gray-800">Managers</h1>

                <Link class="bg-orange-500 hover:bg-orange-600  text-white items-center rounded flex gap-2  px-4 py-3"
                      modal href="{{ route('admin.users.create') }}?role=manager">
                <span>{{ svg('heroicon-s-plus-small','h-5') }}</span>
                <span>  Add Manager </span>
                </Link>
            </div>
        </div>

    </x-slot>

    <x-card>
        <x-splade-table class="mt-5" :for="$data">

            <x-splade-cell avatar as="$item">
                <div class="flex items-center gap-3">
                    <img class="h-10 w-10 rounded-full" src="{{ $item->avatar }}" />
                </div>
            </x-splade-cell>

            <x-splade-cell first_name as="$item">
                <Link
                    href="{{ route('admin.users.managed_profile', ['id' => $item->id, 'tab' => 'customers']) }}"
                    class="font-medium text-[#0d63cc] hover:underline"
                >
                    {{ $item->name }}
                </Link>
            </x-splade-cell>

            <x-splade-cell customers_count as="$item">
                <Link
                    href="{{ route('admin.users.managed_profile', ['id' => $item->id, 'tab' => 'customers']) }}"
                    class="inline-flex min-w-10 justify-center rounded-full bg-blue-50 px-3 py-1 text-sm font-semibold text-blue-700 hover:bg-blue-100"
                >
                    {{ $item->customers_count }}
                </Link>
            </x-splade-cell>

            <x-splade-cell leads_count as="$item">
                <Link
                    href="{{ route('admin.users.managed_profile', ['id' => $item->id, 'tab' => 'leads']) }}"
                    class="inline-flex min-w-10 justify-center rounded-full bg-orange-50 px-3 py-1 text-sm font-semibold text-orange-700 hover:bg-orange-100"
                >
                    {{ $item->leads_count }}
                </Link>
            </x-splade-cell>

            <x-splade-cell last_seen as="$item">
               @if(!$item->last_seen)
                   <p>Not seen</p>
                @else
                   {{ $item->last_seen->diffForHumans() }}
               @endif
            </x-splade-cell>


            <x-splade-cell phone as="$item">
                <div class="flex gap-2 items-center">
                    <a href="tel:{{ $item->phone }}">{{ $item->phone }}</a>

                    <a target="_blank" href="https://wa.me/{{ $item->phone }}?text=Hello">
                        @svg('gmdi-whatsapp-tt', 'h-5')
                    </a>
                </div>
            </x-splade-cell>
            <x-splade-cell email as="$item">
                <div>
                    <p class="mb-1">{{ $item->email }}</p>
                    @if($item->email_verified_at)
                        <span class="bg-green-500 text-white text-sm px-2 py-1 rounded-lg">Verified</span>
                    @else
                        <span class="bg-red-500 mr-3 mt-1 text-sm text-white px-2 py-1 rounded-lg">Unverified</span>

                        <Link method="post"
                              href="{{ route('admin.users.verify_email', $item->id) }}"
                              class="text-green-500">{{ 'Verify' }}</Link>

{{--                        <Link--}}
{{--                            method="post"--}}
{{--                            href="{{ route('admin.users.verify_email', $item->id) }}"--}}
{{--                            confirm="Verify this email"--}}
{{--                            confirm-text="Are you sure you want to verify this email?, Dont do this if the email is not valid"--}}
{{--                            confirm-button="Verify"--}}
{{--                            cancel-button="Cancel"--}}
{{--                            class="text-green-500">Verify</Link>--}}
                    @endif
                </div>

            </x-splade-cell>
            <x-splade-cell actions as="$item">
                @if(is_admin())
                    <Link
                        class="text-white bg-green-700 rounded-md  px-2 py-2 mr-2"
                        confirm="Login as {{ $item->name }}"
                        confirm-text="Are you sure you want to login as this admin?"
                        confirm-button="Login"
                        cancel-button="Cancel"
                        href="{{ route('admin.users.login_as', $item->id) }}" method="POST">
                        {{ svg('heroicon-o-lock-open','h-4') }}
                    </Link>
                @endif



@if(can_delete_action())
                <Link
                    class="text-white bg-red-600 rounded-md  px-2 py-2 mr-2"
                    confirm="Delete"
                    confirm-text="Are you sure you want to delete this user?"
                    confirm-button="Delete"
                    cancel-button="Cancel"
                    href="{{ route('admin.users.destroy', $item->id) }}" method="DELETE">
                    {{ svg('heroicon-o-trash','h-4') }}
                </Link>
@endif


                <Link
                    class="notranslate text-white bg-yellow-300 rounded-md  px-2 py-2 mr-2"
                    translate="no"
                    modal
                    href="{{ route('admin.users.edit', $item->id) }}">
                    {{ svg('heroicon-s-pencil-square', 'h-4') }}
                </Link>


            </x-splade-cell>
        </x-splade-table>
    </x-card>
</x-admin-layout>

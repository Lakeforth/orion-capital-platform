<x-admin-layout>


    <x-slot name="header">

        <div class="w-full bg-white shadow">
            <div class="flex justify-between items-center px-6 py-2">
                <h1 class="text-lg capitalize -font-bold text-gray-800">{{ $title }}</h1>

                <Link class="bg-orange-500 hover:bg-orange-600  text-white items-center rounded flex gap-2  px-4 py-3"
                      modal href="{{ route('admin.users.create') }}?role={{ $role }}">
                <span>{{ svg('heroicon-s-plus-small','h-5') }}</span>
                <span>  Add {{ isset($role) ? $role : 'User' }} </span>
                </Link>
            </div>
        </div>

    </x-slot>



    <x-card>
        <x-splade-table class="mt-5" :role="$role" :for="$data">

            <x-splade-cell avatar as="$item">

                <div class="flex items-center gap-3">
                    <img class="h-10 w-10 rounded-full" src="{{ $item->avatar }}" />
                </div>
            </x-splade-cell>

            <x-splade-cell first_name as="$item">
{{--                <Link modal href="{{ route('admin.users.show', $item->id) }}" class="text-green-500">{{ $item->name }}</Link>--}}

                <p class="text-green-500">{{ $item->name }}</p>
            </x-splade-cell>

            <x-splade-cell last_seen as="$item">
               @if(!$item->last_seen)
                   <p>Not seen</p>
                @else
                   {{ $item->last_seen->diffForHumans() }}
               @endif
            </x-splade-cell>
            <x-splade-cell has_wallet as="$item">
               @if($item->has_wallet)
                   <p>Yes</p>
                @else
                   <p>No</p>
               @endif
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
            <x-splade-cell is_online as="$item">
                @if($item->is_online)
                    <span class="bg-green-500 text-white text-sm px-2 py-1 rounded-lg">Yes</span>
                @else
                    <span class="bg-red-500 text-white text-sm px-2 py-1 rounded-lg">No</span>
                @endif
            </x-splade-cell>


            <x-splade-cell actions as="$item" role="$role">

                @if(is_admin()  || session('selected_user_role') == 'support')

@if(request()->routeIs('admin.users.affiliates'))
                    <Link
                        class="text-white bg-blue-600 rounded-md  px-2 py-2 mr-2"
                        href="{{ route('admin.users.managed_profile', $item->id) }}">
                        {{ svg('heroicon-o-eye','h-4') }}
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

                @endif

                @if(session('selected_user_role') == 'support')
                    <a
                        target="_blank"
                        class="text-white bg-blue-500 rounded-md  px-2 py-2 mr-2"
                        href="{{ route('admin.user_chat_login', $item->id) }}">
                        Chat Login
                    </a>
                @else

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



            </x-splade-cell>

        </x-splade-table>
    </x-card>
</x-admin-layout>

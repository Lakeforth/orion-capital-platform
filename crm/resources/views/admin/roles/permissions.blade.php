<x-admin-layout>
    <x-slot name="header">
        <p class="text-capitalize">{{ $role->name }} permissions</p>
    </x-slot>

    <x-slot name="header_right">
        <Link method="POST" class="bg-blue-900 text-white items-center
        rounded flex gap-2  px-4 py-3"
              href="{{ route('admin.permissions.generate') }}">
        <span>Generate Permissions</span>
        </Link>
    </x-slot>


    <x-card>

            <div>
{{--                @foreach($permissions as $item)--}}
{{--                    <p>{{ $item }}</p>--}}
{{--                @endforeach--}}

                    <x-splade-form action="{{ route('admin.roles.permissions.update') }}" :default="$role">
{{--                        <x-splade-checkboxes class="grid grid-cols-4 gap-3" name="permissions" :options="$permissions" relation />--}}

                        <x-splade-input type="hidden" name="id" />

                        <x-splade-group name="permissions" relation>

                            @foreach($permissionss as $i => $items)
                                <div class="mt-5">
                                    <p class="capitalize font-bold">{{ $i }}</p>
                                    <div class="grid grid-cols-4">
                                        @foreach($items as $item)
                                            <x-splade-checkbox name="permissions[]" label="{{ $item['name'] }}" relation value="{{ $item['id'] }}"  />
                                        @endforeach
                                    </div>

                                </div>

                            @endforeach

                        </x-splade-group>


                        <x-splade-submit class="mt-4" label="Update" />

                    </x-splade-form>
            </div>
    </x-card>
</x-admin-layout>

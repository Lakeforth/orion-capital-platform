<x-admin-layout>
    <x-slot name="header">
        @php
            $userListRoutes = [
                'customer' => ['label' => 'Customers', 'route' => 'admin.users.customers'],
                'user' => ['label' => 'Customers', 'route' => 'admin.users.customers'],
                'lead' => ['label' => 'Leads', 'route' => 'admin.users.leads'],
                'agent' => ['label' => 'Agents', 'route' => 'admin.users.agents'],
                'manager' => ['label' => 'Managers', 'route' => 'admin.users.managers'],
            ];
            $listInfo = $userListRoutes[$editUser->role ?? ''] ?? ['label' => 'Admins', 'route' => 'admin.users.admins'];
        @endphp
        <nav class="flex items-center gap-2 text-sm sm:text-base" aria-label="Breadcrumb">
            <Link href="{{ route($listInfo['route']) }}" class="flex items-center gap-2 text-gray-600 hover:text-gray-900">
                <span>{{ svg('heroicon-m-arrow-small-left', 'h-5') }}</span>
                <span>{{ $listInfo['label'] }}</span>
            </Link>
            <span class="text-gray-400">/</span>
            <Link href="{{ route('admin.users.show', $editUser->id) }}" class="text-gray-600 hover:text-gray-900">
                {{ $editUser->name }}
            </Link>
            <span class="text-gray-400">/</span>
            <span class="text-gray-800 font-medium">Edit</span>
        </nav>
    </x-slot>
    <x-splade-modal max-width="4xl">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Editing {{ $editUser->name }}
        </h2>
        <div class="py-12">

            <x-splade-form :default="$editUser" class="space-y-2" method="PUT" action="{{ $updateAction }}">

                @if(request()->has('total_deposit'))

                    <x-splade-input required label="Total Deposit" name="total_deposit" />
                    <x-splade-input type="hidden" name="email" />
                    <x-splade-input type="hidden" name="phone" />

                @else
                    @include('admin.users.form',['method' => 'update', 'user' => $editUser])
                @endif

                <x-splade-submit class="mt-3" class="mt-3" label="Update" />

            </x-splade-form>

        </div>
    </x-splade-modal>
</x-admin-layout>

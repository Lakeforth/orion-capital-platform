<x-admin-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl">Customers</h2></x-slot>
    <div class="mini-panel p-4">
        <form method="GET" action="{{ route('admin.users.customers') }}" class="flex gap-2 mb-4">
            <label for="customer-search" class="sr-only">Search customers</label>
            <input id="customer-search" name="search" value="{{ $search }}" placeholder="Search by name, email or phone" class="rounded border border-gray-300 px-3 py-2 flex-1" />
            <button type="submit" class="mini-action-btn">Search</button>
        </form>
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm text-start">
                <thead><tr><th class="p-3 text-start">Name</th><th class="p-3 text-start">Email</th><th class="p-3 text-start">Phone</th><th class="p-3 text-start">Actions</th></tr></thead>
                <tbody>
                @forelse($customers as $customer)
                    <tr class="border-t border-gray-200">
                        <td class="p-3">{{ $customer->name }}</td><td class="p-3">{{ $customer->email }}</td><td class="p-3">{{ $customer->phone }}</td>
                        <td class="p-3"><Link href="{{ route('admin.users.show', $customer->id) }}" class="text-blue-600 hover:underline">View customer</Link>
                            <a href="{{ route('admin.frontend.login', $customer->id) }}" target="_blank" rel="noopener" class="text-blue-600 hover:underline ms-4">Login As</a></td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="p-6 text-center">No customers found.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $customers->links() }}</div>
    </div>
</x-admin-layout>

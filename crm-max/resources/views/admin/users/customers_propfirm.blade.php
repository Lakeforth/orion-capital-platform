<x-admin-layout>
    <x-slot name="header">
        <div class="mini-customer-page space-y-0">
            @include('admin.users.partials.header')

            <div class="mini-customer-toolbar flex items-center justify-between px-3 py-2 bg-[#eef2f7] border-b border-gray-200">
                <div></div>

                <div class="flex items-center gap-2 {{ $activeTab !== 'customers' ? 'hidden' : '' }}">
                    <Link href="{{ route('admin.data.labels') }}" modal
                          class="inline-flex items-center gap-2 border border-orange-500 text-orange-600 hover:bg-orange-50 px-3 py-2 rounded-lg font-semibold">
                        {{ svg('heroicon-s-cog-8-tooth', 'w-4 h-4') }}
                        <span>Label Setup</span>
                    </Link>

                    <Link href="{{ route('admin.users.assign_managers') }}" modal
                          class="inline-flex items-center gap-2 bg-[#2563eb] hover:bg-[#1d4ed8] text-white px-3 py-2 rounded-lg font-semibold">
                        <span>Bulk Assign</span>
                    </Link>

                    <Link href="{{ route('admin.users.create') }}?role=customer" modal
                          class="mini-add-customer-btn inline-flex items-center gap-2 bg-[#ff7f00] hover:bg-[#ef7700] text-white px-3 py-2 rounded-lg font-semibold">
                        <span class="inline-flex items-center justify-center w-5 h-5 rounded-full border border-white/70">+</span>
                        <span>Add Customer</span>
                    </Link>
                </div>
            </div>
        </div>
    </x-slot>

    <div class="mini-customer-page">
        @if($activeTab === 'ai_trading')
            @include('admin.users.partials.ai_trading_schedule', [
                'scheduledTradeTable' => $scheduledTradeTable,
            ])
        @elseif($activeTab === 'ai_recommendations')
            @include('admin.users.partials.ai_recommendations', [
                'aiRecommendationTable' => $aiRecommendationTable,
                'redirectTo' => 'customers_ai_recommendations',
            ])
        @else
            <x-card>
                {{-- Prop Firm's own version of customers_mini_v2.blade.php --
                     kept out of scroll range by dropping Finances/Trades
                     entirely and folding Identity/Email/Notes/Source/Campaign
                     into a single "..." popover cell (more_info_propfirm)
                     instead of Splade's column-manager toggle, which wasn't
                     actually surfacing a usable UI to unhide columns. --}}
                <x-splade-table class="mini-customer-table mt-1" :for="$data">
                <x-splade-cell first_name as="$item">
                    @include('admin.users.mini.cells.user_propfirm', ['item' => $item])
                </x-splade-cell>

                <x-splade-cell phone as="$item">
                    @include('admin.users.mini.cells.phone_propfirm', ['item' => $item])
                </x-splade-cell>

                <x-splade-cell status as="$item">
                    @include('admin.users.mini.cells.status', ['item' => $item])
                </x-splade-cell>

                <x-splade-cell manager_id as="$item">
                    @include('admin.users.partials.manager', ['item' => $item])
                </x-splade-cell>

                <x-splade-cell created_at as="$item">
                    @include('admin.users.mini.cells.date', ['item' => $item])
                </x-splade-cell>

                <x-splade-cell more_info as="$item">
                    @include('admin.users.mini.cells.more_info_propfirm', ['item' => $item])
                </x-splade-cell>

                <x-splade-cell actions as="$item">
                    @include('admin.users.mini.cells.actions', ['item' => $item])
                </x-splade-cell>
                </x-splade-table>
            </x-card>
        @endif
    </div>
</x-admin-layout>

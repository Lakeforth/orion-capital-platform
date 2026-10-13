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
            <section class="mb-5">
                <h1 class="text-2xl font-bold text-slate-900">Customers</h1>
                <p class="mt-1 text-sm text-slate-500">Manage client profiles, onboarding, KYC, balances, and account activity.</p>

                <div class="mt-5 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    @foreach([
                        ['key' => 'customers', 'label' => 'Total Customers', 'value' => number_format((int) ($customerMetrics['total'] ?? 0)), 'tone' => 'blue'],
                        ['key' => 'active', 'label' => 'Active Today', 'value' => number_format((int) ($customerMetrics['active_today'] ?? 0)), 'tone' => 'green'],
                        ['key' => 'kyc', 'label' => 'Pending KYC', 'value' => number_format((int) ($customerMetrics['pending_kyc'] ?? 0)), 'tone' => 'blue'],
                        ['key' => 'balance', 'label' => 'Total Account Balance', 'value' => format_price((float) ($customerMetrics['total_account_balance'] ?? 0)), 'tone' => 'violet'],
                    ] as $metric)
                        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                            <div class="flex items-center gap-3">
                                <span class="inline-flex h-12 w-12 shrink-0 items-center justify-center rounded-xl {{ $metric['tone'] === 'green' ? 'bg-emerald-50 text-emerald-600' : ($metric['tone'] === 'violet' ? 'bg-violet-50 text-violet-600' : 'bg-blue-50 text-blue-600') }}">
                                    @if($metric['key'] === 'customers')
                                        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                                    @elseif($metric['key'] === 'active')
                                        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/><path d="m8.5 12 2 2 4-4"/></svg>
                                    @elseif($metric['key'] === 'kyc')
                                        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="4" y="3" width="16" height="18" rx="2"/><circle cx="9" cy="9" r="2"/><path d="M7 15c.8-1.3 3.2-1.3 4 0M14 8h3M14 12h3M14 16h3"/></svg>
                                    @else
                                        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="2.5" y="5" width="19" height="15" rx="2.5"/><path d="M16 12h5.5v4H16a2 2 0 0 1 0-4ZM6 9h6"/><circle cx="17" cy="14" r=".6" fill="currentColor" stroke="none"/></svg>
                                    @endif
                                </span>
                                <div>
                                    <p class="text-xs font-medium text-slate-500">{{ $metric['label'] }}</p>
                                    <p class="mt-1 text-xl font-bold text-slate-900">{{ $metric['value'] }}</p>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>

            <x-card>
                <x-splade-table class="mini-customer-table mt-1" :for="$data">
                <x-splade-cell first_name as="$item">
                    @include('admin.users.mini.cells.user', ['item' => $item])
                </x-splade-cell>

                <x-splade-cell account_id as="$item">
                    @include('admin.users.mini.cells.identity', ['item' => $item])
                </x-splade-cell>

                <x-splade-cell email as="$item">
                    <div class="min-w-[145px] text-sm">
                        <p class="font-medium text-slate-700">{{ $item->email }}</p>
                        <p class="mt-1 text-xs text-slate-400">{{ $item->phone ?: 'No phone number' }}</p>
                    </div>
                </x-splade-cell>

                <x-splade-cell status as="$item">
                    @php($customerStatus = strtolower((string) ($item->status ?: 'pending')))
                    <span class="inline-flex rounded-full border px-3 py-1 text-xs font-semibold {{ in_array($customerStatus, ['active', 'approved'], true) ? 'border-emerald-200 bg-emerald-50 text-emerald-700' : 'border-amber-200 bg-amber-50 text-amber-700' }}">
                        {{ ucfirst($customerStatus) }}
                    </span>
                </x-splade-cell>

                <x-splade-cell verification_status as="$item">
                    @php($kycStatus = strtolower((string) ($item->verification_status ?: 'pending')))
                    <Link modal href="{{ route('admin.user.kyc', $item->id) }}" class="inline-flex items-center gap-1 rounded-full border px-3 py-1 text-xs font-semibold {{ in_array($kycStatus, ['verified', 'approved'], true) ? 'border-emerald-200 bg-emerald-50 text-emerald-700' : 'border-amber-200 bg-amber-50 text-amber-700' }}">
                        @if(in_array($kycStatus, ['verified', 'approved'], true))
                            <span aria-hidden="true">✓</span> Verified
                        @else
                            <span aria-hidden="true">!</span> Pending Review
                        @endif
                    </Link>
                </x-splade-cell>

                <x-splade-cell manager_id as="$item">
                    @include('admin.users.partials.manager', ['item' => $item])
                </x-splade-cell>

                <x-splade-cell source as="$item">
                    @include('admin.users.mini.cells.source', ['item' => $item])
                </x-splade-cell>

                <x-splade-cell trades as="$item">
                    @include('admin.users.mini.cells.trades', ['item' => $item])
                </x-splade-cell>

                <x-splade-cell balance as="$item">
                    <div class="min-w-[112px] text-xs leading-5 text-slate-500">
                        <p><span class="text-slate-400">Balance:</span> <strong class="text-slate-700">{{ format_price($item->estimatedBalance()) }}</strong></p>
                        <p><span class="text-slate-400">Credit:</span> {{ format_price($item->estimatedCreditBalance()) }}</p>
                        <p><span class="text-slate-400">Bonus:</span> {{ format_price($item->estimatedBonusBalance()) }}</p>
                        <p><span class="text-slate-400">Total:</span> <strong class="text-slate-700">{{ format_price($item->totalPortfolioBalance()) }}</strong></p>
                    </div>
                </x-splade-cell>

                <x-splade-cell created_at as="$item">
                    <div class="min-w-[105px] text-xs text-slate-500">
                        @if($item->last_seen)
                            <p class="font-medium text-slate-700">{{ $item->last_seen->format('d M Y, H:i') }}</p>
                            <p class="mt-1 text-slate-400">{{ $item->last_seen->diffForHumans() }}</p>
                        @else
                            <p class="text-slate-400">No activity yet</p>
                        @endif
                    </div>
                </x-splade-cell>

                <x-splade-cell actions as="$item">
                    @include('admin.users.mini.cells.actions', ['item' => $item])
                </x-splade-cell>
                </x-splade-table>
            </x-card>
        @endif
    </div>
</x-admin-layout>

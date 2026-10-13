<x-admin-layout>
    @php
        $tabs = mini_user_tabs();
        $currentTab = collect($tabs)->firstWhere('id', $activeTab) ?? $tabs[0];
    @endphp

    <x-slot name="header">
        <div class="mini-profile-page">
            <nav class="px-4 pt-3 pb-1 text-sm text-gray-500" aria-label="Breadcrumb">
                <Link href="{{ route('admin.users.customers') }}" class="hover:text-[#0d63cc]">Customers</Link>
                <span class="mx-1">/</span>
                <span class="text-gray-700 font-medium">{{ $user->name }}</span>
            </nav>

            <div class="mini-profile-topbar">
                <div class="flex-1 overflow-x-auto">
                    @include('admin.users.partials.header')
                </div>
            </div>

            <div class="mini-profile-summary bg-white px-4 py-4 border-b border-gray-200">
                <div class="grid grid-cols-1 xl:grid-cols-12 gap-4 items-center">
                    <div class="xl:col-span-4 flex items-start gap-4">
                        <Link href="{{ route('admin.users.customers') }}" class="mini-back-btn mt-4">
                            {{ svg('heroicon-o-arrow-left', 'w-5 h-5') }}
                        </Link>

                        <div class="relative">
                            <img class="w-16 h-16 rounded-full object-cover" src="{{ $user->avatar }}" alt="{{ $user->name }}" />
                            <span class="absolute -right-1 -top-1 h-3.5 w-3.5 rounded-full {{ $user->is_online ? 'bg-green-500' : 'bg-gray-300' }} border-2 border-white"></span>
                        </div>

                        <div>
                            <h2 class="text-lg font-semibold text-[#0d63cc]">{{ $user->name }}</h2>
                            <p class="text-sm {{ $user->verification_status === 'approved' ? 'text-green-600' : 'text-red-500' }}">{{ ucfirst($user->verification_status ?? 'unverified') }}</p>
                            <p class="text-sm text-gray-600">{{ $user->email }}</p>
                            <p class="text-sm text-gray-600">{{ $user->phone }}</p>
                            @php
                                $customerTz = $user->effectiveTimezone();
                            @endphp
                            <p class="text-sm text-gray-600" title="{{ $user->timezoneIsInferred() ? 'Inferred from the customer\'s country -- they haven\'t set a time zone' : 'The customer\'s own time zone setting' }}">
                                Local time: <span class="font-medium text-gray-800">{{ now($customerTz)->format('H:i, D d M') }}</span>
                                <span class="text-gray-500">&middot; {{ $customerTz }} (UTC{{ now($customerTz)->format('P') }}){{ $user->timezoneIsInferred() ? ' *' : '' }}</span>
                            </p>
                            <div class="mt-2 flex items-center gap-2">
                                <span class="text-sm text-gray-500">Current Plan:</span>
                                <span class="text-sm text-[#0d63cc] font-semibold">{{ $user?->plan?->title ?? 'Mini Plan' }}</span>
                                @unless(\App\Services\RestrictedManagerAccess::active())
                                <Link modal href="{{ route('admin.users.edit', $user->id) }}?plan" class="notranslate mini-upgrade-btn" translate="no">Upgrade Plan</Link>
                                @endunless
                            </div>
                        </div>
                    </div>

                    <div class="xl:col-span-3">
                        <p class="text-sm text-gray-500">Total Portfolio Balance:</p>
                        <p class="text-2xl text-[#0d63cc] font-semibold">{{ format_money($user->totalPortfolioBalance()) }}</p>
                    </div>

                    <div class="xl:col-span-5 flex xl:justify-end gap-2 flex-wrap">
                        @unless(\App\Services\RestrictedManagerAccess::active())
                        <Link modal href="{{ route('admin.users.edit', $user->id) }}"
                              class="notranslate mini-action-btn mini-action-edit"
                              translate="no">
                            {{ svg('heroicon-o-pencil-square', 'w-4 h-4') }}
                        </Link>
                        @endunless

                        @unless(\App\Services\RestrictedManagerAccess::active())
                        <Link href="{{ route('admin.users.show', $user->id) }}?tab=notes" class="mini-action-btn mini-action-note">
                            {{ svg('heroicon-o-chat-bubble-bottom-center-text', 'w-4 h-4') }}
                            <span>Notes</span>
                        </Link>
                        @endunless

                        <a target="_blank" href="{{ route('admin.frontend.login', $user->id) }}" class="mini-action-btn mini-action-edit">
                            {{ svg('heroicon-o-arrow-top-right-on-square', 'w-4 h-4') }}
                            <span>Login As</span>
                        </a>

@if(can_delete_action())
                            <Link
                                href="{{ route('admin.users.destroy', ['user' => $user->id, 'redirect_to' => 'customers']) }}"
                                method="DELETE"
                                confirm="Delete"
                                confirm-text="Are you sure you want to delete this user account?"
                                confirm-button="Delete"
                                cancel-button="Cancel"
                                class="mini-action-btn mini-action-delete"
                            >
                                {{ svg('heroicon-o-trash', 'w-4 h-4') }}
                                <span>Delete Account</span>
                            </Link>
@endif

                        <Link href="{{ route('admin.users.show', $user->id) }}?prev" class="mini-nav-btn" title="Previous">
                            {{ svg('heroicon-o-chevron-left', 'w-4 h-4') }}
                        </Link>
                        <Link href="{{ route('admin.users.show', $user->id) }}?next" class="mini-nav-btn" title="Next">
                            {{ svg('heroicon-o-chevron-right', 'w-4 h-4') }}
                        </Link>
                    </div>
                </div>
            </div>

            <div class="mini-profile-tabs-wrap bg-white border-b border-gray-200 overflow-x-auto">
                <div class="mini-profile-tabs min-w-max">
                    @foreach($tabs as $tab)
                        @php $isActiveTab = $tab['id'] === $currentTab['id']; @endphp
                        <Link
                            href="{{ route('admin.users.show', $user->id) }}?tab={{ $tab['id'] }}"
                            class="mini-profile-tab {{ $isActiveTab ? 'is-active' : '' }}"
                        >
                            @if($tab['id'] === 'recovery')
                                <span class="inline-flex flex-col items-start leading-none">
                                    <span class="text-[9px] font-semibold uppercase tracking-[0.14em] text-blue-500">RCV</span>
                                    <span class="mt-1">{{ $tab['title'] }}</span>
                                </span>
                            @else
                                {{ $tab['title'] }}
                            @endif
                        </Link>
                    @endforeach
                </div>
            </div>
        </div>
    </x-slot>

    <div class="mini-profile-content bg-[url('/admin/images/bg.svg')] bg-cover bg-center rounded-xl min-h-[520px] p-3">
        @include('admin.users.mini.tabs.'.$currentTab['component'], ['user' => $user, 'notes' => $notes, 'comments' => $comments])
    </div>
</x-admin-layout>

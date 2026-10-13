<x-admin-layout>
    <x-slot name="header">
        <div class="space-y-2">

            @include('admin.users.partials.header')

            <!-- Action Bar -->
            <div class="flex justify-between items-center px-6">
                <!-- Search Box -->
                <div class="w-1/4">

                </div>

                <!-- Action Buttons -->
                <div class="flex items-center space-x-2">

                    <Link href="{{ route('admin.users.create') }}?role=customer" modal
                          class="bg-orange-500 hover:bg-orange-600 text-white px-4 py-2 rounded flex items-center">
                    <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                         xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                    </svg>
                    Add Customer
                    </Link>
                </div>
            </div>
        </div>
    </x-slot>


    <x-card>

        <x-splade-table class="mt-1" :for="$data">

            <x-splade-cell manager_id as="$item">
                @include('admin.users.partials.manager',['item' => $item])
            </x-splade-cell>

            <x-splade-cell kyc as="$item">

                <Link
                    modal
                    class="text-white {{ getKYCStatus($item->verification_status) }} rounded-md  px-2 py-2 mr-2"
                    href="{{ route('admin.user.kyc', $item->id) }}">
                    {{ $item->verification_status }}
                </Link>

            </x-splade-cell>


            <x-splade-cell status as="$item">
                <Link method="POST" href="{{ route('admin.user.toggle',['id' => $item->id,'field' => 'status']) }}"
                      class="py-1 px-2 text-center rounded-md text-white {{ $item->status == 'active' ? 'bg-primary' : 'bg-red-600' }} ">
                <p>{{ $item->status }}</p>
                </Link>
            </x-splade-cell>


            <x-splade-cell first_name as="$item">
                @php($lastLoginText = $item->is_online ? 'Online' : ($item->last_seen ? $item->last_seen->diffForHumans() : ($item->lastLoginAt() ?: 'Never')))
                <UserProfileCard
                    avatar="{{ $item->avatar }}"
                    name="{{ $item->name }}"
                    last-login="{{ $lastLoginText }}"
                    :can-trade="{{ $item->can_trade ? 'true' : 'false' }}"
                    user-show-route="{{ route('admin.users.show', $item->id) }}"
                    phone="{{ $item->phone }}"
                    email="{{ $item->email }}"
                    frontend_login="{{ route('admin.frontend.login', $item->id) }}"
                    :email-verified="{{ $item->email_verified_at ? 'true' : 'false' }}"
                />
            </x-splade-cell>


            <x-splade-cell copy_trader as="$item">

                @include('admin.users.partials.copy_trader',['item' => $item])

            </x-splade-cell>


            <x-splade-cell plan_id as="$item">
                    <Link modal href="{{ route('admin.users.edit', $item->id) }}"
                          style="{{ $item->plan ? getPlanBgColor($item->plan->color) : '' }}"
                          class="py-1 px-2 flex text-center rounded-md text-white bg-gray-600">

                    @if($item->plan)
                        <div class="flex gap-1">
                            <img src="{{ $item->plan->icon }}" class="h-4 w-4 rounded-full" alt=""/>
                            <p>{{ $item?->plan?->title ?? 'not set' }}</p>
                        </div>
                    @else
                        <p class="">No Plan</p>
                        @endif

                        </Link>


            </x-splade-cell>
            <x-splade-cell plan_id as="$item">
                @include('admin.users.partials.plan',['item' => $item])
            </x-splade-cell>

            <x-splade-cell last_seen as="$item">
                <div>
                    @if($item->is_online)
                        <span class="bg-green-500 text-white text-sm px-2 py-1 rounded-lg">Online</span>
                    @else
                        @if(!$item->last_seen)
                            <p>Not seen</p>
                        @else
                            {{ $item->last_seen }}
                            {{--                    {{ $item->last_seen->diffForHumans() }}--}}
                        @endif
                    @endif
                </div>


            </x-splade-cell>


            <x-splade-cell balance as="$item">


                <div class="grid">
                    <Balance
                        wallet_url="{{ route('admin.user.wallet', $item->id) }}"
                        edit_url="{{ route('admin.users.edit', $item->id) }}"
                        balance="{{ format_price($item->estimatedBalance()) }}"
                        credit="{{ format_price($item->estimatedCreditBalance()) }}"
                        custom_wallet="{{ company_settings('custom_wallet') }}"
                        total_balance="{{ format_price($item->totalPortfolioBalance()) }}"
                        id="{{ $item->id }}"
                        is_admin="{{ is_admin() }}"
                    />

                </div>


            </x-splade-cell>


            <x-splade-cell email as="$item">
                <div>
                    <p class="mb-1">{{ $item->email }}</p>
                    @if($item->email_verified_at)
                        <span class="bg-green-500 text-white text-sm px-2 py-1 rounded-lg">Verified</span>
                    @else
                        <span class="bg-red-500 mr-3 mt-1 text-sm text-white px-2 py-1 rounded-lg">Unverified</span>

                    @endif
                </div>

            </x-splade-cell>

            <x-splade-cell actions as="$item">

                @include('admin.users.partials.user_mini_actions',['item' => $item])
            </x-splade-cell>
        </x-splade-table>
    </x-card>


</x-admin-layout>

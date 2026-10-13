<x-admin-layout>
    <x-slot name="header">
        <span class="capitalize">Customers</span>
    </x-slot>

    <x-slot name="header_right">

        <div class="p-1- flex justify-between gap-2">
            <Link class="bg-blue-900 text-white items-center rounded flex gap-2  px-2 py-2" modal
                  href="{{ route('admin.users.create') }}?role=customer">
            <span>{{ svg('heroicon-s-plus-small','h-5') }}</span>
            <span>Add New</span>
            </Link>

        </div>

    </x-slot>


    <x-card>

        <x-splade-table class="mt-5" :for="$data">


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
                <UserToggleButtons
                    copy-trader-route="{{ route('admin.user.toggle', ['id' => $item->id, 'field' => 'copy_trader']) }}"
                    :copy-trader-active="{{ $item->copy_trader ? 'true' : 'false' }}"
                    require-otp-route="{{ route('admin.user.toggle', ['id' => $item->id, 'field' => 'require_otp']) }}"
                    :require-otp-active="{{ $item->require_otp ? 'true' : 'false' }}"
                    can-email-route="{{ route('admin.user.toggle', ['id' => $item->id, 'field' => 'can_email']) }}"
                    :can-email-active="{{ $item->can_email ? 'true' : 'false' }}"
                />
            </x-splade-cell>



            <x-splade-cell plan_id as="$item">
                <p>{{ $item?->plan?->title ?? 'not set' }}</p>
            </x-splade-cell>

            <x-splade-cell last_seen as="$item">
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

            </x-splade-cell>


            <x-splade-cell balance as="$item">


                <div class="grid">
                    <Balance
                        wallet_url="{{ route('admin.user.wallet', $item->id) }}"
                        balance="{{ format_money($item->estimatedBalance()) }}"
                        credit="{{ format_money($item->estimatedCreditBalance()) }}"
                        id="{{ $item->id }}"
                        :is_admin="false"
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

                        {{--                        <Link method="post"--}}
                        {{--                              href="{{ route('admin.users.verify_email', $item->id) }}"--}}
                        {{--                              class="text-green-500">{{ 'Verify' }}</Link>--}}

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

                {{--                @if(is_admin())--}}

                {{--                    <Link--}}
                {{--                        class="text-white bg-green-700 rounded-md  px-2 py-2 mr-2"--}}
                {{--                        confirm="Login as {{ $item->name }}"--}}
                {{--                        confirm-text="Are you sure you want to login as this admin?"--}}
                {{--                        confirm-button="Login"--}}
                {{--                        cancel-button="Cancel"--}}
                {{--                        href="{{ route('admin.users.login_as', $item->id) }}" method="POST">--}}
                {{--                        {{ svg('heroicon-o-lock-open','h-4') }}--}}
                {{--                    </Link>--}}
                {{--                @endif--}}




                <Link
                    modal
                    class="notranslate text-white bg-yellow-300 rounded-md  px-2 py-2 mr-2"
                    translate="no"
                    href="{{ route('admin.users.edit', $item->id) }}">
                    {{ svg('heroicon-s-pencil-square', 'h-4') }}
                </Link>

                <Link
                    class="text-white bg-gray-600 text-white rounded-md  px-2 py-2 mr-2"
                    href="{{ route('admin.user.trades', [$item->id, 'open']) }}">
                    Order Book
                </Link>


            </x-splade-cell>
        </x-splade-table>
    </x-card>


</x-admin-layout>

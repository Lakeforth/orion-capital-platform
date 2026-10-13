<div class="grid grid-cols-1 xl:grid-cols-12 gap-4">
    <div class="xl:col-span-5 space-y-4">
        <div class="mini-panel p-5">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                <div class="space-y-3">
                    <div class="flex items-center justify-between gap-3">
                        <p class="text-gray-600">User {{ ucfirst($user->verification_status ?? 'unverified') }}</p>
                        <Link method="POST" href="{{ route('admin.user.toggle',['id' => $user->id,'field' => 'user_status']) }}"
                              class="mini-toggle {{ $user->user_status === 'active' ? 'is-on' : '' }}">
                            <span></span>
                        </Link>
                    </div>

                    <div class="flex items-center justify-between gap-3">
                        <p class="text-green-600 font-semibold">Enable Trade</p>
                        <Link method="POST" href="{{ route('admin.user.toggle',['id' => $user->id,'field' => 'can_trade']) }}"
                              class="mini-toggle {{ (bool) $user->can_trade ? 'is-on' : '' }}">
                            <span></span>
                        </Link>
                    </div>
                </div>

                <div class="space-y-3">
                    <div class="flex items-center justify-between gap-3">
                        <p class="text-gray-600">Auto Trader</p>
                        <Link method="POST" href="{{ route('admin.user.toggle',['id' => $user->id,'field' => 'copy_trader']) }}"
                              class="mini-toggle {{ (bool) $user->copy_trader ? 'is-on' : '' }}">
                            <span></span>
                        </Link>
                    </div>

                    <div class="flex items-center justify-between gap-3">
                        <p class="text-gray-600">AI Trading</p>
                        <Link method="POST" href="{{ route('admin.user.toggle',['id' => $user->id,'field' => 'ai_trade']) }}"
                              class="mini-toggle {{ (bool) $user->ai_trade ? 'is-on' : '' }}">
                            <span></span>
                        </Link>
                    </div>

                </div>
            </div>
        </div>

        <div class="mini-panel p-4">
            <h3 class="text-lg font-semibold text-gray-700 mb-3">User Information</h3>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-y-2 text-sm">
                <p class="text-gray-500">First Name</p><p class="text-gray-700">{{ $user->first_name ?: 'N/A' }}</p>
                <p class="text-gray-500">Last Name</p><p class="text-gray-700">{{ $user->last_name ?: 'N/A' }}</p>
                <p class="text-gray-500">Phone Number</p><p class="text-gray-700">{{ $user->phone ?: 'N/A' }}</p>
                <p class="text-gray-500">Country</p>
                <p class="text-gray-700 flex items-center gap-1.5">
                    @php $countryInfo = country_flag_and_dial($user->country); @endphp
                    @if($countryInfo && ($countryInfo['flag_url'] ?? null))
                        <img src="{{ $countryInfo['flag_url'] }}" alt="{{ ucfirst($user->country) }}" class="inline-block w-4 h-3 rounded-[2px] object-cover shrink-0" />
                    @endif
                    <span>{{ $user->country ?: 'N/A' }}</span>
                </p>
                <p class="text-gray-500">Email Address</p><p class="text-gray-700 break-all">{{ $user->email }}</p>
                <p class="text-gray-500">Last Seen</p><p class="text-gray-700">{{ $user->is_online ? 'Online' : ($user->last_seen ? $user->last_seen->diffForHumans() : ($user->lastLoginAt() ?: 'Never')) }}</p>
                <p class="text-gray-500">Present Address</p><p class="text-gray-700">{{ $user->address ?: 'N/A' }}</p>
                <p class="text-gray-500">Permanent Address</p><p class="text-gray-700">{{ $user->address ?: 'N/A' }}</p>
            </div>
        </div>
    </div>

    <div class="xl:col-span-7">
        <div class="mini-panel p-4">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-lg font-semibold text-gray-700">Local News</h3>
                <p class="text-gray-500 text-right">
                    <span class="text-2xl text-gray-600 leading-none">{{ now()->format('h:i') }}</span>
                    <span class="text-sm">{{ now()->format('a') }}</span><br>
                    <span class="text-sm">{{ now()->format('D, M j') }}</span>
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <article class="mini-news-card">
                    <p class="text-gray-500 text-sm mb-3">2 mins ago</p>
                    <div class="mini-news-media bg-gradient-to-r from-sky-800 to-blue-500"></div>
                    <h4 class="mt-3 text-base font-semibold text-gray-800">Canada Stock Trading - Canada Stock Trading</h4>
                    <p class="mt-2 text-sm text-gray-600">Search Canada Stock Trading, Information from Trusted Internet Sources. Explore the Best Info Now.</p>
                </article>

                <article class="mini-news-card">
                    <p class="text-gray-500 text-sm mb-3">2 mins ago</p>
                    <div class="mini-news-media bg-gradient-to-r from-blue-900 to-cyan-600"></div>
                    <h4 class="mt-3 text-base font-semibold text-gray-800">Forex Market News &amp; FX</h4>
                    <p class="mt-2 text-sm text-gray-600">Follow all the latest forex news, trading strategies, commodities reports and events at DailyFX.</p>
                </article>
            </div>
        </div>
    </div>
</div>

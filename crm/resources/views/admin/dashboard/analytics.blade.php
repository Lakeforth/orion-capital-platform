<x-admin-layout>
    @php
        $user = auth()->user();
        $displayName = trim((string) ($user->first_name ?? '')) ?: trim((string) ($user->name ?? 'Trader'));
        // Account Status is the account itself (users.user_status), not KYC verification -- admins
        // never verify, so this read PENDING for every brokerage owner.
        // Presentational, by request: every tenant reads Active / Professional on this banner.
        // Both were derived from data that differs per tenant -- user_status is pending on
        // several, and every company is on the Standard plan -- so a fallback would not have
        // changed them. Nothing is gated on this: the only other use of $verificationStatus is
        // the "complete verification" line below. To restore the data-driven values, put back:
        //   $verificationStatus = strtoupper((string) ($user->user_status ?: 'active'));
        //   $accountType = strtoupper((string) ($company?->plan?->title ?? $company?->subscription_type ?? $user->plan?->title ?? 'classic'));
        $verificationStatus = 'ACTIVE';
        // A brokerage's account type is its platform subscription tier (plans), not the
        // client trading plan (account_plans) its admin user happens to be attached to.
        $company = app()->bound('company') ? app('company') : null;
        $accountType = 'PROFESSIONAL';
        $supportUrl = company_settings('support_url', 'https://t.me/livetraderengine');

        $whatsAppNumber = preg_replace('/\D+/', '', (string) company_settings('whatsap_number', company_settings('whatsapp_number', '')));
        $whatsAppLink = $whatsAppNumber ? "https://wa.me/{$whatsAppNumber}" : $supportUrl;

        $telegramHandle = trim((string) company_settings('telegram_general_department', company_settings('telegram_chat_id', 'livetraderengine')));
        $telegramHandle = ltrim($telegramHandle, '@');
        $telegramLink = $telegramHandle && ! is_numeric($telegramHandle) ? "https://t.me/{$telegramHandle}" : $supportUrl;

        $services = [
            [
                'name' => 'Live Trading API',
                'enabled' => module_enabled('live_api'),
                'icon' => 'heroicon-o-signal',
                'brand' => false,
            ],
            [
                'name' => 'Elastic Cloud',
                'enabled' => module_enabled('cloud_hosting'),
                'icon' => 'heroicon-o-cloud',
                'brand' => false,
            ],
            [
                'name' => 'DDOS Security Protection',
                'enabled' => true,
                'icon' => 'heroicon-o-shield-check',
                'brand' => true,
            ],
            [
                'name' => 'Database Back-Up',
                'enabled' => module_enabled('cloud_hosting'),
                'icon' => 'heroicon-o-circle-stack',
                'brand' => false,
            ],
            [
                'name' => '24/7 Support',
                'enabled' => true,
                'icon' => 'heroicon-o-lifebuoy',
                'brand' => true,
            ],
        ];

        $stats = [
            [
                'label' => 'Total Customers',
                'value' => number_format((int) ($data['customers'] ?? 0)),
                'badge' => '+25%',
                'tone' => 'up',
                'spark' => 'M10 32 C 30 28, 44 28, 58 29 C 74 30, 84 26, 92 12 C 98 3, 105 5, 110 18',
            ],
            [
                'label' => 'Online Customers',
                'value' => number_format((int) ($data['online_customers'] ?? 0)),
                'badge' => '+25%',
                'tone' => 'up',
                'spark' => 'M10 33 C 28 31, 41 30, 54 31 C 69 32, 84 31, 94 17 C 100 7, 106 8, 110 20',
            ],
            [
                'label' => 'Leads',
                'value' => number_format((int) ($data['leads'] ?? 0)),
                'badge' => '-25%',
                'tone' => 'down',
                'spark' => 'M10 18 C 26 21, 43 19, 57 20 C 72 21, 80 27, 81 40 C 82 50, 92 49, 110 26',
            ],
            [
                'label' => 'Total Deposited',
                'value' => format_price((float) ($data['total_deposited'] ?? 0)),
                'badge' => null,
                'tone' => 'up',
                'spark' => null,
            ],
        ];

        // Prop Firm calls this Payout Requests (added below) -- showing both
        // would be a duplicate, so Withdrawal is brokerage-only here.
        if (! module_enabled('propfirm_enabled')) {
            $stats[] = [
                'label' => 'Withdrawal',
                'value' => number_format((int) ($data['pending_withdrawals'] ?? 0)),
                'badge' => '-25%',
                'tone' => 'down',
                'spark' => 'M10 17 C 29 20, 44 19, 58 19 C 71 19, 81 27, 81 39 C 81 49, 91 50, 110 27',
            ];
        }

        $stats = array_merge($stats, [
            [
                'label' => 'Pending Deposit',
                'value' => number_format((int) ($data['pending_deposits'] ?? 0)),
                'badge' => '-25%',
                'tone' => 'down',
                'spark' => 'M10 18 C 28 20, 40 22, 55 33 C 71 45, 86 50, 110 34',
            ],
            [
                'label' => 'KYC Verification',
                'value' => number_format((int) ($data['pending_kycs'] ?? 0)),
                'badge' => '!',
                'tone' => 'warn',
                'spark' => null,
            ],
        ]);

        if (module_enabled('propfirm_enabled')) {
            $stats = array_merge($stats, [
                [
                    'label' => 'Active Evaluations',
                    'value' => number_format((int) ($data['active_evaluations'] ?? 0)),
                    'badge' => '',
                    'tone' => 'warn',
                    'spark' => null,
                ],
                [
                    'label' => 'Funded Accounts',
                    'value' => number_format((int) ($data['funded_accounts'] ?? 0)),
                    'badge' => '',
                    'tone' => 'warn',
                    'spark' => null,
                ],
                [
                    'label' => 'Payout Requests',
                    'value' => number_format((int) ($data['payout_requests'] ?? 0)),
                    'badge' => '',
                    'tone' => 'warn',
                    'spark' => null,
                ],
            ]);
        }

        $depositBars = [
            ['label' => '26-Apr', 'deposit' => 2, 'withdrawal' => 2],
            ['label' => '27-Apr', 'deposit' => 2, 'withdrawal' => 2],
            ['label' => '28-Apr', 'deposit' => 2, 'withdrawal' => 2],
            ['label' => '30-Apr', 'deposit' => 6, 'withdrawal' => 2],
            ['label' => '01-May', 'deposit' => 170, 'withdrawal' => 2],
            ['label' => '02-May', 'deposit' => 2, 'withdrawal' => 2],
            ['label' => '03-May', 'deposit' => 2, 'withdrawal' => 2],
            ['label' => '04-May', 'deposit' => 2, 'withdrawal' => 2],
            ['label' => '08-May', 'deposit' => 2, 'withdrawal' => 110],
            ['label' => '09-May', 'deposit' => 2, 'withdrawal' => 2],
            ['label' => '10-May', 'deposit' => 32, 'withdrawal' => 24],
            ['label' => '13-May', 'deposit' => 2, 'withdrawal' => 2],
        ];

        // Real per-country customer counts, not hardcoded Nigerian cities --
        // this app serves tenants worldwide, and there's no per-city data on
        // the user record anyway (only country).
        $geoCompany = app()->bound('company') ? app('company') : null;
        $geoRows = $geoCompany
            ? $geoCompany->users()
                ->whereHasRole(['customer', 'user'])
                ->whereNotNull('country')
                ->where('country', '!=', '')
                ->selectRaw('country, count(*) as users')
                ->groupBy('country')
                ->orderByDesc('users')
                ->limit(5)
                ->get()
                ->map(fn ($row) => ['location' => ucfirst($row->country), 'users' => (int) $row->users])
                ->values()
                ->toArray()
            : [];

        if (empty($geoRows)) {
            $geoRows = [['location' => 'No data yet', 'users' => 0]];
        }

        $trendSeries = [
            ['label' => 'PnL', 'color' => '#ff253a', 'points' => '40,96 110,96 180,208 250,96 320,126 390,96 460,96 530,96 600,147 670,86 740,96 810,96 880,96 950,96 1020,96 1090,96 1160,94'],
            ['label' => 'Volume', 'color' => '#cfd4ff', 'points' => '40,214 110,214 180,214 250,214 320,214 390,214 460,214 530,167 600,214 670,214 740,214 810,176 880,214 950,142 1020,132 1090,214 1160,136'],
            ['label' => 'Deposit', 'color' => '#0b7a93', 'points' => '40,214 110,214 180,214 250,214 320,214 390,214 460,214 530,214 600,214 670,214 740,121 810,214 880,214 950,214 1020,205 1090,195 1160,214'],
            ['label' => 'Withdrawals', 'color' => '#f9a23a', 'points' => '40,219 110,219 180,219 250,219 320,219 390,219 460,219 530,219 600,219 670,219 740,75 810,219 880,152 950,219 1020,181 1090,219 1160,219'],
            ['label' => 'Sign ups', 'color' => '#8fd9ec', 'points' => '40,218 110,218 180,218 250,218 320,218 390,218 460,218 530,167 600,218 670,218 740,218 810,176 880,218 950,142 1020,132 1090,218 1160,136'],
        ];

        $trendBars = [
            ['x' => 172, 'height' => 22, 'color' => '#cfd4ff'],
            ['x' => 302, 'height' => 18, 'color' => '#cfd4ff'],
            ['x' => 532, 'height' => 48, 'color' => '#cfd4ff'],
            ['x' => 704, 'height' => 24, 'color' => '#cfd4ff'],
            ['x' => 872, 'height' => 38, 'color' => '#cfd4ff'],
            ['x' => 972, 'height' => 66, 'color' => '#c3eff8'],
            ['x' => 1014, 'height' => 74, 'color' => '#c3eff8'],
            ['x' => 1150, 'height' => 58, 'color' => '#c3eff8'],
        ];
    @endphp

    <div class="space-y-5 rounded-[32px] bg-[#e6ebf2] p-3 text-slate-900 md:p-5">
        <div class="grid gap-5 lg:grid-cols-[1.08fr_.92fr] lg:items-start">
            <div class="space-y-5">
                <section class="relative overflow-hidden rounded-[32px] bg-[#1b2137] px-5 py-6 text-white shadow-[0_24px_60px_rgba(14,21,40,0.18)] md:px-6 md:py-7">
                    <div class="absolute inset-0 bg-[radial-gradient(circle_at_15%_10%,rgba(78,104,209,0.16),transparent_36%),radial-gradient(circle_at_82%_24%,rgba(49,196,255,0.16),transparent_33%)]"></div>
                    <div class="absolute inset-y-5 right-5 hidden w-[39%] rounded-[24px] bg-cover bg-center opacity-30 lg:block" style="background-image: linear-gradient(rgba(15,23,42,.28), rgba(15,23,42,.28)), url('{{ asset('images/logo_bg_min.jpeg') }}');"></div>
                    <svg class="absolute inset-x-0 bottom-10 hidden h-24 w-full text-[#355cae] md:block" viewBox="0 0 800 140" fill="none" preserveAspectRatio="none">
                        <path d="M0 116C68 83 137 57 202 54C266 51 309 79 384 79C470 79 530 31 608 25C676 20 735 38 800 55" stroke="currentColor" stroke-width="6" stroke-linecap="round"/>
                    </svg>

                    <div class="relative z-10 flex min-h-[248px] flex-col justify-between gap-6">
                        <div class="max-w-[380px]">
                            <p class="text-[20px] font-light leading-tight sm:text-[24px] lg:text-[26px]">
                                Welcome back <span class="font-semibold">{{ $displayName }}</span>
                            </p>
                            @if($verificationStatus !== 'ACTIVE')
                            <p class="mt-2 max-w-[360px] text-[12px] leading-snug text-white/80 sm:text-[14px]">
                                Please complete the verification process to unlock full account access.
                            </p>
                            @endif
                        </div>

                        <div class="max-w-[320px] space-y-3">
                            <div class="flex items-center justify-between gap-4 rounded-xl border border-[#f7eaea] bg-[#fff4f4] px-4 py-3 text-[#77767f] shadow-[0_10px_30px_rgba(5,7,18,0.18)]">
                                <span class="text-[12px] font-medium tracking-[0.02em] sm:text-[13px]">ACCOUNT STATUS:</span>
                                <span class="text-[15px] font-semibold {{ $verificationStatus === 'ACTIVE' ? 'text-[#16a34a]' : 'text-[#ed4454]' }} sm:text-[17px]">{{ $verificationStatus }}</span>
                            </div>

                            <div class="flex items-center justify-between gap-4 rounded-xl border border-[#f7eaea] bg-[#fff4f4] px-4 py-3 text-[#77767f] shadow-[0_10px_30px_rgba(5,7,18,0.18)]">
                                <span class="text-[12px] font-medium tracking-[0.02em] sm:text-[13px]">ACCOUNT TYPE:</span>
                                <span class="text-[15px] font-semibold text-[#0f67dd] sm:text-[17px]">{{ $accountType }}</span>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="overflow-hidden rounded-[32px] bg-white shadow-[0_18px_44px_rgba(15,23,42,0.08)] ring-1 ring-slate-100">
                    <div class="border-b border-slate-200 px-5 py-5 md:px-6">
                        <h2 class="text-[17px] font-semibold uppercase tracking-[0.02em] text-[#111827] sm:text-[18px]">Active Services</h2>
                    </div>
                    <div class="space-y-5 px-5 py-6 md:px-6">
                        @foreach($services as $service)
                            <div class="flex items-center justify-between gap-4">
                                <div class="flex items-center gap-4">
                                    <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-[#eef2ff] {{ $service['brand'] ? 'text-[#123f88]' : 'text-slate-500' }}">
                                        {{ svg($service['icon'], 'h-6 w-6') }}
                                    </div>
                                    <p class="text-[14px] font-medium {{ $service['brand'] ? 'font-semibold text-[#123f88]' : 'text-[#617188]' }} sm:text-[15px]">{{ $service['name'] }}</p>
                                </div>
                                @if($service['enabled'])
                                    <span class="inline-flex h-10 w-10 items-center justify-center rounded-full bg-[#2fb24b] text-[18px] font-bold text-white">✓</span>
                                @else
                                    <span class="inline-flex h-10 w-10 items-center justify-center rounded-full text-[32px] font-light leading-none text-[#ef4151]">×</span>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </section>

                <section class="rounded-[32px] bg-white px-5 py-6 shadow-[0_18px_44px_rgba(15,23,42,0.08)] ring-1 ring-slate-100 md:px-6">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                        <h2 class="text-[17px] font-semibold uppercase tracking-[0.02em] text-[#111827] sm:text-[18px]">Marketing</h2>
                        <div class="flex items-center gap-6 text-[14px] text-slate-500">
                            <span>Daily</span>
                            <span>Weekly</span>
                            <span class="border-b-[3px] border-[#1d223a] pb-1 font-semibold text-[#1d223a]">Monthly</span>
                        </div>
                    </div>

                    <div class="mt-8 grid gap-6 lg:grid-cols-[1.05fr_.95fr]">
                        <div class="mx-auto flex w-full max-w-[260px] flex-col items-center gap-0">
                            <div class="w-[92%] bg-[#9fe2ef]" style="height:42px;clip-path:polygon(6% 0, 94% 0, 88% 100%, 12% 100%);"></div>
                            <div class="w-[78%] bg-[#3e43e6]" style="height:108px;clip-path:polygon(8% 0, 92% 0, 86% 100%, 14% 100%);"></div>
                            <div class="w-[62%] bg-[#4dd067]" style="height:60px;clip-path:polygon(10% 0, 90% 0, 84% 100%, 16% 100%);"></div>
                            <div class="w-[48%] bg-[#7d36b2]" style="height:72px;clip-path:polygon(12% 0, 88% 0, 82% 100%, 18% 100%);"></div>
                            <div class="w-[34%] bg-[#ed5578]" style="height:54px;clip-path:polygon(14% 0, 86% 0, 78% 100%, 22% 100%);"></div>
                        </div>

                        <div class="space-y-4 pt-3">
                            @foreach([
                                ['label' => 'Visits', 'value' => '7,175', 'color' => '#4547ed'],
                                ['label' => 'Unique Visits', 'value' => '5,412', 'color' => '#4dd067'],
                                ['label' => 'Sign Ups', 'value' => '55,458', 'color' => '#7d36b2'],
                                ['label' => "FTD's", 'value' => '8,345', 'color' => '#ed5578'],
                                ['label' => "Self FTD's", 'value' => '8,345', 'color' => '#b93f9d'],
                            ] as $metric)
                                <div class="flex items-center justify-between gap-6 text-[14px] text-slate-700 sm:text-[15px]">
                                    <div class="flex items-center gap-4">
                                        <span class="h-4 w-4 rounded-sm" style="background-color: {{ $metric['color'] }}"></span>
                                        <span>{{ $metric['label'] }}</span>
                                    </div>
                                    <span class="font-semibold text-slate-800">{{ $metric['value'] }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </section>
            </div>

            <div class="space-y-5">
                <section class="rounded-[32px] bg-[#1b2137] px-5 py-4 text-white shadow-[0_24px_60px_rgba(14,21,40,0.14)] md:px-6">
                    <div class="flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
                        <p class="max-w-[190px] text-[16px] font-light leading-tight sm:text-[18px]">Contact Billing Department</p>
                        <div class="grid gap-3 sm:grid-cols-2">
                            <a href="{{ $whatsAppLink }}" target="_blank" rel="noopener" class="inline-flex min-w-[160px] items-center justify-center rounded-[24px] bg-[#28d35f] px-5 py-3.5 text-[14px] font-semibold text-white shadow-[0_12px_24px_rgba(40,211,95,0.35)] transition hover:-translate-y-0.5 hover:text-white">
                                {{ svg('heroicon-o-chat-bubble-left-ellipsis', 'mr-3 h-4 w-4') }}
                                WhatsApp
                            </a>
                            <a href="{{ $telegramLink }}" target="_blank" rel="noopener" class="inline-flex min-w-[160px] items-center justify-center rounded-[24px] bg-[#1491da] px-5 py-3.5 text-[14px] font-semibold text-white shadow-[0_12px_24px_rgba(20,145,218,0.32)] transition hover:-translate-y-0.5 hover:text-white">
                                {{ svg('heroicon-o-paper-airplane', 'mr-3 h-4 w-4') }}
                                Telegram
                            </a>
                        </div>
                    </div>
                </section>

                <section class="grid gap-4 sm:grid-cols-2">
                    @foreach($stats as $stat)
                        <article class="rounded-[28px] bg-white px-4 py-3 shadow-[0_18px_44px_rgba(15,23,42,0.08)] ring-1 ring-slate-100">
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <p class="text-[13px] font-medium text-slate-500 sm:text-[14px]">{{ $stat['label'] }}</p>
                                    <p class="mt-1.5 text-[18px] font-semibold tracking-tight text-[#617188] sm:text-[20px]">{{ $stat['value'] }}</p>
                                </div>

                                @if(! empty($stat['badge']))
                                    @if($stat['tone'] === 'up')
                                        <span class="rounded-full bg-[#ebf8ef] px-3.5 py-1.5 text-[13px] font-semibold text-[#26b34e]">{{ $stat['badge'] }}</span>
                                    @elseif($stat['tone'] === 'down')
                                        <span class="rounded-full bg-[#fff1f1] px-3.5 py-1.5 text-[13px] font-semibold text-[#df3c43]">{{ $stat['badge'] }}</span>
                                    @else
                                        <span class="rounded-full bg-[#fff6e7] px-3.5 py-1.5 text-[13px] font-semibold text-[#ff7d0a]">{{ $stat['badge'] }}</span>
                                    @endif
                                @endif
                            </div>

                            <div class="mt-2 flex min-h-[40px] items-end justify-end">
                                @if($stat['spark'])
                                    <svg width="104" height="46" viewBox="0 0 120 54" fill="none">
                                        <path d="{{ $stat['spark'] }}" stroke="{{ $stat['tone'] === 'up' ? '#27b14c' : '#d92f2f' }}" stroke-width="2.4" stroke-linecap="round"/>
                                    </svg>
                                @else
                                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none">
                                        <path d="M12 9v3m0 4h.01M10.29 3.86 1.82 18A2 2 0 0 0 3.53 21h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0Z" stroke="#ff7d0a" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                @endif
                            </div>
                        </article>
                    @endforeach
                </section>

                <section class="rounded-[32px] bg-white px-5 py-6 shadow-[0_18px_44px_rgba(15,23,42,0.08)] ring-1 ring-slate-100 md:px-6">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                        <h2 class="text-[17px] font-semibold uppercase leading-[0.95] tracking-[0.01em] text-[#111827] sm:text-[18px]">Deposit &amp; Withdraw Report</h2>
                        <div class="flex items-center gap-6 text-[14px] text-slate-500">
                            <span>Daily</span>
                            <span>Weekly</span>
                            <span class="border-b-[3px] border-[#1d223a] pb-1 font-semibold text-[#1d223a]">Monthly</span>
                        </div>
                    </div>

                    <div class="mt-6 rounded-[24px] border border-slate-100 bg-[#fcfcfd] px-4 py-4 md:px-5">
                        <div class="flex h-[280px] gap-3 md:gap-4">
                            <div class="flex w-16 flex-col justify-between py-3 text-[12px] text-slate-400 md:w-24 md:text-[13px]">
                                <span>1,200</span>
                                <span>900</span>
                                <span>600</span>
                                <span>300</span>
                                <span>0</span>
                            </div>
                            <div class="relative flex-1">
                                <div class="absolute inset-0">
                                    <div class="absolute inset-x-0 top-[18%] border-t border-slate-100"></div>
                                    <div class="absolute inset-x-0 top-[38%] border-t border-slate-100"></div>
                                    <div class="absolute inset-x-0 top-[58%] border-t border-slate-100"></div>
                                    <div class="absolute inset-x-0 top-[78%] border-t border-slate-100"></div>
                                </div>
                                <div class="relative flex h-full items-end justify-around gap-2 px-2 pb-12 md:px-5">
                                    @foreach($depositBars as $bar)
                                        <div class="flex items-end gap-2">
                                            <div class="w-3 rounded-full bg-[#1bd89c]" style="height: {{ $bar['deposit'] }}px;"></div>
                                            <div class="w-3 rounded-full bg-[#ef2e35]" style="height: {{ $bar['withdrawal'] }}px;"></div>
                                        </div>
                                    @endforeach
                                </div>
                                <div class="absolute inset-x-0 bottom-0 flex justify-around gap-1 pl-1 pr-1 text-[10px] text-slate-400 md:text-[12px]">
                                    @foreach($depositBars as $bar)
                                        <span class="origin-center -rotate-35 whitespace-nowrap">{{ $bar['label'] }}</span>
                                    @endforeach
                                </div>
                            </div>
                        </div>

                        <div class="mt-6 flex flex-wrap items-center justify-center gap-8 text-[14px] font-medium text-slate-500">
                            <div class="flex items-center gap-3">
                                <span class="h-5 w-5 rounded bg-[#1bd89c]"></span>
                                Deposited
                            </div>
                            <div class="flex items-center gap-3">
                                <span class="h-5 w-5 rounded bg-[#ef2e35]"></span>
                                Withdrawal
                            </div>
                        </div>
                    </div>
                </section>

                <section class="rounded-[32px] bg-white px-5 py-6 shadow-[0_18px_44px_rgba(15,23,42,0.08)] ring-1 ring-slate-100 md:px-6">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                        <h2 class="text-[17px] font-semibold uppercase leading-[0.95] tracking-[0.01em] text-[#111827] sm:text-[18px]">Geographical Insight</h2>
                        <div class="flex items-center gap-3">
                            <button type="button" class="rounded-xl bg-[#f0f1fb] px-4 py-2.5 text-[14px] font-semibold text-slate-700">Highest</button>
                            <button type="button" class="rounded-xl bg-[#1b2137] px-4 py-2.5 text-[14px] font-semibold text-white">Save Report</button>
                        </div>
                    </div>

                    <div class="mt-6 grid gap-6 lg:grid-cols-[0.9fr_1.1fr]">
                        <div>
                            <div class="mb-4 grid grid-cols-[1fr_auto] border-b border-slate-200 pb-4 text-[14px] font-semibold uppercase tracking-[0.08em] text-slate-400">
                                <span>Location</span>
                                <span>Users</span>
                            </div>
                            <div class="space-y-3">
                                @foreach($geoRows as $index => $row)
                                    <div class="grid grid-cols-[1fr_auto] items-center rounded-xl {{ $index === 0 ? 'bg-[#eef1ff] px-4 py-4 text-[#1b2f6f]' : 'px-4 py-2 text-slate-700' }}">
                                        <span class="{{ $index === 0 ? 'text-[16px] font-semibold' : 'text-[14px]' }}">{{ $row['location'] }}</span>
                                        <span class="{{ $index === 0 ? 'text-[16px] font-semibold' : 'text-[14px] font-medium' }}">{{ number_format($row['users']) }}</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <div class="relative min-h-[280px] overflow-hidden rounded-[28px] bg-[radial-gradient(circle_at_center,#ffffff_0%,#f8fafc_100%)]">
                            <svg class="absolute inset-0 h-full w-full" viewBox="0 0 460 320" fill="none">
                                <g fill="#d8dde9">
                                    @foreach(range(0, 14) as $row)
                                        @foreach(range(0, 18) as $col)
                                            @php
                                                $x = 22 + ($col * 22);
                                                $y = 20 + ($row * 18);
                                                $inLeft = ($x > 24 && $x < 172 && $y > 96 && $y < 236 && abs($x - 98) + abs($y - 164) < 118);
                                                $inCenter = ($x > 170 && $x < 278 && $y > 78 && $y < 184 && abs($x - 226) + abs($y - 126) < 82);
                                                $inRight = ($x > 258 && $x < 426 && $y > 92 && $y < 230 && abs($x - 338) + abs($y - 160) < 124);
                                                $show = $inLeft || $inCenter || $inRight;
                                            @endphp
                                            @if($show)
                                                <circle cx="{{ $x }}" cy="{{ $y }}" r="3.4"></circle>
                                            @endif
                                        @endforeach
                                    @endforeach
                                </g>

                                <g fill="#1d74e7">
                                    @foreach(range(0, 6) as $row)
                                        @foreach(range(0, 4) as $col)
                                            <circle cx="{{ 300 + ($col * 14) }}" cy="{{ 184 + ($row * 14) }}" r="4.2"></circle>
                                        @endforeach
                                    @endforeach
                                </g>

                                <circle cx="330" cy="226" r="14" fill="#124ec9"></circle>
                            </svg>
                            <div class="absolute bottom-8 left-[58%] rounded-lg bg-[#171f36] px-3 py-2 text-[13px] font-semibold text-white shadow-lg">
                                {{ $geoRows[0]['location'] }}: {{ number_format($geoRows[0]['users']) }}
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        </div>

        <section class="rounded-[32px] bg-white px-5 py-6 shadow-[0_18px_44px_rgba(15,23,42,0.08)] ring-1 ring-slate-100 md:px-6">
            <h2 class="text-[17px] font-semibold uppercase tracking-[0.02em] text-[#111827] sm:text-[18px]">Trends</h2>

            <div class="mt-5 flex flex-wrap items-center gap-x-7 gap-y-4 text-[14px] font-medium text-slate-700">
                @foreach($trendSeries as $series)
                    <div class="flex items-center gap-3">
                        <span class="inline-flex items-center">
                            <span class="h-[3px] w-10" style="background-color: {{ $series['color'] }}"></span>
                            <span class="ml-[-6px] h-4 w-4 rounded-full border-2 border-white" style="background-color: {{ $series['color'] }}"></span>
                        </span>
                        {{ $series['label'] }}
                    </div>
                @endforeach
            </div>

            <div class="mt-6 overflow-x-auto">
                <div class="min-w-[980px] rounded-[28px] bg-[#fcfcfd] px-5 py-5">
                    <svg viewBox="0 0 1200 290" class="w-full">
                        <g stroke="#e8ebf4" stroke-width="1">
                            <line x1="40" y1="220" x2="1160" y2="220"></line>
                            <line x1="40" y1="170" x2="1160" y2="170"></line>
                            <line x1="40" y1="120" x2="1160" y2="120"></line>
                            <line x1="40" y1="70" x2="1160" y2="70"></line>
                        </g>

                        @foreach($trendBars as $bar)
                            <rect x="{{ $bar['x'] }}" y="{{ 220 - $bar['height'] }}" width="8" height="{{ $bar['height'] }}" rx="3" fill="{{ $bar['color'] }}"></rect>
                        @endforeach

                        @foreach($trendSeries as $series)
                            <polyline fill="none" stroke="{{ $series['color'] }}" stroke-width="4" stroke-linecap="round" stroke-linejoin="round" points="{{ $series['points'] }}"></polyline>
                        @endforeach

                        <g fill="#97a0b7" font-size="14">
                            @foreach(['Jan 05', 'Jan 07', 'Jan 09', 'Jan 11', 'Jan 13', 'Jan 15', 'Jan 17', 'Jan 19', 'Jan 21', 'Jan 23', 'Jan 25', 'Jan 27', 'Jan 29', 'Jan 31', 'Feb 02', 'Feb 04', 'Feb 06'] as $index => $label)
                                <text x="{{ 40 + ($index * 70) }}" y="250">{{ $label }}</text>
                            @endforeach
                        </g>
                    </svg>
                </div>
            </div>
        </section>

        @include('admin.partials.plan-usage')
    </div>
</x-admin-layout>

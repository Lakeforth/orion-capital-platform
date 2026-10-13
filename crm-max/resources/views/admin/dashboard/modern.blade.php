<x-admin-layout>
    @php
        $user = auth()->user();
        $displayName = trim((string) ($user->first_name ?? '')) ?: trim((string) ($user->name ?? 'Trader'));
        // Account Status is the account itself (users.user_status), not KYC verification -- admins
        // never verify, so this read PENDING for every brokerage owner.
        $verificationStatus = strtoupper((string) ($user->user_status ?: 'active'));
        // A brokerage's account type is its platform subscription tier (plans), not the
        // client trading plan (account_plans) its admin user happens to be attached to.
        $company = app()->bound('company') ? app('company') : null;
        $accountType = strtoupper((string) ($company?->plan?->title ?? $company?->subscription_type ?? $user->plan?->title ?? 'classic'));

        $services = [
            ['name' => 'Live Trading API', 'enabled' => module_enabled('live_api'), 'icon' => 'heroicon-o-signal'],
            ['name' => 'Elastic Cloud', 'enabled' => module_enabled('cloud_hosting'), 'icon' => 'heroicon-o-cloud'],
            ['name' => 'DDOS Security Protection', 'enabled' => true, 'icon' => 'heroicon-o-shield-check'],
            ['name' => 'Database Back-Up', 'enabled' => module_enabled('cloud_hosting'), 'icon' => 'heroicon-o-circle-stack'],
            ['name' => '24/7 Support', 'enabled' => true, 'icon' => 'heroicon-o-lifebuoy'],
        ];

        $kpis = [
            [
                'icon' => 'heroicon-o-users', 'label' => 'Total Customers',
                'value' => number_format((int) ($data['customers'] ?? 0)), 'change' => '+25%', 'up' => true,
                'sparkPts' => '0,28 17,23 33,25 50,17 67,19 83,9 100,5',
            ],
            [
                'icon' => 'heroicon-o-user-plus', 'label' => 'Online Customers',
                'value' => number_format((int) ($data['online_customers'] ?? 0)), 'change' => '+25%', 'up' => true,
                'sparkPts' => '0,24 20,22 40,25 60,19 80,21 100,15',
            ],
            [
                'icon' => 'heroicon-o-arrow-trending-up', 'label' => 'Leads',
                'value' => number_format((int) ($data['leads'] ?? 0)), 'change' => '-25%', 'up' => false,
                'sparkPts' => '0,7 20,10 40,8 60,17 80,15 100,25',
            ],
            [
                'icon' => 'heroicon-o-banknotes', 'label' => 'Total Deposited',
                'value' => format_price((float) ($data['total_deposited'] ?? 0)), 'change' => null, 'up' => true,
                'sparkPts' => '0,20 20,18 40,19 60,14 80,16 100,10',
            ],
        ];

        // Prop Firm calls this Payout Requests (added below) -- showing both
        // would be a duplicate, so Withdrawal is brokerage-only here.
        if (! module_enabled('propfirm_enabled')) {
            $kpis[] = [
                'icon' => 'heroicon-o-banknotes', 'label' => 'Withdrawal',
                'value' => number_format((int) ($data['pending_withdrawals'] ?? 0)), 'change' => '-25%', 'up' => false,
                'sparkPts' => '0,9 25,13 50,11 75,20 100,23',
            ];
        }

        if (module_enabled('propfirm_enabled')) {
            $kpis = array_merge($kpis, [
                [
                    'icon' => 'heroicon-o-clipboard-document-check', 'label' => 'Active Evaluations',
                    'value' => number_format((int) ($data['active_evaluations'] ?? 0)), 'change' => '', 'up' => true,
                    'sparkPts' => '0,20 25,18 50,19 75,16 100,14',
                ],
                [
                    'icon' => 'heroicon-o-trophy', 'label' => 'Funded Accounts',
                    'value' => number_format((int) ($data['funded_accounts'] ?? 0)), 'change' => '', 'up' => true,
                    'sparkPts' => '0,22 25,19 50,17 75,13 100,9',
                ],
                [
                    'icon' => 'heroicon-o-currency-dollar', 'label' => 'Payout Requests',
                    'value' => number_format((int) ($data['payout_requests'] ?? 0)), 'change' => '', 'up' => true,
                    'sparkPts' => '0,18 25,20 50,16 75,17 100,12',
                ],
            ]);
        }

        $funnel = [
            ['label' => 'Visits', 'value' => '7,175', 'color' => '#22d3ee'],
            ['label' => 'Unique Visits', 'value' => '5,412', 'color' => '#6366f1'],
            ['label' => 'Sign Ups', 'value' => number_format((int) ($data['customers'] ?? 0)), 'color' => '#10b981'],
            ["label" => "FTD's", 'value' => '8,345', 'color' => '#8b5cf6'],
            ["label" => "Self FTD's", 'value' => '8,345', 'color' => '#fb5454'],
        ];

        // Real per-country customer counts, not hardcoded Nigerian cities --
        // this app serves tenants worldwide, and there's no per-city data on
        // the user record anyway (only country).
        $geoColors = ['#6366f1', '#22d3ee', '#10b981', '#8b5cf6', '#f59e0b'];
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
                ->values()
                ->map(fn ($row, $i) => [
                    'location' => ucfirst($row->country),
                    'users' => (int) $row->users,
                    'color' => $geoColors[$i] ?? '#94a3b8',
                    'hl' => $i === 0,
                ])
                ->toArray()
            : [];

        if (empty($geoRows)) {
            $geoRows = [['location' => 'No data yet', 'users' => 0, 'color' => '#94a3b8', 'hl' => false]];
        }

        $trendLegend = [
            ['label' => 'PnL', 'color' => '#fb5454'],
            ['label' => 'Deposit', 'color' => '#0f766e'],
            ['label' => 'Withdrawals', 'color' => '#f59e0b'],
            ['label' => 'Sign ups', 'color' => 'var(--accent2)'],
        ];

        $hasCaps = collect($planUsage ?? [])->contains(fn ($row) => ! ($row['unlimited'] ?? true));
        $planUsageRows = collect($planUsage ?? [])->map(function ($row) {
            $unlimited = $row['unlimited'] ?? true;
            $used = (int) ($row['used'] ?? 0);
            $limit = (int) ($row['limit'] ?? 0);
            $pct = (! $unlimited && $limit > 0) ? min(100, (int) round($used / $limit * 100)) : 0;

            return [
                'label' => $row['label'] ?? '',
                'used' => number_format($used),
                'limit' => $unlimited ? '∞' : number_format($limit),
                'pct' => $pct.'%',
                'near_limit' => (bool) ($row['near_limit'] ?? false),
                'color' => ($row['near_limit'] ?? false) ? '#fb5454' : 'var(--accent)',
            ];
        })->values();
        $anyNearLimit = $planUsageRows->contains('near_limit', true);
    @endphp

    <div style="display:flex; flex-direction:column; gap:24px;">

        {{-- Welcome banner --}}
        <div style="background: linear-gradient(105deg,#1c2434 0%,#28304a 45%,var(--accent) 118%); border-radius:14px; padding:26px 28px; color:#fff; display:flex; align-items:center; gap:28px; flex-wrap:wrap; position:relative; overflow:hidden;">
            <div style="position:absolute;right:-40px;top:-40px;width:220px;height:220px;border-radius:50%;background:radial-gradient(circle,var(--accent2) 0%,transparent 70%);opacity:0.35;"></div>
            <div style="flex:1; min-width:260px; position:relative;">
                <div style="font-size:22px; font-weight:700; margin-bottom:6px;">Welcome back, {{ $displayName }} 👋</div>
                @if($verificationStatus !== 'ACTIVE')
                <div style="font-size:14px; color:#b8c0d4; max-width:460px; line-height:1.5;">Please complete the verification process to unlock full account access.</div>
                @endif
            </div>
            <div style="display:flex; gap:12px; flex-wrap:wrap; position:relative;">
                <div style="background:rgba(255,255,255,0.08); border:1px solid rgba(255,255,255,0.14); border-radius:10px; padding:12px 18px; min-width:150px;">
                    <div style="font-size:11px; letter-spacing:0.5px; color:#9aa4bd; text-transform:uppercase; margin-bottom:5px;">Account Status</div>
                    <div style="font-size:15px; font-weight:700; color:{{ $verificationStatus === 'ACTIVE' ? '#34d399' : '#ffb020' }};">{{ $verificationStatus }}</div>
                </div>
                <div style="background:rgba(255,255,255,0.08); border:1px solid rgba(255,255,255,0.14); border-radius:10px; padding:12px 18px; min-width:150px;">
                    <div style="font-size:11px; letter-spacing:0.5px; color:#9aa4bd; text-transform:uppercase; margin-bottom:5px;">Account Type</div>
                    <div style="font-size:15px; font-weight:700; color:var(--accent2);">{{ $accountType }}</div>
                </div>
            </div>
        </div>

        {{-- KPI cards --}}
        <div style="display:grid; grid-template-columns:repeat(4,1fr); gap:20px;">
            @foreach($kpis as $kpi)
                <div class="qs-modern-card qs-kpi-card" style="transition: transform .16s ease, box-shadow .16s ease, background .2s ease;">
                    <div style="display:flex; align-items:flex-start; justify-content:space-between;">
                        <div style="width:44px; height:44px; border-radius:11px; background:var(--icon-soft); display:flex; align-items:center; justify-content:center; color:var(--accent);">
                            {{ svg($kpi['icon'], 'w-[22px] h-[22px]') }}
                        </div>
                        @if(! empty($kpi['change']))
                            <div style="display:flex;align-items:center;gap:3px;font-size:12px;font-weight:700;padding:4px 9px;border-radius:20px; {{ $kpi['up'] ? 'color:#10b981;background:rgba(16,185,129,0.12);' : 'color:#fb5454;background:rgba(251,84,84,0.12);' }}">
                                <span>{{ $kpi['change'] }}</span>
                                {{ svg($kpi['up'] ? 'heroicon-o-arrow-trending-up' : 'heroicon-o-arrow-trending-down', 'w-[13px] h-[13px]') }}
                            </div>
                        @endif
                    </div>
                    <div style="font-size:27px; font-weight:800; letter-spacing:-0.5px; line-height:1; margin-top:16px;">{{ $kpi['value'] }}</div>
                    <div style="font-size:13px; color:var(--muted); margin-top:6px;">{{ $kpi['label'] }}</div>
                    <svg viewBox="0 0 100 32" preserveAspectRatio="none" style="width:100%;height:34px;margin-top:12px;overflow:visible;">
                        <polyline points="{{ $kpi['sparkPts'] }}" fill="none" stroke="{{ $kpi['up'] ? '#10b981' : '#fb5454' }}" stroke-width="2.4" stroke-linejoin="round" stroke-linecap="round" vector-effect="non-scaling-stroke"/>
                    </svg>
                </div>
            @endforeach
        </div>

        {{-- Deposit & Withdraw + Weekly Trades --}}
        <div style="display:grid; grid-template-columns:2fr 1fr; gap:20px;">
            <div class="qs-modern-card">
                <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:18px; flex-wrap:wrap; gap:12px;">
                    <div>
                        <div style="font-size:16px; font-weight:700;">Deposit &amp; Withdraw Report</div>
                        <div style="display:flex; gap:20px; margin-top:10px;">
                            <div style="display:flex;align-items:center;gap:7px;font-size:12px;color:var(--muted);"><span style="width:11px;height:11px;border-radius:3px;background:#10b981;"></span>Deposited</div>
                            <div style="display:flex;align-items:center;gap:7px;font-size:12px;color:var(--muted);"><span style="width:11px;height:11px;border-radius:3px;background:#fb5454;"></span>Withdrawal</div>
                        </div>
                    </div>
                    <div style="display:flex; background:var(--soft); border-radius:8px; padding:3px;">
                        <button type="button" style="border:none;font-family:inherit;font-size:12px;font-weight:600;padding:6px 14px;border-radius:6px;background:none;color:var(--muted);">Daily</button>
                        <button type="button" style="border:none;font-family:inherit;font-size:12px;font-weight:600;padding:6px 14px;border-radius:6px;background:none;color:var(--muted);">Weekly</button>
                        <button type="button" style="border:none;font-family:inherit;font-size:12px;font-weight:600;padding:6px 14px;border-radius:6px;background:var(--surface);color:var(--accent);box-shadow:0 1px 2px rgba(0,0,0,0.08);">Monthly</button>
                    </div>
                </div>
                <svg viewBox="0 0 720 260" style="width:100%;height:auto;overflow:visible;">
                    <defs>
                        <linearGradient id="gdep" x1="0" y1="0" x2="0" y2="1">
                            <stop offset="0%" stop-color="#10b981" stop-opacity="0.28"/>
                            <stop offset="100%" stop-color="#10b981" stop-opacity="0"/>
                        </linearGradient>
                    </defs>
                    <line x1="0" y1="30" x2="720" y2="30" stroke="var(--grid)" stroke-width="1"/>
                    <line x1="0" y1="90" x2="720" y2="90" stroke="var(--grid)" stroke-width="1"/>
                    <line x1="0" y1="150" x2="720" y2="150" stroke="var(--grid)" stroke-width="1"/>
                    <line x1="0" y1="210" x2="720" y2="210" stroke="var(--grid)" stroke-width="1"/>
                    <path d="M0 200 L60 205 L120 160 L180 120 L240 175 L300 140 L360 60 L420 150 L480 110 L540 175 L600 90 L660 130 L720 70 L720 240 L0 240 Z" fill="url(#gdep)"/>
                    <polyline points="0,200 60,205 120,160 180,120 240,175 300,140 360,60 420,150 480,110 540,175 600,90 660,130 720,70" fill="none" stroke="#10b981" stroke-width="2.5" stroke-linejoin="round" stroke-linecap="round"/>
                    <polyline points="0,225 60,220 120,228 180,205 240,222 300,215 360,232 420,210 480,224 540,214 600,228 660,218 720,226" fill="none" stroke="#fb5454" stroke-width="2.5" stroke-dasharray="1 6" stroke-linecap="round"/>
                </svg>
                <div style="display:flex;justify-content:space-between;font-size:11px;color:var(--muted2);margin-top:8px;">
                    <span>26 Apr</span><span>29 Apr</span><span>02 May</span><span>05 May</span><span>08 May</span><span>11 May</span><span>13 May</span>
                </div>
            </div>

            <div class="qs-modern-card">
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:18px;">
                    <div style="font-size:16px;font-weight:700;">Weekly Trades</div>
                    <div style="display:flex;align-items:center;gap:6px;font-size:12px;color:var(--muted);background:var(--soft);padding:6px 10px;border-radius:8px;">This Week</div>
                </div>
                <svg viewBox="0 0 300 220" style="width:100%;height:auto;">
                    <rect x="14" y="70" width="20" height="120" rx="5" fill="var(--accent2)"/><rect x="14" y="120" width="20" height="70" rx="5" fill="var(--accent)"/>
                    <rect x="54" y="50" width="20" height="140" rx="5" fill="var(--accent2)"/><rect x="54" y="100" width="20" height="90" rx="5" fill="var(--accent)"/>
                    <rect x="94" y="80" width="20" height="110" rx="5" fill="var(--accent2)"/><rect x="94" y="135" width="20" height="55" rx="5" fill="var(--accent)"/>
                    <rect x="134" y="45" width="20" height="145" rx="5" fill="var(--accent2)"/><rect x="134" y="90" width="20" height="100" rx="5" fill="var(--accent)"/>
                    <rect x="174" y="110" width="20" height="80" rx="5" fill="var(--accent2)"/><rect x="174" y="150" width="20" height="40" rx="5" fill="var(--accent)"/>
                    <rect x="214" y="60" width="20" height="130" rx="5" fill="var(--accent2)"/><rect x="214" y="115" width="20" height="75" rx="5" fill="var(--accent)"/>
                    <rect x="254" y="40" width="20" height="150" rx="5" fill="var(--accent2)"/><rect x="254" y="95" width="20" height="95" rx="5" fill="var(--accent)"/>
                </svg>
                <div style="display:flex;justify-content:space-between;font-size:11px;color:var(--muted2);margin-top:8px;padding:0 6px;">
                    <span>M</span><span>T</span><span>W</span><span>T</span><span>F</span><span>S</span><span>S</span>
                </div>
            </div>
        </div>

        {{-- Active Services + Marketing Funnel --}}
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:20px;">
            <div class="qs-modern-card">
                <div style="font-size:16px; font-weight:700; margin-bottom:6px;">Active Services</div>
                <div style="display:flex; flex-direction:column;">
                    @foreach($services as $service)
                        <div style="display:flex; align-items:center; gap:14px; padding:14px 0; border-bottom:1px solid var(--soft);">
                            <div style="width:42px; height:42px; border-radius:11px; background:var(--soft); display:flex; align-items:center; justify-content:center; color:var(--muted);">
                                {{ svg($service['icon'], 'w-5 h-5') }}
                            </div>
                            <div style="font-size:14px; font-weight:600; color:var(--text); flex:1;">{{ $service['name'] }}</div>
                            @if($service['enabled'])
                                <div style="width:28px; height:28px; border-radius:50%; background:#10b981; display:flex; align-items:center; justify-content:center; color:#fff;">
                                    {{ svg('heroicon-o-check', 'w-4 h-4') }}
                                </div>
                            @else
                                <div style="width:28px; height:28px; border-radius:50%; background:#fb5454; display:flex; align-items:center; justify-content:center; color:#fff; font-size:16px; line-height:1;">&times;</div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="qs-modern-card">
                <div style="font-size:16px; font-weight:700; margin-bottom:20px;">Marketing Funnel</div>
                <div style="display:grid; grid-template-columns:150px 1fr; gap:24px; align-items:center;">
                    <div style="display:flex; flex-direction:column; align-items:center; gap:3px;">
                        <div style="width:100%; height:34px; background:#22d3ee; clip-path:polygon(0 0,100% 0,88% 100%,12% 100%);"></div>
                        <div style="width:76%; height:34px; background:#6366f1; clip-path:polygon(0 0,100% 0,84% 100%,16% 100%);"></div>
                        <div style="width:56%; height:34px; background:#10b981; clip-path:polygon(0 0,100% 0,80% 100%,20% 100%);"></div>
                        <div style="width:38%; height:34px; background:#8b5cf6; clip-path:polygon(0 0,100% 0,74% 100%,26% 100%);"></div>
                        <div style="width:22%; height:34px; background:#fb5454; clip-path:polygon(0 0,100% 0,66% 100%,34% 100%);"></div>
                    </div>
                    <div style="display:flex; flex-direction:column; gap:12px;">
                        @foreach($funnel as $step)
                            <div style="display:flex; align-items:center; gap:10px;">
                                <span style="width:12px; height:12px; border-radius:3px; background:{{ $step['color'] }}; flex:none;"></span>
                                <span style="font-size:13px; color:var(--muted); flex:1;">{{ $step['label'] }}</span>
                                <span style="font-size:14px; font-weight:700; color:var(--text);">{{ $step['value'] }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        {{-- Trends --}}
        <div class="qs-modern-card">
            <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:8px; flex-wrap:wrap; gap:12px;">
                <div style="font-size:16px; font-weight:700;">Trends</div>
                <div style="display:flex; gap:18px; flex-wrap:wrap;">
                    @foreach($trendLegend as $t)
                        <div style="display:flex;align-items:center;gap:7px;font-size:12px;color:var(--muted);"><span style="width:16px;height:3px;border-radius:2px;background:{{ $t['color'] }};"></span>{{ $t['label'] }}</div>
                    @endforeach
                </div>
            </div>
            <svg viewBox="0 0 1100 300" style="width:100%;height:auto;">
                <line x1="0" y1="60" x2="1100" y2="60" stroke="var(--grid)"/>
                <line x1="0" y1="130" x2="1100" y2="130" stroke="var(--grid)"/>
                <line x1="0" y1="200" x2="1100" y2="200" stroke="var(--grid)"/>
                <polyline points="0,90 90,85 180,235 270,95 360,150 450,90 540,95 630,175 720,80 810,90 900,88 990,92 1090,88" fill="none" stroke="#fb5454" stroke-width="3" stroke-linejoin="round"/>
                <polyline points="0,255 90,255 180,255 270,250 360,255 450,230 540,255 630,240 720,255 810,232 900,250 990,244 1090,235" fill="none" stroke="#0f766e" stroke-width="3" stroke-linejoin="round"/>
                <polyline points="0,258 90,258 180,255 270,258 360,258 450,258 540,110 630,255 720,190 810,258 900,225 990,258 1090,258" fill="none" stroke="#f59e0b" stroke-width="3" stroke-linejoin="round"/>
                <polyline points="0,258 90,258 180,258 270,255 360,255 450,190 540,255 630,255 720,240 810,150 900,180 990,255 1090,120" fill="none" stroke="var(--accent2)" stroke-width="3" stroke-linejoin="round"/>
                <text x="0" y="290" font-size="12" fill="var(--muted2)">Jan 05</text>
                <text x="270" y="290" font-size="12" fill="var(--muted2)">Jan 13</text>
                <text x="540" y="290" font-size="12" fill="var(--muted2)">Jan 21</text>
                <text x="810" y="290" font-size="12" fill="var(--muted2)">Jan 29</text>
                <text x="1040" y="290" font-size="12" fill="var(--muted2)">Feb 06</text>
            </svg>
        </div>

        {{-- Geographical Insight + Plan Usage --}}
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:20px;">
            <div class="qs-modern-card">
                <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:16px;">
                    <div style="font-size:16px; font-weight:700;">Geographical Insight</div>
                    <div style="display:flex;gap:8px;">
                        <button type="button" style="background:var(--icon-soft);color:var(--accent);border:none;border-radius:8px;padding:8px 14px;font-family:inherit;font-size:12px;font-weight:600;">Highest</button>
                        <button type="button" style="background:linear-gradient(135deg,var(--accent),var(--accent2));color:#fff;border:none;border-radius:8px;padding:8px 14px;font-family:inherit;font-size:12px;font-weight:600;">Save Report</button>
                    </div>
                </div>
                <div style="display:grid; grid-template-columns:32px 1fr auto; gap:10px 12px; align-items:center; font-size:11px; letter-spacing:0.5px; color:var(--muted2); font-weight:600; text-transform:uppercase; padding-bottom:8px; border-bottom:1px solid var(--soft);">
                    <span></span><span>Location</span><span>Users</span>
                </div>
                @foreach($geoRows as $row)
                    <div style="display:grid;grid-template-columns:32px 1fr auto;gap:12px;align-items:center;padding:13px 10px;border-radius:8px;margin:0 -10px; {{ $row['hl'] ? 'background:var(--icon-soft);' : '' }}">
                        <span style="width:22px; height:15px; border-radius:3px; background:{{ $row['color'] }};"></span>
                        <span style="font-size:14px; font-weight:600; color:var(--text);">{{ $row['location'] }}</span>
                        <span style="font-size:14px; font-weight:700; color:var(--text);">{{ number_format($row['users']) }}</span>
                    </div>
                @endforeach
            </div>

            @if(! empty($planUsage) && $hasCaps)
                <div class="qs-modern-card">
                    <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:18px;">
                        <div style="font-size:16px; font-weight:700;">Plan Usage</div>
                        @if($anyNearLimit)
                            <div style="font-size:12px; color:#fb5454; font-weight:600; background:rgba(251,84,84,0.12); padding:5px 11px; border-radius:20px;">Approaching a limit</div>
                        @endif
                    </div>
                    <div style="display:flex; flex-direction:column; gap:18px;">
                        @foreach($planUsageRows as $row)
                            <div>
                                <div style="display:flex; justify-content:space-between; margin-bottom:8px;">
                                    <span style="font-size:13px; color:var(--muted); font-weight:500;">{{ $row['label'] }}</span>
                                    <span style="font-size:13px; font-weight:700;">{{ $row['used'] }}<span style="color:var(--muted2);font-weight:500;">/{{ $row['limit'] }}</span></span>
                                </div>
                                <div style="height:7px; border-radius:5px; background:var(--soft); overflow:hidden;">
                                    <div style="height:100%; border-radius:5px; width:{{ $row['pct'] }}; background:{{ $row['color'] }};"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>

    </div>
</x-admin-layout>

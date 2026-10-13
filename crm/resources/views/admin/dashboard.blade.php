<x-admin-layout header="Board">

    @if(module_enabled('live_api'))
        <div class="bg-[#21C56E] text-white rounded-md shadow px-6 py-4 mb-6 flex items-center">
            <span class="text-lg font-semibold">Live trading is currently active</span>
            <svg class="w-6 h-6 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
            </svg>
        </div>
    @else
        <div class="bg-red-500 text-white p-4 rounded-md mb-4 shadow relative" id="trading-inactive-alert">
            <div class="flex items-start">
                <div class="flex-1">
                    <h3 class="text-xl font-medium mb-1">Live trading is currently inactive</h3>
                    <p class="mb-2">Trading activities will be disabled. Kindly reach out to our support team to initiate the activation of the Live trading.</p>
                    <p>Thank you.</p>
                </div>
                <button type="button" class="text-white hover:text-gray-100 ml-4" onclick="document.getElementById('trading-inactive-alert').remove()">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>

    @endif

    @if(!module_enabled('cloud_hosting'))
            <div class="bg-red-500 text-white p-4 rounded-md mb-4 shadow relative"
                 id="cloud-hosting-alert">
                <div class="flex items-start">
                    <div class="flex-1">
                        <h3 class="text-xl font-medium mb-1">Cloud Hosting is currently inactive</h3>
                        <p class="mb-2">This could result in reduced functionality and slower loading times. We kindly suggest that you reach out to our support team to initiate the activation of the Cloud Hosting.
                            Your prompt action will help optimize your experience.</p>
                        <p>Thank you.</p>
                    </div>
                    <button type="button" class="text-white hover:text-gray-100 ml-4"
                            onclick="document.getElementById('cloud-hosting-alert').remove()">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- LEFT SECTION (spans 2 columns on lg+) --}}
        <div class=" space-y-6">
            {{-- Greetings Card --}}
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-xl font-semibold text-gray-800">Greetings {{ auth()->user()->name }}</h3>
                <p class="text-gray-500 mt-2">Welcome back</p>
            </div>

            {{-- Current Plan --}}
            <div class="bg-white rounded-lg shadow p-6">
                <h4 class="text-lg font-medium text-gray-700 mb-4">Current Plan</h4>
                <div class="flex items-center space-x-4">
                    <div class="p-2">
                        <img src="/images/professional-icon.svg" alt="Professional" class="h-12 w-12" onerror="this.onerror=null; this.src='data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSI0OCIgaGVpZ2h0PSI0OCIgdmlld0JveD0iMCAwIDI0IDI0IiBmaWxsPSJub25lIiBzdHJva2U9IiM2YjcyODAiIHN0cm9rZS13aWR0aD0iMiIgc3Ryb2tlLWxpbmVjYXA9InJvdW5kIiBzdHJva2UtbGluZWpvaW49InJvdW5kIj48cGF0aCBkPSJNMTcuMyA2LjNjLjQtLjQgMS0uNCAxLjQgMGwxIDFjLjQtLjQuNC0xIDAtMS40bC0xLTFhMSAxIDAgMCAwLTEuNC4xbC0xIDFjLjQtLjQuNC0xIDAtMS40bC0xLTFhMSAxIDAgMCAwLTEuNC4xbC0xIDFjLjQtLjQuNC0xIDAtMS40bC0xLTFhMSAxIDAgMCAwLTEuNC4xbC0xIDFjLjQtLjQuNC0xIDAtMS40bC0xLTFhMSAxIDAgMCAwLTEuNC4xbC0xIDFjLjQtLjQuNC0xIDAtMS40bC0xLTFhMSAxIDAgMCAwLTEuNC4xbC0xIDFjLjQtLjQuNC0xIDAtMS40bC0xLTFhMSAxIDAgMCAwLTEuNC4xbC0xIDFjLjQtLjQuNC0xIDAtMS40bC0xLTFhMSAxIDAgMCAwLTEuNC4xbC0xIDFjLjQtLjQuNC0xIDAtMS40bC0xLTFhMSAxIDAgMCAwLTEuNC4xbC0xIDFjLjQtLjQuNC0xIDAtMS40bC0xLTFhMSAxIDAgMCAwLTEuNC4xbC0xIDFjLjQtLjQuNC0xIDAtMS40bC0xLTFhMSAxIDAgMCAwLTEuNC4xbC0xIDFoMTVsLTE1IDIwaDIwbC0xNS0yMGgxNWwtMTUgMjBoMjBsLTE1LTIwaDE1Ii8+PC9zdmc+'" />
                    </div>
                    <div>
                        <p class="font-semibold text-gray-800">Professional</p>
                        @php($validTill = optional(app('company'))->subscription_valid_till)
                        @if(company_subscription_expired())
                            <p class="text-gray-500 text-sm">Expired at {{ optional($validTill)->format('Y-m-d') ?? '—' }}</p>
                        @else
                            <p class="text-gray-500 text-sm">Active</p>
                        @endif

                    </div>
                </div>
            </div>

            {{-- Active Services --}}
            <div class="bg-white rounded-lg shadow p-6">
                <h4 class="text-lg font-medium text-gray-700 mb-4">Active Services</h4>
                <ul class="space-y-4">
                    <li class="flex items-center justify-between">
                        <div class="flex items-center space-x-3">
                            {{-- RSS Icon --}}
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 5c7.18 0 13 5.82 13 13M6 11a7 7 0 017 7M6 17a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 01-1 1H7a1 1 0 01-1-1v-2z" />
                            </svg>
                            <span class="text-gray-700">Live Trading API Feed</span>
                        </div>
                        <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                    </li>
                    <li class="flex items-center justify-between">
                        <div class="flex items-center space-x-3">
                            {{-- Cloud Icon --}}
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 15a4 4 0 004 4h9a5 5 0 10-.1-9.999 5.002 5.002 0 10-9.78 2.096A4.001 4.001 0 003 15z" />
                            </svg>
                            <span class="text-gray-700">Elastic Hosting</span>
                        </div>
                        <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                    </li>
                    <li class="flex items-center justify-between">
                        <div class="flex items-center space-x-3">
                            {{-- Shield Icon --}}
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-9.618 5.04A11.949 11.949 0 0112 21a11.949 11.949 0 019.618-13.016z" />
                            </svg>
                            <span class="text-gray-700">DDOS Security Protection</span>
                        </div>
                        <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                    </li>
                    <li class="flex items-center justify-between">
                        <div class="flex items-center space-x-3">
                            {{-- Database Icon --}}
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4" />
                            </svg>
                            <span class="text-gray-700">Database Backup</span>
                        </div>
                        <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                    </li>
                    <li class="flex items-center justify-between">
                        <div class="flex items-center space-x-3">
                            {{-- Headset Icon --}}
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                            <span class="text-gray-700">24/7 Support</span>
                        </div>
                        <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                    </li>
                </ul>
            </div>
        </div>

        {{-- RIGHT SECTION (stats + contact card) --}}
        <div class="space-y-6">
            {{-- Stats Grid --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                {{-- Customers --}}
                <div class="bg-[#1b5dd3] text-white rounded-lg shadow p-6">
                    <div class="flex items-center mb-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                        <span class="font-medium">Customers</span>
                    </div>
                    <div class="text-2xl font-bold">{{ $data['customers'] }}</div>
                </div>

                {{-- Total Deposited --}}
                <div class="bg-[#0E9F64] text-white rounded-lg shadow p-6">
                    <div class="flex items-center mb-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span class="font-medium">Total Deposited</span>
                    </div>
                    <div class="text-2xl font-bold">{{ format_price((float) ($data['total_deposited'] ?? 0)) }}</div>
                </div>

                {{-- Online Customers --}}
                <div class="bg-[#21C56E] text-white rounded-lg shadow p-6">
                    <div class="flex items-center mb-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                        <span class="font-medium">Online Customers</span>
                    </div>
                    <div class="text-2xl font-bold">{{ $data['online_customers'] }}</div>
                </div>

                {{-- Leads --}}
                @if(module_enabled('enable_leads'))
                    <div class="bg-[#1E2235] text-white rounded-lg shadow p-6">
                        <div class="flex items-center mb-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                            </svg>
                            <span class="font-medium">Leads</span>
                        </div>
                        <div class="text-2xl font-bold">{{ $data['leads'] }}</div>
                    </div>

                @endif

                {{-- Withdrawal [Pending] -- Prop Firm calls this Payout Requests
                     (shown below) so this would just be a duplicate box there. --}}
                @unless(module_enabled('propfirm_enabled'))
                <div class="bg-[#dc3545] text-white rounded-lg shadow p-6">
                    <div class="flex items-center mb-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                        </svg>
                        <span class="font-medium">Withdrawal [Pending]</span>
                    </div>
                    <div class="text-2xl font-bold">{{ $data['pending_withdrawals'] }}</div>
                </div>
                @endunless

                {{-- Deposit [Pending] --}}
                <div class="bg-[#1E2235] text-white rounded-lg shadow p-6">
                    <div class="flex items-center mb-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span class="font-medium">Deposit [Pending]</span>
                    </div>
                    <div class="text-2xl font-bold">{{ $data['pending_deposits'] }}</div>
                </div>

                {{-- KYC Verification --}}
                <div class="bg-[#f8a13f] text-white rounded-lg shadow p-6">
                    <div class="flex items-center mb-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-9.618 5.04A11.949 11.949 0 0112 21a11.949 11.949 0 019.618-13.016z" />
                        </svg>
                        <span class="font-medium">KYC Verification</span>
                    </div>
                    <div class="text-2xl font-bold">{{ $data['pending_kycs'] }}</div>
                </div>

                @if(module_enabled('propfirm_enabled'))
                    {{-- Active Evaluations --}}
                    <div class="bg-[#f8a13f] text-white rounded-lg shadow p-6">
                        <div class="flex items-center mb-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-9.618 5.04A11.949 11.949 0 0112 21a11.949 11.949 0 019.618-13.016z" />
                            </svg>
                            <span class="font-medium">Active Evaluations</span>
                        </div>
                        <div class="text-2xl font-bold">{{ $data['active_evaluations'] ?? 0 }}</div>
                    </div>

                    {{-- Funded Accounts --}}
                    <div class="bg-[#1E2235] text-white rounded-lg shadow p-6">
                        <div class="flex items-center mb-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span class="font-medium">Funded Accounts</span>
                        </div>
                        <div class="text-2xl font-bold">{{ $data['funded_accounts'] ?? 0 }}</div>
                    </div>

                    {{-- Payout Requests --}}
                    <div class="bg-[#dc3545] text-white rounded-lg shadow p-6">
                        <div class="flex items-center mb-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                            </svg>
                            <span class="font-medium">Payout Requests</span>
                        </div>
                        <div class="text-2xl font-bold">{{ $data['payout_requests'] ?? 0 }}</div>
                    </div>
                @endif
            </div>

            {{-- Contact Billing Department --}}
            <div class="bg-white rounded-lg shadow p-6">
                <h4 class="text-lg font-medium text-gray-700 mb-4">Contact Billing Department</h4>
                <div class="flex justify-between">
                    <a href="#" class="flex items-center bg-[#25d366] hover:bg-green-600 text-white px-4 py-2 rounded">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                        </svg>
                        WHATSAPP
                    </a>
                    <a href="#" class="flex items-center bg-[#229ED9] hover:bg-blue-600 text-white px-4 py-2 rounded">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M11.944 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0a12 12 0 0 0-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 0 1 .171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.48.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z"/>
                        </svg>
                        TELEGRAM
                    </a>
                </div>
            </div>
        </div>

        @include('admin.partials.plan-usage')
    </div>
</x-admin-layout>

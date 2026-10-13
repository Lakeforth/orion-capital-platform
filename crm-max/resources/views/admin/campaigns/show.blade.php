<x-admin-layout>
    <x-slot name="header"></x-slot>
    <x-splade-modal max-width="full w-full max-w-6xl">
        <div class="px-6 pt-5 pb-4">
            <h2 class="font-bold text-xl text-gray-900">{{ $campaign->name }}</h2>
            <p class="text-xs text-gray-500 mt-0.5">Campaign Details</p>
        </div>

        <!-- Compact meta strip -->
        <div class="flex flex-wrap items-center gap-3 px-6 pb-4 border-b">
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold {{ $campaign->is_active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                <span class="h-1.5 w-1.5 rounded-full {{ $campaign->is_active ? 'bg-green-500' : 'bg-red-500' }}"></span>
                {{ $campaign->is_active ? 'Active' : 'Inactive' }}
            </span>

            @if($campaign->language)
                <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs bg-gray-100 text-gray-600">
                    🌐 {{ $campaign->language }}
                </span>
            @endif

            @if($campaign->source)
                <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs bg-blue-50 text-blue-700">
                    📄 {{ $campaign->source }}
                </span>
            @endif

            @if($campaign->origin)
                <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs bg-purple-50 text-purple-700">
                    {{ $campaign->origin }}
                </span>
            @endif

            <span class="text-xs text-gray-400 ml-auto">Created {{ $campaign->created_at->format('M j, Y') }}</span>
        </div>

        <div class="px-6 py-4">
            <!-- Survey URL -->
            @php
                $campaignBaseUrl = rtrim($campaign->company->settings['front_end_url'] ?? url('/'), '/');
            @endphp
            <div class="mb-4 rounded-lg border border-blue-100 bg-blue-50 p-3">
                <p class="mb-1.5 text-xs font-semibold uppercase tracking-wide text-blue-700">Web Form Survey URL</p>
                <div class="flex items-center gap-2">
                    <input type="text" readonly
                        id="survey-url-{{ $campaign->id }}"
                        value="{{ $campaignBaseUrl . '/c/' . $campaign->id }}"
                        class="flex-1 rounded-lg border border-blue-200 bg-white px-3 py-1.5 text-sm text-gray-700 font-mono">
                    <button type="button" onclick="copySurveyUrl('{{ $campaign->id }}')"
                        class="shrink-0 rounded-lg bg-blue-600 px-4 py-1.5 text-sm font-semibold text-white hover:bg-blue-700 transition-colors">
                        Copy
                    </button>
                </div>
            </div>

            @if($campaign->campaign_logo)
                <div class="mb-4 rounded-lg border">
                    <img src="{{ $campaign->campaign_logo }}" alt="Campaign Logo" class="max-h-16">
                </div>
            @endif

            @if($campaign->campaign_favicon)
                <div class="mb-4 rounded-lg border">
                    <img src="{{ $campaign->campaign_favicon }}" alt="Campaign Logo" class="max-h-16">
                </div>
            @endif

            @if($campaign->notes)
                <div class="mb-4 rounded-lg border bg-amber-50 p-3 text-sm italic text-gray-600 leading-relaxed">
                    {{ $campaign->notes }}
                </div>
            @endif

            @if($campaign->whatsapp_number)
                <p>Whatsapp Number</p>
                <div class="mb-4 rounded-lg border bg-amber-50 p-3 text-sm italic text-gray-600 leading-relaxed">
                    {{ $campaign->whatsapp_number }}
                </div>

                @if($campaign->whatsapp_message)
                    <p>Whatsapp Message</p>
                    <div class="mb-4 rounded-lg border bg-amber-50 p-3 text-sm italic text-gray-600 leading-relaxed">
                        {{ $campaign->whatsapp_message }}
                    </div>
                @endif
            @endif

            @if($campaign->cold_lead_chat_id)
                <p>Cold Lead Telegram Chat ID</p>
                <div class="mb-4 rounded-lg border bg-amber-50 p-3 text-sm italic text-gray-600 leading-relaxed">
                    {{ $campaign->cold_lead_chat_id }}
                </div>
            @endif

            @if($campaign->warm_lead_chat_id)
                <p>Warm Lead Telegram Chat ID</p>
                <div class="mb-4 rounded-lg border bg-amber-50 p-3 text-sm italic text-gray-600 leading-relaxed">
                    {{ $campaign->warm_lead_chat_id }}
                </div>
            @endif

            <!-- Leads table -->
            <div class="mb-2 flex items-center justify-between">
                <div>
                    <h3 class="text-sm font-bold text-gray-900">Leads Captured</h3>
                    <p class="text-xs text-gray-500">All leads submitted via the web form survey</p>
                </div>
            </div>

            <x-splade-table class="mt-2" :for="$data">
                <x-splade-cell created_at as="$item">
                    <span class="text-xs text-gray-500">{{ $item->created_at->diffForHumans() }}</span>
                </x-splade-cell>

                <x-splade-cell first_name as="$item">
                    <span class="text-sm text-gray-800">{{ $item->first_name }}</span>
                </x-splade-cell>

                <x-splade-cell last_name as="$item">
                    <span class="text-sm text-gray-800">{{ $item->last_name }}</span>
                </x-splade-cell>

                <x-splade-cell email as="$item">
                    <span class="text-sm text-gray-700">{{ $item->email }}</span>
                </x-splade-cell>

                <x-splade-cell phone as="$item">
                    <span class="text-sm text-gray-700">{{ $item->phone }}</span>
                </x-splade-cell>

                <x-splade-cell country_of_residence as="$item">
                    <span class="text-sm text-gray-700">{{ $item->country_of_residence }}</span>
                </x-splade-cell>

                <x-splade-cell speak_english as="$item">
                    <span class="text-sm text-gray-700">{{ $item->speak_english }}</span>
                </x-splade-cell>

                <x-splade-cell expected_earning as="$item">
                    <span class="text-sm text-gray-700">{{ $item->expected_earning }}</span>
                </x-splade-cell>

                <x-splade-cell income_type as="$item">
                    <span class="text-sm text-gray-700">{{ $item->income_type }}</span>
                </x-splade-cell>

                <x-splade-cell income as="$item">
                    <span class="text-sm text-gray-700">{{ $item->income }}</span>
                </x-splade-cell>

                <x-splade-cell age_bracket as="$item">
                    <span class="text-sm text-gray-700">{{ $item->age_bracket }}</span>
                </x-splade-cell>

                <x-splade-cell occupation as="$item">
                    <span class="text-sm text-gray-700">{{ $item->occupation }}</span>
                </x-splade-cell>

                <x-splade-cell occupation_level as="$item">
                    <span class="text-sm text-gray-700">{{ $item->occupation_level }}</span>
                </x-splade-cell>

                <x-splade-cell investment_experience as="$item">
                    <span class="text-sm text-gray-700">{{ $item->investment_experience }}</span>
                </x-splade-cell>

                <x-splade-cell investment_goal as="$item">
                    <span class="text-sm text-gray-700">{{ $item->investment_goal }}</span>
                </x-splade-cell>

                <x-splade-cell investment_amount as="$item">
                    <span class="text-sm text-gray-700">{{ $item->investment_amount }}</span>
                </x-splade-cell>

                <x-splade-cell got_to_landing_page as="$item">
                    <span class="text-sm text-gray-700">{{ $item->got_to_landing_page ? 'Yes' : 'No' }}</span>
                </x-splade-cell>

                <x-splade-cell clicked_checkout_button as="$item">
                    <span class="text-sm text-gray-700">{{ $item->clicked_checkout_button ? 'Yes' : 'No' }}</span>
                </x-splade-cell>

                <x-splade-cell ip_address as="$item">
                    @if($item->ip_address)
                        <div class="space-y-1">
                            <span class="font-mono text-xs text-gray-500 block truncate max-w-[120px]" title="{{ $item->ip_address }}">
                                {{ $item->ip_address }}
                            </span>
                            @if($item->country)
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-50 text-blue-700 border border-blue-100">
                                    {{ $item->country }}
                                </span>
                            @endif
                        </div>
                    @else
                        <span class="text-gray-300 text-sm">—</span>
                    @endif
                </x-splade-cell>

                <x-splade-cell device_type as="$item">
                    @if($item->device_type)
                        @php
                            $deviceIcon = match(strtolower($item->device_type)) {
                                'mobile'  => '📱',
                                'tablet'  => '📟',
                                default   => '🖥️',
                            };
                        @endphp
                        <span class="inline-flex items-center gap-1 text-xs text-gray-600 capitalize">
                            {{ $deviceIcon }} {{ $item->device_type }}
                        </span>
                    @else
                        <span class="text-gray-300 text-sm">—</span>
                    @endif
                </x-splade-cell>

                <x-splade-cell browser_type as="$item">
                    @if($item->browser_type)
                        <span class="text-xs text-gray-600">{{ $item->browser_type }}</span>
                    @else
                        <span class="text-gray-300 text-sm">—</span>
                    @endif
                </x-splade-cell>
            </x-splade-table>
        </div>

        @if ($campaign->welcome_message_enabled && $campaign->welcomeMessage)
            @php
                $siteName     = get_site_name();
                $supportEmail = company_settings('support_email', '');
                $previewBody  = str_replace(
                    ['{{first_name}}', '{{email}}', '{{password}}', '{{login_url}}', '{{site_name}}', '{{support_email}}'],
                    ['John',           'john@example.com', '&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;', '#', $siteName, $supportEmail],
                    $campaign->welcomeMessage->body
                );
                $previewSubject = str_replace('{{site_name}}', $siteName, $campaign->welcomeMessage->subject);
            @endphp
            <div class="border-t px-6 py-5">
                <h3 class="mb-1 text-sm font-bold text-gray-900">Welcome Email Preview</h3>
                <p class="mb-3 text-xs text-gray-500">This is what leads from this campaign will receive.</p>
                <div class="rounded-xl border bg-white overflow-hidden">
                    <div class="border-b bg-slate-50 px-5 py-3">
                        <p class="text-xs text-gray-500 mb-0.5 uppercase tracking-wide font-medium">Subject</p>
                        <p class="font-semibold text-gray-800 text-sm">{{ $previewSubject }}</p>
                    </div>
                    <div class="px-5 py-5 text-gray-700 leading-relaxed text-sm">{!! $previewBody !!}</div>
                </div>
            </div>
        @endif
    </x-splade-modal>

    <script>
    function copySurveyUrl(id) {
        const el = document.getElementById('survey-url-' + id);
        el.select();
        navigator.clipboard.writeText(el.value).then(function() {
            const btn = el.nextElementSibling;
            btn.textContent = 'Copied!';
            setTimeout(() => btn.textContent = 'Copy', 2000);
        });
    }
    </script>
</x-admin-layout>

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
                           value="{{ $campaignBaseUrl . '/survey?campaign=' . $campaign->id }}"
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

            <!-- Leads table -->
            <div class="mb-2 flex items-center justify-between">
                <div>
                    <h3 class="text-sm font-bold text-gray-900">Leads Captured</h3>
                    <p class="text-xs text-gray-500">All leads submitted via the web form survey</p>
                </div>
            </div>

            <x-splade-table class="mt-2" :for="$data">
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

                <x-splade-cell amount_lost as="$item">
                    <span class="text-sm text-gray-700">{{ $item->amount_lost }}</span>
                </x-splade-cell>

                <x-splade-cell scam_type as="$item">
                    <span class="text-sm text-gray-700">{{ $item->scam_type }}</span>
                </x-splade-cell>

                <x-splade-cell scam_first_payment as="$item">
                    <span class="text-sm text-gray-700">{{ $item->scam_first_payment ? date('F j, Y', strtotime($item->scam_first_payment)) : '' }}</span>
                </x-splade-cell>

                <x-splade-cell scam_payment_method as="$item">
                    <span class="text-sm text-gray-700">{{ $item->scam_payment_method }}</span>
                </x-splade-cell>

                <x-splade-cell created_at as="$item">
                    <span class="text-xs text-gray-500">{{ $item->created_at->diffForHumans() }}</span>
                </x-splade-cell>
            </x-splade-table>
        </div>
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

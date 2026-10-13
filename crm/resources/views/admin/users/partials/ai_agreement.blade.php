<div class="mt-4">
    <h2 class="font-semibold text-lg text-gray-700 leading-tight mb-3">Investment Agreement</h2>

    @if(! $user->hasCompletedAiTradingAgreement())
        <div class="text-center text-gray-500 py-10">
            This user has not yet accepted the Investment Agreement / Risk Disclaimer.
        </div>
    @else
        @php
            $durationLabels = ['12_24' => '12–24 months', '36_60' => '36–60 months'];
            $marketLabels = [
                'commodities' => 'Commodities',
                'forex' => 'Forex',
                'crypto' => 'Crypto',
                'indices' => 'Indices',
                'stocks' => 'Stocks',
            ];
            $instrumentLabels = [
                'gold' => 'Gold',
                'silver' => 'Silver',
                'crude_oil' => 'Crude Oil',
                'natural_gas' => 'Natural Gas',
                'copper' => 'Copper',
                'platinum' => 'Platinum',
            ];
            $selectedMarkets = $user->ai_agreement_markets ?? [];
            $selectedInstruments = $user->ai_agreement_commodity_instruments ?? [];
        @endphp

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 max-w-2xl">
            <div class="border border-gray-200 rounded-xl p-3 bg-white">
                <p class="text-xs text-gray-400 mb-1">Agreement duration</p>
                <p class="text-sm font-semibold text-gray-700">
                    {{ $durationLabels[$user->ai_agreement_duration] ?? ($user->ai_agreement_duration ?: '—') }}
                </p>
            </div>

            <div class="border border-gray-200 rounded-xl p-3 bg-white">
                <p class="text-xs text-gray-400 mb-1">Accepted</p>
                <p class="text-sm font-semibold text-gray-700">
                    {{ $user->ai_agreement_accepted_at?->format('j F Y, g:ia') ?? '—' }}
                </p>
            </div>

            <div class="border border-gray-200 rounded-xl p-3 bg-white sm:col-span-2">
                <p class="text-xs text-gray-400 mb-2">AI markets selected</p>
                @if(count($selectedMarkets))
                    <div class="flex flex-wrap gap-2">
                        @foreach($selectedMarkets as $market)
                            <span class="text-xs font-medium px-2.5 py-1 rounded-full bg-blue-50 text-blue-700 border border-blue-100">
                                {{ $marketLabels[$market] ?? $market }}
                            </span>
                        @endforeach
                    </div>
                @else
                    <p class="text-sm text-gray-400">None recorded</p>
                @endif
            </div>

            @if(in_array('commodities', $selectedMarkets, true))
                <div class="border border-gray-200 rounded-xl p-3 bg-white sm:col-span-2">
                    <p class="text-xs text-gray-400 mb-2">Commodity instruments</p>
                    @if(count($selectedInstruments))
                        <div class="flex flex-wrap gap-2">
                            @foreach($selectedInstruments as $instrument)
                                <span class="text-xs font-medium px-2.5 py-1 rounded-full bg-amber-50 text-amber-700 border border-amber-100">
                                    {{ $instrumentLabels[$instrument] ?? $instrument }}
                                </span>
                            @endforeach
                        </div>
                    @else
                        <p class="text-sm text-gray-400">None recorded</p>
                    @endif
                </div>
            @endif
        </div>
    @endif
</div>

{{-- Renders a transaction's `details` JSON for the admin tables. Everything is
     printed as a plain key/value line except an uploaded proof of payment,
     which is a URL and only useful as something you can actually open. --}}
@if(isset($item->details) && is_array($item->details))
    @foreach($item->details as $key => $value)
        @continue($key === 'payment_proof')
        <p><span class="font-semibold">{{ ucfirst(str_replace('_', ' ', $key)) }}:</span> {{ is_scalar($value) ? $value : json_encode($value) }}</p>
    @endforeach

    @if(! empty($item->details['payment_proof']))
        @php($proof = $item->details['payment_proof'])
        @php($isPdf = str_ends_with(strtolower((string) parse_url($proof, PHP_URL_PATH)), '.pdf'))
        <a href="{{ $proof }}" target="_blank" rel="noopener noreferrer"
           class="mt-1 inline-flex items-center gap-2 text-blue-600 hover:underline">
            @unless($isPdf)
                <img src="{{ $proof }}" alt="Proof of payment" class="h-12 w-12 rounded border object-cover" />
            @endunless
            <span class="font-semibold">Proof of payment{{ $isPdf ? ' (PDF)' : '' }}</span>
        </a>
    @endif
@endif

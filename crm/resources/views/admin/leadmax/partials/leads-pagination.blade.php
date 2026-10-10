@if($paginator->hasPages())
@php $placement = $placement ?? 'bottom'; @endphp
<nav class="lm-pagination lm-pagination-{{ $placement }}" aria-label="Leads pagination {{ $placement }}">
    <span>Showing {{ $paginator->firstItem() }}–{{ $paginator->lastItem() }} of {{ number_format($paginator->total()) }}</span>
    <div class="lm-pagination-controls">
        @if($paginator->onFirstPage())<span class="lm-page lm-page-direction" aria-disabled="true">Previous</span>@else<a class="lm-page lm-page-direction" rel="prev" href="{{ $paginator->previousPageUrl() }}">Previous</a>@endif
        @foreach($paginator->onEachSide(1)->linkCollection()->slice(1, -1) as $link)
            @if($link['active'])<span class="lm-page is-current" aria-current="page">{{ $link['label'] }}</span>
            @elseif($link['url'])<a class="lm-page" aria-label="Go to page {{ $link['label'] }}" href="{{ $link['url'] }}">{{ $link['label'] }}</a>
            @else<span class="lm-page-gap">{{ $link['label'] }}</span>@endif
        @endforeach
        @if($paginator->hasMorePages())<a class="lm-page lm-page-direction" rel="next" href="{{ $paginator->nextPageUrl() }}">Next</a>@else<span class="lm-page lm-page-direction" aria-disabled="true">Next</span>@endif
    </div>
</nav>
@endif

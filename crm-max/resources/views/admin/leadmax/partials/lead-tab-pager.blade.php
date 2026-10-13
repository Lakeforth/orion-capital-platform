{{-- Table footer: "Showing a-b of n", Previous / Page x of y / Next (Figma TableFooter). --}}
<div class="lm-tab-foot {{ ($bare ?? false) ? 'is-bare' : '' }}">
    <span>Showing {{ $rows->firstItem() ?? 0 }}-{{ $rows->lastItem() ?? 0 }} of {{ $rows->total() }}{{ $noun ? ' '.$noun : '' }}</span>
    <div class="lm-tab-pager">
        @if($rows->onFirstPage())<span class="lm-pager-btn is-disabled"><img src="{{ asset('images/leadmax/details/chevron-left.svg') }}" width="10" height="10" alt="">Previous</span>@else<a class="lm-pager-btn" href="{{ $rows->previousPageUrl() }}"><img src="{{ asset('images/leadmax/details/chevron-left.svg') }}" width="10" height="10" alt="">Previous</a>@endif
        <span class="lm-pager-page">Page {{ $rows->currentPage() }} of {{ max(1, $rows->lastPage()) }}</span>
        @if($rows->hasMorePages())<a class="lm-pager-btn is-next" href="{{ $rows->nextPageUrl() }}">Next<img src="{{ asset('images/leadmax/details/chevron-right.svg') }}" width="10" height="10" alt=""></a>@else<span class="lm-pager-btn is-next is-disabled">Next<img src="{{ asset('images/leadmax/details/chevron-right.svg') }}" width="10" height="10" alt=""></span>@endif
    </div>
</div>

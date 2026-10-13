@if($paginator->hasPages())
<div class="leadmax-pagination"><span>Showing {{ $paginator->firstItem() }}–{{ $paginator->lastItem() }} of {{ $paginator->total() }}</span><div>{{ $paginator->onEachSide(1)->links() }}</div></div>
@endif

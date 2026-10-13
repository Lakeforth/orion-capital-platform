<div class="min-w-[105px] text-sm">
    <p class="text-gray-600">Open: <Link href="{{ route('admin.user.trades', [$item->id, 'open']) }}" class="text-[#0d63cc]">{{ $item->open_trades_count }}</Link></p>
    <p class="text-gray-500">Closed: {{ $item->closed_trades_count }}</p>
</div>

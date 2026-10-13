<div>
    <Link modal class="text-green-500 flex my-2 items-center gap-2" href="{{ route('admin.notes.index') }}?user_id={{ $item->id }}">{{ $item->notes_count }} notes
    {{ svg('heroicon-s-pencil-square', 'h-3') }}
    </Link>

    @if(is_admin())

        <UserToggleButtons
            copy-trader-route="{{ route('admin.user.toggle', ['id' => $item->id, 'field' => 'copy_trader']) }}"
            :copy-trader-active="{{ $item->copy_trader ? 'true' : 'false' }}"
            require-otp-route="{{ route('admin.user.toggle', ['id' => $item->id, 'field' => 'require_otp']) }}"
            :require-otp-active="{{ $item->require_otp ? 'true' : 'false' }}"
            can-email-route="{{ route('admin.user.toggle', ['id' => $item->id, 'field' => 'can_email']) }}"
            :can-email-active="{{ $item->can_email ? 'true' : 'false' }}"
        />

    @endif

</div>


<x-admin-layout>
    <x-slot name="header"></x-slot>
    <x-splade-modal max-width="4xl" close-explicitly class="{{ isMini() ? 'mini-add-modal' : '' }}">
        @php
            $modalRole = ucwords(str_replace(['_', '-'], ' ', (string) ($role ?? 'staff')));
        @endphp
        <h2 class="font-semibold text-xl text-gray-800 leading-tight {{ isMini() ? 'mini-add-modal-title' : '' }}">
            {{ isMini() ? 'Add '.$modalRole : 'Add new '.$modalRole }}
        </h2>
        <div class="{{ isMini() ? 'py-6' : 'py-12' }}">

            <x-splade-form :default="$default" class="space-y-2" action="{{ route('admin.users.store') }}">

                @include('admin.users.form',['method' => 'store', 'role' => $role])

                <x-splade-submit class="mt-3" class="mt-3" label="Submit" />

            </x-splade-form>

        </div>

        <x-splade-script>
            document.querySelectorAll('[data-toggle-password]').forEach((button) => {
                if (button.dataset.passwordToggleBound === '1') {
                    return;
                }

                button.dataset.passwordToggleBound = '1';

                button.addEventListener('click', () => {
                    const targetId = button.getAttribute('data-target');
                    const input = targetId ? document.getElementById(targetId) : null;

                    if (!input) return;

                    const showing = input.type === 'text';
                    input.type = showing ? 'password' : 'text';
                });
            });
        </x-splade-script>
    </x-splade-modal>
</x-admin-layout>

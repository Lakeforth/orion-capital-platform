<x-guest-layout>
    <x-auth-card>
        @php($passwordConfirmRoute = app()->has('company') ? 'password.confirm' : 'super.password.confirm')
        <div class="mb-4 text-sm text-gray-600">
            {{ __('This is a secure area of the application. Please confirm your password before continuing.') }}
        </div>

        <x-splade-form action="{{ route($passwordConfirmRoute) }}" class="space-y-4">
            <div class="relative">
                <x-splade-input id="confirm_password" type="password" name="password" required autocomplete="current-password" :label="__('Password')" class="pr-12" />
                <button type="button"
                        data-toggle-password
                        data-target="confirm_password"
                        aria-label="Toggle password visibility"
                        class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-500 hover:text-gray-800">
                    <svg class="h-5 w-5 toggle-eye-open" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    <svg class="hidden h-5 w-5 toggle-eye-closed" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3l18 18M10.477 10.48a3 3 0 014.243 4.243M9.88 9.88a3 3 0 00-.707 3.247M6.228 6.228C4.33 7.538 2.933 9.534 2.458 12c1.274 4.057 5.064 7 9.542 7a9.96 9.96 0 004.772-1.183M15.53 15.53A9.957 9.957 0 0112 19c-4.477 0-8.268-2.943-9.542-7a9.956 9.956 0 012.18-3.568" />
                    </svg>
                </button>
            </div>

            <div class="flex justify-end">
                <x-splade-submit :label="__('Confirm')" />
            </div>
        </x-splade-form>
    </x-auth-card>

    <x-splade-script>
        document.querySelectorAll('[data-toggle-password]').forEach((button) => {
            button.addEventListener('click', () => {
                const targetId = button.getAttribute('data-target');
                const input = document.getElementById(targetId);
                if (!input) return;
                const showing = input.type === 'text';
                input.type = showing ? 'password' : 'text';
                const openIcon = button.querySelector('.toggle-eye-open');
                const closedIcon = button.querySelector('.toggle-eye-closed');
                if (openIcon && closedIcon) {
                    openIcon.classList.toggle('hidden', !showing);
                    closedIcon.classList.toggle('hidden', showing);
                }
            });
        });
    </x-splade-script>
</x-guest-layout>

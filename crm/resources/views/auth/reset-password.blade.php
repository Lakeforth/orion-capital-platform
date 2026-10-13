<x-guest-layout>
    @php($tenant = app()->has('company'))
    @php($email = old('email', $request->email))
    @php(\Illuminate\Support\Facades\View::share('spladePageTitle', 'Reset password | '.get_site_name().' CRM'))

    <x-auth-screen title="Choose a new password" subtitle="Use at least 8 characters. Once it's saved you can sign in with it.">
        {{-- An expired or already used link comes back as an email error. --}}
        @error('email')
            <div class="lm-auth-alert" role="alert">
                <p>{{ $message }}</p>
                <a href="{{ route($tenant ? 'password.request' : 'super.password.request') }}" class="lm-auth-link">Request a new link</a>
            </div>
        @enderror

        <form method="POST" action="{{ route($tenant ? 'password.store' : 'super.password.store') }}" class="lm-auth-form" novalidate>
            @csrf
            <input type="hidden" name="token" value="{{ $request->route('token') }}">

            <div>
                <label for="email" class="lm-auth-label">Email</label>
                <input id="email" type="email" name="email" value="{{ $email }}" class="lm-auth-field" autocomplete="username" required
                    @if($email) readonly @else autofocus @endif />
            </div>

            <div>
                <label for="reset_password" class="lm-auth-label">New password</label>
                <span class="lm-pw-wrap">
                    <input id="reset_password" type="password" name="password" class="lm-auth-field" autocomplete="new-password" minlength="8" required data-lm-pw-generate
                        @if($email) autofocus @endif
                        @error('password') aria-invalid="true" aria-describedby="password-error" @enderror />
                    @include('auth.partials.password-eye')
                </span>
                @error('password')<p id="password-error" class="lm-auth-error" role="alert">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="reset_password_confirmation" class="lm-auth-label">Confirm new password</label>
                <span class="lm-pw-wrap">
                    <input id="reset_password_confirmation" type="password" name="password_confirmation" class="lm-auth-field" autocomplete="new-password" required />
                    @include('auth.partials.password-eye')
                </span>
            </div>

            <button type="submit" class="lm-auth-submit" data-lm-auth-submit="Saving…">Reset password</button>
        </form>

        <p class="lm-auth-back"><a href="{{ route($tenant ? 'login' : 'super.login') }}" class="lm-auth-link">&larr; Back to sign in</a></p>
    </x-auth-screen>
</x-guest-layout>

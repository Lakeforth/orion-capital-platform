<x-guest-layout>
    @php($tenant = app()->has('company'))
    @php(\Illuminate\Support\Facades\View::share('spladePageTitle', 'Forgot password | '.get_site_name().' CRM'))

    <x-auth-screen title="Forgot your password?" subtitle="Enter your email and we'll send you a link to choose a new one.">
        @if(session('status'))
            <p class="lm-auth-status" role="status">{{ session('status') }} Check your inbox, and your spam folder if it isn't there.</p>
        @endif

        <form method="POST" action="{{ route($tenant ? 'password.email' : 'super.password.email') }}" class="lm-auth-form" novalidate>
            @csrf
            <div>
                <label for="email" class="lm-auth-label">Email</label>
                <input id="email" type="email" name="email" value="{{ old('email') }}" class="lm-auth-field" placeholder="name@example.com" autocomplete="username" required autofocus
                    @error('email') aria-invalid="true" aria-describedby="email-error" @enderror />
                @error('email')<p id="email-error" class="lm-auth-error" role="alert">{{ $message }}</p>@enderror
            </div>

            <button type="submit" class="lm-auth-submit" data-lm-auth-submit="Sending…">Email reset link</button>
        </form>

        <p class="lm-auth-back"><a href="{{ route($tenant ? 'login' : 'super.login') }}" class="lm-auth-link">&larr; Back to sign in</a></p>
    </x-auth-screen>
</x-guest-layout>

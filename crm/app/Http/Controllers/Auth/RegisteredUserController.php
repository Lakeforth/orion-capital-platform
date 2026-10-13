<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use App\Services\EmailService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rules;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        return view('auth.register');
    }

    public function store(Request $request): \Illuminate\Foundation\Application|\Illuminate\Routing\Redirector|\Illuminate\Http\RedirectResponse|\Illuminate\Contracts\Foundation\Application
    {

        $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:'.User::class],
            //            'username' => ['required', 'string', 'max:255', 'unique:'.User::class],
            'phone' => ['required', 'string', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = User::create([
            'first_name' => request('first_name'),
            'last_name' => request('last_name'),
            'email' => request('email'),
            'username' => request('username'),
            'phone' => request('phone'),
            'password' => Hash::make(request('password')),
        ]);

        if (! app()->has('company')) {
            Role::firstOrCreate([
                'name' => 'tenant_admin',
            ], [
                'display_name' => 'Tenant Admin',
                'description' => 'Tenant Admin',
            ]);
            $user->addRole('tenant_admin');
        } else {
            $user->addRole('customer');
        }

        if ($user->welcomeEmailEnabled() && is_null($user->welcome_email_at)) {
            try {
                (new EmailService)->sendWelcomeEmail($user);
                $user->forceFill(['welcome_email_at' => now()])->saveQuietly();
            } catch (\Throwable $exception) {
                Log::error('welcome_email.send_failed', [
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'context' => 'web_signup',
                    'message' => $exception->getMessage(),
                ]);
            }
        }

        event(new Registered($user));

        Auth::login($user);

        if (! app()->has('company')) {
            return redirect()->route('super.dashboard');
        }

        return redirect('/dashboard');
    }
}

<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

/**
 * The signed-in user's own account settings, inside the CRM-Max shell.
 *
 * The profile entry in the topbar used to point at the central panel's profile page, which dropped
 * the user out of LeadMax entirely. This covers the same ground -- name, phone, avatar -- without
 * leaving the shell, and only ever touches the authenticated user's own record.
 */
class LeadMaxAccountController extends LeadMaxController
{
    public function index(Request $request): View
    {
        return view('admin.leadmax.account', [
            ...$this->common($request, 'account'),
            'account' => $request->user(),
            // Security section: the recorded sign-ins for this account (web and API), newest first.
            'recentLogins' => \App\Models\LoginHistory::query()->where('user_id', $request->user()->id)->latest()->limit(5)->get(),
        ]);
    }

    public function save(Request $request)
    {
        $user = $request->user();
        $data = $request->validate([
            'first_name' => 'required|string|max:120',
            'last_name' => 'nullable|string|max:120',
            'phone' => 'nullable|string|max:40',
            'avatar' => 'nullable|image|mimes:png,jpg,jpeg,webp|max:2048',
        ]);

        $values = ['first_name' => $data['first_name'], 'last_name' => $data['last_name'] ?? '', 'phone' => $data['phone'] ?? null];
        if ($request->hasFile('avatar')) {
            // The public disk rather than s3: the deploy runs storage:link, and s3 is not
            // configured for every tenant. asset('storage/...') is the shape User::avatar expects.
            $values['avatar'] = asset('storage/'.$request->file('avatar')->store('avatars/'.$user->getKey(), 'public'));
        }
        $user->forceFill($values)->save();

        return redirect()->route('admin.leadmax.account')->with('success', 'Account settings saved.');
    }

    /** The CRM's Google Translate languages (the header dropdown offers the same list). */
    public const LANGUAGES = ['en' => 'English', 'ar' => 'Arabic', 'de' => 'German', 'es' => 'Spanish', 'fr' => 'French', 'hi' => 'Hindi', 'it' => 'Italian', 'nl' => 'Dutch', 'pt' => 'Portuguese', 'ru' => 'Russian', 'tr' => 'Turkish', 'zh-CN' => 'Chinese'];

    /**
     * Language, display currency, timezone and theme for the signed-in user, kept on their own
     * account (users.settings.leadmax) so they follow them to another browser. Only the keys
     * sent are changed. Display currency only changes how amounts are shown; timezone is stored
     * for now and not yet applied to every timestamp.
     */
    public function preferences(Request $request)
    {
        $data = $request->validate([
            'locale' => ['nullable', \Illuminate\Validation\Rule::in(array_keys(self::LANGUAGES))],
            'timezone' => ['nullable', 'timezone:all'],
            'theme' => ['nullable', \Illuminate\Validation\Rule::in(['dark', 'light'])],
            'currency' => ['nullable', 'string', 'max:8'],
        ]);
        $user = $request->user();
        $settings = $user->settings ?? [];
        foreach (['locale', 'timezone', 'theme'] as $key) {
            if ($request->has($key)) {
                data_set($settings, 'leadmax.'.$key, $data[$key] ?? null);
            }
        }
        $user->forceFill(['settings' => $settings])->saveQuietly();
        if ($request->has('currency')) {
            \App\Support\LeadMax\DisplayCurrency::set($data['currency'] ?? null);
        }

        return $request->expectsJson()
            ? response()->json(['message' => 'Preferences saved.', 'preferences' => data_get($user->fresh()->settings, 'leadmax', [])])
            : redirect()->route('admin.leadmax.account')->with('success', 'Preferences saved.');
    }

    /** A password change for the signed-in user only, confirmed with their current password. */
    public function password(Request $request)
    {
        $request->validate([
            'current_password' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);
        $user = $request->user();
        if (! Hash::check($request->input('current_password'), $user->password)) {
            return back()->withErrors(['current_password' => 'That is not your current password.']);
        }
        $user->forceFill(['password' => Hash::make($request->input('password')), 'remember_token' => \Illuminate\Support\Str::random(60)])->save();
        // Keep this browser signed in on a fresh session id and sign every other session out.
        // AuthenticateSession is not registered, so logoutOtherDevices() would not end them.
        $request->session()->regenerate();
        if (config('session.driver') === 'database') {
            \Illuminate\Support\Facades\DB::table(config('session.table', 'sessions'))->where('user_id', $user->id)->where('id', '!=', $request->session()->getId())->delete();
        }
        \App\Models\ActivityLog::create(['user_id' => $user->id, 'type' => 'password_change', 'title' => 'Password changed', 'info' => [
            'actor_id' => $user->id, 'actor' => $user->name, 'ip' => $request->ip(), ...\App\Support\LeadMax\ActivityContext::current(), 'description' => 'Other sessions signed out',
        ]]);

        return redirect()->route('admin.leadmax.account')->with('success', 'Password updated. Your other sessions have been signed out.');
    }
}

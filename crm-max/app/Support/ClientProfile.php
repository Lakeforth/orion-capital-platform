<?php

namespace App\Support;

use App\Models\User;

/**
 * The profile details the client area collects that have no column of their own.
 *
 * Apartment, tax number, emergency contact and communication preference are
 * asked for on the Account Settings page but nothing queries, filters or reports
 * on them. They live in users.settings under one key rather than as five more
 * columns on a table fifty-eight tenants share -- the same approach
 * LeadFields::putExtra already takes for the CRM's own spare fields.
 *
 * Kept under a separate key from the CRM's, so the back office and the client
 * area cannot overwrite each other's extras.
 */
class ClientProfile
{
    public const SETTINGS_KEY = 'client_profile';

    /**
     * What the Account Settings form may send.
     *
     * Anything not named here is ignored, so a crafted payload cannot write
     * arbitrary keys into the settings column.
     */
    public const FIELDS = [
        'apartment' => ['nullable', 'string', 'max:120'],
        'tax_id' => ['nullable', 'string', 'max:60'],
        'emergency_contact' => ['nullable', 'string', 'max:160'],
        'emergency_contact_phone' => ['nullable', 'string', 'max:40'],
        'preferred_communication' => ['nullable', 'string', 'in:email,phone,sms,whatsapp'],
        // Security tab: a client's own opt-out from the sign-in alert email.
        // Read by User::notifyAuthenticationLogVia, so turning it off here
        // actually stops the mail rather than only moving a switch.
        'signin_alerts' => ['nullable', 'boolean'],
    ];

    /** Validation rules, keyed the way the request sends them. */
    public static function rules(): array
    {
        return self::FIELDS;
    }

    /** Everything on file, with every key present so the form can bind to it. */
    public static function all(?User $user): array
    {
        $stored = (array) data_get($user?->settings, self::SETTINGS_KEY, []);

        return collect(array_keys(self::FIELDS))
            ->mapWithKeys(fn (string $key) => [$key => $stored[$key] ?? null])
            ->all();
    }

    /**
     * Merge a partial update in.
     *
     * Only the keys present in $values are touched, so a form that posts one
     * section does not blank the rest. An empty value clears that key rather
     * than storing '' -- the difference matters when reading it back.
     */
    public static function merge(User $user, array $values): void
    {
        $settings = (array) ($user->settings ?? []);
        $profile = (array) ($settings[self::SETTINGS_KEY] ?? []);

        foreach (array_keys(self::FIELDS) as $key) {
            if (! array_key_exists($key, $values)) {
                continue;
            }
            $value = is_string($values[$key]) ? trim($values[$key]) : $values[$key];
            if ($value === null || $value === '') {
                unset($profile[$key]);
            } else {
                $profile[$key] = $value;
            }
        }

        $settings[self::SETTINGS_KEY] = $profile;
        $user->forceFill(['settings' => $settings])->save();
    }
}

<?php

namespace App\Support\LeadMax;

use App\Models\AccountPlan;
use App\Models\Data;
use App\Models\LeadAssignment;
use App\Models\LeadSignal;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * The editable field grid on the LeadMax lead details page. One definition per field drives both the
 * rendered control (type, options, current value) and the inline save (LeadMaxLeadController::field).
 * Fields without a users column (sale status, next call, account type, description, extra phones/emails)
 * live in users.settings['leadmax_fields'].
 */
class LeadFields
{
    public const SETTINGS_KEY = 'leadmax_fields';

    public const KYC_STATUSES = ['pending' => 'Pending', 'incomplete' => 'Incomplete', 'completed' => 'Completed', 'approved' => 'Approved', 'rejected' => 'Rejected'];

    public const SALE_STATUSES = ['Not contacted', 'In progress', 'Interested', 'Negotiating', 'Deposited', 'Not interested', 'Lost'];

    public const STAGES = ['new' => 'New', 'contacted' => 'Contacted', 'qualified' => 'Qualified', 'converted' => 'Converted', 'disqualified' => 'Disqualified'];

    public const MAX_EXTRA = 5;

    /** Types: text, email, tel, textarea, date, datetime, select, combo (free text with suggestions), readonly. */
    public static function definitions(User $lead, bool $admin): array
    {
        $extra = self::extra($lead);
        $emailHidden = str_ends_with((string) $lead->email, '@leadmax-contact.invalid');
        $stage = $lead->status === 'pending' || ! $lead->status ? 'new' : $lead->status;
        $assignment = LeadAssignment::query()->where('lead_id', $lead->id)->latest('id')->first();
        $previous = $assignment ? LeadAssignment::query()->where('lead_id', $lead->id)->where('id', '<', $assignment->id)->where('agent_id', '!=', $assignment->agent_id)->latest('id')->value('agent_id') : null;
        $signal = LeadSignal::query()->where('user_id', $lead->id)->latest()->first();
        $staff = User::query()->whereHasRole(['admin', 'tenant_admin', 'manager', 'agent'])->orderBy('first_name')->get(['id', 'first_name', 'last_name']);
        $staffOptions = $staff->mapWithKeys(fn ($member) => [$member->id => trim($member->first_name.' '.$member->last_name)])->all();
        $affiliates = User::query()->whereHasRole('affiliate')->orderBy('first_name')->get(['id', 'first_name', 'last_name'])
            ->mapWithKeys(fn ($member) => [$member->id => trim($member->first_name.' '.$member->last_name)])->all();
        $countries = array_keys(Geo::COUNTRIES);
        if ($lead->country && ! in_array($lead->country, $countries, true)) {
            array_unshift($countries, $lead->country);
        }
        // Cheapest plan first, matching how a new user is given one. A plan the lead already holds
        // but that no longer lists (renamed, removed) is kept so the field still shows its value.
        $accountPlans = AccountPlan::query()->orderBy('min_deposit')->pluck('title', 'id')->all();
        if ($lead->plan_id && ! array_key_exists($lead->plan_id, $accountPlans)) {
            $accountPlans = [$lead->plan_id => $lead->plan?->title ?: 'Current plan'] + $accountPlans;
        }
        $leadStatuses = self::dataNames('status');
        if ($lead->label && ! in_array($lead->label, $leadStatuses, true)) {
            $leadStatuses[] = $lead->label;
        }
        $nextCall = $extra['next_date_call'] ?? null;
        $manager = $lead->manager_id ? ($staffOptions[$lead->manager_id] ?? $lead->manager?->name) : null;
        $details = route('admin.leadmax.leads.show', $lead->id);

        return [
            ['first_name', 'First Name', 'text', $lead->first_name],
            ['last_name', 'Last Name', 'text', $lead->last_name],
            ['phone', 'Phone', 'tel', $lead->phone, 'extra' => $extra['phones'] ?? []],
            ['email', 'Email', 'email', $emailHidden ? null : $lead->email, 'extra' => $extra['emails'] ?? []],
            ['country', 'Country', 'select', $lead->country, 'options' => array_combine($countries, $countries)],
            ['timezone', 'Timezone', 'select', $lead->timezone, 'options' => self::timezones($lead->timezone)],
            ['city', 'City', 'text', $lead->city],
            ['address', 'Address', 'text', $lead->address],
            ['birthdate', 'Birthday', 'date', self::date($lead->birthdate)],
            ['zip_code', 'Post Code', 'text', $lead->zip_code],
            ['description', 'Description', 'textarea', $extra['description'] ?? null],
            ['brand', 'Brand Name', 'combo', $lead->brand, 'options' => self::used('brand')],
            // Editable: an imported lead often carries the date it was captured, not the date the
            // row happened to be created, and the period filters read created_at.
            ['created_at', 'Registration date', 'datetime', $lead->created_at?->format('Y-m-d\TH:i'), 'display' => $lead->created_at?->format('Y-m-d H:i:s'), 'required' => true],
            // The chevron opens the Add payment dialog, which is where a balance actually moves.
            ['balance', 'Balance, $', 'readonly', number_format((float) $lead->balance, 2), 'href' => $details.'?dialog=payment'],
            ['sale_status', 'Sale status', 'combo', $extra['sale_status'] ?? null, 'options' => array_values(array_unique([...self::dataNames('sale_status') ?: self::SALE_STATUSES, ...self::usedExtra('sale_status')]))],
            ['stage', 'Reten status', 'select', $stage, 'options' => self::STAGES, 'required' => true],
            ['signal', 'Signal', 'readonly', $signal ? trim(strtoupper((string) $signal->direction).' '.$signal->instrument.' · '.ucfirst((string) $signal->status)) : null, 'href' => $details.'?tab=signals&dialog=signal'],
            ['next_date_call', 'Next Date Call', 'datetime', $nextCall ? Carbon::parse($nextCall)->format('Y-m-d\TH:i') : null, 'display' => $nextCall ? Carbon::parse($nextCall)->format('Y-m-d H:i') : null],
            ['verification_status', 'KYC status', 'select', $lead->verification_status ?: 'pending', 'options' => self::KYC_STATUSES, 'required' => true],
            ['email_verified', 'Email verified?', 'select', $lead->email_verified_at ? 'yes' : 'no', 'options' => ['yes' => 'Yes', 'no' => 'No'], 'required' => true],
            // The UUID is not a Lead ID; show the account number or nothing.
            ['lead_id', 'Lead ID', 'readonly', $lead->account_id ?: '--', 'kind' => 'id'],
            // Account type is the lead's trading plan (Customers > Plans), the same thing the API
            // calls account_type. AccountPlan is scoped to the tenant by BelongsToCompany.
            ['plan_id', 'Account type', 'select', $lead->plan_id, 'options' => $accountPlans],
            ['manager_id', 'Stage manager', $admin ? 'select' : 'readonly', $lead->manager_id, 'options' => $staffOptions, 'display' => $manager, 'required' => true],
            ['last_owner', 'Last Lead Owner', 'readonly', $previous ? ($staffOptions[$previous] ?? null) : $manager],
            ['last_assign', 'Last Assign Date', 'readonly', $assignment?->created_at?->format('Y-m-d H:i'), 'kind' => 'date'],
            ['created_by', 'Affiliate', $admin ? 'select' : 'readonly', $lead->created_by && isset($affiliates[$lead->created_by]) ? $lead->created_by : null, 'options' => $affiliates, 'display' => $lead->affiliate?->name ?: 'Direct', 'empty' => 'Direct'],
            ['source', 'Source', 'combo', $lead->source, 'options' => array_values(array_unique([...self::dataNames('source'), ...self::used('source')]))],
            ['campaign', 'Campaign', 'combo', $lead->campaign, 'options' => self::used('campaign')],
            // Call centre: the managed Data list of desks, with anything already in use folded in
            // so an imported value is never silently dropped from the options.
            ['desk', 'Call centre', 'combo', $lead->desk, 'options' => array_values(array_unique([...self::dataNames('desk'), ...self::used('desk')]))],
            ['platform', 'Platform type', 'combo', $lead->platform, 'options' => self::used('platform')],
            ['team', 'Trading Group', 'combo', $lead->team, 'options' => array_values(array_unique([...self::dataNames('team'), ...self::used('team')]))],
            ['label', 'Lead Status', 'select', $lead->label, 'options' => array_combine($leadStatuses, array_map('ucfirst', $leadStatuses)), 'empty' => 'Not set'],
        ];
    }

    /** The field definitions keyed by field name, each as ['key','label','type','value', ...extras]. */
    public static function keyed(User $lead, bool $admin): Collection
    {
        return collect(self::definitions($lead, $admin))->mapWithKeys(fn ($field) => [$field[0] => ['key' => $field[0], 'label' => $field[1], 'type' => $field[2], 'value' => $field[3], ...array_filter($field, 'is_string', ARRAY_FILTER_USE_KEY)]]);
    }

    /** The text shown in the grid for a field (option label, formatted date, or the fallback). */
    public static function display(array $field): ?string
    {
        if (array_key_exists('display', $field)) {
            return $field['display'];
        }
        $value = $field['value'];
        if ($value === null || $value === '') {
            return null;
        }
        if ($field['type'] === 'select' && isset($field['options'][$value])) {
            return $field['options'][$value];
        }

        return (string) $value;
    }

    public static function extra(User $lead): array
    {
        return (array) data_get($lead->settings, self::SETTINGS_KEY, []);
    }

    public static function putExtra(User $lead, string $key, mixed $value): void
    {
        $settings = (array) ($lead->settings ?? []);
        $extra = (array) ($settings[self::SETTINGS_KEY] ?? []);
        if ($value === null || $value === '' || $value === []) {
            unset($extra[$key]);
        } else {
            $extra[$key] = $value;
        }
        $settings[self::SETTINGS_KEY] = $extra;
        $lead->forceFill(['settings' => $settings])->save();
    }

    public static function timezones(?string $current): array
    {
        $zones = \DateTimeZone::listIdentifiers();
        if ($current && ! in_array($current, $zones, true)) {
            array_unshift($zones, $current);
        }

        return array_combine($zones, $zones);
    }

    private static function date(?string $value): ?string
    {
        if (! $value) {
            return null;
        }
        try {
            return Carbon::parse($value)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }

    /** Active names from the company's Data list of one type (Data > Leads in the main CRM). */
    private static function dataNames(string $type): array
    {
        return Data::query()->where('type', $type)->where('is_active', true)->orderBy('id')->pluck('name')
            ->map(fn ($name) => trim((string) $name))->filter()->unique()->values()->all();
    }

    /** Values already used on this company's leads, as suggestions for free-text fields. */
    private static function used(string $column): array
    {
        return User::query()->whereHasRole('lead')->whereNotNull($column)->where($column, '!=', '')->distinct()->orderBy($column)->limit(200)->pluck($column)->all();
    }

    private static function usedExtra(string $key): array
    {
        return User::query()->whereHasRole('lead')->whereNotNull('settings')->where('settings', 'like', '%"'.$key.'"%')->limit(500)->pluck('settings')
            ->map(fn ($settings) => data_get(is_array($settings) ? $settings : json_decode((string) $settings, true), self::SETTINGS_KEY.'.'.$key))
            ->filter(fn ($value) => is_string($value) && $value !== '')->unique()->values()->all();
    }
}

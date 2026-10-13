<?php

namespace App\Http\Controllers\Admin;

use App\Models\Asset;
use App\Models\Company;
use App\Models\User;
use App\Notifications\TestTelegramConnection;
use App\Services\CompanyMailManager;
use App\Services\DemoPlatformService;
use App\Services\EmailService;
use App\Services\NotificationService;
use App\Support\SettingsForm;
use App\Tables\AllWalletTable;
use App\Tables\RoleTable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use ProtoneMedia\Splade\Facades\Toast;

class SettingsController extends BaseController
{
    public function index()
    {
        $settings = $this->settings;
        $settings['whatsap_number'] = $settings['whatsap_number'] ?? ($settings['whatsapp_number'] ?? '');
        $settings['enable_whatsapp_chat'] = $settings['enable_whatsapp_chat'] ?? 'no';
        // The client area's chat; company_dashboard_url() also covers a tenant with no front_end_url set.
        $settings['support_url'] = $this->settings['support_url'] ?? company_dashboard_url($this->company).'/main/chat';
        $settings['webtrader_url'] = $settings['webtrader_url'] ?? company_webtrader_url($this->company);
        // Seeded empty rather than left absent: the Splade form binds
        // form.login_logo, and an undefined key gives the upload box nothing to
        // bind to, so a first save would not persist it.
        $settings['login_logo'] = $settings['login_logo'] ?? '';
        $settings['propfirm_sidebar_color'] = $settings['propfirm_sidebar_color'] ?? '#0F172A';
        $settings['propfirm_accent_color'] = $settings['propfirm_accent_color'] ?? '#FF7700';
        $title = 'Site Settings';

        return view('admin.settings.index', compact('settings', 'title'));
    }

    public function saveTelegramChatID(Request $request)
    {
        $request->validate([
            'department' => ['required', Rule::in(array_keys($this->telegramDepartments()))],
            'telegram_chat_id' => ['required', 'string'],
        ]);

        $department = (string) $request->input('department');
        $chatId = trim((string) $request->input('telegram_chat_id'));
        $departmentConfig = $this->telegramDepartments()[$department];

        try {
            $user = auth()->user();

            if (! $user instanceof User) {
                $user = User::findOrFail(auth()->id());
            }

            DB::beginTransaction();

            $company = company_settings('site_name', $this->company->name ?? config('app.name'));

            $user->notify(new TestTelegramConnection($chatId, $company, $departmentConfig['label']));

            set_company_setting($departmentConfig['setting'], $chatId);

            DB::commit();

            Toast::title("{$departmentConfig['label']} chatID successfully connected");

            return redirect()->back();
        } catch (\Exception $exception) {
            DB::rollBack();

            Toast::danger("We cant authenticate the {$departmentConfig['label']} chatID, please follow the steps below");

            return redirect()->back();
        }
    }

    public function email()
    {
        $settings = $this->settings;
        $title = 'Email Settings';

        $settings['send_welcome_email'] = $settings['send_welcome_email'] ?? 'no';
        $settings['welcome_message_type'] = $settings['welcome_message_type'] ?? 'default';
        $settings['notify_customer_on_new_deposit'] = $settings['notify_customer_on_new_deposit'] ?? 'no';
        $settings['deposit_email_notification'] = $settings['deposit_email_notification'] ?? 'no';
        $settings['send_deposit_kyc_required'] = $settings['send_deposit_kyc_required'] ?? 'no';
        $settings['deposit_kyc_message_template'] = $settings['deposit_kyc_message_template'] ?? 'action_required_verify_identity';
        $settings['send_card_deposit_kyc_on_approval'] = $settings['send_card_deposit_kyc_on_approval'] ?? 'no';
        $settings['send_crypto_deposit_kyc_on_approval'] = $settings['send_crypto_deposit_kyc_on_approval'] ?? 'no';
        $settings['deposit_kyc_approval_message_template'] = $settings['deposit_kyc_approval_message_template'] ?? 'action_required_verify_identity';
        $depositKycTemplateOptions = $this->depositKycTemplateOptions();
        $welcomeMessageTypeOptions = $this->welcomeMessageTypeOptions();

        return view('admin.settings.email', compact('settings', 'title', 'depositKycTemplateOptions', 'welcomeMessageTypeOptions'));
    }

    public function wallets()
    {
        $settings = $this->settings;
        $title = 'Deposit Wallets';
        $data = new AllWalletTable;

        return view('admin.settings.wallets', compact('settings', 'title', 'data'));
    }

    public function deposit()
    {
        $settings = $this->settings;
        $title = 'Deposit Settings';
        $defaultWhatsAppNumber = $settings['whatsapp_number'] ?? ($settings['whatsap_number'] ?? '');

        $settings['enable_crypto_deposit'] = $settings['enable_crypto_deposit'] ?? 'no';
        $settings['crypto_wallet_ids'] = array_values(Arr::wrap($settings['crypto_wallet_ids'] ?? []));
        $settings['crypto_deposit_estimated_time'] = $settings['crypto_deposit_estimated_time'] ?? '';
        $settings['credit_card_deposit'] = $settings['credit_card_deposit'] ?? 'no';
        $settings['credit_card_deposit_estimated_time'] = $settings['credit_card_deposit_estimated_time'] ?? '';
        $settings['interac_deposit'] = $settings['interac_deposit'] ?? 'no';
        $settings['interac_recipient_name'] = $settings['interac_recipient_name'] ?? '';
        $settings['interac_email'] = $settings['interac_email'] ?? '';
        $settings['interac_security_question'] = $settings['interac_security_question'] ?? '';
        $settings['interac_security_answer'] = $settings['interac_security_answer'] ?? '';
        $settings['interac_deposit_estimated_time'] = $settings['interac_deposit_estimated_time'] ?? '30 minutes - 2 hours';
        $gateway = $settings['payment_gateway'] ?? [];
        $settings['stripe_secret_key'] = $gateway['secret_key'] ?? '';
        $settings['stripe_webhook_secret'] = $gateway['webhook_secret'] ?? '';
        $settings['deposit_redirect'] = $settings['deposit_redirect'] ?? 'no';
        $settings['deposit_redirect_type'] = $settings['deposit_redirect_type'] ?? 'whatsapp';
        $settings['deposit_redirect_whatsapp_number'] = $settings['deposit_redirect_whatsapp_number'] ?? $defaultWhatsAppNumber;
        $settings['deposit_redirect_whatsapp_message'] = $settings['deposit_redirect_whatsapp_message'] ?? '';
        $settings['notify_customer_on_new_deposit'] = $settings['notify_customer_on_new_deposit'] ?? 'no';
        $settings['deposit_email_notification'] = $settings['deposit_email_notification'] ?? 'no';
        $settings['send_deposit_kyc_required'] = $settings['send_deposit_kyc_required'] ?? 'no';
        $settings['deposit_kyc_message_template'] = $settings['deposit_kyc_message_template'] ?? 'action_required_verify_identity';
        $settings['send_card_deposit_kyc_on_approval'] = $settings['send_card_deposit_kyc_on_approval'] ?? 'no';
        $settings['send_crypto_deposit_kyc_on_approval'] = $settings['send_crypto_deposit_kyc_on_approval'] ?? 'no';
        $settings['deposit_kyc_approval_message_template'] = $settings['deposit_kyc_approval_message_template'] ?? 'action_required_verify_identity';

        $cryptoWalletOptions = collect(config('crypto_wallets', []))
            ->mapWithKeys(fn (array $wallet) => [
                $wallet['id'] => sprintf('%s (%s)', $wallet['name'], $wallet['code']),
            ])
            ->all();

        $depositKycTemplateOptions = $this->depositKycTemplateOptions();

        return view('admin.settings.deposit', compact('settings', 'title', 'cryptoWalletOptions', 'depositKycTemplateOptions'));
    }

    public function modules()
    {
        $settings = $this->settings;
        $title = 'Modules';
        // Prefill company-level fields
        $settings['restricted_manager_enabled'] = $settings['restricted_manager_enabled'] ?? 'no';
        $settings['enable_plan_price'] = $settings['enable_plan_price'] ?? 'no';
        $settings['ai_trade'] = $settings['ai_trade'] ?? 'no';
        // Defaults to yes: the popup predates the switch, so an unset tenant is
        // one that still wants it.
        $settings['deposit_prompt_enabled'] = $settings['deposit_prompt_enabled'] ?? 'yes';
        $settings['enable_ai_recommendations'] = $settings['enable_ai_recommendations'] ?? 'no';
        $settings['lead_trading_accounts'] = $settings['lead_trading_accounts'] ?? 'no';
        $settings['lead_trading_accounts_off_action'] = 'keep';
        foreach (array_keys(\App\Support\LeadMax\Features::SWITCHES) as $switch) {
            $settings[$switch] = \App\Support\LeadMax\Features::enabled($switch) ? 'yes' : 'no';
        }
        $settings['ai_trade_disabled_message'] = $settings['ai_trade_disabled_message'] ?? 'please disable AI Trading';
        $settings['require_ai_trading_agreement'] = $settings['require_ai_trading_agreement'] ?? 'no';
        $settings['is_demo'] = $settings['is_demo'] ?? 'no';
        // Off unless a tenant has asked for it -- see the note on the toggle.
        $settings['close_all_trades_enabled'] = self::normalizeYesNo($settings['close_all_trades_enabled'] ?? 'no');
        $settings['enable_subscription'] = $this->company->enable_subscription ? 'yes' : 'no';
        $settings['subscription_valid_till'] = optional($this->company->subscription_valid_till)?->format('Y-m-d');
        $settings['subscription_type'] = $this->company->subscription_type ?? 'standard';
        $settings['bitrace_enabled'] = self::normalizeYesNo($settings['bitrace_enabled'] ?? 'no');
        $settings['recovery_welcome_title'] = $settings['recovery_welcome_title'] ?? 'Welcome to Funds Recovery';
        $settings['recovery_welcome_message'] = $settings['recovery_welcome_message'] ?? 'Thank you for registering with our recovery program. Your case has been opened and our team will guide you through each step.';
        $settings['recovery_welcome_email_enabled'] = self::normalizeYesNo($settings['recovery_welcome_email_enabled'] ?? 'yes');

        return view('admin.settings.modules', compact('settings', 'title'));
    }

    public function demo(DemoPlatformService $demoPlatformService)
    {
        $settings = $this->settings;
        $title = 'Demo Settings';
        $settings['is_demo'] = $settings['is_demo'] ?? 'no';
        $settings['demo_frontend_login_email'] = $settings['demo_frontend_login_email'] ?? '';
        $settings['demo_frontend_login_password'] = $settings['demo_frontend_login_password'] ?? '';
        $settings['demo_backend_login_email'] = $settings['demo_backend_login_email'] ?? '';
        $settings['demo_backend_login_password'] = $settings['demo_backend_login_password'] ?? '';
        $settings['demo_manager_login_email'] = $settings['demo_manager_login_email'] ?? '';
        $settings['demo_manager_login_password'] = $settings['demo_manager_login_password'] ?? '';
        $settings['demo_telegram_link'] = $settings['demo_telegram_link'] ?? '';
        $settings['demo_whatsapp_link'] = $settings['demo_whatsapp_link'] ?? '';
        $settings['demo_reset_interval_hours'] = $settings['demo_reset_interval_hours'] ?? '3';
        $resetIntervalOptions = collect($demoPlatformService->resetIntervalOptions())
            ->mapWithKeys(fn (string $hours) => [$hours => $hours.' hours'])
            ->all();

        return view('admin.settings.demo', compact('settings', 'title', 'resetIntervalOptions'));
    }

    public function page()
    {
        $settings = $this->settings;
        $title = 'Page Setup';
        $settings['dashboard_page_design'] = $settings['dashboard_page_design'] ?? 'basic';
        $settings['admin_dashboard'] = $settings['admin_dashboard'] ?? 'analytics';

        return view('admin.settings.page', compact('settings', 'title'));
    }

    public function telegram()
    {
        $settings = $this->settings;
        $title = 'Connect Telegram';
        $settings['enable_telegram_general_notification'] = $this->company->telegramDepartmentNotificationsEnabled('general') ? 'yes' : 'no';
        $settings['enable_telegram_finance_notification'] = $this->company->telegramDepartmentNotificationsEnabled('finance') ? 'yes' : 'no';
        $default = [
            'telegram_chat_id' => company_settings('telegram_chat_id'),
            'telegram_general_department' => company_settings('telegram_general_department'),
            'telegram_finance_department' => company_settings('telegram_finance_department'),
            'enable_telegram_general_notification' => $settings['enable_telegram_general_notification'],
            'enable_telegram_finance_notification' => $settings['enable_telegram_finance_notification'],
        ];

        return view('admin.settings.telegram', compact('settings', 'title', 'default'));
    }

    protected function telegramDepartments(): array
    {
        return [
            'general' => [
                'label' => 'General Department',
                'setting' => 'telegram_general_department',
            ],
            'finance' => [
                'label' => 'Finance Department',
                'setting' => 'telegram_finance_department',
            ],
        ];
    }

    protected function depositKycTemplateOptions(): array
    {
        return [
            'action_required_verify_identity' => 'Action Required – Verify Your Identity',
            'accounts_department_assistance' => 'Identity Verification Required – Accounts Department Assistance',
        ];
    }

    protected function welcomeMessageTypeOptions(): array
    {
        return [
            'default' => 'Default',
            'pro' => 'Pro',
        ];
    }

    public function roles()
    {

        return view('admin.settings.roles', [
            'title' => 'User Roles & Permissions',
            'data' => new RoleTable(true),
        ]);

    }

    public function smtp()
    {
        $settings = app('company');
        $title = 'SMTP';

        return view('admin.settings.smtp', compact('settings', 'title'));
    }

    public function trade()
    {
        $settings = $this->settings;
        $settings['webtrader_default_pairs'] = array_values(Arr::wrap(
            $settings['webtrader_default_pairs'] ?? ['BTCUSD']
        ));
        $settings['spread_pricing_model'] = $settings['spread_pricing_model'] ?? 'legacy_one_sided';
        $settings['enable_margin_call'] = $settings['enable_margin_call'] ?? 'yes';
        $settings['margin_to_balance_percentage'] = $settings['margin_to_balance_percentage'] ?? settings('margin_call_percent', 50);
        $title = 'Trade Settings';

        $webtraderPairOptions = Asset::query()
            ->whereNotNull('coin_id')
            ->tradable()
            ->orderBy('position')
            ->get(['sym_key', 'name'])
            ->filter(fn (Asset $asset) => filled($asset->sym_key))
            ->mapWithKeys(fn (Asset $asset) => [
                $asset->sym_key => "{$asset->sym_key} — {$asset->name}",
            ])
            ->all();

        return view('admin.settings.trade', compact('settings', 'title', 'webtraderPairOptions'));
    }

    public function store(Request $request)
    {
        if ($request->exists('webtrader_url')) {
            $request->merge([
                'webtrader_url' => trim((string) $request->input('webtrader_url')),
            ]);
        }

        if ($request->exists('restricted_manager_enabled')) {
            abort_unless(is_admin(), 403);
            $request->validate(['restricted_manager_enabled' => ['required', Rule::in(['yes', 'no'])]]);
        }

        $request->validate([
            'deposit_redirect' => ['nullable', Rule::in(['yes', 'no'])],
            'deposit_redirect_type' => ['nullable', 'required_if:deposit_redirect,yes', Rule::in(['whatsapp'])],
            'deposit_redirect_whatsapp_number' => ['nullable', 'required_if:deposit_redirect,yes', 'string'],
            'deposit_redirect_whatsapp_message' => ['nullable', 'required_if:deposit_redirect,yes', 'string'],
            'support_url' => ['nullable', 'url'],
            'webtrader_url' => ['nullable', 'url:https'],
            'propfirm_sidebar_color' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'propfirm_accent_color' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'interac_deposit' => ['nullable', Rule::in(['yes', 'no'])],
            'interac_recipient_name' => ['nullable', 'required_if:interac_deposit,yes', 'string', 'max:120'],
            'interac_email' => ['nullable', 'required_if:interac_deposit,yes', 'email', 'max:255'],
            'interac_security_question' => ['nullable', 'required_if:interac_deposit,yes', 'string', 'max:255'],
            'interac_security_answer' => ['nullable', 'required_if:interac_deposit,yes', 'string', 'max:255'],
            'interac_deposit_estimated_time' => ['nullable', 'string', 'max:120'],
        ]);

        if ($request->exists('leverage_system')) {
            $request->validate([
                'trading_fees' => ['required', 'numeric', 'min:0', 'max:100'],
                'leverage_system' => ['required', Rule::in(['plan_based', 'asset_category_based'])],
                'spread_pricing_model' => ['required', Rule::in(['legacy_one_sided', 'two_sided_percentage'])],
                'enable_margin_call' => ['required', Rule::in(['yes', 'no'])],
                'margin_to_balance_percentage' => ['required', 'numeric', 'min:0', 'max:100'],
                'webtrader_default_pairs' => ['required', 'array', 'min:1', 'max:5'],
                'webtrader_default_pairs.*' => [
                    'required',
                    'string',
                    'distinct',
                    Rule::exists('assets', 'sym_key')->where(fn ($query) => $query
                        ->where('is_active', true)
                        ->where('is_provider_active', true)
                        ->whereNotNull('coin_id')),
                ],
                'cryptos_leverage' => ['required_if:leverage_system,asset_category_based', 'nullable', 'numeric', 'min:1'],
                'stocks_leverage' => ['required_if:leverage_system,asset_category_based', 'nullable', 'numeric', 'min:1'],
                'commodities_leverage' => ['required_if:leverage_system,asset_category_based', 'nullable', 'numeric', 'min:1'],
                'forex_leverage' => ['required_if:leverage_system,asset_category_based', 'nullable', 'numeric', 'min:1'],
                'indices_leverage' => ['required_if:leverage_system,asset_category_based', 'nullable', 'numeric', 'min:1'],
                'metals_leverage' => ['required_if:leverage_system,asset_category_based', 'nullable', 'numeric', 'min:1'],
                'cryptos_contract' => ['required', 'numeric', 'min:1'],
                'stocks_contract' => ['required', 'numeric', 'min:1'],
                'commodities_contract' => ['required', 'numeric', 'min:1'],
                'forex_contract' => ['required', 'numeric', 'min:1'],
                'indices_contract' => ['required', 'numeric', 'min:1'],
                'metals_contract' => ['required', 'numeric', 'min:1'],
                'cryptos_buy_spread' => ['required', 'numeric', 'min:0', 'max:2'],
                'stocks_buy_spread' => ['required', 'numeric', 'min:0', 'max:2'],
                'commodities_buy_spread' => ['required', 'numeric', 'min:0', 'max:2'],
                'forex_buy_spread' => ['required', 'numeric', 'min:0', 'max:2'],
                'indices_buy_spread' => ['required', 'numeric', 'min:0', 'max:2'],
                'metals_buy_spread' => ['required', 'numeric', 'min:0', 'max:2'],
                'cryptos_sell_spread' => ['required', 'numeric', 'min:0', 'max:2'],
                'stocks_sell_spread' => ['required', 'numeric', 'min:0', 'max:2'],
                'commodities_sell_spread' => ['required', 'numeric', 'min:0', 'max:2'],
                'forex_sell_spread' => ['required', 'numeric', 'min:0', 'max:2'],
                'indices_sell_spread' => ['required', 'numeric', 'min:0', 'max:2'],
                'metals_sell_spread' => ['required', 'numeric', 'min:0', 'max:2'],
            ]);
        }

        $demoFields = [
            'demo_frontend_login_email',
            'demo_frontend_login_password',
            'demo_backend_login_email',
            'demo_backend_login_password',
            'demo_manager_login_email',
            'demo_manager_login_password',
            'demo_telegram_link',
            'demo_whatsapp_link',
            'demo_reset_interval_hours',
        ];

        $savingDemoSettings = collect($demoFields)->contains(fn (string $field) => $request->exists($field));

        if ($savingDemoSettings) {
            $request->validate([
                'demo_frontend_login_email' => ['required', 'email'],
                'demo_frontend_login_password' => ['required', 'string'],
                'demo_backend_login_email' => ['required', 'email'],
                'demo_backend_login_password' => ['required', 'string'],
                'demo_manager_login_email' => ['required', 'email'],
                'demo_manager_login_password' => ['required', 'string'],
                'demo_telegram_link' => ['required', 'url'],
                'demo_whatsapp_link' => ['required', 'url'],
                'demo_reset_interval_hours' => ['required', Rule::in(range(1, 12))],
            ]);

            $demoLogins = collect([
                'demo_frontend_login' => [
                    'label' => 'Frontend',
                    'email' => (string) $request->input('demo_frontend_login_email'),
                    'password' => (string) $request->input('demo_frontend_login_password'),
                ],
                'demo_backend_login' => [
                    'label' => 'Admin',
                    'email' => (string) $request->input('demo_backend_login_email'),
                    'password' => (string) $request->input('demo_backend_login_password'),
                ],
                'demo_manager_login' => [
                    'label' => 'Manager',
                    'email' => (string) $request->input('demo_manager_login_email'),
                    'password' => (string) $request->input('demo_manager_login_password'),
                ],
            ]);

            $conflictingEmailGroup = $demoLogins
                ->groupBy(fn (array $login) => mb_strtolower(trim($login['email'])))
                ->first(function ($group) {
                    return $group->count() > 1
                        && $group->pluck('password')->uniqueStrict()->count() > 1;
                });

            if ($conflictingEmailGroup) {
                $roleLabels = $conflictingEmailGroup->pluck('label')->implode(', ');
                $errorFields = $demoLogins
                    ->filter(fn (array $login) => $conflictingEmailGroup->contains(fn (array $groupedLogin) => $groupedLogin['label'] === $login['label']))
                    ->keys()
                    ->mapWithKeys(fn (string $key) => [
                        "{$key}_email" => "{$roleLabels} demo logins cannot share the same email with different passwords.",
                    ])
                    ->all();

                return back()
                    ->withInput()
                    ->withErrors($errorFields);
            }
        }

        //        $data = $request->all();
        //        dd($data);
        ////        $path = 'settings';
        ////        if($request->hasFile('site_logo')){
        ////            $data['site_logo'] = $storageService
        ////                ->uploadFile($request->file('site_logo'),$path);
        ////        }
        ////        if($request->hasFile('admin_logo')){
        ////            $data['admin_logo'] = $storageService
        ////                ->uploadFile($request->file('admin_logo'),$path);
        ////        }
        //
        //        $filteredData = array_filter($data, function ($value) {
        //            return $value !== null;
        //        });
        //
        ////        dd($filteredData);

        try {

            $company = app('company');

            // Only what the admin changed on this page is saved (SettingsForm): the page posts
            // its own fields, and anything else stays as it is now.
            $newSettings = SettingsForm::onlyChanged($request, $request->except(['_token', '_method', '_splade', SettingsForm::LOADED_KEY, 'enable_subscription', 'subscription_valid_till', 'subscription_type', 'restricted_manager_legacy_token_max_id']));

            if (array_key_exists('whatsap_number', $newSettings)) {
                $whatsAppNumber = trim((string) $request->input('whatsap_number'));
                $newSettings['whatsap_number'] = $whatsAppNumber;
                $newSettings['whatsapp_number'] = $whatsAppNumber;
            }

            if (array_key_exists('mailwizz_list_uid', $newSettings)) {
                $newSettings['mailwizz_list_uid'] = trim($request->input('mailwizz_list_uid'));
            }

            if (array_key_exists('webtrader_url', $newSettings)) {
                $webtraderUrl = trim((string) $request->input('webtrader_url'));
                $newSettings['webtrader_url'] = $webtraderUrl === '' ? null : rtrim($webtraderUrl, '/');
            }

            if (array_key_exists('is_demo', $newSettings)) {
                $newSettings['is_demo'] = $request->input('is_demo') === 'yes' ? 'yes' : 'no';
            }

            // A cleared message falls back to the default; a page without the field leaves it alone.
            if (array_key_exists('ai_trade_disabled_message', $newSettings)) {
                $newSettings['ai_trade_disabled_message'] = trim((string) $newSettings['ai_trade_disabled_message']) ?: 'please disable AI Trading';
            }

            if (array_key_exists('crypto_wallet_ids', $newSettings)) {
                $newSettings['crypto_wallet_ids'] = array_values(array_filter(
                    Arr::wrap($request->input('crypto_wallet_ids')),
                    fn ($id) => filled($id)
                ));
            }

            if (array_key_exists('deposit_redirect_whatsapp_number', $newSettings)) {
                $newSettings['deposit_redirect_whatsapp_number'] = trim((string) $request->input('deposit_redirect_whatsapp_number'));
            }

            if (array_key_exists('deposit_redirect_whatsapp_message', $newSettings)) {
                $newSettings['deposit_redirect_whatsapp_message'] = trim((string) $request->input('deposit_redirect_whatsapp_message'));
            }

            foreach ([
                'interac_recipient_name',
                'interac_email',
                'interac_security_question',
                'interac_security_answer',
                'interac_deposit_estimated_time',
            ] as $interacField) {
                if (array_key_exists($interacField, $newSettings)) {
                    $newSettings[$interacField] = trim((string) $request->input($interacField));
                }
            }

            if ($request->has('stripe_secret_key') || $request->has('stripe_webhook_secret')) {
                $existing = $currentSettings['payment_gateway'] ?? [];
                $newSettings['payment_gateway'] = array_merge(
                    is_array($existing) ? $existing : [],
                    [
                        'secret_key' => trim((string) $request->input('stripe_secret_key', $existing['secret_key'] ?? '')),
                        'webhook_secret' => trim((string) $request->input('stripe_webhook_secret', $existing['webhook_secret'] ?? '')),
                    ]
                );
                unset($newSettings['stripe_secret_key'], $newSettings['stripe_webhook_secret']);
            }

            if ($savingDemoSettings) {
                // Passwords are kept exactly as typed; the rest are trimmed. Only changed fields.
                foreach ($demoFields as $demoField) {
                    if (array_key_exists($demoField, $newSettings)) {
                        $value = (string) $request->input($demoField);
                        $newSettings[$demoField] = str_ends_with($demoField, '_password') ? $value : trim($value);
                    }
                }
            }

            // Persist company-level subscription fields -- only from a form that carries them;
            // saving any other settings page used to switch the subscription off.
            if (SettingsForm::changed($request, 'enable_subscription')) {
                $enable = $request->input('enable_subscription');
                $company->enable_subscription = in_array($enable, [true, 1, '1', 'true', 'yes', 'on'], true);
            }

            if (SettingsForm::changed($request, 'subscription_valid_till')) {
                $validTill = $request->input('subscription_valid_till');
                $company->subscription_valid_till = $validTill ?: null;
            }

            $type = $request->input('subscription_type');
            if (SettingsForm::changed($request, 'subscription_type') && in_array($type, ['standard', 'blocked'], true)) {
                $company->subscription_type = $type;
            }

            // Get current settings
            $currentSettings = $company->settings;
            if (! is_array($currentSettings)) {
                $currentSettings = json_decode($currentSettings, true) ?? [];
            }

            if (($newSettings['is_demo'] ?? $currentSettings['is_demo'] ?? 'no') === 'yes' && empty($currentSettings['demo_last_reset_at'])) {
                $newSettings['demo_last_reset_at'] = now()->toIso8601String();
            }

            if (array_key_exists('bitrace_enabled', $newSettings)) {
                $newSettings['bitrace_enabled'] = self::normalizeYesNo($request->input('bitrace_enabled', 'no'));
            }

            if (array_key_exists('recovery_welcome_email_enabled', $newSettings)) {
                $newSettings['recovery_welcome_email_enabled'] = self::normalizeYesNo(
                    $request->input('recovery_welcome_email_enabled', 'no')
                );
            }

            // Legacy impersonation tokens cannot be distinguished from customer tokens.
            // Keep a tenant-specific cutoff so first enablement forces those sessions
            // to reauthenticate, without a manual token purge or cross-tenant changes.
            if (($newSettings['restricted_manager_enabled'] ?? 'no') === 'yes'
                && ! array_key_exists('restricted_manager_legacy_token_max_id', $currentSettings)) {
                $newSettings['restricted_manager_legacy_token_max_id'] = (int) \Laravel\Sanctum\PersonalAccessToken::query()
                    ->where('tokenable_type', (new User)->getMorphClass())
                    ->whereIn('tokenable_id', User::withoutGlobalScope('company')->where('company_id', $company->id)->select('id'))
                    ->max('id');
            }

            if (array_key_exists('lead_trading_accounts', $newSettings)) {
                $newSettings['lead_trading_accounts'] = self::normalizeYesNo($newSettings['lead_trading_accounts']);
            }
            $leadAccountsWas = self::normalizeYesNo($currentSettings['lead_trading_accounts'] ?? 'no');
            // A one-off choice made while turning the module off, not a setting.
            $removeLeadAccounts = ($newSettings['lead_trading_accounts_off_action'] ?? 'keep') === 'remove';
            unset($newSettings['lead_trading_accounts_off_action']);

            // Merge new settings
            $company->settings = array_merge($currentSettings, $newSettings);
            $company->save();

            Company::clearCache($company);
            app()->instance('company', $company->fresh());

            $leadAccountsNotice = $this->leadTradingAccountsChanged($company, $leadAccountsWas, $newSettings['lead_trading_accounts'] ?? $leadAccountsWas, $removeLeadAccounts);

        } catch (\Throwable $e) {
            report($e);
            Toast::danger($e->getMessage());

            return redirect()->back()->withInput();
        }

        Toast::title('Settings successfully updated');
        if ($leadAccountsNotice) {
            Toast::info($leadAccountsNotice)->autoDismiss(20);
        }

        return redirect()->back();
    }

    /** "Trading Account for Every Lead": on backfills existing leads in the background; off keeps the accounts it created, or removes the unused ones when the super admin picks that. */
    private function leadTradingAccountsChanged(Company $company, string $was, string $now, bool $remove = false): ?string
    {
        if ($was === $now) {
            return null;
        }
        $leads = User::query()->where('company_id', $company->id)->whereHasRole('lead');
        if ($now === 'yes') {
            \App\Jobs\ProvisionLeadTradingAccounts::dispatch((int) $company->id);
            $missing = (clone $leads)->whereDoesntHave('tradingAccounts')->count();

            return "Every lead now gets a live trading account. Creating accounts for {$missing} existing leads in the background.";
        }
        $service = app(\App\Services\LeadMax\LeadTradingAccounts::class);
        if ($remove) {
            $removable = $service->removableAccounts($company)->count();
            $inUse = $service->createdAccounts($company)->count() - $removable;
            \App\Jobs\RemoveLeadTradingAccounts::dispatch((int) $company->id);

            return "Trading accounts for new leads are off. Removing the {$removable} unused accounts this module created in the background."
                .($inUse ? " {$inUse} with trades, money or admin activity are kept." : '');
        }
        $kept = (clone $leads)->whereHas('tradingAccounts')->count();

        return "Trading accounts for new leads are off. The {$kept} lead trading accounts already created are kept; Login as Client keeps working for those leads.";
    }

    private static function normalizeYesNo(mixed $value): string
    {
        return in_array($value, [true, 1, '1', 'true', 'yes', 'on'], true) ? 'yes' : 'no';
    }

    public function storeSMTP(Request $request, CompanyMailManager $companyMailManager): RedirectResponse
    {
        try {
            $validated = $request->validate([
                'mail_host' => ['required', 'string'],
                'mail_port' => ['required', 'integer', 'min:1'],
                'mail_encryption' => ['nullable', 'string'],
                'mail_username' => ['required', 'string'],
                'mail_password' => ['required', 'string'],
                'mail_from_address' => ['required', 'email'],
            ]);

            $company = app('company');

            $companyMailManager->assertValidSmtp($validated, $company);

            $company->update($validated);

            Toast::title('SMTP successfully updated');
        } catch (\Throwable $exception) {
            Toast::danger($exception->getMessage());
        }

        return redirect()->back();
    }

    public function testSMTP(Request $request, NotificationService $notificationService): RedirectResponse
    {
        try {
            $validated = $request->validate([
                'email' => 'required',
            ]);

            $notificationService->sendEmailMessage('Testing SMTP', 'SMTP test email', auth()->user(), $validated['email']);

            Toast::title('SMTP mail sent');

        } catch (\Exception $e) {
            Toast::danger($e->getMessage());
        }

        return redirect()->back();

    }

    public function testWelcomeEmail(Request $request, EmailService $emailService): RedirectResponse
    {
        try {
            $validated = $request->validate([
                'email' => ['required', 'email'],
                'welcome_message_type' => ['required', Rule::in(array_keys($this->welcomeMessageTypeOptions()))],
            ]);

            $user = auth()->user();

            if (! $user instanceof User) {
                $user = User::findOrFail(auth()->id());
            }

            $emailService->sendWelcomeEmail($user, $validated['welcome_message_type'], $validated['email']);

            Toast::title('Welcome email test sent');
        } catch (\Throwable $exception) {
            Toast::danger($exception->getMessage());
        }

        return redirect()->back();
    }
}

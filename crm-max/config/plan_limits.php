<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Tenant plan-limit message
    |--------------------------------------------------------------------------
    |
    | Shown when a tenant plan limit is hit on a customer-facing endpoint. The
    | end-user of a tenant cannot upgrade anything -- the tenant (broker) owns
    | the plan -- so the default is a "contact support" message rather than an
    | upgrade CTA. Resolution order at runtime:
    |   company.settings['plan_limit_message']  (company-defined override)
    |     -> config('plan_limits.messages.'.$key) (optional per-key message)
    |       -> config('plan_limits.default_message')
    |
    */
    'default_message' => env('PLAN_LIMIT_MESSAGE', 'Please contact support.'),

    'messages' => [
        // 'open_trades_max' => 'You have reached the maximum number of open trades.',
        'trading_accounts_max' => 'You have reached the number of live trading accounts available on your account. Please contact support if you need another one.',
        'practice_accounts_max' => 'You have reached the number of demo accounts available on your account. Use one of your existing demo accounts, or contact support.',
    ],

    /*
    |--------------------------------------------------------------------------
    | Admin upgrade message
    |--------------------------------------------------------------------------
    |
    | Shown to a tenant ADMIN (web) when they hit a cap. Unlike the customer
    | "contact support" message, the admin owns the plan and can upgrade, so this
    | is an upgrade prompt. ":label" is replaced with the limit's label.
    |
    */
    'upgrade_message' => env(
        'PLAN_LIMIT_UPGRADE_MESSAGE',
        "You've reached your plan's :label limit. Please upgrade your plan to add more.",
    ),

    /*
    |--------------------------------------------------------------------------
    | Limit key registry + fall-through defaults
    |--------------------------------------------------------------------------
    |
    | Every known limit key with the value applied when neither a company
    | override nor the company's plan defines it. Defaults are UNLIMITED (-1) so
    | that adding a brand-new key never throttles existing companies until a cap
    | is explicitly set on a plan -- limits can be added anytime and resolve for
    | all companies without a per-company migration.
    |
    | Value semantics (per key): -1 = unlimited, 0 = hard block, N = cap.
    |
    */
    'defaults' => [
        'open_trades_max' => -1,
        'open_trades_per_account_max' => -1,
        'trade_max_duration_seconds' => -1,
        'customers_max' => -1,
        'managers_max' => -1,
        'admins_max' => -1,
        'trading_accounts_max' => -1,
        'practice_accounts_max' => -1,
        'leads_max' => -1,
        'daily_trades_max' => -1,
    ],

    /*
    |--------------------------------------------------------------------------
    | Human labels (admin usage panel + plan editor)
    |--------------------------------------------------------------------------
    */
    'labels' => [
        'open_trades_max' => 'Open trades (per user)',
        'open_trades_per_account_max' => 'Open trades (per account)',
        'trade_max_duration_seconds' => 'Max trade duration (seconds)',
        'customers_max' => 'Customers',
        'managers_max' => 'Managers',
        'admins_max' => 'Admins',
        'trading_accounts_max' => 'Live trading accounts',
        'practice_accounts_max' => 'Demo (practice) accounts',
        'leads_max' => 'Leads',
        'daily_trades_max' => 'Trades per day',
    ],

    /*
    |--------------------------------------------------------------------------
    | Usage panel
    |--------------------------------------------------------------------------
    |
    | Company-scoped keys surfaced in the tenant admin dashboard usage panel.
    | Counting is implemented in App\Services\PlanLimitUsage.
    |
    */
    'usage_panel' => [
        'customers_max',
        'managers_max',
        'admins_max',
        'trading_accounts_max',
        'leads_max',
    ],
];

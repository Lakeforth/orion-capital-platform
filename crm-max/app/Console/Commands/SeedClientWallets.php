<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Models\TradingAccount;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

class SeedClientWallets extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'wallet-components:seed-client-wallets
        {--dry-run : Report counts only, write nothing}
        {--company= : Scope to a single company ID}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Seed the client-level Credit/Bonus wallets from each sole-account user\'s existing TradingAccount.credit/.bonus (relabeling already-embedded money, never moving it) -- part of the wallet redesign that moves Credit/Bonus off the per-account component system.';

    /**
     * Credit and Bonus are moving from being TradingAccount-level components
     * back to client-level wallets (Account rows, title=credit_wallet /
     * bonus_wallet). For a sole-account user, whatever's currently in
     * TradingAccount.credit/.bonus (set by the earlier
     * BackfillWalletComponents run) is already real money sitting inside
     * that account's balance -- this command does NOT move it. It only
     * seeds the new client-level wallet with a record of what's already
     * there:
     *
     * - credit_wallet.balance = -account.credit (the sign convention Account
     *   ::draw()/repay() expects: 0 = nothing drawn, negative = amount
     *   drawn/owed)
     * - credit_wallet.credit_limit = account.credit (a safe floor -- starts
     *   fully drawn, $0 available, until an admin explicitly raises the
     *   real limit per client; this is a business decision, not something
     *   derivable from data)
     * - bonus_wallet.balance = account.bonus (plain wallet, no limit concept)
     *
     * TradingAccount.balance is never touched by this command. 0/multi
     * -account users are left untouched and flagged for manual review, same
     * "unambiguous or don't guess" rule BackfillWalletComponents already
     * established.
     */
    public function handle(): void
    {
        $dryRun = (bool) $this->option('dry-run');
        $companyId = $this->option('company');

        $companies = Company::query()
            ->when($companyId, fn ($q, $id) => $q->where('id', $id))
            ->cursor();

        $totalUsers = 0;
        $totalSeeded = 0;
        $totalFailed = 0;
        $needsManualReview = [];

        foreach ($companies as $company) {
            app()->instance('company', $company);

            $usersForCompany = 0;
            $seededForCompany = 0;
            $failedForCompany = 0;
            $snapshots = [];

            TradingAccount::query()
                ->withoutGlobalScope('company')
                ->where('company_id', $company->id)
                ->with('user')
                ->chunkById(200, function ($accounts) use (
                    $dryRun,
                    $company,
                    &$usersForCompany,
                    &$seededForCompany,
                    &$failedForCompany,
                    &$totalUsers,
                    &$totalSeeded,
                    &$totalFailed,
                    &$needsManualReview,
                    &$snapshots
                ) {
                    // Group by user first -- chunkById iterates accounts, but
                    // "sole account" needs to be evaluated per user, and we
                    // only want to process each user once.
                    $accountsByUser = $accounts->filter(fn ($a) => $a->user)->groupBy('user_id');

                    foreach ($accountsByUser as $userId => $userAccounts) {
                        $user = $userAccounts->first()->user;
                        $sameCompanyAccountCount = $user->tradingAccounts()->where('company_id', $company->id)->count();
                        $isSoleAccount = $sameCompanyAccountCount === 1;

                        $usersForCompany++;
                        $totalUsers++;

                        if (! $isSoleAccount) {
                            $needsManualReview[] = [
                                'user_id' => $user->id,
                                'email' => $user->email ?? null,
                                'company_id' => $company->id,
                                'trading_account_count' => $sameCompanyAccountCount,
                            ];

                            continue;
                        }

                        $account = $userAccounts->first();
                        $creditToSeed = (float) $account->credit;
                        $bonusToSeed = (float) $account->bonus;

                        $creditWallet = $user->credit_wallet();
                        $bonusWallet = $user->bonus_wallet();

                        $before = [
                            'user_id' => $user->id,
                            'trading_account_id' => $account->id,
                            'trading_account_balance' => (float) $account->balance,
                            'account_credit_column' => $creditToSeed,
                            'account_bonus_column' => $bonusToSeed,
                            'prior_credit_wallet_balance' => (float) ($creditWallet->balance ?? 0),
                            'prior_credit_wallet_limit' => (float) ($creditWallet->credit_limit ?? 0),
                            'prior_bonus_wallet_balance' => (float) ($bonusWallet->balance ?? 0),
                        ];

                        $after = [
                            'credit_wallet_balance' => -$creditToSeed,
                            'credit_wallet_limit' => $creditToSeed,
                            'bonus_wallet_balance' => $bonusToSeed,
                        ];

                        $snapshots[] = ['before' => $before, 'after' => $after];

                        if ($dryRun) {
                            $seededForCompany++;
                            $totalSeeded++;

                            continue;
                        }

                        try {
                            $creditWallet->update([
                                'balance' => -$creditToSeed,
                                'credit_limit' => $creditToSeed,
                            ]);
                            $bonusWallet->update([
                                'balance' => $bonusToSeed,
                            ]);

                            $seededForCompany++;
                            $totalSeeded++;
                        } catch (Throwable $e) {
                            $failedForCompany++;
                            $totalFailed++;
                            Log::error('wallet-components.seed-client-wallets.failed', [
                                'user_id' => $user->id,
                                'error' => $e->getMessage(),
                            ]);
                            $this->error("Failed for user {$user->id}: {$e->getMessage()}");
                        }
                    }
                });

            if ($usersForCompany === 0) {
                continue;
            }

            if (! empty($snapshots)) {
                $prefix = $dryRun ? 'wallet-seed-snapshots/preview-' : 'wallet-seed-snapshots/company-';
                Storage::disk('local')->put(
                    "{$prefix}{$company->id}-".now()->format('Ymd-His').'.json',
                    json_encode($snapshots, JSON_PRETTY_PRINT)
                );
            }

            $this->line(
                ($dryRun ? '[dry-run] ' : '')
                ."Company {$company->id} ({$company->name}): {$usersForCompany} user(s), "
                ."{$seededForCompany} seeded"
                .($failedForCompany > 0 ? ", {$failedForCompany} failed" : '')
            );
        }

        $this->info(
            ($dryRun ? '[dry-run] ' : '')
            ."Total: {$totalUsers} user(s) processed, {$totalSeeded} seeded"
            .($totalFailed > 0 ? ", {$totalFailed} failed" : '')
            .'.'
        );

        if (! empty($needsManualReview)) {
            $this->warn(count($needsManualReview).' user(s) have 0 or 2+ trading accounts -- left untouched, needs manual review:');
            foreach ($needsManualReview as $entry) {
                $this->line(
                    "  user {$entry['user_id']} ({$entry['email']}) company {$entry['company_id']}: "
                    ."{$entry['trading_account_count']} account(s)"
                );
            }

            if (! $dryRun) {
                Storage::disk('local')->put(
                    'wallet-seed-snapshots/needs-manual-review-'.now()->format('Ymd-His').'.json',
                    json_encode($needsManualReview, JSON_PRETTY_PRINT)
                );
            }
        }
    }
}

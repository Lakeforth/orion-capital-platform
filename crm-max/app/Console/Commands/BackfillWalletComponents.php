<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Models\Transaction;
use App\Models\TradingAccount;
use App\Models\TradingAccountComponentMovement;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

class BackfillWalletComponents extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'wallet-components:backfill
        {--dry-run : Report counts only, write nothing}
        {--company= : Scope to a single company ID}
        {--force : Recompute even accounts that already look backfilled -- safe only when balance has not drifted since (e.g. via TradingAccountComponentMovement source=trade_settlement), since it overwrites from current balance, not adds on top}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Backfill deposit/credit/bonus/profit columns on existing TradingAccount rows for the four-component wallet system.';

    /**
     * Revised a second time, 2026-07-20, same day: the first correction
     * (net lifetime deposits from the Transaction ledger, profit = leftover)
     * was itself wrong on the one real account it was checked against --
     * Nick pasted the account's actual trade history and its 55 closed
     * trades sum to $134,746.29 (confirmed via
     * trades()->where('status',1)->sum('profit')), not the $102,741.33
     * the Transaction-ledger reconstruction produced. The trades table is
     * the literal mechanism that ever added this money to the balance
     * (TradingAccount::adjustBalanceForTrade reads Trade::profit directly)
     * -- it's authoritative, not an inference, and unlike the historical
     * Transaction ledger it's already correctly attributed to one specific
     * account (trading_account_id), so this works for EVERY account, not
     * just sole-account users.
     *
     * Formula:
     * 1. Credit/Bonus: for a sole-account user only, prefer the EXACT
     *    historical figures from that user's balance_consolidation
     *    Transaction(s) -- there can be more than one, confirmed on Richard
     *    White (an initial consolidation + a later top-up), sum across all
     *    of them -- plus whatever's currently still sitting in the legacy
     *    credit_wallet/bonus_wallet Account rows (covers post-consolidation
     *    top-ups). 0/multi-account users: 0, no folding, flagged for manual
     *    review (unchanged from before -- still no safe way to attribute a
     *    legacy user-level wallet to one of several accounts).
     * 2. Profit: sum of this account's own closed trades' profit
     *    (trades()->where('status',1)->sum('profit')), floored at 0 and
     *    capped at whatever's left after credit/bonus -- applies to every
     *    account, sole or multi.
     * 3. Deposit: whatever remains after credit/bonus/profit.
     * 4. `users.total_deposit` synced to the new deposit figure for
     *    sole-account users (the only case where it's unambiguous which
     *    account's deposit figure represents the user), so the Analytics
     *    page and the new wallet breakdown can't visibly disagree.
     */
    public function handle(): void
    {
        $dryRun = (bool) $this->option('dry-run');
        $force = (bool) $this->option('force');
        $companyId = $this->option('company');

        $companies = Company::query()
            ->when($companyId, fn ($q, $id) => $q->where('id', $id))
            ->cursor();

        $totalAccounts = 0;
        $totalFolded = 0;
        $totalFailed = 0;
        $needsManualReview = [];

        foreach ($companies as $company) {
            app()->instance('company', $company);

            $accountsForCompany = 0;
            $foldedForCompany = 0;
            $failedForCompany = 0;
            $snapshots = [];

            TradingAccount::query()
                ->withoutGlobalScope('company')
                ->where('company_id', $company->id)
                ->when(! $force, fn ($q) => $q->where('deposit', 0)->where('credit', 0)->where('profit', 0))
                ->with('user')
                ->chunkById(200, function ($accounts) use (
                    $dryRun,
                    &$accountsForCompany,
                    &$foldedForCompany,
                    &$failedForCompany,
                    &$totalAccounts,
                    &$totalFolded,
                    &$totalFailed,
                    &$needsManualReview,
                    &$snapshots
                ) {
                    foreach ($accounts as $account) {
                        $accountsForCompany++;
                        $totalAccounts++;

                        $user = $account->user;
                        $sameCompanyAccountCount = $user
                            ? $user->tradingAccounts()->where('company_id', $account->company_id)->count()
                            : 0;
                        $isSoleAccount = $sameCompanyAccountCount === 1;

                        $balance = (float) $account->balance;
                        $existingBonusColumn = (float) $account->bonus;

                        $before = [
                            'account_id' => $account->id,
                            'user_id' => $user?->id,
                            'balance' => $balance,
                            'bonus' => $existingBonusColumn,
                            'prior_deposit' => (float) $account->deposit,
                            'prior_credit' => (float) $account->credit,
                            'prior_profit' => (float) $account->profit,
                        ];

                        $creditToFold = 0.0;
                        $bonusToFold = 0.0;
                        $consolidationCount = 0;

                        if ($user && $isSoleAccount) {
                            // A user can have MORE THAN ONE balance_consolidation
                            // row (confirmed: Richard White has two, an initial
                            // one plus a later top-up) -- sum across all of them,
                            // never just the first.
                            $consolidations = Transaction::where('user_id', $user->id)
                                ->where('type', 'balance_consolidation')
                                ->get();
                            $consolidationCount = $consolidations->count();

                            $consolidationCreditSum = 0.0;
                            $consolidationBonusSum = 0.0;

                            foreach ($consolidations as $consolidation) {
                                if (is_array($consolidation->details)) {
                                    $consolidationCreditSum += (float) ($consolidation->details['credit_moved'] ?? 0);
                                    $consolidationBonusSum += (float) ($consolidation->details['bonus_moved'] ?? 0);
                                }
                            }

                            $creditToFold += $consolidationCreditSum;
                            $bonusToFold += $consolidationBonusSum;

                            $creditToFold += (float) ($user->credit_wallet()->balance ?? 0);
                            $bonusToFold += (float) ($user->bonus_wallet()->balance ?? 0);

                            $before['consolidation_count'] = $consolidationCount;
                            $before['consolidation_credit_moved_sum'] = $consolidationCreditSum;
                            $before['consolidation_bonus_moved_sum'] = $consolidationBonusSum;
                            $before['legacy_credit_wallet_balance'] = (float) ($user->credit_wallet()->balance ?? 0);
                            $before['legacy_bonus_wallet_balance'] = (float) ($user->bonus_wallet()->balance ?? 0);
                        } elseif ($user) {
                            $needsManualReview[] = [
                                'user_id' => $user->id,
                                'email' => $user->email ?? null,
                                'company_id' => $account->company_id,
                                'trading_account_count' => $sameCompanyAccountCount,
                                'legacy_credit_wallet_balance' => (float) ($user->credit_wallet()->balance ?? 0),
                                'legacy_bonus_wallet_balance' => (float) ($user->bonus_wallet()->balance ?? 0),
                            ];
                        }

                        // Credit/Bonus: for a sole-account user (the only case
                        // where creditToFold/bonusToFold are ever non-zero),
                        // fully RE-DERIVE from their source records every run
                        // rather than adding on top of the current column --
                        // caught a real double-count bug here on the second
                        // --force re-run: adding a freshly-recomputed
                        // bonusToFold on top of $existingBonusColumn (which
                        // already held the PRIOR run's fold) doubled it. This
                        // re-derivation is only correct because credit/bonus
                        // on these accounts has never been funded any other
                        // way (verified via TradingAccountComponentMovement:
                        // zero admin_funding/trade_settlement rows exist for
                        // this company) -- if that ever changes, this needs
                        // to account for organic (non-fold) funding
                        // separately. For multi/0-account users (never
                        // folded, creditToFold/bonusToFold always 0), the
                        // existing column values are left exactly as they
                        // were -- never overwritten to 0.
                        if ($isSoleAccount) {
                            $credit = min($creditToFold, $balance);
                            $bonus = min($bonusToFold, max(0, $balance - $credit));
                        } else {
                            $credit = (float) $account->credit;
                            $bonus = $existingBonusColumn;
                        }
                        $remaining = max(0, $balance - $credit - $bonus);

                        $realizedProfit = max(0, (float) $account->trades()->where('status', 1)->sum('profit'));
                        $before['realized_profit_from_trades'] = $realizedProfit;

                        $profit = min($realizedProfit, $remaining);
                        $deposit = max(0, $remaining - $profit);

                        $after = [
                            'deposit' => $deposit,
                            'credit' => $credit,
                            'bonus' => $bonus,
                            'profit' => $profit,
                        ];

                        $snapshots[] = ['before' => $before, 'after' => $after];

                        $didFold = $credit > 0 || $bonus > 0;

                        if ($dryRun) {
                            if ($didFold) {
                                $foldedForCompany++;
                                $totalFolded++;
                            }

                            continue;
                        }

                        try {
                            $account->deposit = $deposit;
                            $account->credit = $credit;
                            $account->bonus = $bonus;
                            $account->profit = $profit;
                            $account->recalculateBalance();
                            $account->save();

                            $account->componentMovements()->create([
                                'component' => 'deposit',
                                'delta' => $deposit,
                                'balance_after' => $deposit,
                                'source' => TradingAccountComponentMovement::SOURCE_BACKFILL,
                                'created_at' => now(),
                            ]);

                            if ($isSoleAccount && $user) {
                                // Keep the pre-existing analytics stat in sync
                                // with the new component so the two never
                                // visibly disagree again.
                                $user->total_deposit = $deposit;
                                $user->save();

                                // Zero out whatever's currently in the legacy
                                // wallets (post-consolidation top-ups, or the
                                // whole thing for a never-consolidated user) --
                                // already zero if a prior consolidation already
                                // did this, harmless no-op either way.
                                if ((float) ($user->credit_wallet()->balance ?? 0) > 0) {
                                    $user->credit_wallet()->update(['balance' => 0]);
                                }
                                if ((float) ($user->bonus_wallet()->balance ?? 0) > 0) {
                                    $user->bonus_wallet()->update(['balance' => 0]);
                                }
                            }

                            if ($didFold) {
                                $foldedForCompany++;
                                $totalFolded++;
                            }
                        } catch (Throwable $e) {
                            $failedForCompany++;
                            $totalFailed++;
                            Log::error('wallet-components.backfill.failed', [
                                'trading_account_id' => $account->id,
                                'error' => $e->getMessage(),
                            ]);
                            $this->error("Failed for account {$account->id}: {$e->getMessage()}");
                        }
                    }
                });

            if ($accountsForCompany === 0) {
                continue;
            }

            if (! empty($snapshots)) {
                $prefix = $dryRun ? 'wallet-backfill-snapshots/preview-' : 'wallet-backfill-snapshots/company-';
                Storage::disk('local')->put(
                    "{$prefix}{$company->id}-".now()->format('Ymd-His').'.json',
                    json_encode($snapshots, JSON_PRETTY_PRINT)
                );
            }

            $this->line(
                ($dryRun ? '[dry-run] ' : '')
                ."Company {$company->id} ({$company->name}): {$accountsForCompany} account(s), "
                ."{$foldedForCompany} with legacy credit/bonus folded in"
                .($failedForCompany > 0 ? ", {$failedForCompany} failed" : '')
            );
        }

        $this->info(
            ($dryRun ? '[dry-run] ' : '')
            ."Total: {$totalAccounts} account(s) processed, {$totalFolded} with legacy wallets folded"
            .($totalFailed > 0 ? ", {$totalFailed} failed" : '')
            .'.'
        );

        if (! empty($needsManualReview)) {
            $this->warn(count($needsManualReview).' user(s) have credit/bonus wallet money but 0 or 2+ trading accounts -- left untouched, needs manual review:');
            foreach ($needsManualReview as $entry) {
                $this->line(
                    "  user {$entry['user_id']} ({$entry['email']}) company {$entry['company_id']}: "
                    ."{$entry['trading_account_count']} account(s), "
                    ."credit_wallet={$entry['legacy_credit_wallet_balance']}, bonus_wallet={$entry['legacy_bonus_wallet_balance']}"
                );
            }

            if (! $dryRun) {
                Storage::disk('local')->put(
                    'wallet-backfill-snapshots/needs-manual-review-'.now()->format('Ymd-His').'.json',
                    json_encode($needsManualReview, JSON_PRETTY_PRINT)
                );
            }
        }
    }
}

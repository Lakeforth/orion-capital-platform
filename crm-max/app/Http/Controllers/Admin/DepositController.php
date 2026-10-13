<?php

namespace App\Http\Controllers\Admin;

use App\Events\UserUpdate;
use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Transaction;
use App\Models\TradingAccount;
use App\Models\TradingAccountComponentMovement;
use App\Models\User;
use App\Services\NotificationService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use ProtoneMedia\Splade\Facades\Toast;

class DepositController extends Controller
{
    /**
     * "Account Wallet" deposits/withdrawals must land on the user's real
     * trading account once they have one -- the legacy User::balance column
     * is invisible everywhere the app actually reads balance from once a
     * TradingAccount exists (Dashboard, Trading Accounts page, trade room).
     * Only resolves when the user has exactly one account, same "unambiguous
     * or don't guess" rule ResolveTradingAccount's middleware already uses --
     * falls back to the legacy path for 0 or >1 accounts.
     */
    private function resolveSoleTradingAccount(User $user): ?TradingAccount
    {
        $accounts = $user->tradingAccounts;

        return $accounts->count() === 1 ? $accounts->first() : null;
    }

    /**
     * Resolves an explicitly-chosen trading account from the deposit form's
     * "trading_account:{id}" or "trading_account:{id}:{component}" option
     * (see the accounts() helper). Scoped to this user's own accounts only
     * -- never trust a submitted ID without that check, an admin form field
     * is still user input.
     */
    private function resolveChosenTradingAccount(User $user, string $accountField): ?TradingAccount
    {
        if (! str_starts_with($accountField, 'trading_account:')) {
            return null;
        }

        $rest = substr($accountField, strlen('trading_account:'));
        $tradingAccountId = str_contains($rest, ':') ? strstr($rest, ':', true) : $rest;

        return $user->tradingAccounts->firstWhere('id', $tradingAccountId);
    }

    /**
     * Parses the "trading_account:{id}:{component}" suffix, defaulting to
     * "deposit" for the plain "trading_account:{id}" form (used by tenants
     * not opted into the wallet-component system, and as a
     * backward-compatible default for any stale/unsuffixed value). Profit is
     * intentionally not a valid admin-choosable component here -- it only
     * ever comes from trade settlement (TradingAccount::adjustBalanceForTrade()).
     */
    private function resolveChosenComponent(string $accountField): string
    {
        $component = (string) strrchr($accountField, ':');
        $component = ltrim($component, ':');

        return in_array($component, ['deposit', 'credit', 'bonus'], true) ? $component : 'deposit';
    }

    /**
     * Sets the client's Credit Wallet limit -- a business decision made per
     * client, not something derived from data. Never touches the drawn
     * balance itself.
     */
    public function updateCreditLimit(Request $request, $id)
    {
        $validated = $request->validate([
            'credit_limit' => 'required|numeric|min:0',
        ]);

        $user = User::findOrFail($id);
        $user->credit_wallet()->update(['credit_limit' => $validated['credit_limit']]);

        Toast::success('Credit limit updated.');

        return redirect()->back();
    }

    public function create($id)
    {
        $user = User::findOrFail($id);
        $type = request()->query('type', 'deposit');

        if (has_accounts()) {

            $accounts = $user->accounts()->get();
            $tradingAccounts = $user->tradingAccounts;
            $tradingAccountsPaginated = TradingAccount::withoutGlobalScope('company')
                ->where('company_id', $user->company_id)
                ->where('user_id', $user->id)
                ->where('account_type', '!=', TradingAccount::TYPE_CHALLENGE)
                ->latest()
                ->paginate(10);

            if ($accounts->count() < 1) {

                collect(['credit_wallet', 'bonus_wallet'])->each(fn ($title) => Account::create([
                    'user_id' => $user->id,
                    'balance' => 0,
                    'title' => $title,
                ])
                );

            }

            // Default the form to the user's own trading account when they
            // have exactly one -- same "unambiguous or don't guess" rule
            // used everywhere else this session -- otherwise fall back to
            // the generic wallet option so the form still has a valid default.
            $defaultAccount = $tradingAccounts->count() === 1
                ? 'trading_account:'.$tradingAccounts->first()->id
                : 'account_wallet';

            return view('admin.deposit.create', [
                'user' => $user,
                'type' => $type,
                'accounts' => $accounts,
                'tradingAccounts' => $tradingAccounts,
                'tradingAccountsPaginated' => $tradingAccountsPaginated,
                'default' => [
                    'user_id' => $id,
                    'type' => $type,
                    'notify' => 'no',
                    'show_in_history' => 'yes',
                    'alert_notify' => 'no',
                    'alert_color' => $type == 'deposit' ? 'green' : 'red',
                    'account' => $defaultAccount,
                ],
            ]);
        } else {
            return view('admin.deposit.create', [
                'user' => $user,
                'type' => $type,
                'default' => [
                    'user_id' => $id,
                    'type' => $type,
                    'notify' => 'no',
                    'show_in_history' => 'yes',
                    'alert_notify' => 'no',
                    'alert_color' => $type == 'deposit' ? 'green' : 'red',
                    'account' => 'account_wallet',
                ],
            ]);
        }

    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric',
            'user_id' => 'required',
            'type' => 'required',
            'description' => 'nullable|string',
            'account' => 'nullable|string',
            'account_group' => 'nullable|string',
            'component' => 'nullable|in:deposit,credit,bonus',
            'show_in_history' => 'nullable|string',
            'alert_notify' => 'nullable|string',
            'alert_color' => 'nullable|string',
            'date' => 'nullable|date',
            'notify' => 'required|in:yes,no',
        ]);

        // Two-step picker (wallet_components_enabled tenants): combine
        // account_group + component back into the exact same
        // "trading_account:{id}:{component}" / "spot_wallet" format the
        // rest of this method already expects, so nothing downstream needs
        // to know the form UI changed. Only append :component when a real
        // trading account was chosen -- ignore a stray leftover component
        // value from before the admin switched to spot_wallet (Splade's
        // v-if hides the field but doesn't necessarily clear it).
        if (! empty($validated['account_group'])) {
            $validated['account'] = str_starts_with($validated['account_group'], 'trading_account:')
                ? $validated['account_group'].':'.($validated['component'] ?? 'deposit')
                : $validated['account_group'];
        }

        $type = $validated['type'];

        $alert_notify = $validated['alert_notify'] ?? 'no';

        $show_in_history = $validated['show_in_history'] ?? 'yes';

        if ($show_in_history == 'no') {
            $t_type = $type === 'withdraw' ? 'system_withdrawal' : 'system_deposit';
        } else {
            $t_type = $type === 'withdraw' ? 'w' : 'd';
        }

        $transactionDate = $validated['date'] ?? null;

        $transaction = Transaction::create([
            'user_id' => $validated['user_id'],
            'amount' => $validated['amount'],
            'status' => 'approved',
            'type' => $t_type,
            'reason' => $validated['description'] ?? null,
            'account' => $validated['account'],
            'created_at' => $transactionDate ? Carbon::parse($transactionDate) : now(),
        ]);

        $user = User::findOrFail($validated['user_id']);

        $alert_color = $validated['alert_color'] ?? 'green';

        $color = $alert_color == 'green' ? 'success' : 'danger';

        if ($alert_notify == 'yes') {
            app(NotificationService::class)->sendSystemAnnouncement(
                $user,
                (string) ($validated['description'] ?? ''),
                $color,
                [
                    'transaction_id' => $transaction->id,
                    'transaction_type' => $t_type,
                    'amount' => (float) $validated['amount'],
                ]
            );

        }

        $account_title = 'Account';
        $legacyFlat = company_settings('wallet_components_enabled', 'no') != 'yes';

        if ($type === 'withdraw') {

            if (isset($validated['account'])) {
                if ($validated['account'] == 'credit_wallet') {
                    $account_title = 'Legacy Credit Wallet';
                    $account = $user->credit_wallet();
                    if ($account) {
                        $transaction->account_id = $account->id;
                        $user->credit_wallet()->withdraw($validated['amount']);

                        broadcast(new UserUpdate($user->id));

                    }

                }
                if ($validated['account'] == 'bonus_wallet') {
                    $account_title = 'Legacy Bonus Wallet';
                    $account = auth()->user()->bonus_wallet();
                    if ($account) {
                        $transaction->account_id = $account->id;
                        $user->bonus_wallet()->withdraw($validated['amount']);
                    }
                }
                if ($validated['account'] == 'spot_wallet') {
                    $account_title = 'Spot Wallet';
                    $user->withdraw($validated['amount']);
                }
                if ($validated['account'] == 'account_wallet') {
                    $account_title = 'Account Wallet';
                    $tradingAccount = $this->resolveSoleTradingAccount($user);
                    if ($tradingAccount) {
                        $transaction->account_id = $tradingAccount->id;
                        if ($legacyFlat) {
                            $tradingAccount->withdraw($validated['amount']);
                        } else {
                            $tradingAccount->debitComponent('deposit', (float) $validated['amount'], TradingAccountComponentMovement::SOURCE_ADMIN_FUNDING, $transaction->id, auth()->id());
                        }
                    } else {
                        $user->withdraw($validated['amount']);
                    }
                }

                if ($chosenTradingAccount = $this->resolveChosenTradingAccount($user, $validated['account'])) {
                    $component = $this->resolveChosenComponent($validated['account']);
                    $account_title = 'Trading Account #'.$chosenTradingAccount->account_id.($legacyFlat ? '' : ' ('.ucfirst($component).')');
                    $transaction->account_id = $chosenTradingAccount->id;
                    if ($legacyFlat) {
                        $chosenTradingAccount->withdraw($validated['amount']);
                    } else {
                        $chosenTradingAccount->debitComponent($component, (float) $validated['amount'], TradingAccountComponentMovement::SOURCE_ADMIN_FUNDING, $transaction->id, auth()->id());
                    }
                }

            } else {
                $tradingAccount = $this->resolveSoleTradingAccount($user);
                if ($tradingAccount) {
                    if ($legacyFlat) {
                        $tradingAccount->withdraw($validated['amount']);
                    } else {
                        $tradingAccount->debitComponent('deposit', (float) $validated['amount'], TradingAccountComponentMovement::SOURCE_ADMIN_FUNDING, $transaction->id, auth()->id());
                    }
                } else {
                    $user->withdraw($validated['amount']);
                }
            }

            log_activity("$account_title Withdrawal", 'Admin user => '.auth()->user()->email.' withdrawn '.format_price($validated['amount']), $validated['user_id'], 'transaction');

        } else {

            log_activity("$account_title Deposit", 'Admin user => '.auth()->user()->email.' deposited '.$validated['amount'], $validated['user_id'], 'transaction');

            if (isset($validated['account'])) {
                if ($validated['account'] == 'credit_wallet') {
                    $account = $user->credit_wallet();
                    if ($account) {
                        $transaction->account_id = $account->id;
                        $user->credit_wallet()->deposit($validated['amount']);

                        broadcast(new UserUpdate($user->id));
                    }

                }
                if ($validated['account'] == 'bonus_wallet') {
                    $account = $user->bonus_wallet();
                    if ($account) {
                        $transaction->account_id = $account->id;
                        $user->bonus_wallet()->deposit($validated['amount']);
                    }
                }

                if ($validated['account'] == 'spot_wallet') {
                    $user->deposit($validated['amount']);
                    // Hidden adjustments (show_in_history=no) are not deposits; keep them out of the total.
                    if ($show_in_history !== 'no') {
                        $user->addTotaldeposit((float) $validated['amount']);
                    }
                }

                if ($validated['account'] == 'account_wallet') {
                    $tradingAccount = $this->resolveSoleTradingAccount($user);
                    if ($tradingAccount) {
                        $transaction->account_id = $tradingAccount->id;
                        if ($legacyFlat) {
                            $tradingAccount->deposit($validated['amount']);
                        } else {
                            $tradingAccount->creditComponent('deposit', (float) $validated['amount'], TradingAccountComponentMovement::SOURCE_ADMIN_FUNDING, $transaction->id, auth()->id());
                        }
                    } else {
                        $user->deposit($validated['amount']);
                    }
                    if ($show_in_history !== 'no') {
                        $user->addTotaldeposit((float) $validated['amount']);
                    }
                }

                if ($chosenTradingAccount = $this->resolveChosenTradingAccount($user, $validated['account'])) {
                    $component = $this->resolveChosenComponent($validated['account']);
                    $account_title = 'Trading Account #'.$chosenTradingAccount->account_id.($legacyFlat ? '' : ' ('.ucfirst($component).')');
                    $transaction->account_id = $chosenTradingAccount->id;
                    if ($legacyFlat) {
                        $chosenTradingAccount->deposit($validated['amount']);
                        if ($show_in_history !== 'no') {
                            $user->addTotaldeposit((float) $validated['amount']);
                        }
                    } else {
                        $chosenTradingAccount->creditComponent($component, (float) $validated['amount'], TradingAccountComponentMovement::SOURCE_ADMIN_FUNDING, $transaction->id, auth()->id());
                        // Only "Deposit" is real deposited money -- Credit (broker loan)
                        // and Bonus (promotional grant) are admin-fundable but were never
                        // deposited by the client, so they don't count toward the
                        // lifetime "Total Deposits" stat shown in analytics.
                        if ($component === 'deposit') {
                            if ($show_in_history !== 'no') {
                                $user->addTotaldeposit((float) $validated['amount']);
                            }
                        }
                    }
                }

            } else {
                $tradingAccount = $this->resolveSoleTradingAccount($user);
                if ($tradingAccount) {
                    if ($legacyFlat) {
                        $tradingAccount->deposit($validated['amount']);
                    } else {
                        $tradingAccount->creditComponent('deposit', (float) $validated['amount'], TradingAccountComponentMovement::SOURCE_ADMIN_FUNDING, $transaction->id, auth()->id());
                    }
                } else {
                    $user->deposit($validated['amount']);
                }
            }
        }

        Toast::success('Account '.$type.' of '.format_price($validated['amount']).' was successful');

        return redirect()->back();
    }
}

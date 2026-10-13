# Client admin controls

Approved scope: archive trading accounts, transfer funds between a customer's live accounts, and offer tenant-specific restricted Manager access.

## Account management

Open a customer, then Trading Accounts. **Manage account** opens a modal. The same modal is available from account details.

- Archive uses the existing soft-delete column and disables the account. It does not delete trade history. Archived accounts and their recent administrative activity remain accessible from the Archived accounts filter.
- Balance, Deposit, Profit, Bonus and Credit must all be zero. Open positions, pending/active scheduled trades, and pending customer deposits or withdrawals block archiving. Challenge accounts are excluded.
- Transfers require distinct active live accounts belonging to the same customer and tenant, with the same base currency. Amounts accept two decimal places, matching the account storage precision. No currency conversion occurs.
- Available funds are limited by both reserved margin and current free margin. With wallet components enabled, only Profit and Deposit move, preserving their categories. Bonus and Credit cannot move through this action. The customer's Spot Wallet is untouched.
- Changes and their audit record share one database transaction. Customer and account row locks serialize these admin operations. A per-form UUID makes identical retries idempotent; reuse with different details is rejected.
- The new audit table records actor, tenant, customer, source, destination, amount, currency, timestamp and balances before and after. The detail page shows the latest 25 relevant actions.

## Restricted Managers

**Settings > Modules > Restricted Manager access** controls `restricted_manager_enabled` (`yes`/`no`, default `no`). Only administrators can change this setting. It applies to the existing Manager role; users also holding Admin, Tenant Admin or Superadmin retain their administrative access.

Enabled Managers receive a customer list using the existing manager assignment filter and a profile with viewing controls. Account funding, transfers, withdrawals, account creation, edits, permission changes and other mutations are rejected by middleware, including direct requests. Customer impersonation is view-only too. Trading-account passwords are omitted in these viewing sessions and launch-token generation is blocked. Restricted customer API tokens expire after two hours.

New Manager-issued impersonation tokens include the actor ID, so the current tenant setting also applies to tokens issued by this version before the setting was enabled. Web impersonation checks the original manager session ID and restores the manager on return.

## Deployment and activation

1. Review and deploy the application change through the existing Livetrader release process. Run the additive migration `2026_09_10_000001_create_trading_account_admin_actions_table` before the new release serves requests. There is no environment-variable change or automatic tenant opt-in.
2. The setting is available to every tenant, off by default. Each tenant administrator can enable it when needed; no tenant-specific deployment is required.
3. First enablement records a tenant-specific legacy API-token cutoff. Older untracked customer sessions receive HTTP 401 and must sign in again, preventing legacy Manager impersonation tokens from retaining financial access. The UI explains this consequence. New logins and attributable Manager viewing tokens remain usable; other tenants are unaffected. No token deletion or manual session cleanup is needed. The cutoff cannot be supplied or reset through the settings form.
4. Verify archive/transfer workflows using designated test accounts, not customer funds.

Rollback the application release without dropping the audit table or restoring archived accounts automatically. Reversing an actual transfer or archive is a separate operational decision. Turning the Manager setting off permits ordinary Manager access again, but does not restore expired legacy sessions; already restricted viewing tokens remain view-only until a new login.

## Verification

Local browser checks exercised a light-mode modal without changing the underlying page URL, transfers of 40 and 60 between designated fixture accounts, archiving the resulting empty account, retained audit history, and a restricted Manager customer list/profile without edit controls.

Feature tests cover amount precision, tenant/customer isolation, account type and currency checks, margin, component preservation, idempotency, rollback on audit failure, archived login/launch/selection rejection, retained trade relations, pending requests, direct permission bypass attempts, impersonation credentials, return-to-manager, default-off behavior and tenant setting isolation.

Three failures in `TradingAccountTradeFlowTest` were reproduced identically against unchanged production commit `0424126`: an older login-password fixture, a closed-trade balance expectation, and an equity expectation including bonus. They are pre-existing and outside this change.

The related `WalletComponentsTest` trade-settlement test also fails identically on `0424126` (expected Profit 30, actual 0). The remaining wallet component checks pass.

# CRM-Max — the data it works with

The Eloquent models are part of the wider platform and are not included in this
folder. This is what CRM-Max reads and writes, so the code in `2-admin-crm-max/`
can be followed without them.

## Tables

| Model | Table | Holds |
|---|---|---|
| `User` | `users` | Everyone: leads, clients, agents, managers, affiliates and admins. Which one someone is comes from their roles, not a column |
| `Transaction` | `transactions` | Deposits, withdrawals and manual adjustments, each with a type, status, amount and method |
| `TradingAccount` | `trading_accounts` | A client's trading accounts — balance, credit, leverage, live or practice |
| `Trade` | `trades` | Open and closed positions |
| `Asset` | `assets` | The instrument list: symbol, type, rate, 24h volume. Shared across the platform |
| `UserId` | `user_ids` | KYC documents, grouped into submission attempts, each with a review decision |
| `ActivityLog` | `activity_logs` | Everything that happened to a lead: calls, reminders and calendar events, status changes, logins |
| `Note` | `notes` | Comments left on a lead |
| `LeadSignal` | `lead_signals` | Trading signals sent to a lead |
| `LeadInvoice` | `lead_invoices` | Invoices raised against a lead |
| `LeadSplitRule` | `lead_split_rules` | How incoming leads are distributed to agents |
| `LoginHistory` | `login_histories` | Sign-in records, used by the sessions tab |
| `Message` | `messages` | Chat between staff and clients |
| `SupportTicket` | `support_tickets` | Service requests raised from a lead |

## Things worth knowing before reading the code

**Roles, not types.** A lead that deposits becomes a client by gaining a role, not by
moving table. `whereHasRole('lead')` is how the leads list is scoped.

**`manager_id` is the owner.** On a lead it points at the agent who owns it. On an
agent it points at the manager they report to. The same column carries both
relationships, one tier apart.

**Two staff tiers.** An agent holds both the `manager` and `agent` roles; a manager
holds `manager` only. `App\Support\LeadMax\Staff` is the single place that decides
which is which — the permission checks still read `manager`, which is why agents
carry it too.

**Calendar events are activity-log rows.** There is no events table. A reminder or
meeting is an `activity_logs` row of type `reminder_created`, with the date, time,
priority, type and who it is with in its `info` JSON. Rows written before event types
existed have no `event_type` and are read as meetings.

**KYC documents are grouped into attempts.** `user_ids.attempt` groups the documents
sent together. An attempt stays open while any of its documents is pending, so several
uploads land in one; a resubmission after a rejection opens the next. Documents are
stored privately and served through the application, never linked directly.

**Money is denormalised deliberately.** `users.ftd_amount` holds the first-time deposit
so the leads list and its KPI cards do not aggregate the transactions table on every
page load.

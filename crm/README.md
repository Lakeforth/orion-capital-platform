## Layout

| | |
|---|---|
| `app/Http/Controllers/Admin/LeadMax*.php` | One controller per area: leads, finances, reports, tools (assets, calendar, markups), trades, affiliates, imports |
| `app/Services/LeadMax/` | The lead-details tab data |
| `app/Support/LeadMax/` | Lead fields, staff tiers, account numbers, per-company assets, display currency, feature switches |
| `resources/views/admin/leadmax/` | Blade templates, with page bodies split into `partials/` so a page can be re-served on its own for in-place updates |
| `resources/css/leadmax*.css`, `resources/js/leadmax*.js` | Assets, built by Vite |
| `routes-crm-max.php` | The module's route table, extracted from the application's `routes/admin.php` |
| `data-model.md` | The tables CRM-Max reads and writes |
| `env.example` | Settings the application reads |

## Why the JavaScript looks the way it does

Plain modules with document-level event delegation, and no inline `<script>` in any
Blade file. That is deliberate rather than old-fashioned: pages are rendered
server-side and swapped in as fragments, so a handler bound to an element at load time
would be bound to an element that no longer exists.

## Stack

PHP 8.3 (composer requires ^8.2), Laravel 11, MySQL 8.0, Redis for cache, sessions and
queues. Vite builds the CSS and JS.

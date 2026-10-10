# Orion Capital — platform source

Everything Orion Capital runs on, in one place.

## What is in here

| Folder | What it is |
|---|---|
| `website/` | The public website — Astro, built to static files |
| `webtrader-plus-clientarea/` | Where clients sign in: accounts, deposits and withdrawals, KYC, and the web trader |
| `crm/` | CRM-Max, the staff side: leads, agents, managers, finances, assets, calendar, reports, KYC review |

Each folder has its own README covering what it is, how it builds.

The three are separate applications that talk to each other over HTTP, which is why
they are separate folders rather than one tree. `crm/` serves the API that
`webtrader-plus-clientarea/` reads; `website/` links across to them.

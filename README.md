# Orion Capital — platform source

Everything Orion Capital runs on, in one place.

## What is in here

| Folder | What it is |
|---|---|
| `website/` | The public website — Astro, built to static files |
| `client-area/` | Where clients sign in: accounts, deposits and withdrawals, KYC, and the web trader |
| `crm/` | CRM-Max, the staff side: leads, agents, managers, finances, assets, calendar, reports, KYC review |

Each folder has its own README covering what it is, how it builds, and what was
withheld from it.

The three are separate applications that talk to each other over HTTP, which is why
they are separate folders rather than one tree. `crm/` serves the API that
`client-area/` reads; `website/` is independent of both and only links across to them.

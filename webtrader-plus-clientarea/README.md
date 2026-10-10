# client-area

The client area and the web trader. One React application — which part a visitor
sees depends on the route.

## Layout

| | |
|---|---|
| `src/pages/` | Client area: dashboard, trading accounts, withdrawals, transaction history, KYC, partners, settings |
| `src/components/trading/` | The web trader: chart, order ticket, positions, market watch |
| `src/components/mt4-light/` | An alternative light theme for the trade room |
| `src/pages/auth/` | Sign-in, registration and password recovery, dispatching to a theme chosen per brand |
| `src/store/` | Zustand stores: signed-in user, assets, trades, site settings |
| `src/lib/axios.ts` | The API client — every back-office call goes through it |
| `HANDOVER/env.example` | Every setting this application reads. Names only, no values |

## Build

```
npm install
npm run build    # writes dist/
```

Node 20 or newer. React 19, Vite 6, TypeScript, Tailwind.

## Branding is not compiled in

Logos, colours, names and which modules are enabled are read from the back office at
run time, over `/api/v1/site-settings`. The same build serves every brand on the
platform; nothing here needs rebuilding to change how Orion Capital looks.


<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Withheld pages
    |--------------------------------------------------------------------------
    |
    | Pages that are not ready for anyone yet. A withheld page leaves the
    | CRM-Max sidebar entirely -- it is not rendered greyed out, the way a
    | feature a tenant has switched off is -- and its routes stop answering.
    |
    | This is deliberately not per-tenant: it parks a page during development,
    | rather than turning one off for a customer. For the per-tenant switches
    | see App\Support\LeadMax\Features::SWITCHES (Settings > Modules).
    |
    | To bring a page back, delete its entry here. Nothing else needs changing:
    | the sidebar entry still carries its real route, so it reappears wired up.
    |
    | Keyed by the sidebar's own key => route name patterns, with the
    | "admin.leadmax." prefix already stripped.
    |
    */

    'hidden_pages' => [
        // Managers was parked here during development and is back in the sidebar.
    ],

];

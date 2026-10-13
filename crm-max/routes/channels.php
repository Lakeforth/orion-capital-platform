<?php

use Illuminate\Support\Facades\Broadcast;


//Broadcast::routes([
//    'prefix'     => 'broadcasting/auth',
//    'middleware' => ['web','auth'],
//]);
//
//// 2) Token‑based (SPA / Sanctum)
//Broadcast::routes([
//    'prefix'     => 'api/v1/broadcasting/auth',
//    'middleware' => ['api','auth:sanctum'],
//]);

Broadcast::channel('users.{id}', function (\App\Models\User $user, $id) {
    return $user->id == $id;
});

Broadcast::channel('user.{id}', function (\App\Models\User $user, $id) {
    return $user->id == $id;
});


Broadcast::channel('chat.user.{id}', function ($user, $id) {
//    return (string) $user->id === (string) $id;
    return true;
});

Broadcast::channel('trade.{id}', function ($user, $id) {
    return (string) $user->id === (string) $id;
});

// Lets an admin/staff viewer live-update a client's open/closed trade
// tables (back-office trading-account page) without polling -- scoped to
// the same admin roles the panel itself gates on (AdminAccess middleware)
// plus a same-company check so one tenant's staff can't listen in on
// another tenant's client.
Broadcast::channel('admin.trade.{id}', function ($user, $id) {
    if (! $user->hasRole(['superadmin', 'admin', 'tenant_admin', 'marketer', 'vendor', 'manager', 'rider', 'agent'])) {
        return false;
    }

    $target = \App\Models\User::find($id);

    return $target && $target->company_id === $user->company_id;
});

Broadcast::channel('notification.{id}', function (\App\Models\User $user, $id) {
    return $user->id == $id;
});


Broadcast::channel('chat.customer.{customerId}', function ($user, $customerId) {
    // allow the customer themself...
    if ($user->id === $customerId) {
        return true;
    }

    return true;

    // ...or any admin user
//    return (bool) $user->is_admin;
});


Broadcast::channel('notification', function () {

});


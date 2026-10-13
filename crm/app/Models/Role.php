<?php

namespace App\Models;

use Laratrust\Models\Role as RoleModel;

class Role extends RoleModel
{
    public $guarded = [];


    public function scopeDefault($query)
    {
        if (is_admin()) {
            return $query->where('name', '!=', 'superadmin');
        }

        // Prop Firm's Manager -> Agent -> Customer hierarchy: a manager may
        // only create agents, an agent may only create customers. Brokerage
        // (propfirm_enabled off) keeps the original unrestricted behavior.
        $propfirmEnabled = filter_var(company_settings('propfirm_enabled', false), FILTER_VALIDATE_BOOLEAN);
        $user = auth()->user();

        if ($propfirmEnabled && $user) {
            if ($user->hasRole('agent')) {
                return $query->whereIn('name', ['customer', 'user']);
            }

            if ($user->hasRole('manager')) {
                return $query->where('name', 'agent');
            }
        }

        return $query->where('name', '!=', 'superadmin')->where('name', '!=', 'admin');
    }
}

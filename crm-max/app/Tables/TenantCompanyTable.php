<?php

namespace App\Tables;

use App\Models\User;
use Illuminate\Http\Request;
use ProtoneMedia\Splade\AbstractTable;
use ProtoneMedia\Splade\SpladeTable;

class TenantCompanyTable extends AbstractTable
{
    public function authorize(Request $request)
    {
        return true;
    }

    public function for()
    {
        return User::query()
            ->whereHasRole('tenant_admin')
            ->withCount('companies')
            ->latest();
    }

    public function configure(SpladeTable $table)
    {
        $table
            ->withGlobalSearch(columns: ['company_name', 'first_name', 'last_name', 'email'])
            ->column('company_name', 'Company', sortable: true)
            ->column('first_name', 'First Name', sortable: true)
            ->column('last_name', 'Last Name', sortable: true)
            ->column('email', sortable: true)
            ->column('phone', sortable: true)
            ->column('companies_count', 'Platforms', sortable: true)
            ->paginate(10);
    }
}

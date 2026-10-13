<?php

namespace App\Tables;

use App\Models\Company;
use App\Models\Domain;
use Illuminate\Http\Request;
use ProtoneMedia\Splade\AbstractTable;
use ProtoneMedia\Splade\SpladeTable;

class TenantDomainTable extends AbstractTable
{
    public function authorize(Request $request)
    {
        return true;
    }

    public function for()
    {
        $query = Domain::query()
            ->with('company')
            ->whereHas('company', function ($query) {
                $query->where('owner_id', auth()->id());
            })
            ->latest();

        if ($platformId = request('filter.platform_id')) {
            $query->where('company_id', $platformId);
        }

        return $query;
    }

    public function configure(SpladeTable $table)
    {
        $companyOptions = Company::query()
            ->where('owner_id', auth()->id())
            ->pluck('name', 'id')
            ->toArray();

        $serverOptions = [];
        foreach (config('forge.servers', []) as $server) {
            $label = $server['label'] ?? $server['name'] ?? $server['id'] ?? 'Server';
            $serverOptions[(string) $server['id']] = $label;
        }

        $table
            ->withGlobalSearch(columns: ['domain'])
            ->column('server_id', sortable: true, label: 'Server')
            ->column('domain', sortable: true)
            ->column('website_domain', sortable: true, hidden: true)
            ->column('company_id', 'Platform', sortable: true)
            ->column('is_active', 'Active', sortable: true)
            ->column('is_primary', 'Primary', hidden: true, sortable: true)
            ->column('status', 'SSL Status', sortable: true)
            ->column('actions')
            ->selectFilter(
                key: 'company_id',
                options: $companyOptions,
                label: 'Platform',
                noFilterOptionLabel: 'All Platforms',
            )
            ->selectFilter(
                key: 'server_id',
                options: $serverOptions,
                label: 'Server',
                noFilterOptionLabel: 'All Servers',
            )
            ->paginate(10);
    }
}

<?php

namespace App\Tables;

use App\Models\User;
use Illuminate\Http\Request;
use ProtoneMedia\Splade\AbstractTable;
use ProtoneMedia\Splade\SpladeTable;

/**
 * Prop Firm's own version of MiniCustomerTable -- kept as a fully separate
 * class rather than branching inside MiniCustomerTable because is_mini() is
 * a global app config, not per-tenant, so MiniCustomerTable is shared with
 * every brokerage tenant on this box. This is additive only: a new class
 * routed to for propfirm_enabled tenants, brokerage's table untouched.
 */
class PropFirmCustomerTable extends AbstractTable
{
    public function authorize(Request $request)
    {
        return true;
    }

    public function for()
    {
        if (is_admin()) {
            return User::query()
                ->whereHasRole(['customer', 'user'])
                ->with(['affiliate', 'manager'])
                ->withCount(['notes']);
        }

        return User::query()
            ->where('manager_id', auth()->id())
            ->whereHasRole(['customer', 'user'])
            ->with(['affiliate', 'manager'])
            ->withCount(['notes']);
    }

    public function configure(SpladeTable $table)
    {
        $status = get_status();
        $campaigns = get_campaigns();
        $sources = get_sources();

        $table
            ->withGlobalSearch(columns: ['first_name', 'phone', 'last_name', 'email'])
            ->defaultSort('created_at', 'desc')
            ->column(key: 'first_name', label: 'User', sortable: true)
            ->column(key: 'phone', label: 'Phone', sortable: true)
            ->column(key: 'status', label: 'Status', sortable: true)
            ->column(key: 'manager_id', label: 'Agent', sortable: false)
            ->column(key: 'created_at', label: 'Date', sortable: true)
            // Identity/Email/Notes/Source/Campaign live inside the "more"
            // popover cell instead of their own always-visible columns --
            // Splade's hidden:true column-manager toggle wasn't actually
            // surfacing a usable UI to unhide them, so this renders a real,
            // guaranteed-visible "..." affordance per row instead.
            ->column(key: 'more_info', label: '', sortable: false)
            ->column('actions')
            ->selectFilter(
                key: 'status',
                options: array_combine($status, $status),
                label: 'Status',
                noFilterOptionLabel: 'All Status'
            )
            ->selectFilter(
                key: 'source',
                options: array_combine($sources, $sources),
                label: 'Source',
                noFilterOptionLabel: 'All Source'
            )
            ->selectFilter(
                key: 'campaign',
                options: array_combine($campaigns, $campaigns),
                label: 'Campaign',
                noFilterOptionLabel: 'All Campaigns'
            );

        $table->paginate(10);
    }
}

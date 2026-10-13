<?php

namespace App\Tables;

use App\Models\Campaign;
use App\Models\Company;
use Illuminate\Http\Request;
use ProtoneMedia\Splade\AbstractTable;
use ProtoneMedia\Splade\SpladeTable;

class CampaignTable extends AbstractTable
{
    /**
     * Create a new class instance.
     */
    public function __construct()
    {
        //
    }

    public function authorize(Request $request)
    {
        return true;
    }

    /**
     * The resource or query builder.
     *
     * @return mixed
     */
    public function for()
    {
        return Campaign::with('company');
    }

    public function configure(SpladeTable $table)
    {
        $table
            ->withGlobalSearch(columns: ['name', 'source', 'company.name'])
            ->column('owner', 'Owner')
            ->column('name', 'Campaign Name', sortable: true)
            ->column('source', 'Source Type', sortable: true)
            ->column('company.name', 'Platform/Tenant', sortable: true)
            ->column('last_lead_fetched', 'Last Lead Fetched')
            ->column('updated_at', 'Last Updated')
            ->column('welcome_message_enabled', 'Welcome Message Enabled', sortable: true)
            ->column('is_active', 'Active', sortable: true)
            ->column('use_new_survey', 'Use New Survey', sortable: true)
            ->column('send_to_third_party', '3rd Party API', sortable: true)
            ->column(
                key: 'webhook_url',
                label: 'Webhook URL',
                as: fn ($webhook_url) => $webhook_url
            )
            ->column('actions')
            ->paginate(10);
    }
}

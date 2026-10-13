<?php

namespace App\Tables;


use App\Models\User;
use Illuminate\Http\Request;
use ProtoneMedia\Splade\AbstractTable;
use ProtoneMedia\Splade\SpladeTable;

class CampaignLeadTable extends AbstractTable
{
    /**
     * Create a new instance.
     *
     * @return void
     */
    public function __construct(public string $campaignId)
    {
        //
    }

    /**
     * Determine if the user is authorized to perform bulk actions and exports.
     *
     * @return bool
     */
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
        return User::with('manager')
            ->where('campaign_id', $this->campaignId)
            ->where('is_new_survey', false)
            ->whereHasRole('lead');
    }

    /**
     * Configure the given SpladeTable.
     *
     * @param \ProtoneMedia\Splade\SpladeTable $table
     * @return void
     */


    public function configure(SpladeTable $table)
    {
        $table
            ->withGlobalSearch(columns: ['first_name', 'last_name', 'phone', 'email'])
            ->defaultSort('created_at','desc')
            ->column('created_at', 'Timestamp', sortable: true)
            ->column('first_name', sortable: true)
            ->column('last_name', sortable: true)
            ->column('email', sortable: true)
            ->column('phone', sortable: true)
            ->column('country_of_residence', 'Country of Residence')
            ->column('speak_english', 'Speak English')
            ->column('expected_earning', 'Expected Earning')
            ->column('income_type', 'Income Type')
            ->column('income', 'Annual Income')
            ->column('age_bracket', 'Age Bracket')
            ->column('occupation', 'Occupation')
            ->column('occupation_level', 'Occupation Level')
            ->column('investment_experience', 'Investment Experience Level')
            ->column('investment_goal', 'Primary Investment Goal')
            ->column('investment_amount', 'Investment Amount')
            ->column('got_to_landing_page', 'Got to landing Page')
            ->column('clicked_checkout_button', 'Clicked Checkout Button')
            ->column('ip_address', 'IP Address')
            ->column('device_type', 'Device', canBeHidden: false)
            ->column('browser_type', 'Browser', canBeHidden: false)
            ->paginate(10);
    }

}

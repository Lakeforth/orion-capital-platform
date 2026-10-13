<?php

namespace App\Tables;


use App\Models\User;
use Illuminate\Http\Request;
use ProtoneMedia\Splade\AbstractTable;
use ProtoneMedia\Splade\SpladeTable;

class CampaignSurveyTable extends AbstractTable
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
            ->where('is_new_survey', true)
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
            ->column('first_name', sortable: true)
            ->column('last_name', sortable: true)
            ->column('email', sortable: true)
            ->column('phone', sortable: true)
            ->column('amount_lost', 'Amount Lost')
            ->column('scam_type', 'Scam Type')
            ->column('scam_first_payment', 'Date of First Payment')
            ->column('scam_payment_method', 'Payment Method')
            ->column('created_at', 'Timestamp', sortable: true)
            ->paginate(10);
    }

}

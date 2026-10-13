<?php

namespace App\Http\Controllers\Admin;

use App\Actions\ApiTokenAction;
use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Models\Token;
use App\Models\WelcomeMessage;
use App\Tables\CampaignLeadTable;
use App\Tables\CampaignSurveyTable;
use App\Tables\CampaignTable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use ProtoneMedia\Splade\Facades\Toast;

class CampaignController extends Controller
{
    public function index()
    {
        return view('admin.campaigns.index', [
            'data' => CampaignTable::class,
        ]);
    }

    public function toggle($id, $field): RedirectResponse
    {
        $item = Campaign::findOrFail($id);

        if (!in_array($field, ['is_active', 'welcome_message_enabled'])) {
            Toast::danger('Invalid request');
            return redirect()->back();
        }

        $item->{$field} = !$item->{$field};
        $item->save();

        Toast::title('Campaign updated successfully');
        return redirect()->back();
    }

    public function create()
    {
        $welcomeMessages = WelcomeMessage::all();
        return view('admin.campaigns.create', compact('welcomeMessages'));
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request);

        $data['created_by'] = auth()->id();
        $data['end_zone'] = $data['end_zone'] ?: 'platform';

        if ($data['tags']) {
            if (is_string($data['tags'])) {
                $data['tags'] = explode(',', $data['tags']);
            }
        }

        $campaign = Campaign::create($data);

        if ($campaign) {
            if (($data['origin'] ?? null) === 'Google Sheets') { // Create webhook URL for Google Sheets origin
                $apiKey = (new ApiTokenAction())->createToken(auth()->id(), 10);

                $baseUrl = rtrim(company_settings('front_end_url'), '/');
                $webhookUrl = $baseUrl.'/api/v1/leads/store?apikey='.$apiKey->token.'&cpn='.$campaign->id;

                $newlyCreatedCampaign = Campaign::find($campaign->id);
                $newlyCreatedCampaign->webhook_url = $webhookUrl;
                $newlyCreatedCampaign->save();
            }

            Toast::title('Campaign has been created successfully');
        }
        else {
            Toast::danger('Failed to create campaign');
        }

        return redirect()->back();
    }

    public function show(string $id)
    {
        $campaign = Campaign::with(['company', 'welcomeMessage'])->findOrFail($id);

        if ($campaign->use_new_survey) {
            return view('admin.campaigns.new-survey', [
                'campaign' => $campaign,
                'data' => new CampaignSurveyTable($campaign->id),
            ]);
        }
        else {
            return view('admin.campaigns.show', [
                'campaign' => $campaign,
                'data' => new CampaignLeadTable($campaign->id),
            ]);
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $campaign = Campaign::findOrFail($id);
        $welcomeMessages = WelcomeMessage::all();
        return view('admin.campaigns.edit', compact('campaign', 'welcomeMessages'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $data = $this->validateData($request);
        $data['end_zone'] = $data['end_zone'] ?: 'platform';

        $campaign = Campaign::findOrFail($id);

        if ($data['tags']) {
            if (is_string($data['tags'])) {
                $data['tags'] = explode(',', $data['tags']);
            }
        }

        $data['use_new_survey'] = isset($data['use_new_survey']) && $data['use_new_survey'];
        $data['send_to_third_party'] = isset($data['send_to_third_party']) && $data['send_to_third_party'];
        $data['welcome_message_enabled'] = isset($data['welcome_message_enabled']) && $data['welcome_message_enabled'];
        $updated = $campaign->update($data);

        if ($updated) {
            Toast::title('Campaign was updated successfully');
        }
        else {
            Toast::danger('Failed to update campaign. Please try again');
        }

        return redirect()->back();
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $campaign = Campaign::findOrFail($id);

        if ($campaign->webhook_url) { // Get the token from the webhook URL and delete it to render the URL useless
            $apiKeys = explode('?apikey=', $campaign->webhook_url);
            $apiToken = $apiKeys[1];
            $tokenString = explode('&', $apiToken);
            $actualToken = $tokenString[0];

            if ($actualToken) {
                Token::where('user_id', $campaign->created_by)->where('token', $actualToken)->delete();
            }
        }

        $deleted = $campaign->delete();

        if ($deleted) {
            Toast::title('Campaign was deleted successfully');
        }
        else {
            Toast::danger('Failed to delete campaign. Please try again');
        }

        return redirect()->back();
    }

    private function validateData(Request $request): array
    {
        $rules = [
            'name'                    => ['required', 'string'],
            'source'                  => ['required', 'string'],
            'origin'                  => ['nullable', 'string'],
            'language'                => ['required', 'string'],
            'tags'                    => ['nullable'],
            'metadata'                => ['nullable'],
            'notes'                   => ['nullable'],
            'welcome_message_enabled' => ['nullable'],
            'welcome_message_id'      => ['nullable', 'exists:welcome_messages,id'],
            'send_to_third_party'     => ['nullable'],
            'use_new_survey'          => ['nullable'],
            'product_id'              => ['nullable', 'string'],
            'end_zone'                => ['nullable', 'string'],
            'whatsapp_number'         => ['nullable', 'numeric'],
            'whatsapp_message'        => ['nullable', 'string'],
            'owner'                   => ['nullable', 'string'],
            'cold_lead_chat_id'       => ['nullable', 'string'],
            'warm_lead_chat_id'       => ['nullable', 'string'],
            'hot_lead_chat_id'        => ['nullable', 'string'],
            'campaign_logo'           => ['nullable'],
            'campaign_favicon'           => ['nullable'],
            'tracking_script'         => ['nullable', 'string'],
        ];

        return $request->validate($rules);
    }
}

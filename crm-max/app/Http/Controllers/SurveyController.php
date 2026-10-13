<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Models\User;
use App\Services\EmailService;
use App\Services\LeadMax\LeadCapture;
use App\Services\TelegramBotService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Jobs\SendAffilioLead;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class SurveyController extends Controller
{
    public function show(Campaign $campaign)
    {
        abort_if(! $campaign->is_active, 404);

        return response()->file(public_path('spa.html'));
    }


    public function capture(Campaign $campaign, Request $request, TelegramBotService $telegramBotService)
    {
        abort_if(! $campaign->is_active, 404);

        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name'  => 'required|string|max:255',
            'email'      => 'required|email|max:255',
            'phone'      => 'nullable|string|max:50',
            'country'    => 'nullable|string|max:100',
            'ip_address'  => 'nullable|string|max:50',
            'device_type'  => 'nullable|string|max:20',
            'browser_type' => 'nullable|string|max:30',
        ]);

        $ip = $request->ip();

        $existingUser = User::where('email', $validated['email'])->first();

        if (! $existingUser) {
            $plainPassword = Str::random(6).rand(10, 99);

            $user = User::create([
                'id'                  => (string) Str::uuid(),
                'first_name'          => $validated['first_name'],
                'last_name'           => $validated['last_name'],
                'email'               => $validated['email'],
                'phone'               => $validated['phone'] ?? '',
                'country'             => $validated['country'] ?? '',
                'password'            => Hash::make($plainPassword),
                'company_id'          => $campaign->company_id,
                'campaign_id'         => $campaign->id,
                'source'              => 'web-form',
                'ip_address'          => $validated['ip_address'] ?? $ip,
                'device_type'         => $validated['device_type'] ?? null,
                'browser_type'        => $validated['browser_type'] ?? null,
                'approved'            => 1,
                'can_email'           => 1,
                'can_trade'           => '1',
                'currency_id'         => 1,
                'verification_status' => 'pending',
                'autotrader_status'   => 'inactive',
                'balance'             => 0,
                'total_deposit'       => 0,
                'savings_wallet'      => 0,
                'reserved'            => 0,
                'ftd_amount'          => 0,
                'copy_trader'         => 0,
                'funds_privilege'     => 0,
                'require_otp'         => 0,
                'is_online'           => 0,
                'banned'              => 0,
                'created_at'          => now(),
                'updated_at'          => now(),
            ]);

            $user->addRole('lead');
            $user->addRole('customer');
            app(LeadCapture::class)->record($user, 'website', LeadCapture::fromRequest($request));
        } else {
            $user = $existingUser;
        }

        $campaign->last_lead_fetched = now();
        $campaign->save();

        $this->fireWebhook($campaign, $validated, $ip);
        $this->fireAffilio($campaign, $validated, $ip);

        if ($campaign->cold_lead_chat_id) { // Send the lead to Telegram
            $telegramMessage = "New Lead \n\n";
            $telegramMessage .= "Name: ".$user->first_name." ".$user->last_name." \n";
            $telegramMessage .= "Email: ".$user->email." \n";
            $telegramMessage .= "Phone: ".$user->phone." \n";
            $telegramMessage .= "Created Time: ".$user->created_at?->format('F j Y H:ia') ?? 'N/A';

            try {
                $telegramBotService->sendMessage($campaign->cold_lead_chat_id, $telegramMessage);
            }  catch (\Exception $e) {
                logger('Something went wrong with Telegram bot message for Cold Leads', [
                    'message' => $e->getMessage(),
                    'line' => $e->getLine(),
                ]);
            }
        }

        if ($campaign->end_zone === "thank_you") {
            $frontendUrl = rtrim(company_settings('front_end_url', config('app.url')), '/');

            if ($campaign->whatsapp_number && $campaign->whatsapp_message) {
                $whatsappMessage = urlencode($campaign->whatsapp_message);
                $whatsappPhone = urlencode($campaign->whatsapp_number);
                $redirectUrl = "{$frontendUrl}/thank-you?campaign={$campaign->name}&whatsapp_number={$whatsappPhone}&whatsapp_message={$whatsappMessage}";
            }
            else {
                $redirectUrl = "{$frontendUrl}/thank-you?campaign={$campaign->name}";
            }

            return response()->json([
                'redirect_url' => $redirectUrl,
            ]);
        }

        $token       = $user->createToken('survey-magic-link')->plainTextToken;
        $frontendUrl = rtrim(company_settings('front_end_url', config('app.url')), '/');

        // Auto-login normally happens instantly client-side via redirect_url below,
        // but a new lead should also receive their login details by email (their
        // generated password isn't shown anywhere else, and this is the only
        // record of it if they close the tab before the redirect completes).
        if (! $existingUser) {
            try {
                $logoUrl = $campaign->campaign_logo ? getFullImage($campaign->campaign_logo) : null;
                (new EmailService)->sendCredentialsEmail($user, $plainPassword, 'en', $token, $logoUrl);
            } catch (\Throwable $e) {
                logger()->error('survey.capture.credentials_email_failed', [
                    'user_id' => $user->id,
                    'error'   => $e->getMessage(),
                ]);
            }
        }

        return response()->json([
            'redirect_url' => "{$frontendUrl}/auto-login?token={$token}",
        ]);
    }

    public function survey(Campaign $campaign, Request $request, TelegramBotService $telegramBotService)
    {
        abort_if(! $campaign->is_active, 404);

        $validated = $request->validate([
            'email'   => 'required|email|max:255',
            'answers' => 'required|array',
            'answers.age_bracket' => 'nullable|string',
            'answers.language' => 'nullable|string',
            'answers.country_of_residence' => 'nullable|string',
            'answers.occupation' => 'nullable|string',
            'answers.occupation_level' => 'nullable|string',
            'answers.earn_intent' => 'nullable|string',
            'answers.experience' => 'nullable|string',
            'answers.goal' => 'nullable|string',
            'answers.income' => 'nullable|string',
            'answers.income_source' => 'nullable|string',
            'answers.invest_intent' => 'nullable|string',
        ]);

        $existingUser = User::where('email', $validated['email'])->first();

        if (! $existingUser) {
            return $this->errorResponse('User not found', 422);
        }

        // Each of the values is optional so that we can know where the person stops. i.e the frontend has to call this endpoint for each submission.
        $existingUser->update([
            'country_of_residence' => $validated['answers']['country_of_residence'] ?? $existingUser->country_of_residence,
            'age_bracket' => $validated['answers']['age_bracket'] ?? $existingUser->age_bracket,
            'occupation' => $validated['answers']['occupation'] ?? $existingUser->occupation,
            'occupation_level' => $validated['answers']['occupation_level'] ?? $existingUser->occupation_level,
            'speak_english' => $validated['answers']['language'] ?? $existingUser->speak_english,
            'expected_earning' => $validated['answers']['earn_intent'] ?? $existingUser->expected_earning,
            'investment_experience' => $validated['answers']['experience'] ?? $existingUser->investment_experience,
            'income' => $validated['answers']['income'] ?? $existingUser->income,
            'income_type' => $validated['answers']['income_source'] ?? $existingUser->income_type,
            'investment_goal' => $validated['answers']['goal'] ?? $existingUser->investment_goal,
            'investment_amount' => $validated['answers']['invest_intent'] ?? $existingUser->investment_amount,
        ]);

        if ($campaign->warm_lead_chat_id) { // Send the lead to Telegram
            $telegramMessage = "New Lead \n\n";
            $telegramMessage .= "Name: ".$existingUser->first_name." ".$existingUser->last_name." \n";
            $telegramMessage .= "Email: ".$existingUser->email." \n";
            $telegramMessage .= "Phone: ".$existingUser->phone." \n\n";
            $telegramMessage .= "Current Country of Residence: ".$existingUser->country_of_residence." \n";
            $telegramMessage .= "Can Speak English: ".$existingUser->speak_english." \n";
            $telegramMessage .= "Expected Earning: ".$existingUser->expected_earning." \n";
            $telegramMessage .= "Age Bracket: ".$existingUser->age_bracket." \n";
            $telegramMessage .= "Occupation: ".$existingUser->occupation." \n";
            $telegramMessage .= "Occupation Level: ".$existingUser->occupation_level." \n\n";
            $telegramMessage .= "Income Bracket: ".$existingUser->income_type." \n";
            $telegramMessage .= "Annual Earnings: ".$existingUser->income." \n";
            $telegramMessage .= "Investment Goal: ".$existingUser->investment_goal." \n";
            $telegramMessage .= "Investment Amount: ".$existingUser->investment_amount." \n";
            $telegramMessage .= "Experience: ".$existingUser->investment_experience." \n\n";
            $telegramMessage .= "Created Time: ".$existingUser->created_at?->format('F j Y H:ia') ?? 'N/A';

            try {
                $telegramBotService->sendMessage($campaign->warm_lead_chat_id, $telegramMessage);
            } catch (\Exception $e) {
                logger('Something went wrong with Telegram bot message for Warm Leads', [
                    'message' => $e->getMessage(),
                    'line' => $e->getLine(),
                ]);
            }
        }

        return $this->successResponse('Survey data was saved successfully');
    }

    public function surveyStatus(Campaign $campaign, Request $request, TelegramBotService $telegramBotService)
    {
        abort_if(! $campaign->is_active, 404);

        $validated = $request->validate([
            'email'   => 'required|email|max:255',
            'status'  => 'required|string|in:landing_page,checkout',
        ]);

        $existingUser = User::where('email', $validated['email'])->first();

        if (! $existingUser) {
            return $this->errorResponse('User not found', 422);
        }

        $almostPaid = $readyToPay = false;

        if ($validated['status'] === 'landing_page') {
            $existingUser->got_to_landing_page = true;
            $almostPaid = true;
        }
        else {
            $existingUser->clicked_checkout_button = true;
            $readyToPay = true;
        }

        $existingUser->save();

        if ($campaign->hot_lead_chat_id) { // Send the lead to Telegram
            $telegramMessage = "New Lead \n\n";
            $telegramMessage .= "Name: ".$existingUser->first_name." ".$existingUser->last_name." \n";
            $telegramMessage .= "Email: ".$existingUser->email." \n";
            $telegramMessage .= "Phone: ".$existingUser->phone." \n\n";

            if ($almostPaid) {
                $telegramMessage .= "Almost Paid = 🪙"."\n\n\n";
            }

            if ($readyToPay) {
                $telegramMessage .= "Ready to Pay = 💵💵"." \n\n\n";
            }

            $telegramMessage .= "Current Country of Residence = ".$existingUser->country_of_residence." \n";
            $telegramMessage .= "Can Speak English = ".$existingUser->speak_english." \n";
            $telegramMessage .= "Age Bracket = ".$existingUser->age_bracket." \n";
            $telegramMessage .= "Occupation = ".$existingUser->occupation." \n\n";
            $telegramMessage .= "Occupation Level = ".$existingUser->occupation_level." \n\n";
            $telegramMessage .= "Income Bracket = ".$existingUser->income_type." \n";
            $telegramMessage .= "Annual Earnings = ".$existingUser->income." \n";
            $telegramMessage .= "Investment Goal = ".$existingUser->investment_goal." \n";
            $telegramMessage .= "Investment Amount = ".$existingUser->investment_amount." \n";
            $telegramMessage .= "Expected Earning = ".$existingUser->expected_earning." \n";
            $telegramMessage .= "Experience = ".$existingUser->investment_experience." \n\n";
            $telegramMessage .= "Created Time = ".$existingUser->created_at?->format('F j Y H:ia') ?? 'N/A';

            try {
                $telegramBotService->sendMessage($campaign->hot_lead_chat_id, $telegramMessage);
            } catch (\Exception $e) {
                logger('Something went wrong with Telegram bot message for Hot Leads', [
                    'message' => $e->getMessage(),
                    'line' => $e->getLine(),
                ]);
            }
        }

        return $this->successResponse('Data was saved successfully');
    }

    public function thankYou(Campaign $campaign)
    {
        abort_if(! $campaign->is_active, 404);

        return redirect('/thank-you');
    }

    public function submit(Campaign $campaign, Request $request)
    {
        abort_if(! $campaign->is_active, 404);

        $validated = $request->validate([
            'first_name'     => 'required|string|max:255',
            'email'          => 'required|email|max:255',
            'phone'          => 'nullable|string|max:50',
            'country'        => 'nullable|string|max:100',
            'ip_address'     => 'nullable|string|max:50',
            'survey_answers' => 'nullable|array',
        ]);

        $ip = $request->ip();

        $existingUser = User::where('email', $validated['email'])->first();

        if (! $existingUser) {
            $plainPassword = Str::random(6).rand(10, 99);

            $user = User::create([
                'id'                  => (string) Str::uuid(),
                'first_name'          => $validated['first_name'],
                'last_name'           => '',
                'email'               => $validated['email'],
                'phone'               => $validated['phone'] ?? '',
                'country'             => $validated['country'] ?? '',
                'password'            => Hash::make($plainPassword),
                'company_id'          => $campaign->company_id,
                'campaign_id'         => $campaign->id,
                'source'              => 'web-form',
                'ip_address'          => $validated['ip_address'] ?? $ip,
                'approved'            => 1,
                'can_email'           => 1,
                'can_trade'           => '1',
                'currency_id'         => 1,
                'verification_status' => 'pending',
                'autotrader_status'   => 'inactive',
                'balance'             => 0,
                'total_deposit'       => 0,
                'savings_wallet'      => 0,
                'reserved'            => 0,
                'ftd_amount'          => 0,
                'copy_trader'         => 0,
                'funds_privilege'     => 0,
                'require_otp'         => 0,
                'is_online'           => 0,
                'banned'              => 0,
                'created_at'          => now(),
                'updated_at'          => now(),
            ]);

            $user->addRole('lead');
            $user->addRole('customer');
            app(LeadCapture::class)->record($user, 'campaign', LeadCapture::fromRequest($request));
        } else {
            $user = $existingUser;
        }

        $campaign->last_lead_fetched = now();
        $campaign->save();

        $this->fireWebhook($campaign, $validated, $ip);
        $this->fireAffilio($campaign, $validated, $ip);


        $token       = $user->createToken('survey-magic-link')->plainTextToken;
        $frontendUrl = rtrim(company_settings('front_end_url', config('app.url')), '/');

        return redirect()->away("{$frontendUrl}/auto-login?token={$token}");
    }

    private function getSurveyQuestions(Campaign $campaign): array
    {
        $metadata = $campaign->metadata ?? [];

        if (! empty($metadata['survey_questions'])) {
            return $metadata['survey_questions'];
        }

        return [
            [
                'id'      => 'language',
                'text'    => '🇬🇧 Do you speak English?',
                'type'    => 'radio',
                'options' => ['Yes', 'No'],
            ],
            [
                'id'      => 'income',
                'text'    => 'What is your Annual / Yearly Income?',
                'type'    => 'radio',
                'options' => ['Less than $10,000', '$10,000 - $50,000', '$50,000 - $100,000', '$100,000 - Above'],
            ],
            [
                'id'      => 'experience',
                'text'    => 'What is your investment experience level?',
                'type'    => 'radio',
                'options' => ['Beginner', 'Intermediate', 'Experienced'],
            ],
            [
                'id'      => 'goal',
                'text'    => 'What is your primary investment goal?',
                'type'    => 'radio',
                'options' => ['Short-term gains', 'Long-term wealth', 'Passive income'],
            ],
        ];
    }

    private function fireWebhook(Campaign $campaign, array $data, string $ip): void
    {
        if (empty($campaign->webhook_url)) {
            return;
        }

        try {
            Http::timeout(5)->post($campaign->webhook_url, [
                'first_name'     => $data['first_name'],
                'email'          => $data['email'],
                'phone'          => $data['phone'] ?? '',
                'country'        => $data['country'] ?? '',
                'ip_address'     => $data['ip_address'] ?? $ip,
                'device_type'    => $data['device_type'] ?? '',
                'browser_type'   => $data['browser_type'] ?? '',
                'campaign_id'    => $campaign->id,
                'campaign_name'  => $campaign->name,
                'survey_answers' => $data['survey_answers'] ?? [],
            ]);
        } catch (\Throwable $e) {
            logger()->error('survey.webhook_failed', [
                'campaign_id' => $campaign->id,
                'error'       => $e->getMessage(),
            ]);
        }
    }

    private function fireAffilio(Campaign $campaign, array $data, string $ip): void
    {
        if (! $campaign->send_to_third_party) {
            return;
        }

        $apiUrl = config('services.affilio.url');

        if (empty($apiUrl)) {
            return;
        }

        $metadata  = $campaign->metadata ?? [];
        $lid       = $metadata['affilio_lid'] ?? config('services.affilio.lid', 302);
        $mpc5      = $metadata['affilio_mpc5'] ?? $campaign->name;
        $countryCode = $this->isoCountryCode($data['country'] ?? '');
        $language  = $this->languageFromCountry($countryCode);
        $payload = [
            'lid'         => (int) $lid,
            'firstName'   => $data['first_name'],
            'lastName'    => $data['last_name'] ?? '',
            'email'       => $data['email'],
            'phone'       => $data['phone'] ?? '',
            'password'    => substr(str_shuffle('ABCDEFGHJKLMNPQRSTUVWXYZabcdefghjkmnpqrstuvwxyz23456789'), 0, 6) . rand(10, 99),
            'ip'          => $data['ip_address'] ?? $ip,
            'deviceType'  => $data['device_type'] ?? '',
            'browser'     => $data['browser_type'] ?? '',
            'country'     => $countryCode,
            'countryCode' => $countryCode,
            'language'    => $language,
            'mpc5'        => (string) $mpc5,
            'funnelName'  => $campaign->name,
            // Best-effort signal only -- Affilio's API has no documented concept
            // of this; their whole domain is Cloudflare-locked to a single
            // register-lead endpoint, so there's no separate event/status call
            // to make after the fact. Sent two ways: a named field in case their
            // schema happens to read it, and mpc4 (an unused sibling of the
            // confirmed-working mpc5 "source ID" slot) since numbered tracking
            // slots like this are far more likely to actually surface on their
            // dashboard than a field name we're only guessing at.
            'autoLogin'   => $campaign->end_zone !== 'thank_you' ? 'true' : 'false',
            'mpc4'        => $campaign->end_zone !== 'thank_you' ? 'auto_login' : 'thank_you',
        ];

        SendAffilioLead::dispatch($campaign->id, $payload, $apiUrl);
    }

    // Maps country name or ISO code to a 2-letter ISO code
    private function isoCountryCode(string $country): string
    {
        $map = [
            'United Kingdom' => 'GB', 'UK' => 'GB', 'Great Britain' => 'GB',
            'United States'  => 'US', 'USA' => 'US',
            'Germany'        => 'DE', 'France' => 'FR', 'Spain' => 'ES',
            'Italy'          => 'IT', 'Netherlands' => 'NL', 'Poland' => 'PL',
            'Sweden'         => 'SE', 'Norway' => 'NO', 'Denmark' => 'DK',
            'Finland'        => 'FI', 'Belgium' => 'BE', 'Switzerland' => 'CH',
            'Austria'        => 'AT', 'Portugal' => 'PT', 'Czech Republic' => 'CZ',
            'Australia'      => 'AU', 'Canada' => 'CA', 'New Zealand' => 'NZ',
            'South Africa'   => 'ZA', 'Nigeria' => 'NG', 'Kenya' => 'KE',
            'Ghana' => 'GH', 'India' => 'IN', 'Singapore' => 'SG',
            'UAE' => 'AE', 'United Arab Emirates' => 'AE',
            'Puerto Rico' => 'PR',
        ];

        // Already a 2-letter code
        if (strlen($country) === 2) {
            return strtoupper($country);
        }

        return $map[$country] ?? strtoupper(substr($country, 0, 2));
    }

    private function languageFromCountry(string $isoCode): string
    {
        $map = [
            'GB' => 'en', 'US' => 'en', 'AU' => 'en', 'CA' => 'en', 'NZ' => 'en',
            'NG' => 'en', 'KE' => 'en', 'GH' => 'en', 'ZA' => 'en', 'SG' => 'en',
            'DE' => 'de', 'AT' => 'de', 'CH' => 'de',
            'FR' => 'fr', 'BE' => 'fr',
            'ES' => 'es',
            'IT' => 'it',
            'NL' => 'nl',
            'PL' => 'pl',
            'SE' => 'sv',
            'NO' => 'no',
            'DK' => 'da',
            'FI' => 'fi',
            'PT' => 'pt',
            'CZ' => 'cs',
            'IN' => 'hi',
            'AE' => 'ar',
            'PR' => 'es',
        ];

        return $map[strtoupper($isoCode)] ?? 'en';
    }
}


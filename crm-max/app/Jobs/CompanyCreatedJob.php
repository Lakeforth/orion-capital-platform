<?php

namespace App\Jobs;

use App\Models\AccountPlan;
use App\Models\Asset;
use App\Models\Company;
use App\Models\CompanyAsset;
use App\Models\Data;
use App\Services\EmailService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class CompanyCreatedJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(public int $id) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $company_id = $this->id;
        AccountPlan::whereNull('company_id')
            ->get()
            ->each(fn ($plan) => tap($plan->replicate(), function ($copy) use ($company_id) {
                $copy->company_id = $company_id;
                $copy->save();
            }));

        Data::query()->whereNull('company_id')
            ->get()
            ->each(fn ($plan) => tap($plan->replicate(), function ($copy) use ($company_id) {
                $copy->company_id = $company_id;
                $copy->save();
            }));

        $allIds = Asset::pluck('id')->toArray();

        CompanyAsset::create([
            'company_id' => $company_id,
            'assets' => $allIds,
        ]);

        $company = Company::with('owner')->find($company_id);

        $settings = $company->settings;

        if (is_string($settings)) {
            $settings = json_decode($settings, true) ?? [];
        } elseif (! is_array($settings)) {
            $settings = [];
        }

        $settings['site_name'] = $company->name;
        $settings['front_end_url'] = $company->url;
        $settings['chat_url'] = $company->url;
        $settings['crm_url'] = $company->url.'/panel';

        $settings['login_page_design'] = 'ic';
        $settings['register_page_design'] = 'ic';
        $settings['dashboard_page_design'] = 'basic';
        $settings['admin_dashboard'] = 'analytics';

        $settings['live_api'] = 'yes';
        $settings['cloud_hosting'] = 'yes';
        $settings['enable_leads'] = 'no';
        $settings['enable_managers'] = 'no';
        $settings['leadmax_enabled'] = 'no';
        $settings['enable_plan'] = 'no';
        $settings['enable_plan_price'] = 'no';
        $settings['enable_admins'] = 'no';

        $company->settings = $settings;

        $company->save();

        if ($company->owner) {
            try {
                (new EmailService)->sendPlatformUrls($company);
            } catch (\Throwable $th) {
                Log::error('platform_email_send_failed', [
                    'platform_id' => $company->id,
                    'context' => 'create_platform',
                    'message' => $th->getMessage(),
                ]);
            }
        }
    }
}

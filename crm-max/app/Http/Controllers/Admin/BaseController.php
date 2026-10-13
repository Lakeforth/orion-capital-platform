<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use Illuminate\Http\Request;

class BaseController extends Controller
{
    protected Company $company;
    protected array $settings;

    public function __construct()
    {
        $this->company = app('company');
        $this->settings = $this->getCompanySettings($this->company);
    }

    protected function getCompanySettings(Company $company): array
    {
        $settings = $company->settings;

        if (!is_array($settings)) {
            return json_decode($settings, true) ?? [];
        }

        return $settings;
    }

}

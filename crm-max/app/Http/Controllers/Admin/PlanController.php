<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AccountPlan;
use App\Tables\PlanTable;
use Illuminate\Http\Request;
use ProtoneMedia\Splade\Facades\Toast;

class PlanController extends Controller
{
    private const PLAN_AMOUNT_MAX = '9999999999.99';

    public function index()
    {
        return view('admin.plans.index', [
            'data' => PlanTable::class,
        ]);
    }

    public function create()
    {
        return view('admin.plans.create');
    }

    public function script()
    {
        $company = app()->bound('company') ? app('company') : null;
        $frontendUrl = rtrim((string) company_settings('front_end_url', $company->url ?? config('app.url')), '/');
        $embedPath = '/'.ltrim((string) config('platform.plans_embed_path', '/embed/plans'), '/');
        $embedUrl = $frontendUrl.$embedPath;

        $script = <<<HTML
<iframe
  id="plans-widget"
  src="{$embedUrl}"
  width="100%"
  height="700"
  style="border:none; display:block;"
></iframe>
<script>
  window.addEventListener('message', function(e) {
    if (e.data && e.data.type === 'plans-embed-height') {
      document.getElementById('plans-widget').style.height = e.data.height + 'px';
    }
  });
</script>
HTML;

        return view('admin.plans.script', compact('script', 'embedUrl'));
    }

    public function store(Request $request)
    {
        $validated = $this->getData($request);

        AccountPlan::create($validated);

        Toast::success('Plan created successfully.');

        return redirect()->back();
    }

    public function edit($id)
    {
        $plan = AccountPlan::findOrFail($id);

        return view('admin.plans.edit', compact('plan'));
    }

    public function update(Request $request, $id)
    {
        $plan = AccountPlan::findOrFail($id);
        $validated = $this->getData($request);

        $validated['icon'] = $request->icon ?? $plan->icon;
        $validated['image'] = $request->image ?? $plan->image;

        $plan->update($validated);

        Toast::success('Plan updated successfully.');

        return redirect()->back();
    }

    public function destroy($id)
    {
        $asset = AccountPlan::findOrFail($id);
        $asset->delete();

        Toast::success('Plan deleted successfully.');

        return redirect()->back();
    }

    protected function getData(Request $request): array
    {
        $planPriceEnabled = module_enabled('enable_plan_price');

        $data = $request->validate([
            'title' => 'required|string',
            'color' => 'nullable|string',
            'icon' => 'nullable|string',
            'image' => 'nullable|string',
            'leverage' => 'nullable',
            'min_deposit' => 'required|numeric|min:0|max:'.self::PLAN_AMOUNT_MAX,
            'price' => ($planPriceEnabled ? 'required' : 'nullable').'|numeric|min:0|max:'.self::PLAN_AMOUNT_MAX,
            'description' => 'nullable|string',
            'features' => 'nullable|array',
            'expert_advisors' => 'nullable|array',
            'features.*' => 'nullable|string|max:255',
        ]);

        // Filter out any empty values
        $features = array_filter($data['features'] ?? [], fn ($value) => ! is_null($value) && $value !== '');

        $data['features'] = $features;
        $data['price'] = $planPriceEnabled ? (float) ($data['price'] ?? 0) : 0;

        return $data;
    }

    public function getPlans()
    {
        return AccountPlan::query()->get();
    }
}

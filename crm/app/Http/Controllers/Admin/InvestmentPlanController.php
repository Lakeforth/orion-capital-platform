<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Models\InvestmentPlan;
use App\Services\AssetService;
use App\Tables\ECNPlanTable;
use App\Tables\InvestmentPlanTable;
use App\Tables\PortfolioPlanTable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use ProtoneMedia\Splade\Facades\Toast;

class InvestmentPlanController extends Controller
{
    public function index()
    {
        return view('admin.investment-plans.index', [
            'data' => InvestmentPlanTable::class
        ]);
    }


    public function toggle($id, $field): RedirectResponse
    {
        $item = InvestmentPlan::findOrFail($id);
        $item->{$field} = !$item->{$field};
        $item->save();
        Toast::title('Status Updated successfully');
        return redirect()->back();
    }


    public function portfolioPlans()
    {
        return view('admin.investment-plans.portfolio_plans', [
            'data' => PortfolioPlanTable::class
        ]);
    }

    public function ecnPlans()
    {
        return view('admin.investment-plans.ecn_plans', [
            'data' => ECNPlanTable::class
        ]);
    }



    public function create(Request $request)
    {
        $default = [
            'period_type' => 'month',
            'investment_type' => $request->get('investment_type', 'investment'),
        ];
        return view('admin.investment-plans.create', compact('default'));
    }

    public function store(Request $request)
    {
        $validated = $this->getData($request);

        $validated['type'] = in_array($validated['investment_type'],['ecn','portfolio']) ? $validated['investment_type'] : $validated['type'];

        InvestmentPlan::create($validated);

        Toast::success('Investment plan created successfully.');

        return redirect()->back();
    }

    public function edit($id)
    {
        $investmentPlan = InvestmentPlan::findOrFail($id);
        return view('admin.investment-plans.edit', compact('investmentPlan'));
    }

    public function update(Request $request, InvestmentPlan $investmentPlan)
    {
        $validated = $this->getData($request);

        if(isset($validated['image']) && !$validated['image']){
            $validated['image'] = $investmentPlan->image;
        }
        $investmentPlan->update($validated);

        Toast::success('Investment plan updated successfully.');

        return redirect()->back();
    }

    public function getData(Request $request): array
    {
        $data =  $request->validate([
            'title' => 'required|string|max:255',
            'category' => 'nullable|string',
            'type' => 'nullable|string',
            'currency' => 'nullable|string',
            'sector' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'min_investment' => 'nullable|numeric|min:0',
            'price' => 'nullable|numeric|min:0',
            'period' => 'nullable|numeric|min:0',
            'percent_profit' => 'nullable|numeric|min:0',
            'investment_type' => 'nullable',
            'max_investment' => 'nullable|numeric|min:0|gte:min_investment',
            'image' => 'nullable',
            'asset' => 'nullable',
        ]);

        if($data['investment_type'] == 'ecn') {
            $asset = Asset::query()->where('sym_key', $data['asset'])->first();

            $assetService = new AssetService();
            if($asset){
                $assetService->updateRate($asset);

                $data['category'] = $asset->name;

            }
        }

        return $data;
    }

    public function getPlans(){
        $items = InvestmentPlan::select('title','id')->get();
        return $this->successResponse('plans', $items);
    }
}

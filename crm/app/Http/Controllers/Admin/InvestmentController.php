<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Models\Investment;
use App\Tables\AllInvestmentTable;
use App\Tables\ECNInvTable;
use App\Tables\LiveAssetTable;
use App\Tables\PortfolioInvTable;
use Illuminate\Http\Request;
use ProtoneMedia\Splade\Facades\Toast;

class InvestmentController extends Controller
{
    public function index()
    {
        return view('admin.investments.index', [
            'data' => new AllInvestmentTable()
        ]);
    }

    public function live_markets()
    {
        return view('admin.investments.live_market', [
            'data' => new LiveAssetTable()
        ]);
    }

    public function live_market_edit($id)
    {
        $asset = Asset::findOrFail($id);
        return view('admin.investments.live_market_edit', compact('asset'));
    }

    public function show($id)
    {
        return $id;
    }

    public function pending()
    {
        return view('admin.investments.index', [
            'data' => new AllInvestmentTable(null, 'pending'),
            'title' => 'Pending Requests'
        ]);
    }

    public function portfolios()
    {
        return view('admin.investments.index', [
            'data' => new PortfolioInvTable(),
            'title' => 'Portfolio Investment Listing'
        ]);
    }

    public function ecns()
    {
        return view('admin.investments.index', [
            'data' => new ECNInvTable(),
            'title' => 'ECN Investment Listing'
        ]);
    }

    public function create()
    {
        $default = [];
        return view('admin.investments.create', compact('default'));
    }

    public function store(Request $request)
    {
        $validated = $this->getData($request);

        Investment::create($validated);

        Toast::success('Investment plan created successfully.');

        return redirect()->back();
    }

    public function edit($id)
    {
        $investment = Investment::findOrFail($id);
        return view('admin.investments.edit', compact('investment'));
    }

    public function update(Request $request, $id)
    {
        $investment = Investment::findOrFail($id);

        $validated = $this->getData($request);

        $investment->update($validated);

        Toast::success('Investment updated successfully.');

        return redirect()->back();
    }

    public function getData(Request $request): array
    {
        return $request->validate([
//            'user_id' => 'required|string|max:255',
            'manager_id' => 'nullable|string',
            'investment_plan_id' => 'nullable|string',
            'investment_account_id' => 'nullable|string|max:255',
            'amount' => 'nullable',
            'status' => 'nullable',
            'current_qty' => 'nullable',
            'start_date' => 'nullable',
            'end_date' => 'nullable',
            'issue' => 'nullable|string',
            'issue_type' => 'nullable|string',
            'currency' => 'nullable|string',
            'latest_valuation' => 'nullable',
            'total_profit' => 'nullable',
            'title' => 'nullable',
            'avg_price_per_unit' => 'nullable',
            'latest_price_per_unit' => 'nullable',
            'asset_class' => 'nullable',
        ]);
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\Investment;
use App\Models\InvestmentPlan;
use App\Tables\InvestmentTable;
use App\Tables\MyInvAccountTable;
use Carbon\Carbon;
use Illuminate\Http\Request;
use ProtoneMedia\Splade\Facades\Toast;

class InvestmentController extends Controller
{
    public function markets(){

        $plans = InvestmentPlan::query()->where('investment_type','investment')->latest()->get();

        return view('investment.markets', compact('plans'));
    }

    public function accounts(){
        return view('investment.accounts', [
            'data' => new MyInvAccountTable(auth()->id())
        ]);
    }
    public function investments(){

        return view('investment.investments',[
            'data' => new InvestmentTable()
        ]);
    }

    public function requestContract(Request $request){

        $request->validate([
            'confirmed' => 'required',
            'plan_id' => 'required'
        ]);


        $account_id = auth()->user()->investment_accounts->whenNotEmpty(fn($accounts) => $accounts->first()->id);


        $plan = InvestmentPlan::findOrFail($request['plan_id']);

        Investment::create([
            'user_id' => auth()->id(),
            'investment_plan_id' => $request['plan_id'],
            'investment_account_id' => $account_id,
            'amount' => $plan->min_investment,
            'currency' => $plan->currency,
            'issue' => $plan->category,
            'issue_type' => $plan->type,
            'asset_class' => $plan->sector,
            'start_date' => Carbon::now(),
            'status' => 'request',
        ]);

        Toast::success('Contract Request successfully submitted');

        return redirect()->route('investment.investments');

    }

    public function dashboard(){
        $plans = [];
        $assets = Asset::where('is_live_market', true)->where('featured', true)->inRandomOrder()->take(5)->get();
        return view('investment.dashboard', compact('plans','assets'));
    }
}

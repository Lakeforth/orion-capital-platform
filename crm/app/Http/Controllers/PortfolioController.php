<?php

namespace App\Http\Controllers;

use App\Jobs\InvestmentUpdaterJob;
use App\Models\Investment;
use App\Models\InvestmentPlan;
use App\Models\Transaction;
use App\Models\Wallet;
use App\Services\InvestmentService;
use App\Tables\TransactionTable;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use ProtoneMedia\Splade\Facades\Toast;
use ProtoneMedia\Splade\SpladeTable;

class PortfolioController extends Controller
{
    public function index()
    {
        InvestmentUpdaterJob::dispatch(auth()->id(),'portfolio');

        return view('portfolio.index');
    }

    public function investments()
    {
        $investments = InvestmentPlan::query()
            ->where('is_active', true)
            ->where('investment_type', 'portfolio')->get();

        $active_investments = Investment::query()
            ->where('investment_type', 'portfolio')->get();

        if(count($active_investments) > 0){
            InvestmentUpdaterJob::dispatch(auth()->id(),'portfolio');
        }

        return view('portfolio.investments', compact('investments','active_investments'));
    }

    public function investmentHistory()
    {
        $investments = InvestmentPlan::query()
            ->where('is_active', true)
            ->where('investment_type', 'portfolio')->get();

        $active_investments = Investment::query()
            ->where('investment_type', 'portfolio')->get();

        if(count($active_investments) > 0){
            InvestmentUpdaterJob::dispatch(auth()->id(),'portfolio');
            InvestmentUpdaterJob::dispatch(auth()->id(),'ecn');
        }

        return view('portfolio.investment_history', compact('investments','active_investments'));
    }

    public function deposit(){
        $default = [];
        $wallets = Wallet::query()->where('is_active', true)->get();
        return view('portfolio.deposit', compact('default', 'wallets'));
    }

    public function ecn()
    {
        return view('portfolio.ecn');
    }

    public function getECNData(Request $request)
    {
        $query = InvestmentPlan::query()->with('inv_asset')
            ->where('is_active', true)
            ->where('investment_type', 'ecn');

        // Handle search
        if ($request->has('search')) {
            $query->where('title', 'like', '%' . $request->search . '%');
        }

        // Handle category filter - this will work with category[] in the URL
        if ($request->has('category')) {
            $query->whereIn('category', $request->category);
        }

        // Get the collection of investment plans
        $investments = $query->get();

        // Transform the collection using map
        $transformedInvestments = $investments->map(function ($investment) {
            return [
                'id' => $investment->id,
                'title' => $investment->title,
                'category' => $investment->category,
                'description' => $investment->description,
                'price' => $investment->price,
                'min_investment' => $investment->min_investment,
                'max_investment' => $investment->max_investment,
                'sale_price' => $investment->inv_asset?->rate ?? 0,
                'created_at' => $investment->created_at,
                'updated_at' => $investment->updated_at,
            ];
        });

        // Fix the distinct usage error - use select() before distinct()
        $categories = InvestmentPlan::select('category')->where('investment_type', 'ecn')->distinct()->pluck('category');

        return response()->json([
            'investments' => $transformedInvestments,
            'categories' => $categories
        ]);
    }

    public function portfolios()
    {
        $portfolios = Investment::query()->where('investment_type', 'portfolio')->get();
        return view('portfolio.portfolios', compact('portfolios'));
    }

    public function purchase(Request $request){
        $validated = $request->validate([
            'investment_plan_id' => 'required|exists:investment_plans,id',
            'unit' => 'nullable',
        ]);

        $user = auth()->user();

        $unit = $validated['unit'] ?? 1;
        $investmentPlan = InvestmentPlan::findOrFail($validated['investment_plan_id']);

        $amount = $unit * $investmentPlan->price;

        if($user->balance < $amount){
            return $this->errorResponse('Insufficient balance');
        }

        $total_profit = $amount * $investmentPlan->percent_profit / 100;

        Investment::create([
            'title' => $investmentPlan->title,
            'investment_plan_id' => $investmentPlan->id,
            'percent_profit' => 0,
            'investment_type' => 'portfolio',
            'user_id' => auth()->id(),
            'amount' => $amount,
            'start_date' => Carbon::now(),
            'status' => 'active',
            'total_profit' => $total_profit,
            'units' => $unit,
            'end_date' => Carbon::now()->addMonths($investmentPlan->period),
        ]);

        $user->update([
            'balance' => $user->balance - $amount,
        ]);


        Transaction::create([
            'user_id' => auth()->id(),
            'type' => 'withdrawal',
            'method' => 'wallet',
            'status' => 'approved',
            'amount' => $amount,
            'reason' => "Purchase of $unit unit(s) of {$investmentPlan->title} for ". format_price($amount),
        ]);

        log_activity("Purchase of {$investmentPlan->title}",auth()->user()->email." purchased $unit units of {$investmentPlan->title} for ".$amount.' via wallet balance',auth()->id(),'withdrawal');


        Toast::success('Investment successful');

        return $this->successResponse('Investment successful');
    }

    public function purchaseECN(Request $request){
        $validated = $request->validate([
            'investment_plan_id' => 'required|exists:investment_plans,id',
            'unit' => 'nullable',
        ]);

        $user = auth()->user();

        $unit = $validated['unit'] ?? 1;
        $investmentPlan = InvestmentPlan::with('inv_asset')->findOrFail($validated['investment_plan_id']);

        $amount = $unit * $investmentPlan->price;

        if($user->balance < $amount){
            return $this->errorResponse('Insufficient balance, Please fund your account', 401);
        }

        if(!$investmentPlan->asset){
            return $this->errorResponse('This ECN Listing is no longer available, please contact support', 401);
        }


        Investment::create([
            'investment_plan_id' => $investmentPlan->id,
            'asset' => $investmentPlan->asset,
            'title' => "{$investmentPlan->inv_asset->name} ({$investmentPlan->title})",
            'total_profit' => ($unit * $investmentPlan->inv_asset?->rate) - $amount,
            'latest_valuation' => ($unit * $investmentPlan->inv_asset?->rate),
            'percent_profit' => 0,
            'investment_type' => 'ecn',
            'status' => 'active',
            'user_id' => auth()->id(),
            'amount' => $amount,
            'units' => $unit,
        ]);

        $user->update([
            'balance' => $user->balance - $amount,
        ]);

        Transaction::create([
            'user_id' => auth()->id(),
            'type' => 'debit',
            'method' => 'wallet',
            'status' => 'approved',
            'amount' => $amount,
            'reason' => "ECN purchase of ". format_price($amount),
        ]);

        log_activity('ECN purchase',auth()->user()->email.' purchased ECN of '.$amount.' via wallet balance',auth()->id(),'withdrawal');


        Toast::success('ECN purchase successful');

        return $this->successResponse('ECN purchase successful');
    }

    public function sellECN(Request $request){

        $investmentService = new InvestmentService();

        $validated = $request->validate([
            'investment_id' => 'required|exists:investments,id',
        ]);


        $investment = Investment::with('inv_asset')->findOrFail($validated['investment_id']);


        $investmentService->sellECN($investment);


        Toast::success('ECN sold successful');

        return $this->successResponse('ECN sold successful');
    }

    public function dashboard()
    {
        InvestmentUpdaterJob::dispatch(auth()->id(),'portfolio');

        return view( 'portfolio.dashboard');
    }

    public function withdrawal(){
        $default = ['method' => 'crypto'];
        return view('portfolio.withdrawal',  compact('default'));
    }
    public function transactions(){
        return view('portfolio.transactions');
    }

    public function getTransactions(Request $request){
        $user = Auth::user();

        // Start with a base query for user's transactions
        $query = Transaction::query()->where('user_id', $user->id);

        // Apply search if provided
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;

            $query->where(function($q) use ($search) {
                // Search in multiple columns
                $q->where('amount', 'like', "%{$search}%")
                    ->orWhere('reason', 'like', "%{$search}%")
                    ->orWhere('status', 'like', "%{$search}%")
                    ->orWhere('method', 'like', "%{$search}%")
                    ->orWhere('date', 'like', "%{$search}%")
                    ->orWhere('t_type', 'like', "%{$search}%");

                // Convert d/w to deposit/withdrawal for better search
                if (strtolower($search) === 'deposit') {
                    $q->orWhere('type', 'd');
                } elseif (strtolower($search) === 'withdrawal') {
                    $q->orWhere('type', 'w');
                }

                // Search in JSON details if possible
                $q->orWhereRaw("LOWER(details) LIKE ?", ["%".strtolower($search)."%"]);
            });
        }

        // Filter by type if provided
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        // Filter by status if provided
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Order by creation date, newest first
        $query->orderBy('created_at', 'desc');

        // Get paginated results (3 per page or use your preferred number)
        $perPage = $request->per_page ?? 10;
        $transactions = $query->paginate($perPage);

        // Format the details for each transaction for display
        $transactions->getCollection()->transform(function ($transaction) {
            // Format date for better readability
            $transaction->date = $transaction->created_at->format('d M Y');
            $transaction->time = $transaction->created_at->format('H:i:s');

            return $transaction;
        });

        return response()->json($transactions);
    }

    public function getInvestments(Request $request)
    {
        $user = Auth::user();

        $query = Investment::query()
            ->where('investment_type','portfolio')
            ->where('user_id', $user->id);

        // Apply search if provided
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;

            $query->where(function($q) use ($search) {
                // Search in multiple columns
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('amount', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Apply status filter
        if ($request->has('status')) {
            $status = $request->status;

            if ($status === 'in_progress') {
                // In progress investments are active and not yet ended
                $query->where(function($q) {
                        $q->whereNull('completed_on');
                    });
            } elseif ($status === 'completed') {
                // Completed investments have either 'completed' status or have passed their end date
                $query->where(function($q) {
                    $q->whereNotNull('completed_on');
                });
            }
        }else{
            $query->where(function($q) {
                $q->whereNull('completed_on');
            });
        }

        // Order by creation date, newest first
        $query->orderBy('created_at', 'desc');

        // Get paginated results
        $perPage = $request->per_page ?? 10;
        $investments = $query->paginate($perPage);


        if(count($investments) > 0){
            InvestmentUpdaterJob::dispatch(auth()->id(),'portfolio');
        }

        return response()->json($investments);
    }

    public function getECNInvestments(Request $request)
    {
        $user = Auth::user();

        $query = Investment::query()
            ->where('investment_type','ecn')
            ->where('user_id', $user->id);

        // Apply search if provided
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;

            $query->where(function($q) use ($search) {
                // Search in multiple columns
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('amount', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Apply status filter
        if ($request->has('status')) {
            $status = $request->status;

            if ($status === 'active') {
                // In progress investments are active and not yet ended
                $query->where(function($q) {
                        $q->whereNull('completed_on');
                    });
            } elseif ($status === 'sold') {
                // Completed investments have either 'completed' status or have passed their end date
                $query->where(function($q) {
                    $q->whereNotNull('completed_on');
                });
            }
        }else{
            $query->where(function($q) {
                $q->whereNull('completed_on');
            });
        }

        // Order by creation date, newest first
        $query->orderBy('created_at', 'desc');

        // Get paginated results
        $perPage = $request->per_page ?? 10;
        $investments = $query->paginate($perPage);


        if(count($investments) > 0){
            InvestmentUpdaterJob::dispatch(auth()->id(),'ecn');
        }

        return response()->json($investments);
    }

    public function convertStore(Request $request){
        $user = \auth()->user();

        if($user->portfolio_balance < 1){
            Toast::danger('Insufficient portfolio balance');
        }else{

            Transaction::create([
                'user_id' => auth()->id(),
                'type' => 'conversion',
                'method' => 'wallet',
                'status' => 'approved',
                'amount' => $user->portfolio_balance,
                'reason' => "Conversion of portfolio balance to wallet balance",
            ]);



            log_activity('Portfolio balance conversion',auth()->user()->email.' converted portfolio balance to wallet balance',auth()->id(),'conversion');


            $user->update([
                'balance' => $user->balance + $user->portfolio_balance,
                'portfolio_balance' => 0,
            ]);

            Toast::success('Portfolio balance converted to wallet balance');
        }

        return redirect()->back();
    }

    public function convertPortfolio(){
        $default = [
            'portfolio' => auth()->user()->portfolio_balance,
            'balance' => auth()->user()->balance,
        ];
        return view('portfolio.convert_portfolio', compact('default'));
    }
}

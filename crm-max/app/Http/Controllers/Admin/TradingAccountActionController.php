<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TradingAccount;
use App\Services\AdminTradingAccountService;
use Illuminate\Http\Request;
use ProtoneMedia\Splade\Facades\Toast;

class TradingAccountActionController extends Controller
{
    public function create(string $id, string $accountId)
    {
        abort_unless(is_admin(), 403);
        $account = TradingAccount::where('company_id', auth()->user()->company_id)->where('user_id', $id)->findOrFail($accountId);
        $destinations = TradingAccount::where('company_id', auth()->user()->company_id)->where('user_id', $id)
            ->where('account_type', 'live')->where('is_active', true)->where('id', '!=', $accountId)->get()
            ->filter(fn ($item) => strtolower($item->base_currency) === strtolower($account->base_currency))
            ->mapWithKeys(fn ($item) => [$item->id => '#'.$item->account_id.' '.($item->name ?? '')]);

        return view('admin.users.trading_accounts.actions', compact('account', 'destinations'));
    }

    public function store(Request $request, string $id, string $accountId, AdminTradingAccountService $service)
    {
        $data = $request->validate([
            'request_id' => ['required', 'uuid'], 'action' => ['required', 'in:archive,transfer'],
            'destination_id' => ['required_if:action,transfer', 'nullable', 'uuid'],
            'amount' => ['required_if:action,transfer', 'nullable', 'numeric', 'min:0.01', 'decimal:0,2', 'max:1000000000000'],
            'confirmed' => ['accepted'],
        ]);
        $service->execute(auth()->user(), $id, $accountId, $data['request_id'],
            $data['action'] === 'transfer' ? $data['destination_id'] : null,
            $data['action'] === 'transfer' ? (float) $data['amount'] : 0);
        Toast::success($data['action'] === 'transfer' ? 'Funds transferred successfully.' : 'Trading account archived. History has been retained.');

        return redirect()->route('admin.users.show', ['user' => $id, 'tab' => 'trading_accounts']);
    }
}

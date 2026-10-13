<?php

namespace App\Http\Controllers\Admin;

use App\Events\TradeUpdated;
use App\Models\ActivityLog;
use App\Models\Trade;
use App\Models\User;
use App\Services\TradeBalanceService;
use App\Services\TradeService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * LeadMax Open Trades / All Trades. Staff see the trades of the leads they can see (agents: their own);
 * admins can edit, close and delete a trade and set a client's spread. Every change is logged on the lead
 * with its before/after values.
 *
 * Money rules: an open trade's P&L is derived from the price, so a new P&L is reached by back-solving the
 * opening price (TradeService::applyPnlToOpenTrade, as the main CRM does). A closed trade's P&L was already
 * settled into the account, so changing it settles only the difference into the trading account
 * (TradeBalanceService::applyTradePnl, the same path closing a trade uses).
 */
class LeadMaxTradeController extends LeadMaxController
{
    private const TRADES_PER_PAGE = 25;

    private const LOGGED = ['opening_price', 'closing_price', 'volume', 'leverage', 'margin', 'take_profit', 'stop_loss', 'profit', 'opened_at', 'close_at'];

    public function open(Request $request)
    {
        return $this->listing($request, 'open');
    }

    public function index(Request $request)
    {
        return $this->listing($request, 'all');
    }

    /** Everyone whose trades staff may see: leads and converted clients (customer/user), agents only their own. */
    private function clientQuery(): Builder
    {
        $query = User::query()->whereHasRole(['lead', 'customer', 'user']);

        return is_admin() ? $query : $query->where('manager_id', auth()->id());
    }

    private function tradeQuery(): Builder
    {
        return Trade::query()->whereIn('user_id', $this->clientQuery()->select('users.id'));
    }

    private function listing(Request $request, string $scope)
    {
        $search = trim($request->string('search')->toString());
        $type = in_array($request->input('type'), ['buy', 'sell'], true) ? $request->input('type') : null;
        $state = $scope === 'open' ? 'open' : (in_array($request->input('state'), ['open', 'closed'], true) ? $request->input('state') : null);
        $leadId = is_string($request->input('lead_id')) && $request->input('lead_id') !== '' ? $request->input('lead_id') : null;

        $base = $this->tradeQuery()
            ->when($type, fn ($q) => $q->where('trade_type', $type))
            ->when($leadId, fn ($q) => $q->where('user_id', $leadId))
            ->when($search !== '', fn ($q) => $q->where(fn ($w) => $w->where('trade_id', 'like', "%{$search}%")
                ->orWhereHas('asset', fn ($a) => $a->where('sym', 'like', "%{$search}%")->orWhere('sym_key', 'like', "%{$search}%")->orWhere('name', 'like', "%{$search}%"))
                ->orWhereHas('user', fn ($u) => $u->where('first_name', 'like', "%{$search}%")->orWhere('last_name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%")->orWhere('account_id', 'like', "%{$search}%"))));

        $query = (clone $base)
            ->when($state === 'open', fn ($q) => $q->where('status', 0))
            ->when($state === 'closed', fn ($q) => $q->where('status', 1));

        // Open positions on this page get a fresh P&L, the same refresh the trade room triggers on every poll.
        $trades = $query->with(['user', 'asset', 'tradingAccount'])->orderBy('status')->latest()->paginate(self::TRADES_PER_PAGE)->withQueryString();
        $service = app(TradeService::class);
        $trades->getCollection()->where('status', 0)->each(fn (Trade $trade) => rescue(fn () => $service->updateTradeProfit($trade), null, false));

        $totals = (clone $base)->toBase()->selectRaw('SUM(CASE WHEN status = 0 THEN 1 ELSE 0 END) as open_count,
            SUM(CASE WHEN status = 0 THEN profit ELSE 0 END) as floating,
            SUM(CASE WHEN status = 0 THEN margin ELSE 0 END) as margin,
            SUM(CASE WHEN status = 1 THEN 1 ELSE 0 END) as closed_count,
            SUM(CASE WHEN status = 1 THEN profit ELSE 0 END) as realised')->first();

        $editing = $request->filled('trade') ? $this->tradeQuery()->with(['user', 'asset', 'tradingAccount'])->find($request->input('trade')) : null;
        $lead = $leadId ? $this->clientQuery()->find($leadId) : null;

        return view('admin.leadmax.trades', [
            ...$this->common($request, $scope === 'open' ? 'open-trades' : 'all-trades'),
            'scope' => $scope, 'trades' => $trades, 'totals' => $totals, 'search' => $search, 'type' => $type, 'state' => $state,
            'lead' => $lead, 'editing' => $editing, 'dialog' => $editing ? $request->input('dialog', 'edit') : null,
            'canEdit' => is_admin(),
        ]);
    }

    public function update(Request $request, string $id)
    {
        abort_unless(is_admin(), 403);
        $trade = $this->tradeQuery()->with(['user', 'asset', 'tradingAccount'])->findOrFail($id);
        $open = (int) $trade->status === 0;
        $data = $request->validate([
            'opening_price' => 'required|numeric|gt:0',
            'closing_price' => [$open ? 'nullable' : 'required', 'numeric', 'gt:0'],
            'volume' => 'required|numeric|gt:0',
            'leverage' => 'required|integer|min:1|max:1000',
            'take_profit' => 'nullable|numeric|min:0',
            'stop_loss' => 'nullable|numeric|min:0',
            'profit' => 'nullable|numeric',
            'opened_at' => 'nullable|date',
            'close_at' => 'nullable|date',
            'note' => 'nullable|string|max:500',
        ]);
        $before = $this->snapshot($trade);

        DB::transaction(function () use ($trade, $data, $open) {
            $oldProfit = (float) $trade->profit;
            $oldVolume = (float) $trade->volume;
            $oldLeverage = max(1, (int) $trade->leverage);

            $trade->opening_price = $data['opening_price'];
            $trade->volume = $data['volume'];
            $trade->leverage = $data['leverage'];
            // Margin is volume-based collateral: scale it with the new size and leverage.
            if ($oldVolume > 0) {
                $trade->margin = round((float) $trade->margin * ((float) $data['volume'] / $oldVolume) * ($oldLeverage / (int) $data['leverage']), 2);
            }
            $trade->take_profit = (float) ($data['take_profit'] ?? 0);
            $trade->is_take_profit = $trade->take_profit > 0;
            $trade->stop_loss = (float) ($data['stop_loss'] ?? 0);
            $trade->is_stop_loss = $trade->stop_loss > 0;
            if (! empty($data['opened_at'])) {
                $trade->opened_at = $data['opened_at'];
            }

            if ($open) {
                $trade->save();
                $service = app(TradeService::class);
                $wanted = isset($data['profit']) ? (float) $data['profit'] : null;
                $wanted !== null && abs($wanted - $oldProfit) > 0.000001
                    ? $service->applyPnlToOpenTrade($trade->fresh(['asset', 'user']), $wanted)
                    : $service->updateTradeProfit($trade->fresh());

                return;
            }

            $trade->closing_price = $data['closing_price'];
            if (! empty($data['close_at'])) {
                $trade->close_at = $data['close_at'];
            }
            $newProfit = isset($data['profit']) ? (float) $data['profit'] : $oldProfit;
            $trade->profit = $newProfit;
            $trade->save();

            $difference = round($newProfit - $oldProfit, 6);
            if ($difference != 0.0) {
                app(TradeBalanceService::class)->applyTradePnl($trade->user, $difference, $trade->tradingAccount,
                    'Trade '.$trade->trade_id.' P&L corrected by '.auth()->user()->email);
            }
        });

        $trade->refresh();
        $changes = $this->diff($before, $this->snapshot($trade));
        $this->log($trade, 'trade_edited', 'Trade edited', ['changes' => $changes, 'description' => $data['note'] ?? null]);

        return response()->json(['message' => 'Trade updated', 'changes' => $changes]);
    }

    public function close(string $id)
    {
        abort_unless(is_admin(), 403);
        $trade = $this->tradeQuery()->where('status', 0)->findOrFail($id);
        $result = app(TradeService::class)->closeTrade($trade->id, auth()->user());
        abort_if(($result['status'] ?? 0) < 1, 422, $result['msg'] ?? 'The trade could not be closed.');
        $trade->refresh();
        $this->log($trade, 'trade_closed_by_staff', 'Trade closed by staff', ['closing_price' => $trade->closing_price, 'profit' => $trade->profit]);

        return response()->json(['message' => 'Trade closed']);
    }

    public function destroy(Request $request, string $id)
    {
        abort_unless(is_admin(), 403);
        $data = $request->validate(['password' => 'required|string', 'reverse_pnl' => 'sometimes|boolean']);
        abort_unless(Hash::check($data['password'], auth()->user()->password), 422, 'The confirmation password is incorrect.');
        $trade = $this->tradeQuery()->with(['user', 'tradingAccount'])->findOrFail($id);
        $reverse = (int) $trade->status === 1 && $request->boolean('reverse_pnl') && (float) $trade->profit != 0.0;

        DB::transaction(function () use ($trade, $reverse) {
            if ($reverse) {
                app(TradeBalanceService::class)->applyTradePnl($trade->user, -1 * (float) $trade->profit, $trade->tradingAccount,
                    'Trade '.$trade->trade_id.' deleted by '.auth()->user()->email.', P&L reversed');
            }
            $trade->delete();
        });
        $this->log($trade, 'trade_deleted', 'Trade deleted', ['snapshot' => $this->snapshot($trade), 'pnl_reversed' => $reverse]);

        return response()->json(['message' => $reverse ? 'Trade deleted and its P&L reversed' : 'Trade deleted']);
    }

    /** A client's spread: a multiplier on the company markup (blank = standard 1x, 0 = no spread, at most 20x). */
    public function spread(Request $request, string $leadId)
    {
        abort_unless(is_admin(), 403);
        $lead = $this->clientQuery()->findOrFail($leadId);
        $data = $request->validate(['spread_multiplier' => 'nullable|numeric|min:0|max:20']);
        $old = $lead->spread_multiplier;
        $new = isset($data['spread_multiplier']) && $data['spread_multiplier'] !== '' ? (float) $data['spread_multiplier'] : null;
        $lead->forceFill(['spread_multiplier' => $new])->save();
        $this->activity($lead, 'spread_changed', 'Client spread changed', ['from' => $old, 'to' => $new]);

        return response()->json(['message' => 'Client spread updated']);
    }

    private function snapshot(Trade $trade): array
    {
        return collect(self::LOGGED)->mapWithKeys(fn ($field) => [$field => $trade->{$field} instanceof \DateTimeInterface
            ? $trade->{$field}->format('Y-m-d H:i:s') : (is_numeric($trade->{$field}) ? (float) $trade->{$field} : $trade->{$field})])->all();
    }

    private function diff(array $before, array $after): array
    {
        return collect($after)->filter(fn ($value, $field) => $before[$field] != $value)
            ->map(fn ($value, $field) => ['from' => $before[$field], 'to' => $value])->all();
    }

    private function log(Trade $trade, string $type, string $title, array $info): void
    {
        broadcast(new TradeUpdated($trade->user_id));
        log_activity($type, $title.' '.$trade->trade_id.' by '.auth()->user()->email);
        if ($trade->user) {
            $this->activity($trade->user, $type, $title, ['trade_id' => $trade->trade_id, ...$info]);
        }
    }

    private function activity(User $lead, string $type, string $title, array $info): void
    {
        ActivityLog::create(['user_id' => $lead->id, 'type' => $type, 'title' => $title, 'info' => ['actor_id' => auth()->id(), 'actor' => auth()->user()->name, 'ip' => request()->ip(), ...\App\Support\LeadMax\ActivityContext::current(), ...$info]]);
    }
}

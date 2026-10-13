<?php

namespace App\Http\Controllers\Admin;

use App\Events\TradeUpdated;
use App\Http\Controllers\Controller;
use App\Models\Trade;
use App\Models\User;
use App\Services\TradeService;
use App\Tables\TradeTable;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use ProtoneMedia\Splade\Facades\Toast;

class TradeController extends Controller
{
    public function index()
    {
        return view('admin.trades.index', [
            'data' => new TradeTable,
        ]);
    }

    public function edit($id)
    {
        $trade = Trade::findOrFail($id);

        return view('admin.trades.edit_trade', [
            'trade' => $trade,
            'default' => [
                'opened_at' => $this->dateTimeLocalValue($trade->getRawOriginal('opened_at')),
                'close_at' => $this->dateTimeLocalValue($trade->getRawOriginal('close_at')),
                'volume' => $trade->volume,
                'leverage' => $trade->leverage,
                'opening_price' => $trade->opening_price,
                'closing_price' => $trade->closing_price,
                'margin' => $trade->margin,
                'profit' => $trade->profit,
            ],
        ]);
    }

    public function destroy($id)
    {
        $item = Trade::findOrFail($id);

        $item->delete();

        $msg = "Trade with ID {$item->trade_id} deleted by user ID ".auth()->user()->email.' at '.Carbon::now();

        log_activity('trade_deleted', $msg);

        broadcast(new TradeUpdated($item->user_id));
        Toast::title('Trade deleted successfully');

        return redirect()->back();
    }

    public function update(Request $request, $id)
    {
        $data = Trade::findOrFail($id);

        $validated = $this->getData($request);

        if ($data->profit != $validated['profit']) {
            $user = User::findOrFail($data->user_id);
            $oldProfit = $data->profit;
            $newProfit = $validated['profit'];
            $difference = $newProfit - $oldProfit;

            DB::transaction(function () use ($difference, $user) {
                $user = $user->fresh();
                $user->balance = $user->balance + $difference;
                $user->save();
            });

            Toast::success('User balance updated successfully');
        }

        $profitChanged = (float) $data->profit !== (float) $validated['profit'];

        $data->update($validated);

        // An OPEN trade's P&L is derived, not stored: updateTradeProfit()
        // recomputes it from (current - opening_price) * volume on the next
        // /open/trades poll, and the trade room recomputes it again on every
        // price tick. Writing the profit column directly therefore appeared
        // to work and then silently reverted -- the admin's figure was
        // overwritten twice before anyone saw it.
        //
        // Back-solve an opening_price that MAKES the derivation produce the
        // requested figure instead, so it survives both recomputations and
        // then moves with the market like any other position. Closed trades
        // are historical records and keep the value written above.
        if ($profitChanged && (int) $data->status === 0) {
            app(TradeService::class)->applyPnlToOpenTrade(
                $data->fresh(),
                (float) $validated['profit']
            );
        }

        // Broadcast only after both the requested fields and the derived
        // opening price have been persisted. Consumers commonly refetch on
        // this event; broadcasting earlier made that refetch race the write
        // and return the old P&L.
        broadcast(new TradeUpdated($data->user_id));

        Toast::success('Trade updated successfully');

        return redirect()->back();
    }

    public function getData(Request $request): array
    {
        return $request->validate([
            'margin' => 'required|numeric',
            'volume' => 'required|numeric',
            'leverage' => 'required|numeric',
            'profit' => 'required|numeric',
            'opening_price' => 'required|numeric',
            'closing_price' => 'required|numeric',
            'trade_type' => 'nullable',
            'opened_at' => 'sometimes|nullable|date',
            'close_at' => 'nullable',
        ]);
    }

    private function dateTimeLocalValue($value): ?string
    {
        if (! $value) {
            return null;
        }

        return Carbon::parse($value)->format('Y-m-d H:i');
    }
}

<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Models\Trade;
use App\Models\User;
use App\Services\EmailService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

class SendAiTradeDailySummary extends Command
{
    protected $signature = 'ai-trade:send-daily-summary
        {--company= : Only send for a specific company ID}
        {--user= : Only send for a specific user ID}
        {--date= : Summary date in YYYY-MM-DD format}
        {--force : Ignore duplicate protection and send anyway}';

    protected $description = 'Send the daily AI trading summary email to users with AI Trade enabled.';

    public function handle(EmailService $emailService): int
    {
        $date = $this->resolveDate();

        if (! $date) {
            $this->error('Invalid --date value. Use YYYY-MM-DD.');

            return self::FAILURE;
        }

        $companies = Company::query()
            ->when($this->option('company'), fn ($query, $companyId) => $query->whereKey($companyId))
            ->orderBy('id')
            ->get();

        if ($companies->isEmpty()) {
            $this->warn('No companies matched the criteria.');

            return self::SUCCESS;
        }

        $sentCount = 0;

        foreach ($companies as $company) {
            app()->instance('company', $company);

            $users = User::query()
                ->where('company_id', $company->id)
                ->where('ai_trade', true)
                ->whereNotNull('email')
                ->when($this->option('user'), fn ($query, $userId) => $query->whereKey($userId))
                ->get();

            if ($users->isEmpty()) {
                $this->line("Company {$company->name}: no AI Trade users found.");

                continue;
            }

            foreach ($users as $user) {
                $summary = $this->buildSummary($user, $date);
                $emailService->sendAiTradeDailySummary($user, $summary, $date, (bool) $this->option('force'));

                $sentCount++;
                $this->line("Sent AI summary to {$user->name} <{$user->email}> for {$date->toDateString()}");
            }
        }

        $this->info("AI daily summaries processed: {$sentCount}");

        return self::SUCCESS;
    }

    private function resolveDate(): ?Carbon
    {
        $value = $this->option('date');

        if (! $value) {
            return now()->startOfDay();
        }

        try {
            return Carbon::parse((string) $value)->startOfDay();
        } catch (\Throwable) {
            return null;
        }
    }

    private function buildSummary(User $user, Carbon $date): array
    {
        $todayTrades = Trade::query()
            ->with('asset')
            ->where('company_id', $user->company_id)
            ->where('user_id', $user->id)
            ->whereBetween('created_at', [$date->copy()->startOfDay(), $date->copy()->endOfDay()])
            ->orderByDesc('created_at')
            ->get();

        $yesterdayTrades = Trade::query()
            ->where('company_id', $user->company_id)
            ->where('user_id', $user->id)
            ->whereBetween('created_at', [
                $date->copy()->subDay()->startOfDay(),
                $date->copy()->subDay()->endOfDay(),
            ])
            ->get();

        $todayPnl = (float) $todayTrades->sum(fn (Trade $trade) => (float) $trade->profit);
        $yesterdayPnl = (float) $yesterdayTrades->sum(fn (Trade $trade) => (float) $trade->profit);
        $changePercent = abs($yesterdayPnl) > 0.00001
            ? (($todayPnl - $yesterdayPnl) / abs($yesterdayPnl)) * 100
            : null;

        $wins = $todayTrades->filter(fn (Trade $trade) => (float) $trade->profit > 0)->count();
        $totalTrades = $todayTrades->count();
        $bestPair = $this->bestPair($todayTrades);

        return [
            'pnl' => $todayPnl,
            'change_percent' => $changePercent,
            'total_trades' => $totalTrades,
            'win_rate' => $totalTrades > 0 ? number_format(($wins / $totalTrades) * 100).'%' : '0%',
            'best_pair' => $bestPair,
            'insight' => $this->insightText($todayTrades, $todayPnl, $bestPair),
            'transactions' => $this->transactionRows($todayTrades),
        ];
    }

    private function bestPair(Collection $trades): string
    {
        if ($trades->isEmpty()) {
            return '--';
        }

        $topPair = $trades
            ->groupBy(fn (Trade $trade) => $this->formatPair($trade))
            ->map(fn (Collection $rows) => $rows->sum(fn (Trade $trade) => (float) $trade->profit))
            ->sortDesc()
            ->keys()
            ->first();

        return (string) ($topPair ?: '--');
    }

    private function insightText(Collection $trades, float $todayPnl, string $bestPair): string
    {
        if ($trades->isEmpty()) {
            return 'AI Trading monitored the market today but did not open any new positions. The strategy stayed selective and waited for clearer setups.';
        }

        $wins = $trades->filter(fn (Trade $trade) => (float) $trade->profit > 0)->count();
        $winRate = $trades->count() > 0 ? ($wins / $trades->count()) * 100 : 0;
        $pairText = $bestPair !== '--' ? $bestPair : 'your active market set';

        if ($todayPnl > 0 && $winRate >= 60) {
            return "Market volatility increased around {$pairText} today. The AI strategy adapted well and captured high-probability entries with a {$winRate}% win rate.";
        }

        if ($todayPnl < 0) {
            return "The session was mixed across {$pairText}. AI Trading stayed active, but tighter conditions reduced overall performance while keeping risk controls in place.";
        }

        return "AI Trading remained active across {$pairText} today and balanced entry quality with risk control while scanning for stronger setups.";
    }

    private function transactionRows(Collection $trades): array
    {
        return $trades
            ->take(3)
            ->map(function (Trade $trade): array {
                $profit = (float) $trade->profit;

                return [
                    'pair' => $this->formatPair($trade),
                    'type' => ucfirst((string) $trade->trade_type),
                    'type_color' => $trade->trade_type === 'sell' ? '#d46a6a' : '#16b66a',
                    'entry_price' => number_format((float) $trade->opening_price, 2),
                    'pnl' => ($profit > 0 ? '+' : '').number_format($profit, 2),
                    'pnl_color' => $profit < 0 ? '#d64545' : '#16b66a',
                ];
            })
            ->all();
    }

    private function formatPair(Trade $trade): string
    {
        $asset = $trade->asset;

        if (! $asset) {
            return 'N/A';
        }

        $symbol = strtoupper((string) ($asset->sym ?: $asset->ex_sym ?: $asset->name));
        $base = strtoupper((string) ($asset->base ?: 'USD'));

        if (str_contains($symbol, '/')) {
            return $symbol;
        }

        return "{$symbol}/{$base}";
    }
}

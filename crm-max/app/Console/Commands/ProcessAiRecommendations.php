<?php

namespace App\Console\Commands;

use App\Models\AiRecommendation;
use App\Services\AiRecommendationService;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;

class ProcessAiRecommendations extends Command
{
    protected $signature = 'ai-recommendations:process {--limit=100}';

    protected $description = 'Deliver due scheduled AI recommendations.';

    public function handle(AiRecommendationService $service): int
    {
        $now = CarbonImmutable::now();
        $limit = (int) $this->option('limit');
        $delivered = 0;

        AiRecommendation::query()
            ->with('user')
            ->whereIn('status', [
                AiRecommendation::STATUS_SCHEDULED,
                AiRecommendation::STATUS_RECURRING,
                AiRecommendation::STATUS_QUEUED,
            ])
            ->whereNotNull('scheduled_at')
            ->where('scheduled_at', '<=', $now)
            ->orderBy('scheduled_at')
            ->limit($limit)
            ->get()
            ->each(function (AiRecommendation $recommendation) use ($service, $now, &$delivered) {
                $service->deliver($recommendation, $now);
                $delivered++;
            });

        $this->info("AI recommendations processed. Delivered: {$delivered}.");

        return self::SUCCESS;
    }
}

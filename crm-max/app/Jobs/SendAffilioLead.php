<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;

class SendAffilioLead implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 10;

    public function __construct(
        private string $campaignId,
        private array  $payload,
        private string $apiUrl,
    ) {}

    public function handle(): void
    {
        $response = Http::timeout(10)
            ->withHeaders([
                'apiKey'      => config('services.affilio.api_key'),
                'username'    => config('services.affilio.username'),
                'password'    => config('services.affilio.password'),
                'User-Agent'  => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                'Accept'      => 'application/json',
            ])
            ->withOptions(['version' => CURL_HTTP_VERSION_1_1])
            ->post($this->apiUrl, $this->payload);

        $status = $response->status();

        // 4xx = permanent rejection (wrong country, bad data) — no point retrying
        if ($status >= 400 && $status < 500) {
            logger()->warning('survey.affilio_rejected', [
                'campaign_id' => $this->campaignId,
                'status'      => $status,
                'body'        => $response->body(),
            ]);
            return;
        }

        if (! $response->successful()) {
            logger()->warning('survey.affilio_failed', [
                'campaign_id' => $this->campaignId,
                'status'      => $status,
                'body'        => $response->body(),
                'attempt'     => $this->attempts(),
            ]);

            throw new \RuntimeException(
                "Affilio API returned {$status} on attempt {$this->attempts()}"
            );
        }
    }

    public function backoff(): array
    {
        return [10, 30, 60, 120, 180, 300, 300, 300, 300, 300];
    }
}

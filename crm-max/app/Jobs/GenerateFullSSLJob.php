<?php

namespace App\Jobs;

use App\Models\Domain;
use App\Services\SSLService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GenerateFullSSLJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 30;
    public int $backoff = 20;

    public function __construct(public Domain $domain)
    {
        //
    }

    public function handle(): void
    {
        Log::info('generating ssl started');

        $sslService = new SSLService();
        $sslService->provisionDomain($this->domain);
    }
}

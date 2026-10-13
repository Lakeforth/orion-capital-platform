<?php

namespace App\Jobs;

use App\Models\Domain;
use App\Services\SSLService;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class ActivateSSLJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public string|int $domain_id,
        public string|int $cert_id,
    ) {}

    public function handle(SSLService $sslService)
    {
        try {
            $domain = Domain::findOrFail($this->domain_id);

            $result = $sslService->enableDomainCertificate($domain);

            if (!$result['success']) {
                Log::error('SSL activation failed', [
                    'domain' => $domain,
                    'error' => $result['error'] ?? 'Unknown error',
                ]);

                return;
            }

            Log::info('ssl activation started');
        } catch (Exception $e) {
            Log::error('Error in SSL activation job', [
                'domain_id' => $this->domain_id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    public function failed(Throwable $exception)
    {
        Log::error('SSL activation job failed', [
            'domain' => $this->domain_id,
            'error' => $exception->getMessage(),
        ]);
    }
}

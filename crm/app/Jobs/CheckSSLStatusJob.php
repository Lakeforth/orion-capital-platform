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

class CheckSSLStatusJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 30;
    public int $backoff = 60;

    public function __construct(public string|int $domain_id)
    {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $site = Domain::findOrFail($this->domain_id);
        $sslService = new SSLService();

        try {
            $result = $sslService->getDomainCertificateStatus($site);

            if (!$result['success']) {
                Log::error('SSL certificate check failed', [
                    'site_id' => $site->id,
                    'error' => $result['message'] ?? 'Unknown error',
                ]);
                return;
            }

            $status = $result['status'];

            if (in_array($status, ['installed', 'active'], true)) {
                $site->status = 'active';
                $site->save();

                return;
            }

            if ($status === 'failed') {
                $site->status = 'failed';
                $site->save();

                return;
            }

            $sslService->enableDomainCertificate($site);
            $site->status = 'activating_ssl';
            $site->save();

            $this->release($this->backoff);
        } catch (Exception $e) {
            Log::error('Error checking SSL certificate status', [
                'domain' => $this->domain_id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    public function failed(Throwable $exception)
    {
        Log::error('SSL status check job failed', [
            'domain_id' => $this->domain_id,
            'error' => $exception->getMessage(),
        ]);
    }
}

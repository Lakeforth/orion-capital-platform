<?php

namespace App\Jobs;

use App\Models\Domain;
use App\Services\SSLService;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CheckDomainCertStatus implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 30;
    public int $backoff = 20;

    public function __construct(public string $domain_id)
    {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $site = Domain::findOrFail($this->domain_id);
        $sslService = new SSLService();

        Log::info('CheckDomainCertStatus', [
            'domain_id' => $this->domain_id,
            'domain_record_id' => $site->domain_record_id,
        ]);

        try {
            $result = $sslService->getDomainCertificateStatus($site);

            Log::info('CheckDomainCertStatus response', [$result]);

            if (!$result['success']) {
                Log::error('Domain SSL certificate check failed', [
                    'site_id' => $site->id,
                    'error' => $result['message'] ?? 'Unknown error',
                ]);

                $site->status = 'failed';
                $site->save();

                return;
            }

            $status = $result['status'];

            if (in_array($status, ['installed', 'active'], true)) {
                $site->status = 'active';
                $site->save();

                return;
            }

            if (in_array($status, ['created', 'ready', 'issued'], true)) {
                $sslService->enableDomainCertificate($site);
                $site->status = 'activating_ssl';
                $site->save();

                $this->release($this->backoff);
                return;
            }

            if ($status === 'failed') {
                $site->status = 'failed';
                $site->save();

                return;
            }

            $this->release($this->backoff);
        } catch (Exception $e) {
            Log::error('Error checking SSL certificate status', [
                'domain' => $this->domain_id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}

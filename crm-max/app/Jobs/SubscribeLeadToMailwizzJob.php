<?php

namespace App\Jobs;

use App\Services\MailWizzService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class SubscribeLeadToMailwizzJob implements ShouldQueue
{
    use Queueable, InteractsWithQueue;

    public int $tries = 3;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public string $listUid,
        public string $email,
        public string $firstName,
        public string $lastName,
        public string $phone
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // This job needs to fail gracefully if something goes wrong, hence we're logging the error and do nothing with the response.
        try {
            $apiResponse = (new MailWizzService())->subscribeNewUser(
                $this->listUid,
                $this->email,
                $this->firstName,
                $this->lastName,
                $this->phone,
            );

            if (isset($apiResponse->status) && strtolower($apiResponse->status) == 'success') {
                Log::info('Successfully subscribed a lead to Mailwizz list', [
                    'email' => $this->email,
                    'name' => $this->firstName.' '.$this->lastName,
                    'listUid' => $this->listUid,
                    'subscriberUid' => $apiResponse->data?->record?->subscriber_uid ?? null,
                ]);
            }
            else {
                Log::error('Failed to subscribe a lead to Mailwizz list', [
                    'email' => $this->email,
                    'name' => $this->firstName.' '.$this->lastName,
                    'listUid' => $this->listUid,
                    'response' => json_encode($apiResponse),
                ]);
            }
        }
        catch (\Exception $e) {
            Log::info('An error occurred with Mailwizz Lead Subscription job', [
                'message' => $e->getMessage(),
                'statusCode' => $e->getCode(),
                'email' => $this->email,
                'name' => $this->firstName.' '.$this->lastName,
                'listUid' => $this->listUid,
            ]);
        }
    }
}

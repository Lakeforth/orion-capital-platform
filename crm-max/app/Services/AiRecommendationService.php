<?php

namespace App\Services;

use App\Models\AiRecommendation;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Log;

class AiRecommendationService
{
    public function deliver(AiRecommendation $recommendation, ?CarbonInterface $now = null): void
    {
        $now ??= now();
        $recommendation->loadMissing(['user', 'company']);

        if (! $recommendation->user) {
            return;
        }

        $previousCompany = app()->bound('company') ? app('company') : null;

        if ($recommendation->company) {
            app()->instance('company', $recommendation->company);
        }

        try {
            $recommendation->forceFill([
                'is_active' => true,
                'last_delivered_at' => $now,
                'delivery_count' => $recommendation->delivery_count + 1,
                'status' => $recommendation->schedule_type === AiRecommendation::SCHEDULE_RECURRING
                    ? AiRecommendation::STATUS_RECURRING
                    : AiRecommendation::STATUS_SENT,
            ])->save();

            app(NotificationService::class)->notify(
                $recommendation->message,
                'ai_recommendation',
                $recommendation->heading,
                $recommendation->user,
                'info',
                [
                    'meta' => [
                        'recommendation_id' => $recommendation->id,
                        'source_name' => $recommendation->source_name,
                        'schedule_type' => $recommendation->schedule_type,
                    ],
                ]
            );

            if ($recommendation->send_email) {
                $this->sendEmail($recommendation);
            }

            if ($recommendation->schedule_type === AiRecommendation::SCHEDULE_RECURRING) {
                $recommendation->forceFill([
                    'scheduled_at' => $this->nextScheduledAt($recommendation, $now),
                    'status' => AiRecommendation::STATUS_RECURRING,
                ])->save();
            }
        } finally {
            if ($previousCompany) {
                app()->instance('company', $previousCompany);
            } else {
                app()->forgetInstance('company');
            }
        }
    }

    public function nextScheduledAt(AiRecommendation $recommendation, CarbonInterface $from): CarbonInterface
    {
        return match ($recommendation->recurrence) {
            'weekly' => $from->copy()->addWeek(),
            'monthly' => $from->copy()->addMonth(),
            default => $from->copy()->addDay(),
        };
    }

    private function sendEmail(AiRecommendation $recommendation): void
    {
        try {
            app(NotificationService::class)->sendMail(
                $recommendation->user->email,
                $this->mailData($recommendation),
                $recommendation->company_id
            );
        } catch (\Throwable $exception) {
            Log::error('AI recommendation email failed: '.$exception->getMessage(), [
                'recommendation_id' => $recommendation->id,
                'user_id' => $recommendation->user_id,
            ]);
        }
    }

    private function mailData(AiRecommendation $recommendation): array
    {
        $siteName = (string) (company_settings('site_name') ?: config('app.name'));
        $firstName = trim((string) $recommendation->user->first_name) ?: 'there';

        return [
            'template' => 'ai_trade',
            'kind' => 'recommendation',
            'title' => $recommendation->heading,
            'user' => $recommendation->user,
            'site_name' => $siteName,
            'logo_url' => getFullImage(company_settings('front_end_logo', asset('images/logo.png'))),
            'support_url' => company_settings('support_url', config('app.url')),
            'support_email' => company_settings('support_email', app()->bound('company') ? app('company')->email : null),
            'variant' => 'success',
            'hero_title' => 'AI',
            'hero_accent' => 'Recommendation',
            'greeting' => "Hi {$firstName}",
            'intro' => '',
            'recommendation_heading' => $recommendation->heading,
            'recommendation_source' => $recommendation->source_name,
            'recommendation_body' => $recommendation->message,
            'summary' => $recommendation->message,
            'cta_text' => 'View Recommendation',
            'cta_url' => rtrim((string) company_settings('front_end_url', config('app.url')), '/').'/main/dashboard',
            'footer_note' => "Trade on the go with {$siteName}",
        ];
    }
}

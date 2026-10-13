<?php

namespace App\Console\Commands;

use App\Models\Message;
use App\Services\EmailService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class NotifyAdminUnreadMessages extends Command
{
    protected $signature = 'messages:notify-admin';

    protected $description = 'Notify admin of any customer message not replied after 3 minutes';

    public function handle(EmailService $emailService)
    {

        if (company_settings('send_email_on_unread_message', settings('send_email_on_unread_message', 'no')) == 'no') {
            return 0;
        }

        $interval = (int) company_settings('unread_message_notification_interval', settings('unread_message_notification_interval', 3));

        $threshold = now()->subMinutes($interval);

        Message::where('is_admin', false)
            ->whereNull('mail_sent_at')
            ->whereNull('read_at')
            ->where('created_at', '<=', $threshold)
            ->cursor()
            ->each(function (Message $message) use ($emailService) {
                $cacheKey = "notified_message_{$message->id}";

                $message->mail_sent_at = now();
                $message->saveQuietly();

                $company_id = $message->company_id;
                if (! Cache::has($cacheKey)) {
                    $emailService->sendAdminUnreadMessage($message, $company_id);
                    Cache::put($cacheKey, true, now()->addDay());
                }
            });

        return 0;
    }
}

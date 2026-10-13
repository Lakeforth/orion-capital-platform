<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use NotificationChannels\Telegram\TelegramMessage;

class ActivityTelegramNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $companyName,
        public string $title,
        public string $message,
        public array $buttons = [],
    ) {}

    public function via(object $notifiable): array
    {
        return ['telegram'];
    }

    public function toTelegram($notifiable): TelegramMessage
    {
        $message = TelegramMessage::create()
            ->content("*{$this->companyName}*\n")
            ->line($this->title)
            ->line('')
            ->line($this->message);

        foreach ($this->buttons as $button) {
            $type = $button['type'] ?? 'url';

            if ($type === 'callback') {
                $message->buttonWithCallback($button['text'], $button['callback_data']);

                continue;
            }

            $message->button($button['text'], $button['url']);
        }

        return $message;
    }
}

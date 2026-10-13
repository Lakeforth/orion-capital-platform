<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use NotificationChannels\Telegram\TelegramMessage;

class TestTelegramConnection extends Notification
{
    use Queueable;

    public function __construct(
        public string $chatId,
        public string $company,
        public ?string $department = null
    ) {}

    public function via(object $notifiable): array
    {
        return ['telegram'];
    }

    public function toTelegram($notifiable)
    {
        $domain = $this->company;
        $departmentLine = $this->department
            ? "Congratulations, you will start receiving {$domain} {$this->department} activity notification from me"
            : "Congratulations, you will start receiving {$domain} activity notification from me";

        return TelegramMessage::create()
            ->to($this->chatId)
            ->content('Hurray!!')
            ->line('')
            ->line('Its me Livetrader Verification Bot')
            ->line($departmentLine);
    }
}

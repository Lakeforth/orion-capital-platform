<?php

namespace App\Notifications;

use Carbon\Carbon;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AccountNotification extends Notification
{
    public $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->line('The introduction to the notification.')
            ->action('Notification Action', url('/'))
            ->line('Thank you for using our application!');
    }

    public function toArray(object $notifiable): array
    {
        return array_merge($this->data, [
            'title' => $this->data['title'] ?? null,
            'message' => $this->data['message'] ?? null,
            'type' => $this->data['type'] ?? null,
            'color' => $this->data['color'] ?? null,
            'time' => $this->data['time'] ?? Carbon::now(),
        ]);
    }

    public function toBroadcast($notifiable): BroadcastMessage
    {
        return new BroadcastMessage([
            'message' => $this->data['message'],
            'title' => $this->data['title'],
        ]);
    }
}

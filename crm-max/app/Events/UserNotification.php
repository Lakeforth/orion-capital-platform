<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Queue\SerializesModels;

class UserNotification implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(public string $user_id, public array $data)
    {
        //
    }


    public function broadcastOn(): array
    {
        return [ new PrivateChannel('notification.' . $this->user_id) ];
    }

    public function broadcastAs(): string
    {
        return 'notification.message';
    }

    public function toBroadcast(): BroadcastMessage
    {
        return new BroadcastMessage([
            'message'=>$this->data['message'],
            'title'=>$this->data['title'],
        ]);
    }
}

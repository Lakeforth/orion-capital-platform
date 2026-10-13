<?php

namespace App\Listeners;

use App\Events\TradeUpdated;
use App\Events\UserUpdate;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class TradeUpdatedListener
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(TradeUpdated $event): void
    {
        $user_id = $event->user_id;

        broadcast(new UserUpdate($user_id));
    }
}

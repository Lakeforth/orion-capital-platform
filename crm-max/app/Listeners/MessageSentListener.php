<?php

namespace App\Listeners;

use App\Events\MessageSent;
use App\Models\Message;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class MessageSentListener implements ShouldQueue
{

    use InteractsWithQueue;

    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(MessageSent $event): void
    {
        Log::info("Messages sent ".$event->message->is_admin);

        if($event->message->is_admin){
            Log::info("Admin Messages marked as read");
            Message::where('customer_id', $event->message->customer_id)
                ->where('is_admin', 0)
                ->whereNull('read_at')
                ->update(['read_at' => Carbon::now()]);
        }
    }
}

<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Authenticated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UpdateLastActivity implements ShouldQueue
{
    use InteractsWithQueue;

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
    public function handle(Authenticated $event): void
    {
        if ($user = $event->user ?? null) {
            $key = "user-last-activity-{$user->id}";
            if (!Cache::has($key)) {
                DB::table('users')
                    ->where('id', $user->id)
                    ->update(['last_seen' => now()]);

                Cache::put($key, true, now()->addMinute());
            }
        }
    }
}

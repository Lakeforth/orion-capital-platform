<?php

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schedule;

Schedule::command('splade:cleanup-uploads')->daily();

// Process jobs in the default queue
//Schedule::command('queue:work --stop-when-empty --max-jobs=50 --max-time=300')
//    ->everyMinute()
//    ->withoutOverlapping()
//    ->runInBackground()
//    ->onFailure(function () {
//        Log::error('Queue processing failed');
//    });
//
//// Process failed jobs
//Schedule::command('queue:retry all')
//    ->dailyAt('00:00')
//    ->withoutOverlapping();
//
//// Clean up expired failed jobs
//Schedule::command('queue:prune-failed --hours=168')
//->weekly();

// Once a second, from one process a minute (--loop): the job writes only instruments whose
// provider tick has moved on, so each instrument follows its own feed however often that feed
// ticks (today forex and metals about every 15 s, crypto once a minute). In the background so
// the other tasks are not held up; the 2-minute lock means a crashed run never stops the next.
Schedule::command('app:asset-updater --loop')->everyMinute()->withoutOverlapping(2)->runInBackground();
Schedule::command('app:asset-sync')->dailyAt('00:00');

/*
 * One owner per asset category: the price microservice (app:asset-updater)
 * prices everything, including forex.
 *
 * app:forex-updater is a second forex source polling OANDA directly on the
 * same one-minute cadence. Checked against production 2026-08-11: it does not
 * in fact race the microservice job -- it matches rows on sym_key in OANDA's
 * EUR_USD format, the microservice matches on coin_id, and no row carries
 * both, so the two jobs write disjoint sets. What it does instead is worse in
 * a quieter way. It has created a duplicate row for 38 of the 68 pairs it
 * covers (one EUR/USD from the microservice, a second from OANDA), all 68 of
 * its rows sit at is_provider_active = 0 because the daily sync disables
 * anything absent from the microservice feed, none of them are tradable,
 * and no trade has ever been opened on one. So the job spends a request a
 * minute maintaining rows nobody can see or trade, while seeding duplicates
 * of instruments that are already priced properly.
 *
 * Disabled by default rather than deleted: the command still exists and can
 * be run by hand, and setting FOREX_UPDATER_ENABLED=true restores the
 * schedule in one env var if OANDA is ever meant to be the forex owner
 * instead. Cleaning up the 68 orphan rows is deliberately left alone --
 * that's a data decision, not a code one.
 */
Schedule::command('app:forex-updater')
    ->everyMinute()
    ->withoutOverlapping()
    ->when(fn () => (bool) config('app.forex_updater_enabled'));
Schedule::command('app:margin_call')->everyFiveMinutes();
Schedule::command('app:update-currency')->dailyAt('02:00');
Schedule::command('app:update-currency')->everyMinute();
Schedule::command('messages:notify-admin')->everyMinute();
// Lead reminders falling due, emailed to the lead's assigned staff when its alerts are on (StaffLeadAlerts).
Schedule::command('leadmax:reminder-alerts')->everyMinute()->withoutOverlapping();
Schedule::command('ai-trade:send-daily-summary')->dailyAt('23:55');
Schedule::command('ai-trade:process-scheduled')->everyMinute()->withoutOverlapping();
Schedule::command('trades:auto-close-expired')->everyMinute()->withoutOverlapping();
// Web trader pending orders whose level was reached while the client's terminal was closed.
Schedule::command('trades:trigger-pending')->everyMinute()->withoutOverlapping();
Schedule::command('ai-recommendations:process')->everyMinute()->withoutOverlapping();
Schedule::command('demo:reset-platforms')->hourly();
Schedule::command('propfirm:evaluate')->hourly()->withoutOverlapping();

Schedule::command('backup:clean')->daily()->at('01:00');
Schedule::command('backup:run')->daily()->at('01:30');

// DB-IP Lite publishes its free country database early each month (IpCountry).
Schedule::command('leadmax:geoip-update')->monthlyOn(3, '03:20')->withoutOverlapping();

<?php

namespace App\Console\Commands;

use App\Services\PendingOrderService;
use Illuminate\Console\Command;

/**
 * Opens web trader pending orders whose price level has been reached. A client's own terminal
 * also checks on every poll; this sweep covers orders whose client is offline.
 */
class TriggerPendingOrders extends Command
{
    protected $signature = 'trades:trigger-pending';

    protected $description = 'Open pending (limit/stop) orders whose price level has been reached.';

    public function handle(PendingOrderService $orders): int
    {
        $this->info('Pending orders triggered: '.$orders->checkAll());

        return self::SUCCESS;
    }
}

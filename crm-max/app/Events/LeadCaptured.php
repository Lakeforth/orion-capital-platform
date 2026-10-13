<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;

/**
 * A new lead has been committed with its attribution row.
 *
 * Carries ids rather than models: it is dispatched after the creating transaction commits and
 * may be handled in a queued context with no company bound.
 */
class LeadCaptured
{
    use Dispatchable;

    public function __construct(
        public string $leadId,
        public int $attributionId,
        public int $companyId,
    ) {}
}

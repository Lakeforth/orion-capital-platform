<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Thrown when a tenant plan limit is reached. Renders an "upgrade required"
 * response (HTTP 403) for API/JSON clients so the frontend can prompt, and a
 * Splade toast + redirect back for tenant admin (web) actions.
 */
class PlanLimitExceededException extends Exception
{
    public function __construct(
        public readonly string $limitKey,
        public readonly int $limit,
        public readonly int $used,
        string $message = '',
    ) {
        parent::__construct($message !== '' ? $message : 'Plan limit reached.');
    }

    public function render(Request $request): JsonResponse|Response|null
    {
        // Customer-facing (API/app): the end-user of a tenant cannot upgrade, so
        // keep the "contact support" message resolved at the enforce() site.
        if ($request->is('api/*') || $request->expectsJson()) {
            return new JsonResponse([
                'status' => 'failed',
                'error' => $this->getMessage(),
                'message' => $this->getMessage(),
                'error_type' => 'upgrade_required',
                'data' => [
                    'upgrade_required' => true,
                    'limit_key' => $this->limitKey,
                    'limit' => $this->limit,
                    'used' => $this->used,
                ],
            ], 403);
        }

        // Tenant admin (web): they own the plan, so prompt them to upgrade. Flashed
        // for a SweetAlert dialog rendered in layouts/admin.blade.php (with a native
        // alert() fallback if the library is unavailable).
        session()->flash('plan_limit_alert', [
            'title' => 'Upgrade required',
            'message' => $this->upgradeMessage(),
            'limit_key' => $this->limitKey,
            'limit' => $this->limit,
            'used' => $this->used,
        ]);

        return back();
    }

    /**
     * Upgrade prompt for tenant admins, with the limit label interpolated.
     */
    public function upgradeMessage(): string
    {
        $label = config('plan_limits.labels.'.$this->limitKey, 'plan');

        $template = config(
            'plan_limits.upgrade_message',
            "You've reached your plan's :label limit. Please upgrade your plan to add more.",
        );

        return str_replace(':label', $label, $template);
    }
}

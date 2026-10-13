<?php

namespace App\Services;

use App\Exceptions\PlanLimitExceededException;
use App\Models\Company;

/**
 * Resolves tenant plan limits for a company and enforces them.
 *
 * A limit key resolves through (first defined wins):
 *   1. company.settings['limit_overrides'][$key]   (per-company escape hatch)
 *   2. company.plan.limits[$key]                    (the plan tier's cap)
 *   3. config('plan_limits.defaults.'.$key)         (registry default, unlimited)
 *   4. UNLIMITED
 *
 * Value semantics: self::UNLIMITED (-1) = no cap, 0 = hard block, N = cap.
 */
class PlanLimitService
{
    public const UNLIMITED = -1;

    public function __construct(private ?Company $company = null) {}

    public static function for(?Company $company = null): self
    {
        $company ??= app()->bound('company') ? app('company') : null;

        return new self($company instanceof Company ? $company : null);
    }

    public function company(): ?Company
    {
        return $this->company;
    }

    /**
     * Resolved limit value for a key. -1 means unlimited.
     */
    public function get(string $key): int
    {
        $override = $this->companyOverride($key);
        if ($override !== null) {
            return (int) $override;
        }

        $planLimits = $this->planLimits();
        if (array_key_exists($key, $planLimits) && $planLimits[$key] !== null) {
            return (int) $planLimits[$key];
        }

        $default = config('plan_limits.defaults.'.$key);
        if ($default !== null) {
            return (int) $default;
        }

        return self::UNLIMITED;
    }

    public function isUnlimited(string $key): bool
    {
        return $this->get($key) === self::UNLIMITED;
    }

    /**
     * Remaining allowance given a current usage count, or null when unlimited.
     */
    public function remaining(string $key, int $used): ?int
    {
        if ($this->isUnlimited($key)) {
            return null;
        }

        return max(0, $this->get($key) - $used);
    }

    /**
     * Whether a further unit is blocked given the current usage count.
     * True when used >= cap (i.e. the next create/open would exceed it).
     */
    public function exceeds(string $key, int $used): bool
    {
        if ($this->isUnlimited($key)) {
            return false;
        }

        return $used >= $this->get($key);
    }

    /**
     * Throw an upgrade-required exception when the key's cap is reached.
     */
    public function enforce(string $key, int $used): void
    {
        if ($this->exceeds($key, $used)) {
            throw new PlanLimitExceededException(
                $key,
                $this->get($key),
                $used,
                $this->message($key),
            );
        }
    }

    /**
     * Tenant-facing message for a limit key.
     */
    public function message(string $key): string
    {
        if ($this->company) {
            $override = $this->company->setting('plan_limit_message');
            if (filled($override)) {
                return $override;
            }
        }

        return config('plan_limits.messages.'.$key)
            ?? config('plan_limits.default_message', 'Please contact support.');
    }

    private function companyOverride(string $key)
    {
        if (! $this->company) {
            return null;
        }

        $overrides = $this->company->setting('limit_overrides', []);

        if (! is_array($overrides)) {
            return null;
        }

        return $overrides[$key] ?? null;
    }

    private function planLimits(): array
    {
        $limits = $this->company?->plan?->limits;

        return is_array($limits) ? $limits : [];
    }
}

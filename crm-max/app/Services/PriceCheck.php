<?php

namespace App\Services;

/**
 * Outcome of a single PriceValidator decision. Carries the reason and enough
 * context to log it, so callers never have to re-derive why a tick was
 * dropped.
 */
class PriceCheck
{
    private function __construct(
        public readonly bool $accepted,
        public readonly ?string $reason = null,
        public readonly array $context = [],
        public readonly bool $shouldLog = true,
    ) {}

    public static function accept(): self
    {
        return new self(accepted: true);
    }

    /**
     * @param  bool  $shouldLog  false for outcomes that are expected in normal
     *                           operation (a closed market re-sending its last tick) and would
     *                           only bury the real rejections in noise.
     */
    public static function reject(string $reason, array $context = [], bool $shouldLog = true): self
    {
        return new self(accepted: false, reason: $reason, context: $context, shouldLog: $shouldLog);
    }

    public function rejected(): bool
    {
        return ! $this->accepted;
    }
}

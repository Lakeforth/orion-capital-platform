<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * A KYC write that the attempt's current state does not allow (HTTP 409).
 *
 * `errorType` is what callers branch on; the message is for people. The client API
 * returns kyc_under_review in its errorResponse shape, agreed with the frontend.
 */
class KycConflict extends RuntimeException
{
    public function __construct(string $message, public readonly string $errorType)
    {
        parent::__construct($message);
    }

    public static function underReview(): self
    {
        return new self('Your KYC documents are being reviewed. You can upload again once the review is finished.', 'kyc_under_review');
    }

    public static function awaitingReview(): self
    {
        return new self('A KYC submission is already awaiting review.', 'kyc_awaiting_review');
    }

    public static function stale(): self
    {
        return new self('This KYC changed since you opened it. Refresh and try again.', 'kyc_version_conflict');
    }

    public static function notAllowed(string $message): self
    {
        return new self($message, 'kyc_transition_not_allowed');
    }
}

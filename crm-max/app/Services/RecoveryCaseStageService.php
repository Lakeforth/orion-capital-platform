<?php

namespace App\Services;

use App\Models\BitraceCase;
use App\Models\BitraceCaseActivity;

class RecoveryCaseStageService
{
    public const CASE_OPENED = 'Case Opened';

    public const REQUEST_SUBMITTED = 'Request Submitted';

    public const UNDER_REVIEW = 'Under Review';

    public const INVESTIGATION = 'Investigation';

    public const RECOVERY = 'Recovery';

    public const CLOSED = 'Closed';

    /**
     * Canonical, ordered steps a recovery case moves through. These are the
     * names used to seed a case's phases, which are the single source of
     * truth for progress. REQUEST_SUBMITTED is retained as a constant for
     * historical backfill migrations but is not part of the ordered flow.
     */
    public const STAGES = [
        self::CASE_OPENED,
        self::UNDER_REVIEW,
        self::INVESTIGATION,
        self::RECOVERY,
        self::CLOSED,
    ];

    public function advanceTo(
        BitraceCase $case,
        string $stage,
        string $description,
        string $actor = 'system',
    ): BitraceCase {
        $stage = trim($stage);

        if ($stage === '') {
            throw new \InvalidArgumentException('Recovery case stage cannot be empty.');
        }

        if ($case->current_stage === $stage) {
            return $case;
        }

        $previousStage = $case->current_stage;

        $case->update(['current_stage' => $stage]);

        BitraceCaseActivity::create([
            'case_id' => $case->id,
            'type' => 'stage_change',
            'description' => $description !== ''
                ? $description
                : sprintf(
                    'Stage changed from "%s" to "%s".',
                    $previousStage ?? 'none',
                    $stage,
                ),
            'actor' => $actor,
        ]);

        return $case->fresh();
    }

    public function initialStageForSubmittedCase(): string
    {
        return self::CASE_OPENED;
    }
}

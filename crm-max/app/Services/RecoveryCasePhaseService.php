<?php

namespace App\Services;

use App\Models\BitraceCase;
use App\Models\BitraceCasePhase;

class RecoveryCasePhaseService
{
    public function __construct(
        private readonly EmailService $emailService,
        private readonly RecoveryCaseStageService $stageService,
    ) {}

    /**
     * Make the given phase the active one. Phases are the single source of
     * truth for progress, so activation cascades statuses across the ordered
     * set: earlier phases are marked completed, later phases pending. The
     * case's current_stage is mirrored from the active phase name and a
     * stage_change activity is logged.
     */
    public function activate(BitraceCase $case, BitraceCasePhase $phase, string $actor = 'admin'): BitraceCasePhase
    {
        $now = now();
        $previousStage = $case->current_stage;

        foreach ($case->phases()->get() as $sibling) {
            if ($sibling->id === $phase->id) {
                continue;
            }

            if ($sibling->order < $phase->order) {
                $sibling->update([
                    'is_active' => false,
                    'status' => 'completed',
                    'completed_at' => $sibling->completed_at ?? $now,
                ]);

                continue;
            }

            $sibling->update([
                'is_active' => false,
                'status' => 'pending',
                'completed_at' => null,
            ]);
        }

        $phase->update([
            'is_active' => true,
            'status' => 'active',
            'activated_at' => $phase->activated_at ?? $now,
            'completed_at' => null,
        ]);

        $this->stageService->advanceTo(
            $case,
            $phase->name,
            sprintf('Advanced to "%s".', $phase->name),
            $actor,
        );

        $this->syncStatusToPhase($case, $phase);
        $this->emailRecoveryPhaseIfChanged($case, $phase->name, $previousStage);

        return $phase->fresh();
    }

    /**
     * Keep the case state axis (status) in sync with the flow axis (phases):
     * reaching the Closed phase closes the case, while re-activating an
     * earlier phase reopens work. A withdrawn case is never auto-changed.
     */
    private function syncStatusToPhase(BitraceCase $case, BitraceCasePhase $phase): void
    {
        if ($case->status === 'withdrawn') {
            return;
        }

        $isClosedPhase = strcasecmp($phase->name, RecoveryCaseStageService::CLOSED) === 0;

        if ($isClosedPhase) {
            if ($case->status !== 'closed') {
                $case->update(['status' => 'closed']);
            }

            return;
        }

        if (in_array($case->status, ['open', 'closed'], true)) {
            $case->update(['status' => 'in_progress']);
        }
    }

    private function emailRecoveryPhaseIfChanged(BitraceCase $case, string $phaseName, ?string $previousStage): void
    {
        if ($previousStage === $phaseName) {
            return;
        }

        $case->loadMissing('user');

        if (! $case->user || ! filter_var((string) $case->user->email, FILTER_VALIDATE_EMAIL)) {
            return;
        }

        $this->emailService->sendRecoveryPhaseEnteredEmail($case->user, $case, $phaseName, $previousStage);
    }
}

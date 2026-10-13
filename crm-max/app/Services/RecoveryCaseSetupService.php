<?php

namespace App\Services;

use App\Models\BitraceActionType;
use App\Models\BitraceCase;
use App\Models\BitraceCaseActivity;
use App\Models\BitraceCasePhase;
use App\Models\BitracePhaseAction;

class RecoveryCaseSetupService
{
    /**
     * Client-facing action slugs that live on the first ("Case Opened") phase.
     */
    private const INITIAL_PHASE_ACTION_SLUGS = [
        'submit_wallet_details',
        'test_transfer_request',
    ];

    /**
     * Seed a case with the canonical, ordered phases. Phases are the single
     * source of truth for case progress, so every case is created with the
     * full ordered set: the first phase is active and carries the client's
     * initial actions, the rest are pending.
     */
    public function setupDefaultPhaseAndActions(BitraceCase $case): void
    {
        if ($case->phases()->exists()) {
            return;
        }

        $stages = RecoveryCaseStageService::STAGES;
        $firstPhase = null;

        foreach (array_values($stages) as $index => $name) {
            $order = $index + 1;
            $isActive = $order === 1;

            $phase = BitraceCasePhase::create([
                'case_id' => $case->id,
                'name' => $name,
                'order' => $order,
                'status' => $isActive ? 'active' : 'pending',
                'is_active' => $isActive,
                'activated_at' => $isActive ? now() : null,
            ]);

            if ($isActive) {
                $firstPhase = $phase;
            }
        }

        if ($firstPhase) {
            $this->attachInitialActions($firstPhase);
        }

        BitraceCaseActivity::create([
            'case_id' => $case->id,
            'type' => 'stage_change',
            'description' => 'Case opened. '.RecoveryCaseStageService::CASE_OPENED.' phase activated.',
            'actor' => 'system',
        ]);
    }

    private function attachInitialActions(BitraceCasePhase $phase): void
    {
        foreach (self::INITIAL_PHASE_ACTION_SLUGS as $slug) {
            $actionType = BitraceActionType::where('slug', $slug)->first();

            if (! $actionType) {
                continue;
            }

            BitracePhaseAction::create([
                'phase_id' => $phase->id,
                'action_type_id' => $actionType->id,
                'status' => 'pending',
            ]);
        }
    }
}

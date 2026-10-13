<?php

use App\Models\BitraceCase;
use App\Models\BitraceCasePhase;
use App\Models\BitracePhaseAction;
use App\Services\RecoveryCaseStageService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Normalize every existing case to the canonical, ordered phase set so
     * that phases are the single source of truth for progress. Legacy phases
     * (e.g. "Initial Recovery") are folded into "Case Opened" and removed.
     */
    public function up(): void
    {
        $stages = RecoveryCaseStageService::STAGES;
        $firstStage = $stages[0];

        BitraceCase::withoutGlobalScopes()
            ->with('phases.actions')
            ->chunkById(100, function ($cases) use ($stages, $firstStage) {
                foreach ($cases as $case) {
                    DB::transaction(function () use ($case, $stages, $firstStage) {
                        $this->normalizeCase($case, $stages, $firstStage);
                    });
                }
            });
    }

    public function down(): void
    {
        // Non-reversible data normalization.
    }

    private function normalizeCase(BitraceCase $case, array $stages, string $firstStage): void
    {
        $existingPhases = $case->phases;

        $activeStage = $this->resolveActiveStage($case, $existingPhases, $stages, $firstStage);
        $activeOrder = array_search($activeStage, $stages, true) + 1;

        $canonical = [];

        foreach (array_values($stages) as $index => $name) {
            $order = $index + 1;

            $phase = $existingPhases->first(
                fn ($p) => strtolower(trim($p->name)) === strtolower($name)
            );

            if (! $phase) {
                $phase = BitraceCasePhase::create([
                    'case_id' => $case->id,
                    'name' => $name,
                    'order' => $order,
                    'status' => 'pending',
                    'is_active' => false,
                ]);
            }

            $canonical[$name] = $phase;
        }

        $firstPhase = $canonical[$firstStage];
        $canonicalIds = collect($canonical)->pluck('id')->all();

        foreach ($existingPhases as $phase) {
            if (in_array($phase->id, $canonicalIds, true)) {
                continue;
            }

            BitracePhaseAction::where('phase_id', $phase->id)
                ->update(['phase_id' => $firstPhase->id]);

            $phase->delete();
        }

        $now = now();

        foreach ($stages as $index => $name) {
            $order = $index + 1;
            $phase = $canonical[$name];

            if ($order < $activeOrder) {
                $phase->update([
                    'order' => $order,
                    'is_active' => false,
                    'status' => 'completed',
                    'activated_at' => $phase->activated_at ?? $now,
                    'completed_at' => $phase->completed_at ?? $now,
                ]);
            } elseif ($order === $activeOrder) {
                $phase->update([
                    'order' => $order,
                    'is_active' => true,
                    'status' => 'active',
                    'activated_at' => $phase->activated_at ?? $now,
                    'completed_at' => null,
                ]);
            } else {
                $phase->update([
                    'order' => $order,
                    'is_active' => false,
                    'status' => 'pending',
                    'activated_at' => null,
                    'completed_at' => null,
                ]);
            }
        }

        if ($case->current_stage !== $activeStage) {
            $case->update(['current_stage' => $activeStage]);
        }
    }

    private function resolveActiveStage(
        BitraceCase $case,
        $existingPhases,
        array $stages,
        string $firstStage,
    ): string {
        if (in_array($case->current_stage, $stages, true)) {
            return $case->current_stage;
        }

        $activePhase = $existingPhases->firstWhere('is_active', true);

        if ($activePhase) {
            foreach ($stages as $name) {
                if (str_contains(strtolower($activePhase->name), strtolower($name))
                    || str_contains(strtolower($name), strtolower($activePhase->name))
                ) {
                    return $name;
                }
            }
        }

        return $firstStage;
    }
};

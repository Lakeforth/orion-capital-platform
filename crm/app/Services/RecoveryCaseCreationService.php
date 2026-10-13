<?php

namespace App\Services;

use App\Exceptions\ActiveRecoveryCaseExistsException;
use App\Models\BitraceCase;
use App\Models\BitraceCaseActivity;
use App\Models\User;

class RecoveryCaseCreationService
{
    public function __construct(
        private readonly EmailService $emailService,
        private readonly RecoveryCaseStageService $stageService,
        private readonly RecoveryCaseSetupService $setupService,
    ) {}

    public function createForUser(
        User $user,
        string $title,
        ?string $notes = null,
        string $activityDescription = 'Recovery case opened.',
        string $actor = 'client',
        ?string $assignedManagerId = null,
        bool $enforceSingleActiveCase = true,
    ): BitraceCase {
        $company = $user->company;

        if (! $company) {
            throw new \RuntimeException('User does not belong to a company.');
        }

        if ($enforceSingleActiveCase && BitraceCase::userHasActiveCase($user->id, $company->id)) {
            throw new ActiveRecoveryCaseExistsException;
        }

        $manager = $this->resolveManager($company->id, $assignedManagerId);

        $case = BitraceCase::withoutGlobalScopes()->create([
            'company_id' => $company->id,
            'user_id' => $user->id,
            'manager_id' => $manager?->id,
            'assigned_manager_id' => $manager?->id,
            'case_number' => BitraceCase::generateCaseNumber($company->id),
            'title' => $title,
            'status' => 'open',
            'current_stage' => $this->stageService->initialStageForSubmittedCase(),
            'notes' => $notes,
        ]);

        BitraceCaseActivity::create([
            'case_id' => $case->id,
            'type' => 'stage_change',
            'description' => $activityDescription,
            'actor' => $actor,
        ]);

        $this->setupService->setupDefaultPhaseAndActions($case);

        $this->emailRecoveryPhaseIfPossible($case, $this->stageService->initialStageForSubmittedCase());

        return $case->fresh(['phases.actions.actionType']);
    }

    public function createForUserByAdmin(
        User $user,
        string $title,
        ?string $notes = null,
        ?string $assignedManagerId = null,
        string $actor = 'admin',
    ): BitraceCase {
        return $this->createForUser(
            $user,
            $title,
            $notes,
            'Case opened.',
            $actor,
            $assignedManagerId,
            enforceSingleActiveCase: false,
        );
    }

    private function resolveManager(int $companyId, ?string $assignedManagerId): ?User
    {
        if ($assignedManagerId) {
            $selected = User::withoutGlobalScopes()
                ->where('id', $assignedManagerId)
                ->where('company_id', $companyId)
                ->whereHas('roles', fn ($q) => $q->whereIn('name', ['admin', 'manager']))
                ->first();

            if ($selected) {
                return $selected;
            }
        }

        return User::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->whereHas('roles', fn ($q) => $q->whereIn('name', ['admin', 'manager']))
            ->where('status', 'active')
            ->first();
    }

    private function emailRecoveryPhaseIfPossible(BitraceCase $case, string $phaseName): void
    {
        $case->loadMissing('user');

        if (! $case->user || ! filter_var((string) $case->user->email, FILTER_VALIDATE_EMAIL)) {
            return;
        }

        $this->emailService->sendRecoveryPhaseEnteredEmail($case->user, $case, $phaseName);
    }
}

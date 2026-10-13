<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BitraceActionType;
use App\Models\BitraceCase;
use App\Models\BitraceCaseActivity;
use App\Models\BitraceCasePhase;
use App\Models\BitracePhaseAction;
use App\Models\BitraceTransferRequest;
use App\Models\User;
use App\Services\NotificationService;
use App\Services\RecoveryCaseActivityService;
use App\Services\RecoveryCasePhaseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use ProtoneMedia\Splade\Facades\Toast;

class RecoveryController extends Controller
{
    public function listActionTypes(): JsonResponse
    {
        $actionTypes = BitraceActionType::orderBy('name')->get();

        return $this->successResponse('Action types retrieved', $actionTypes);
    }

    public function createPhase(Request $request, string $caseId): JsonResponse
    {
        $case = $this->findCase($caseId);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'order' => ['nullable', 'integer', 'min:0'],
        ]);

        $phase = BitraceCasePhase::create([
            'case_id' => $case->id,
            'name' => $validated['name'],
            'order' => $validated['order'] ?? 0,
            'status' => 'pending',
        ]);

        return $this->successResponse('Phase created', $phase, 201);
    }

    /**
     * Advance the case to the next phase in order. Progression is strictly
     * sequential - staff cannot jump ahead or skip steps - so the case flow
     * stays consistent with the client-facing progress bar.
     */
    public function advancePhase(Request $request, string $caseId): JsonResponse|RedirectResponse
    {
        $case = $this->findCase($caseId);

        $phases = $case->phases()->orderBy('order')->get();
        $currentOrder = $phases->firstWhere('is_active', true)?->order ?? 0;
        $next = $phases->first(fn (BitraceCasePhase $phase) => $phase->order > $currentOrder);

        if (! $next) {
            return $this->finishAdminAction($request, 'Case is already at the final phase.');
        }

        $admin = auth()->user();

        app(RecoveryCasePhaseService::class)->activate($case, $next, $admin->name ?? 'admin');

        $case->loadMissing('user');
        if ($case->user) {
            app(NotificationService::class)->sendRecoveryAdminActionNotification(
                $case->user,
                'Case Update',
                sprintf('Your case has advanced to the %s stage.', $next->name),
            );
        }

        return $this->finishAdminAction($request, 'Advanced to '.$next->name.'.', $next->fresh());
    }

    public function updatePhase(Request $request, string $caseId, string $phaseId): JsonResponse|RedirectResponse
    {
        $case = $this->findCase($caseId);
        $phase = $this->findPhase($case->id, $phaseId);

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'order' => ['sometimes', 'integer', 'min:0'],
            'status' => ['sometimes', 'in:pending,active,completed'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $activating = isset($validated['is_active']) && $validated['is_active'];
        unset($validated['is_active']);

        if ($activating) {
            $validated['status'] = 'active';
        }

        if (! empty($validated)) {
            $phase->update($validated);
        }

        if ($activating) {
            $admin = auth()->user();
            app(RecoveryCasePhaseService::class)->activate(
                $case,
                $phase->fresh(),
                $admin->name ?? 'admin',
            );
        }

        return $this->finishAdminAction($request, 'Phase updated.', $phase->fresh());
    }

    public function deletePhase(Request $request, string $caseId, string $phaseId): JsonResponse|RedirectResponse
    {
        $case = $this->findCase($caseId);
        $phase = $this->findPhase($case->id, $phaseId);

        $phase->delete();

        return $this->finishAdminAction($request, 'Phase deleted.');
    }

    public function addAction(Request $request, string $caseId, string $phaseId): JsonResponse|RedirectResponse
    {
        $case = $this->findCase($caseId);
        $phase = $this->findPhase($case->id, $phaseId);

        $validated = $request->validate([
            'action_type_id' => ['required', 'uuid', 'exists:bitrace_action_types,id'],
        ]);

        $action = BitracePhaseAction::create([
            'phase_id' => $phase->id,
            'action_type_id' => $validated['action_type_id'],
            'status' => 'pending',
        ]);

        return $this->finishAdminAction($request, 'Action added.', $action->load('actionType'), 201);
    }

    public function removeAction(Request $request, string $caseId, string $phaseId, string $actionId): JsonResponse|RedirectResponse
    {
        $case = $this->findCase($caseId);
        $phase = $this->findPhase($case->id, $phaseId);
        $action = BitracePhaseAction::where('phase_id', $phase->id)->findOrFail($actionId);

        $action->delete();

        return $this->finishAdminAction($request, 'Action removed.');
    }

    public function updateActionStatus(Request $request, string $caseId, string $phaseId, string $actionId): JsonResponse|RedirectResponse
    {
        $case = $this->findCase($caseId);
        $phase = $this->findPhase($case->id, $phaseId);
        $action = BitracePhaseAction::where('phase_id', $phase->id)->findOrFail($actionId);

        $validated = $request->validate([
            'status' => ['required', 'in:pending,completed,skipped'],
        ]);

        $action->loadMissing('actionType');
        $action->update(['status' => $validated['status']]);

        $description = $this->actionStatusDescription($action, $validated['status']);
        $admin = auth()->user();

        app(RecoveryCaseActivityService::class)->logAdminAction(
            $case,
            $description,
            $admin->name ?? 'admin',
        );

        $case->loadMissing('user');
        if ($case->user) {
            app(NotificationService::class)->sendRecoveryAdminActionNotification(
                $case->user,
                'Case Update',
                $description,
            );
        }

        return $this->finishAdminAction($request, 'Action status updated.', $action->fresh()->load('actionType'));
    }

    public function listTransferRequests(string $caseId): JsonResponse
    {
        $case = $this->findCase($caseId);

        $transferRequests = BitraceTransferRequest::where('case_id', $case->id)
            ->with(['phaseAction.actionType'])
            ->latest()
            ->get();

        return $this->successResponse('Transfer requests retrieved', $transferRequests);
    }

    public function updateTransferRequest(Request $request, string $caseId, string $transferId): JsonResponse|RedirectResponse
    {
        $case = $this->findCase($caseId);

        $transfer = BitraceTransferRequest::where('case_id', $case->id)
            ->findOrFail($transferId);

        $validated = $request->validate([
            'status' => ['required', 'in:pending,initiated,completed,rejected'],
        ]);

        $transfer->loadMissing('phaseAction.actionType');
        $transfer->update(['status' => $validated['status']]);

        $description = $this->transferStatusDescription($transfer, $validated['status']);
        $admin = auth()->user();

        app(RecoveryCaseActivityService::class)->logAdminAction(
            $case,
            $description,
            $admin->name ?? 'admin',
        );

        if ($validated['status'] === 'rejected' && $transfer->phaseAction) {
            $transfer->phaseAction->update(['status' => 'pending']);
        }

        $case->loadMissing('user');
        if ($case->user) {
            app(NotificationService::class)->sendRecoveryAdminActionNotification(
                $case->user,
                'Transfer Update',
                $description,
            );
        }

        return $this->finishAdminAction(
            $request,
            'Transfer request updated.',
            $transfer->fresh()->load('phaseAction.actionType'),
        );
    }

    public function reassignCase(Request $request, string $caseId): JsonResponse|RedirectResponse
    {
        $case = $this->findCase($caseId);

        $validated = $request->validate([
            'assigned_manager_id' => ['required', 'string', 'exists:users,id'],
        ]);

        $companyId = auth()->user()->company_id;

        $manager = User::withoutGlobalScopes()
            ->where('id', $validated['assigned_manager_id'])
            ->where('company_id', $companyId)
            ->first();

        if (! $manager) {
            return $this->errorResponse('Manager not found', 404);
        }

        $case->update([
            'assigned_manager_id' => $manager->id,
            'manager_id' => $manager->id,
        ]);

        BitraceCaseActivity::create([
            'case_id' => $case->id,
            'type' => 'stage_change',
            'description' => sprintf(
                'Case reassigned to %s %s.',
                $manager->first_name,
                $manager->last_name
            ),
            'actor' => auth()->user()->name ?? 'admin',
        ]);

        return $this->finishAdminAction($request, 'Case reassigned.', $case->fresh()->load('assignedManager'));
    }

    private function finishAdminAction(
        Request $request,
        string $message,
        mixed $data = null,
        int $jsonStatus = 200,
    ): JsonResponse|RedirectResponse {
        // Web (Splade) requests are the named admin.* routes and must receive a
        // redirect + Toast so the page re-renders with fresh state. Splade sends
        // an Accept: application/json header, so expectsJson() cannot be used to
        // distinguish them from the unnamed API routes.
        if (! $request->routeIs('admin.*')) {
            return $this->successResponse($message, $data, $jsonStatus);
        }

        Toast::title($message);

        return redirect()->back();
    }

    private function findCase(string $caseId): BitraceCase
    {
        $companyId = auth()->user()->company_id;

        return BitraceCase::withoutGlobalScope('company')
            ->where('id', $caseId)
            ->where('company_id', $companyId)
            ->firstOrFail();
    }

    private function findPhase(string $caseId, string $phaseId): BitraceCasePhase
    {
        return BitraceCasePhase::where('case_id', $caseId)
            ->findOrFail($phaseId);
    }

    private function actionStatusDescription(BitracePhaseAction $action, string $status): string
    {
        $slug = $action->actionType?->slug;
        $label = match ($slug) {
            'submit_wallet_details' => 'Wallet address',
            'test_transfer_request' => 'Test transfer',
            default => $action->actionType?->name ?? 'Action',
        };

        return match ($status) {
            'completed' => "{$label} approved.",
            'skipped' => "{$label} skipped.",
            'pending' => "{$label} returned to pending.",
            default => "{$label} status updated to {$status}.",
        };
    }

    private function transferStatusDescription(BitraceTransferRequest $transfer, string $status): string
    {
        return match ($status) {
            'initiated' => 'Test transfer initiated.',
            'completed' => 'Test transfer completed.',
            'rejected' => 'Test transfer rejected. You may submit a new request.',
            'pending' => 'Test transfer returned to pending.',
            default => "Test transfer status updated to {$status}.",
        };
    }
}

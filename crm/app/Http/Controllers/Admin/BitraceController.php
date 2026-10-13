<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BitraceActionType;
use App\Models\BitraceCase;
use App\Models\BitraceCaseActivity;
use App\Models\User;
use App\Services\RecoveryCaseCreationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use ProtoneMedia\Splade\Facades\Toast;

class BitraceController extends Controller
{
    public function casesByUser(Request $request, int $userId): JsonResponse
    {
        $companyId = auth()->user()->company_id;

        $user = User::withoutGlobalScopes()
            ->where('id', $userId)
            ->where('company_id', $companyId)
            ->firstOrFail();

        $cases = BitraceCase::withoutGlobalScope('company')
            ->where('company_id', $companyId)
            ->where('user_id', $user->id)
            ->with(['assignedManager:id,first_name,last_name'])
            ->latest()
            ->get();

        return $this->successResponse('Cases retrieved', $cases);
    }

    public function show(string $id): JsonResponse
    {
        $case = $this->findCase($id);
        $case->load($this->caseDetailRelations());

        return $this->successResponse('Case retrieved', $case);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => ['required', 'string', 'exists:users,id'],
            'title' => ['required', 'string', 'max:255'],
            'assigned_manager_id' => ['nullable', 'string', 'exists:users,id'],
            'manager_id' => ['nullable', 'string', 'exists:users,id'],
            'notes' => ['nullable', 'string'],
        ]);

        $companyId = auth()->user()->company_id;

        $user = User::withoutGlobalScopes()
            ->where('id', $validated['user_id'])
            ->where('company_id', $companyId)
            ->firstOrFail();

        $managerId = $validated['assigned_manager_id'] ?? $validated['manager_id'] ?? null;

        $case = app(RecoveryCaseCreationService::class)->createForUserByAdmin(
            $user,
            $validated['title'],
            $validated['notes'] ?? null,
            $managerId,
            auth()->user()->name ?? 'admin',
        );

        return $this->successResponse('Case created', $case, 201);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $case = $this->findCase($id);

        $validated = $request->validate([
            'title' => ['sometimes', 'string', 'max:255'],
            'status' => ['sometimes', 'in:open,in_progress,completed,closed,withdrawn'],
            'manager_id' => ['sometimes', 'nullable', 'string', 'exists:users,id'],
            'assigned_manager_id' => ['sometimes', 'nullable', 'string', 'exists:users,id'],
            'eth_wallet_address' => ['sometimes', 'nullable', 'string', 'max:255'],
            'notes' => ['sometimes', 'nullable', 'string'],
        ]);

        if (isset($validated['assigned_manager_id'])) {
            $validated['manager_id'] = $validated['assigned_manager_id'];
        } elseif (isset($validated['manager_id'])) {
            $validated['assigned_manager_id'] = $validated['manager_id'];
        }

        $case->update($validated);

        return $this->successResponse('Case updated', $case->fresh());
    }

    public function destroy(string $id): JsonResponse
    {
        $case = $this->findCase($id);
        $case->delete();

        return $this->successResponse('Case deleted');
    }

    public function addActivity(Request $request, string $id): JsonResponse
    {
        $case = $this->findCase($id);

        $validated = $request->validate([
            'type' => ['required', 'string', 'in:stage_change,message,document_upload'],
            'description' => ['required', 'string'],
        ]);

        $activity = BitraceCaseActivity::create([
            'case_id' => $case->id,
            'type' => $validated['type'],
            'description' => $validated['description'],
            'actor' => auth()->user()->name ?? 'admin',
        ]);

        return $this->successResponse('Activity added', $activity, 201);
    }

    public function tabContent(int|string $userId): View
    {
        $companyId = auth()->user()->company_id;

        $user = User::withoutGlobalScopes()
            ->where('id', $userId)
            ->where('company_id', $companyId)
            ->firstOrFail();

        $cases = BitraceCase::withoutGlobalScope('company')
            ->where('company_id', $companyId)
            ->where('user_id', $user->id)
            ->with(['assignedManager:id,first_name,last_name'])
            ->latest()
            ->get();

        return view('admin.users.bitrace_cases', compact('user', 'cases'));
    }

    public function createForm(int|string $userId): View
    {
        $companyId = auth()->user()->company_id;

        $user = User::withoutGlobalScopes()
            ->where('id', $userId)
            ->where('company_id', $companyId)
            ->firstOrFail();

        $managers = $this->companyManagers($companyId);

        return view('admin.users.bitrace_case_create', compact('user', 'managers'));
    }

    public function storeCase(Request $request, int|string $userId): RedirectResponse
    {
        $companyId = auth()->user()->company_id;

        $user = User::withoutGlobalScopes()
            ->where('id', $userId)
            ->where('company_id', $companyId)
            ->firstOrFail();

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'assigned_manager_id' => ['nullable', 'string', 'exists:users,id'],
            'manager_id' => ['nullable', 'string', 'exists:users,id'],
            'notes' => ['nullable', 'string'],
        ]);

        $managerId = $validated['assigned_manager_id'] ?? $validated['manager_id'] ?? null;

        $case = app(RecoveryCaseCreationService::class)->createForUserByAdmin(
            $user,
            $validated['title'],
            $validated['notes'] ?? null,
            $managerId,
            auth()->user()->name ?? 'admin',
        );

        Toast::title('Case '.$case->case_number.' opened successfully.');

        return redirect()->back();
    }

    public function showCase(int|string $userId, string $caseId): View
    {
        $companyId = auth()->user()->company_id;

        $user = User::withoutGlobalScopes()
            ->where('id', $userId)
            ->where('company_id', $companyId)
            ->firstOrFail();

        $case = BitraceCase::withoutGlobalScope('company')
            ->where('company_id', $companyId)
            ->where('user_id', $user->id)
            ->where('id', $caseId)
            ->firstOrFail();

        $case->load($this->caseDetailRelations());

        $managers = $this->companyManagers($companyId);
        $actionTypes = BitraceActionType::orderBy('name')->get();
        $activePhase = $case->phases->firstWhere('is_active', true);

        return view('admin.users.bitrace_case_show', compact(
            'user',
            'case',
            'managers',
            'actionTypes',
            'activePhase',
        ));
    }

    public function destroyCase(int|string $userId, string $caseId): RedirectResponse
    {
        $companyId = auth()->user()->company_id;

        User::withoutGlobalScopes()
            ->where('id', $userId)
            ->where('company_id', $companyId)
            ->firstOrFail();

        $case = BitraceCase::withoutGlobalScope('company')
            ->where('company_id', $companyId)
            ->where('user_id', $userId)
            ->where('id', $caseId)
            ->firstOrFail();

        $caseNumber = $case->case_number;
        $case->delete();

        Toast::title("Case {$caseNumber} deleted.");

        return redirect()->to(route('admin.users.show', $userId).'?tab=recovery');
    }

    private function findCase(string $id): BitraceCase
    {
        $companyId = auth()->user()->company_id;

        return BitraceCase::withoutGlobalScope('company')
            ->where('id', $id)
            ->where('company_id', $companyId)
            ->firstOrFail();
    }

    private function caseDetailRelations(): array
    {
        return [
            'user:id,first_name,last_name,email',
            'assignedManager:id,first_name,last_name',
            'phases' => fn ($q) => $q->orderBy('order')->with('actions.actionType'),
            'transferRequests' => fn ($q) => $q->latest()->with('phaseAction.actionType'),
            'activities' => fn ($q) => $q->latest()->limit(50),
        ];
    }

    private function companyManagers(int $companyId)
    {
        return User::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->whereHas('roles', fn ($q) => $q->whereIn('name', ['admin', 'manager']))
            ->select('id', 'first_name', 'last_name')
            ->get();
    }
}

<?php

use App\Models\BitraceCase;
use App\Services\RecoveryCaseStageService;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        BitraceCase::withoutGlobalScopes()
            ->whereIn('current_stage', [
                RecoveryCaseStageService::REQUEST_SUBMITTED,
                RecoveryCaseStageService::UNDER_REVIEW,
            ])
            ->update(['current_stage' => RecoveryCaseStageService::CASE_OPENED]);
    }

    public function down(): void
    {
        // No-op: cannot reliably restore prior placeholder labels.
    }
};

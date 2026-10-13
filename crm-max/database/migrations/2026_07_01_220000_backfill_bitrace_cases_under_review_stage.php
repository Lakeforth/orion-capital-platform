<?php

use App\Models\BitraceCase;
use App\Services\RecoveryCaseStageService;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        BitraceCase::withoutGlobalScopes()
            ->where('current_stage', RecoveryCaseStageService::REQUEST_SUBMITTED)
            ->update(['current_stage' => RecoveryCaseStageService::UNDER_REVIEW]);
    }

    public function down(): void
    {
        BitraceCase::withoutGlobalScopes()
            ->where('current_stage', RecoveryCaseStageService::UNDER_REVIEW)
            ->update(['current_stage' => RecoveryCaseStageService::REQUEST_SUBMITTED]);
    }
};

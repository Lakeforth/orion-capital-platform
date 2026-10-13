<?php

use App\Models\BitraceCase;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        BitraceCase::withoutGlobalScopes()
            ->where(function ($query) {
                $query->whereNull('current_stage')
                    ->orWhere('current_stage', '');
            })
            ->update(['current_stage' => 'Under Review']);
    }

    public function down(): void
    {
        // No-op: cannot reliably restore previous null values.
    }
};

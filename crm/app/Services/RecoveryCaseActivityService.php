<?php

namespace App\Services;

use App\Models\BitraceCase;
use App\Models\BitraceCaseActivity;

class RecoveryCaseActivityService
{
    public const TYPE_SUBMISSION = 'submission';

    public const TYPE_ADMIN_UPDATE = 'admin_update';

    public function logClientSubmission(BitraceCase $case, string $description, string $actor = 'client'): BitraceCaseActivity
    {
        return $this->log($case, self::TYPE_SUBMISSION, $description, $actor);
    }

    public function logAdminAction(BitraceCase $case, string $description, string $actor = 'admin'): BitraceCaseActivity
    {
        return $this->log($case, self::TYPE_ADMIN_UPDATE, $description, $actor);
    }

    private function log(BitraceCase $case, string $type, string $description, string $actor): BitraceCaseActivity
    {
        return BitraceCaseActivity::create([
            'case_id' => $case->id,
            'type' => $type,
            'description' => $description,
            'actor' => $actor,
        ]);
    }
}

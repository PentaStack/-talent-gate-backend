<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\User;

class ActivityLogService
{
    public function record(
        string $action,
        ?User $user = null,
        ?string $subjectType = null,
        ?int $subjectId = null,
        array $metadata = [],
    ): ActivityLog {
        return ActivityLog::create([
            'user_id' => $user?->id,
            'action' => $action,
            'subject_type' => $subjectType,
            'subject_id' => $subjectId,
            'metadata' => $metadata ?: null,
        ]);
    }
}

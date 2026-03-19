<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;

class ActivityLogger
{
    /**
     * Log an activity.
     *
     * @param int|null $userId
     * @param string $action
     * @param string $description
     * @param string|null $ipAddress
     * @return ActivityLog
     */
    public static function log(?int $userId, string $action, string $description, ?string $ipAddress = null): ActivityLog
    {
        return ActivityLog::create([
            'user_id' => $userId ?? Auth::id(),
            'action' => $action,
            'description' => $description,
            'ip_address' => $ipAddress ?? request()->ip(),
        ]);
    }
}

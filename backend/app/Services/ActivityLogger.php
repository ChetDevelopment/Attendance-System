<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\User;

class ActivityLogger
{
    public static function log(int $userId, string $action, string $description = null, ?string $ip = null): ActivityLog
    {
        $userName = null;
        try {
            $user = User::find($userId);
            $userName = $user?->name;
        } catch (\Exception $e) {
            $userName = null;
        }

        return ActivityLog::create([
            'user_id' => $userId,
            'user_name' => $userName,
            'action' => strtoupper($action),
            'description' => $description,
            'ip_address' => $ip,
        ]);
    }
}

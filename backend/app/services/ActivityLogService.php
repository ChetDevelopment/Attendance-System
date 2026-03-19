<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\Request;

class ActivityLogService
{
    public function record(?User $user, string $action, ?string $description = null, ?string $ipAddress = null): ?ActivityLog
    {
        if (!$user) {
            return null;
        }

        return ActivityLog::create([
            'user_id' => $user->id,
            'action' => $action,
            'description' => $description,
            'ip_address' => $ipAddress,
        ]);
    }

    public function recordFromRequest(?User $user, Request $request, string $action, ?string $description = null): ?ActivityLog
    {
        return $this->record($user, $action, $description, $request->ip());
    }
}

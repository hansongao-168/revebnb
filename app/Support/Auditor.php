<?php

namespace App\Support;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class Auditor
{
    public static function record(?string $actorType, ?int $actorId, string $action, ?Model $subject = null, array $properties = []): void
    {
        AuditLog::query()->create([
            'actor_type' => $actorType,
            'actor_id' => $actorId,
            'action' => $action,
            'subject_type' => $subject ? $subject::class : null,
            'subject_id' => $subject?->getKey(),
            'properties' => $properties === [] ? null : $properties,
            'ip_address' => Request::ip(),
        ]);
    }

    public static function recordFromGuard(string $guard, string $action, ?Model $subject = null, array $properties = []): void
    {
        $user = Auth::guard($guard)->user();
        if ($user instanceof Model) {
            self::record($user::class, (int) $user->getKey(), $action, $subject, $properties);

            return;
        }

        self::record(null, null, $action, $subject, $properties);
    }
}

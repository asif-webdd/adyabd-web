<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class ActivityLogger
{
    /**
     * Log any model event with full context.
     */
    public static function log(
        Model   $subject,
        string  $event,
        ?string $action      = null,
        array   $properties  = [],
        ?string $causerType  = null,
        ?int    $causerId    = null,
        ?string $causerName  = null,
    ): void {
        // Resolve causer from whichever guard is active
        if (! $causerId) {
            foreach (['admin', 'web', 'api'] as $guard) {
                if (Auth::guard($guard)->check()) {
                    $user       = Auth::guard($guard)->user();
                    $causerId   = $user->getKey();
                    $causerType = get_class($user);
                    $causerName = $user->name ?? $user->email ?? $user->phone ?? "#{$causerId}";
                    break;
                }
            }
        }

        ActivityLog::create([
            'log_name'     => strtolower(class_basename($subject)),
            'description'  => $action ?? ($event . ' ' . class_basename($subject)),
            'event'        => $event,
            'action'       => $action,
            'subject_type' => get_class($subject),
            'subject_id'   => $subject->getKey(),
            'causer_type'  => $causerType,
            'causer_id'    => $causerId,
            'causer_name'  => $causerName,
            'ip_address'   => Request::ip(),
            'user_agent'   => Request::userAgent(),
            'properties'   => $properties ?: null,
            'created_at'   => now(),
        ]);
    }

    /**
     * Log a manual event (login, logout, custom action).
     */
    public static function logManual(
        string  $logName,
        string  $event,
        string  $action,
        ?Model  $subject    = null,
        array   $properties = [],
    ): void {
        $causerId   = null;
        $causerType = null;
        $causerName = 'System';

        foreach (['admin', 'web', 'api'] as $guard) {
            if (Auth::guard($guard)->check()) {
                $user       = Auth::guard($guard)->user();
                $causerId   = $user->getKey();
                $causerType = get_class($user);
                $causerName = $user->name ?? $user->email ?? $user->phone ?? "#{$causerId}";
                break;
            }
        }

        ActivityLog::create([
            'log_name'     => $logName,
            'description'  => $action,
            'event'        => $event,
            'action'       => $action,
            'subject_type' => $subject ? get_class($subject) : null,
            'subject_id'   => $subject?->getKey(),
            'causer_type'  => $causerType,
            'causer_id'    => $causerId,
            'causer_name'  => $causerName,
            'ip_address'   => Request::ip(),
            'user_agent'   => Request::userAgent(),
            'properties'   => $properties ?: null,
            'created_at'   => now(),
        ]);
    }
}

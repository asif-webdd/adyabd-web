<?php

namespace App\Support;

use Illuminate\Support\Facades\{Cache, DB, Schema};

/** Shows a warning banner in the admin panel when background jobs would silently pile up. */
class QueueHealth
{
    public static function banner(): string
    {
        try {
            if (config('queue.default') === 'sync') {
                return '';
            }
            $problems = [];

            $beat = Cache::get('scheduler_heartbeat');
            if (!$beat || now()->timestamp - $beat > 300) {
                $problems[] = 'The Laravel scheduler is not running. Add this cron line on the server: '
                    . '<code>* * * * * cd ' . base_path() . ' &amp;&amp; php artisan schedule:run &gt;&gt; /dev/null 2&gt;&amp;1</code>';
            }

            if (config('queue.default') === 'database' && Schema::hasTable('jobs')) {
                $stuck = DB::table('jobs')->where('created_at', '<', now()->subMinutes(10)->timestamp)->count();
                if ($stuck > 0) {
                    $problems[] = "{$stuck} background job(s) have been waiting for more than 10 minutes (bulk notifications / block notices not sent yet).";
                }
            }
            if (Schema::hasTable('failed_jobs') && ($f = DB::table('failed_jobs')->count()) > 0) {
                $problems[] = "{$f} background job(s) failed. Check storage/logs or run <code>php artisan queue:failed</code>.";
            }

            if (!$problems) {
                return '';
            }
            return '<div style="margin-bottom:1rem;padding:.75rem 1rem;border-radius:.5rem;background:#fef3c7;color:#92400e;font-size:.875rem">'
                . '<strong>Background jobs:</strong><ul style="margin:.25rem 0 0 1.25rem;list-style:disc"><li>'
                . implode('</li><li>', $problems) . '</li></ul></div>';
        } catch (\Throwable $e) {
            return '';
        }
    }
}

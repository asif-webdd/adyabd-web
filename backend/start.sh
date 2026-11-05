#!/bin/sh
# Start script for the live server (used as the app's "Start command":  sh start.sh)
# Runs the one-time/idempotent setup, warms caches, starts the scheduler and then the web server.
set -e
php artisan shop:prod-setup
php artisan storage:link --force || true
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan filament:cache-components || true
# Cron replacement: runs the scheduled jobs (auto-blocks, courier status, daily report, queue drain) in the background
php artisan schedule:work > /dev/null 2>&1 &
# Built-in PHP server, 2 workers, with upload limits raised (PHP's default is only 2 MB per file).
# Same thing `php artisan serve` does, but lets us pass -d settings. Listens on $PORT (default 8080).
export PHP_CLI_SERVER_WORKERS="${PHP_CLI_SERVER_WORKERS:-2}"
cd public
exec php -d upload_max_filesize=20M -d post_max_size=24M -d memory_limit=256M \
  -S "0.0.0.0:${PORT:-8080}" ../vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php
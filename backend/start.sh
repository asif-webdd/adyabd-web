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
# Built-in PHP server with 2 workers (small server). Listens on $PORT (default 8080).
export PHP_CLI_SERVER_WORKERS="${PHP_CLI_SERVER_WORKERS:-2}"
exec php artisan serve --host=0.0.0.0 --port="${PORT:-8080}" --no-reload
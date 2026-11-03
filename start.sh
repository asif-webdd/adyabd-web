#!/usr/bin/env bash
# Starts the Laravel API (port 8000) and the Nuxt website (port 3000). Ctrl+C stops both.
cd "$(dirname "$0")"
trap 'kill 0' EXIT INT TERM
( cd backend  && php artisan serve --port=8000 ) &
( cd frontend && npm run dev ) &
wait

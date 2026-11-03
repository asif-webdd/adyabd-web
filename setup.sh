#!/usr/bin/env bash
# One-command setup for macOS / Linux.   Usage:  ./setup.sh     then:  ./start.sh
set -e
cd "$(dirname "$0")"

say()  { printf "\n\033[1;32m==> %s\033[0m\n" "$1"; }
fail() { printf "\n\033[1;31mERROR: %s\033[0m\n" "$1"; exit 1; }

say "Checking tools"
command -v php      >/dev/null || fail "PHP not found. Install PHP 8.2+ (macOS: brew install php)."
command -v composer >/dev/null || fail "Composer not found. Install from https://getcomposer.org"
command -v node     >/dev/null || fail "Node.js not found. Install Node 20.19+ or 22+ (https://nodejs.org)."
php -r 'exit(version_compare(PHP_VERSION,"8.2.0",">=")?0:1);' || fail "PHP 8.2 or newer is required (you have $(php -r 'echo PHP_VERSION;'))."
node -e 'const [a,b]=process.versions.node.split(".").map(Number); process.exit((a===20&&b>=19)||a>=22?0:1)' \
  || fail "Node 20.19+ or 22+ is required (you have $(node -v)). Use nvm: nvm install 22"
for ext in mbstring intl gd curl zip pdo_sqlite; do
  php -m | grep -qi "^$ext$" || fail "PHP extension '$ext' is missing. (macOS Homebrew PHP has them all; on Linux: sudo apt install php-$ext)"
done

say "Backend: installing PHP packages"
cd backend
composer install --no-interaction --prefer-dist
[ -f .env ] || cp .env.example .env
mkdir -p database storage/framework/{cache,sessions,views} storage/logs bootstrap/cache
[ -f database/database.sqlite ] || touch database/database.sqlite
grep -q '^APP_KEY=base64' .env || php artisan key:generate --force

say "Backend: tables + demo data (first run) / only new migrations (existing database)"
php artisan shop:setup
php artisan storage:link >/dev/null 2>&1 || true
php artisan optimize:clear >/dev/null 2>&1 || true
cd ..

say "Frontend: installing packages"
cd frontend
[ -f .env ] || cp .env.example .env
npm install --no-audit --no-fund
cd ..

cat <<MSG

\033[1;32mAll set!\033[0m  Start both servers with:   ./start.sh

  Website : http://localhost:3000
  Admin   : http://localhost:8000/admin     (admin@bazar.com.bd / Admin@12345 – from backend/.env)
  Demo customer : 01711000001 / Password@123

MSG

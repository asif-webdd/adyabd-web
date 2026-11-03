# One-command setup for Windows (PowerShell).   Usage:  .\setup.ps1     then:  .\start.ps1
$ErrorActionPreference = 'Stop'
Set-Location $PSScriptRoot
function Fail($m) { Write-Host "ERROR: $m" -ForegroundColor Red; exit 1 }
function Say($m)  { Write-Host "`n==> $m" -ForegroundColor Green }

Say 'Checking tools'
if (-not (Get-Command php -ErrorAction SilentlyContinue))      { Fail 'PHP not found. Install PHP 8.2+ (XAMPP/Laragon) and add it to PATH.' }
if (-not (Get-Command composer -ErrorAction SilentlyContinue)) { Fail 'Composer not found. Install from https://getcomposer.org' }
if (-not (Get-Command node -ErrorAction SilentlyContinue))     { Fail 'Node.js not found. Install Node 22 LTS from https://nodejs.org' }
php -r 'exit(version_compare(PHP_VERSION,"8.2.0",">=")?0:1);'
if ($LASTEXITCODE -ne 0) { Fail 'PHP 8.2 or newer is required.' }
node -e "const [a,b]=process.versions.node.split('.').map(Number); process.exit((a===20&&b>=19)||a>=22?0:1)"
if ($LASTEXITCODE -ne 0) { Fail "Node 20.19+ or 22+ is required (you have $(node -v))." }

Say 'Backend: installing PHP packages'
Set-Location backend
composer install --no-interaction --prefer-dist
if (-not (Test-Path .env)) { Copy-Item .env.example .env }
if (-not (Test-Path database\database.sqlite)) { New-Item database\database.sqlite -ItemType File | Out-Null }
if (-not (Select-String -Path .env -Pattern '^APP_KEY=base64' -Quiet)) { php artisan key:generate --force }
Say 'Backend: tables + demo data (first run) / only new migrations (existing database)'
php artisan shop:setup
php artisan storage:link 2>$null
php artisan optimize:clear 2>$null | Out-Null
Set-Location ..

Say 'Frontend: installing packages'
Set-Location frontend
if (-not (Test-Path .env)) { Copy-Item .env.example .env }
npm install --no-audit --no-fund
Set-Location ..

Write-Host "`nAll set!  Start both servers with:  .\start.ps1" -ForegroundColor Green
Write-Host "  Website : http://localhost:3000"
Write-Host "  Admin   : http://localhost:8000/admin   (admin@bazar.com.bd / Admin@12345 - from backend\.env)"
Write-Host "  Demo customer : 01711000001 / Password@123"

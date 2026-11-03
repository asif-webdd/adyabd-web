# Starts the Laravel API (8000) and the Nuxt website (3000) in two windows.
Set-Location $PSScriptRoot
Start-Process powershell -ArgumentList '-NoExit','-Command',"cd '$PSScriptRoot\backend'; php artisan serve --port=8000"
Start-Process powershell -ArgumentList '-NoExit','-Command',"cd '$PSScriptRoot\frontend'; npm run dev"
Write-Host 'Website: http://localhost:3000   Admin: http://localhost:8000/admin'

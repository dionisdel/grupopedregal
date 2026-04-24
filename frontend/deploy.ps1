# Deploy frontend to Laravel public folder and push to IONOS
# Usage: .\frontend\deploy.ps1

$ErrorActionPreference = "Stop"
$SSH_KEY = "C:\Users\dionisdel\.ssh\ionos_grupopedregal"
$SSH_HOST = "su720237@access-5020300065.webspace-host.com"
$REMOTE_PATH = "~/public/grupopedregal"

Write-Host "=== Building frontend ===" -ForegroundColor Cyan
$env:NEXT_PUBLIC_API_URL = "https://www.grupopedregal.es"
Remove-Item -Recurse -Force ".next" -ErrorAction SilentlyContinue
Remove-Item -Recurse -Force "out" -ErrorAction SilentlyContinue
npx next build
if ($LASTEXITCODE -ne 0) { Write-Host "Build failed!" -ForegroundColor Red; exit 1 }

Write-Host "=== Copying to Laravel public ===" -ForegroundColor Cyan
$laravelPublic = "..\backend\public"
# Clean old frontend files (keep Laravel's index.php, .htaccess, css, fonts, js)
Remove-Item -Recurse -Force "$laravelPublic\_next" -ErrorAction SilentlyContinue
Remove-Item -Recurse -Force "$laravelPublic\app" -ErrorAction SilentlyContinue
Remove-Item -Recurse -Force "$laravelPublic\productos" -ErrorAction SilentlyContinue
Remove-Item -Recurse -Force "$laravelPublic\contacto" -ErrorAction SilentlyContinue
Remove-Item -Recurse -Force "$laravelPublic\login" -ErrorAction SilentlyContinue
Remove-Item -Recurse -Force "$laravelPublic\registro" -ErrorAction SilentlyContinue

# Copy frontend build output
Copy-Item -Recurse -Force "out\_next" "$laravelPublic\_next"
Copy-Item -Recurse -Force "out\app" "$laravelPublic\app"
Copy-Item -Recurse -Force "out\productos" "$laravelPublic\productos"
Copy-Item -Recurse -Force "out\contacto" "$laravelPublic\contacto"
Copy-Item -Recurse -Force "out\login" "$laravelPublic\login"
Copy-Item -Recurse -Force "out\registro" "$laravelPublic\registro"
Copy-Item -Force "out\index.html" "$laravelPublic\index.html"
Copy-Item -Force "out\404.html" "$laravelPublic\404.html"

Write-Host "=== Committing and pushing ===" -ForegroundColor Cyan
Set-Location ..
git add -A
git commit -m "deploy: update frontend static build"
git push origin main

Write-Host "=== Updating server ===" -ForegroundColor Cyan
ssh -i $SSH_KEY -o BatchMode=yes $SSH_HOST "cd $REMOTE_PATH && git pull origin main 2>&1 && cd backend && php artisan config:cache 2>&1 && echo DEPLOY_OK"

Write-Host "=== Done! ===" -ForegroundColor Green

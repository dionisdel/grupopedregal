Set-Location $PSScriptRoot\frontend

# Build
Remove-Item -Recurse -Force .next, out -ErrorAction SilentlyContinue
$env:NEXT_PUBLIC_API_URL = "https://www.grupopedregal.es"
npx next build
if ($LASTEXITCODE -ne 0) { Write-Host "BUILD FAILED" -ForegroundColor Red; exit 1 }

# Copy to Laravel
$dst = "..\backend\public"
foreach ($d in @("_next","app","productos","contacto","login","registro")) {
    Remove-Item -Recurse -Force "$dst\$d" -ErrorAction SilentlyContinue
    Copy-Item -Recurse -Force "out\$d" "$dst\$d"
}
Copy-Item -Force out\index.html "$dst\index.html"
Copy-Item -Force out\404.html "$dst\404.html"

# Push
Set-Location ..
git add -A
git commit -m "deploy: update frontend"
git push origin main

# Update server
ssh -i C:\Users\dionisdel\.ssh\ionos_grupopedregal -o BatchMode=yes su720237@access-5020300065.webspace-host.com "cd ~/public/grupopedregal && git pull origin main"

Write-Host "DEPLOY OK" -ForegroundColor Green

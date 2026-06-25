Write-Host "==========================================================" -ForegroundColor Cyan
Write-Host "   Préparation du déploiement Hostinger (Laravel + Vue)" -ForegroundColor Cyan
Write-Host "==========================================================" -ForegroundColor Cyan

$baseDir = Get-Location
$appDir = Join-Path $baseDir "ya-consulting-app"
$deployDir = Join-Path $baseDir "deploy_hostinger"
$zipFile = Join-Path $baseDir "hostinger_upload.zip"

Write-Host "`n[1/5] Compilation des assets Vue.js (npm run build)..." -ForegroundColor Yellow
Set-Location $appDir
npm install
npm run build

Write-Host "`n[2/5] Optimisation de PHP (composer)..." -ForegroundColor Yellow
composer install --optimize-autoloader --no-dev

Set-Location $baseDir

Write-Host "`n[3/5] Nettoyage et création des dossiers Hostinger..." -ForegroundColor Yellow
if (Test-Path $deployDir) { Remove-Item -Recurse -Force $deployDir }
if (Test-Path $zipFile) { Remove-Item -Force $zipFile }

New-Item -ItemType Directory -Force -Path (Join-Path $deployDir "public_html") | Out-Null
New-Item -ItemType Directory -Force -Path (Join-Path $deployDir "core") | Out-Null

Write-Host "`n[4/5] Copie des fichiers et sécurisation..." -ForegroundColor Yellow
# Copier le code dans 'core' (sauf node_modules pour alléger)
Copy-Item -Path "$appDir\*" -Destination "$deployDir\core" -Recurse -Exclude "node_modules"

# Déplacer le dossier public vers public_html
Move-Item -Path "$deployDir\core\public\*" -Destination "$deployDir\public_html" -Force
Remove-Item -Recurse -Force "$deployDir\core\public"

# Mise à jour du fichier index.php pour Hostinger
$indexPath = Join-Path $deployDir "public_html\index.php"
$indexContent = Get-Content $indexPath -Raw
$indexContent = $indexContent -replace "require __DIR__\.'/../vendor/autoload\.php';", "require __DIR__.'/../core/vendor/autoload.php';"
$indexContent = $indexContent -replace "\$app = require_once __DIR__\.'/../bootstrap/app\.php';", "`$app = require_once __DIR__.'/../core/bootstrap/app.php';"
Set-Content -Path $indexPath -Value $indexContent

# Lier le stockage : Création d'un petit script pour Hostinger (car les symlinks locaux cassent au zip)
Write-Host "Création du script symlink.php..." -ForegroundColor DarkGray
$symlinkScript = "<?php`n`$targetFolder = __DIR__.'/../core/storage/app/public';`n`$linkFolder = __DIR__.'/storage';`nsymlink(`$targetFolder, `$linkFolder);`necho 'Symlink cree avec succes ! Veuillez supprimer ce fichier maintenant.';`n?>"
Set-Content -Path "$deployDir\public_html\symlink.php" -Value $symlinkScript

Write-Host "`n[5/5] Création de l'archive ZIP ($zipFile)..." -ForegroundColor Yellow
Compress-Archive -Path "$deployDir\*" -DestinationPath $zipFile -Force

Write-Host "`n==========================================================" -ForegroundColor Green
Write-Host "   SUCCES ! Le fichier hostinger_upload.zip a été créé." -ForegroundColor Green
Write-Host "   Vous pouvez maintenant l'envoyer sur Hostinger !" -ForegroundColor Green
Write-Host "==========================================================" -ForegroundColor Green

#!/usr/bin/env bash
# Quitter s'il y a une erreur
set -o errexit

echo "📦 Installation des dépendances PHP..."
composer install --prefer-dist --no-dev --optimize-autoloader --no-interaction

echo "📦 Installation des dépendances Node.js (Vue.js)..."
npm install

echo "🔨 Compilation des assets Vue.js..."
npm run build

echo "🧹 Nettoyage des caches Laravel..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "🗄️ Lancement des migrations..."
php artisan migrate --force --seed

#!/bin/sh

# Création et lien de la base SQLite sur le disque persistant
if [ ! -f /data/database.sqlite ]; then
    touch /data/database.sqlite
fi
ln -sf /data/database.sqlite /var/www/html/database/database.sqlite

# Création et lien du dossier d'uploads sur le disque persistant
mkdir -p /data/uploads
ln -sfn /data/uploads /var/www/html/storage/app/public

# Migration de la base de données
php artisan migrate --force

# Lien symbolique du stockage standard de Laravel
php artisan storage:link

# Effacer le cache
php artisan optimize:clear
php artisan view:cache
php artisan event:cache

# Démarrer PHP-FPM en arrière-plan
php-fpm &

# Démarrer Nginx en premier plan
nginx -g "daemon off;"

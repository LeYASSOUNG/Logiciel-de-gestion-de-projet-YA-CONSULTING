# Étape 1 : Construction du Frontend (Node.js)
FROM node:20 AS frontend
WORKDIR /app
COPY ya-consulting-app/package*.json ./
RUN npm install
COPY ya-consulting-app/ ./
RUN npm run build

# Étape 2 : Construction du Backend (PHP)
FROM php:8.2-fpm AS backend

# Installation des dépendances système requises par Laravel
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    nginx \
    sqlite3

# Nettoyage du cache apt
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Installation des extensions PHP
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd

# Installation de Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copie du code Laravel
COPY ya-consulting-app/ .

# Copie des fichiers compilés depuis l'étape frontend
COPY --from=frontend /app/public/build ./public/build

# Installation des dépendances PHP
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Configuration des permissions pour Laravel
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Copie de la configuration Nginx
COPY nginx.conf /etc/nginx/sites-available/default

# Rendre le script de démarrage exécutable
COPY start.sh /usr/local/bin/start.sh
RUN chmod +x /usr/local/bin/start.sh

# Exposer le port par défaut de Render (ou 80)
EXPOSE 80

# Script de démarrage
ENTRYPOINT ["/usr/local/bin/start.sh"]

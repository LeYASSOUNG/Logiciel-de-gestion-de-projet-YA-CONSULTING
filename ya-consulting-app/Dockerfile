FROM php:8.2-apache

# Installer les dépendances système et Node.js
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    zip \
    unzip \
    libpq-dev \
    nodejs \
    npm

# Nettoyer le cache apt
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Installer les extensions PHP (y compris pdo_pgsql pour Render)
RUN docker-php-ext-install pdo_mysql pdo_pgsql mbstring exif pcntl bcmath gd zip

# Activer le module Apache mod_rewrite
RUN a2enmod rewrite

# Obtenir Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Définir le dossier de travail
WORKDIR /var/www/html

# Copier le code existant dans le conteneur
COPY . .

# Installer les dépendances PHP
RUN composer install --prefer-dist --no-dev --optimize-autoloader --no-interaction

# Installer les dépendances Node.js et compiler Vue.js
RUN npm install
RUN npm run build

# Donner les permissions à Apache
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# Modifier la racine d'Apache pour pointer vers le dossier "public" de Laravel
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Exposer le port 80
EXPOSE 80

# Script de démarrage pour lancer les migrations puis Apache
CMD php artisan migrate --force --seed && apache2-foreground

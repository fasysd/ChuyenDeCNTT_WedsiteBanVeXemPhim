FROM php:8.2-apache

# Install system dependencies and PHP extensions
RUN apt-get update && apt-get install -y \
    git \
    curl \
    unzip \
    libzip-dev \
    libpq-dev \
    libsqlite3-dev \
    libonig-dev \
    && docker-php-ext-install pdo_mysql pdo_pgsql pdo_sqlite mbstring zip bcmath \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Install Node.js 20 & npm for Vite build
RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Enable Apache mod_rewrite and set DocumentRoot to /var/www/html/public
RUN a2enmod rewrite
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
    && sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

WORKDIR /var/www/html

# Copy application files
COPY . .

# Install PHP & JS dependencies and build frontend assets
RUN composer install --no-dev --optimize-autoloader --no-interaction \
    && npm ci \
    && npm run build \
    && rm -rf node_modules

# Set permissions for Laravel storage and cache
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Render uses the PORT environment variable (default 80 if not set)
CMD sh -c ' \
    PORT=${PORT:-80}; \
    sed -i "s/Listen 80/Listen ${PORT}/g" /etc/apache2/ports.conf; \
    sed -i "s/:80/:${PORT}/g" /etc/apache2/sites-available/000-default.conf; \
    php artisan storage:link || true; \
    php artisan config:clear || true; \
    php artisan migrate --force || true; \
    apache2-foreground \
'

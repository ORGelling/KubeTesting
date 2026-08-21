FROM php:8.4-fpm-alpine

# PHP extensions Laravel needs, plus Nginx/Supervisor and Node for Vite.
RUN apk add --no-cache \
      nginx \
      supervisor \
      nodejs \
      npm \
      libzip-dev \
      oniguruma-dev \
      linux-headers \
      $PHPIZE_DEPS \
    && docker-php-ext-install \
      pdo_mysql \
      mbstring \
      sockets \
      zip \
    && pecl install redis \
    && docker-php-ext-enable sockets redis \
    && php -m | grep -qx 'sockets'

# Composer comes from the official Composer image.
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Install PHP dependencies first. --no-scripts is necessary here because
# the rest of the Laravel application has not been copied yet.
COPY composer.json composer.lock ./
RUN composer install \
      --no-dev \
      --prefer-dist \
      --no-interaction \
      --no-scripts

# Install JavaScript dependencies before copying application source.
COPY package.json package-lock.json ./
RUN npm ci

# Now copy the full Laravel application source.
COPY . .

# Laravel's Composer scripts and later PHP-FPM runtime need these directories.
RUN mkdir -p \
      bootstrap/cache \
      storage/logs \
      storage/framework/cache/data \
      storage/framework/sessions \
      storage/framework/views \
    && chmod -R ug+rwX bootstrap/cache storage

# Composer's post-autoload script runs `php artisan package:discover`.
RUN composer dump-autoload --optimize --no-dev

# Wayfinder requires PHP and Laravel to generate TypeScript route/action files.
# PHP and Node are both available in this image.
RUN php artisan wayfinder:generate --with-form \
    && npm run build

# Nginx and PHP-FPM configuration.
COPY infra/nginx/default.conf /etc/nginx/http.d/default.conf
COPY infra/supervisord.conf /etc/supervisord.conf

# PHP-FPM workers run as www-data. Laravel must write logs, compiled views,
# sessions, and runtime cache data.
RUN chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R ug+rwX storage bootstrap/cache

EXPOSE 8080

CMD ["supervisord", "-c", "/etc/supervisord.conf"]

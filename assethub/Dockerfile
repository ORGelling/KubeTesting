FROM node:22-alpine AS frontend

WORKDIR /app

COPY package*.json ./
RUN npm ci

COPY . .
RUN npm run build


FROM php:8.3-fpm-alpine

RUN apk add --no-cache \
      nginx \
      supervisor \
      $PHPIZE_DEPS \
    && docker-php-ext-install pdo_mysql mbstring \
    && pecl install redis \
    && docker-php-ext-enable redis

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY composer.json composer.lock ./
RUN composer install \
      --no-dev \
      --no-scripts \
      --prefer-dist \
      --no-interaction

COPY . .

RUN composer dump-autoload --optimize --no-dev

COPY --from=frontend /app/public/build ./public/build
COPY infra/nginx/default.conf /etc/nginx/http.d/default.conf
COPY infra/supervisord.conf /etc/supervisord.conf

RUN chown -R nobody:nobody storage bootstrap/cache

CMD ["supervisord", "-c", "/etc/supervisord.conf"]

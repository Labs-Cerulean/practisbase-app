# Railpack looks up PHP by cloning github.com/jdx/vfox-php, and that repository is gone.
# Installing PHP 8.3 from the FrankenPHP image skips that lookup.

FROM node:22-bookworm-slim AS assets
WORKDIR /app
COPY package.json vite.config.js ./
COPY resources ./resources
COPY public ./public
RUN npm install && npm run build

FROM dunglas/frankenphp:1-php8.3
WORKDIR /app

# Composer downloads packages as zip files. The previous Nixpacks image
# already had these. This base image does not.
RUN apt-get update \
    && apt-get install -y --no-install-recommends git unzip \
    && rm -rf /var/lib/apt/lists/* \
    && install-php-extensions gd pdo_pgsql zip

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY . .
COPY --from=assets /app/public/build ./public/build

RUN composer install --optimize-autoloader --no-scripts --no-interaction \
    && mkdir -p storage/framework/sessions storage/framework/views storage/framework/cache storage/logs bootstrap/cache \
    && chmod -R a+rwx storage bootstrap/cache \
    && chmod +x /app/docker/start.sh

ENV SERVER_NAME=":8080"

EXPOSE 8080

CMD ["/app/docker/start.sh"]

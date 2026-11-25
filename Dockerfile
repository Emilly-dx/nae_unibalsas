# Imagem para hospedar a versão de testes (ex.: Render, plano grátis).
FROM php:8.4-cli

RUN apt-get update \
    && apt-get install -y --no-install-recommends ca-certificates curl git unzip libpq-dev libzip-dev libicu-dev \
    && docker-php-ext-install pdo_pgsql pdo_mysql zip intl bcmath opcache \
    && curl -fsSL https://deb.nodesource.com/setup_22.x | bash - \
    && apt-get install -y --no-install-recommends nodejs \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app
COPY . .

ARG VITE_APP_NAME="NAE UNIBALSAS"
ENV COMPOSER_ALLOW_SUPERUSER=1 \
    VITE_APP_NAME=${VITE_APP_NAME}

RUN composer install --no-dev --optimize-autoloader --no-interaction --no-progress --no-scripts \
    && php artisan package:discover --ansi \
    && npm ci --audit false --fund false \
    && npm run build \
    && rm -rf node_modules \
    && mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache \
    && chmod +x docker/start.sh

ENV PHP_CLI_SERVER_WORKERS=4 \
    LOG_CHANNEL=stderr

EXPOSE 10000
CMD ["sh", "docker/start.sh"]

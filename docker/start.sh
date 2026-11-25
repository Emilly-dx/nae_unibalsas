#!/bin/sh
# Início do contêiner: prepara o banco e sobe o servidor na porta do painel.
set -e

export APP_URL="${APP_URL:-$RENDER_EXTERNAL_URL}"

php artisan optimize
php artisan migrate --force
php artisan nae:preparar-servidor

exec php artisan serve --host=0.0.0.0 --port="${PORT:-10000}" --no-reload

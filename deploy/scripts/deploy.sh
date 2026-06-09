#!/usr/bin/env bash

set -Eeuo pipefail

APP_DIR="${APP_DIR:-/var/www/html}"
maintenance_enabled=0

restore_application() {
    exit_code=$?

    if [[ "$maintenance_enabled" -eq 1 ]]; then
        php artisan up || true
    fi

    exit "$exit_code"
}

trap restore_application EXIT

cd "$APP_DIR"

bash deploy/scripts/predeploy-check.sh

php artisan down --retry=60
maintenance_enabled=1

composer install --no-interaction --prefer-dist --optimize-autoloader
npm ci
rm -rf -- public/build
npm run build
php artisan config:clear
APP_ENV=testing APP_MAINTENANCE_DRIVER=cache APP_MAINTENANCE_STORE=array php artisan test

composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader
npm prune --omit=dev
php artisan migrate --force
php artisan optimize:clear
php artisan optimize
php artisan queue:restart
php artisan up

maintenance_enabled=0
trap - EXIT

echo "Deployment completed successfully."

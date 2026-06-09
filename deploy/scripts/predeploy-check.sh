#!/usr/bin/env bash

set -Eeuo pipefail

APP_DIR="${APP_DIR:-/var/www/library-management}"

cd "$APP_DIR"

required_commands=(php composer npm node)
required_extensions=(bcmath curl dom fileinfo mbstring openssl pdo_mysql tokenizer xml zip)

for command_name in "${required_commands[@]}"; do
    if ! command -v "$command_name" >/dev/null 2>&1; then
        echo "Missing required command: $command_name" >&2
        exit 1
    fi
done

for extension_name in "${required_extensions[@]}"; do
    if ! php -r "exit(extension_loaded('$extension_name') ? 0 : 1);"; then
        echo "Missing required PHP extension: $extension_name" >&2
        exit 1
    fi
done

required_files=(
    .env
    artisan
    package.json
    resources/js/reader.js
)

for required_file in "${required_files[@]}"; do
    if [[ ! -f "$required_file" ]]; then
        echo "Missing required file: $required_file" >&2
        exit 1
    fi
done

for writable_directory in storage bootstrap/cache; do
    if [[ ! -d "$writable_directory" || ! -w "$writable_directory" ]]; then
        echo "Directory must exist and be writable: $writable_directory" >&2
        exit 1
    fi
done

php artisan app:production-check

echo "Pre-deploy checks passed."

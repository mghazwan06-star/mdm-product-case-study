#!/bin/sh
# Runs every time the backend container starts.
set -e

cd /var/www/backend

echo "Installing PHP dependencies..."
composer install --no-interaction --prefer-dist --no-progress

if [ ! -f .env ]; then
    echo "Creating .env from .env.example..."
    cp .env.example .env
fi

if ! grep -q '^APP_KEY=base64:' .env; then
    echo "Generating application key..."
    php artisan key:generate --force
fi

echo "Running database migrations..."
php artisan migrate --force

echo "Backend ready on http://localhost:8000"
exec php artisan serve --host=0.0.0.0 --port=8000

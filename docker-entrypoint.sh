#!/bin/sh

# Start PHP-FPM immediately in foreground
if [ "$1" = "php-fpm" ]; then
    # Run setup in background (don't wait for it)
    (
        sleep 2  # Give PHP-FPM a moment to start
        echo "Running setup tasks in background..."
        for i in $(seq 1 30); do
            php artisan db:monitor > /dev/null 2>&1 && break
            sleep 1
        done
        [ ! -f .env ] && cp .env.example .env 2>/dev/null || true
        [ -f .env ] && ! grep -q "APP_KEY=base64:" .env 2>/dev/null && php artisan key:generate --force 2>/dev/null || true
        [ ! -d "vendor" ] && composer install --no-interaction --prefer-dist --optimize-autoloader 2>/dev/null || true
        php artisan migrate --force 2>/dev/null || true
        php artisan db:seed --force 2>/dev/null || true
        echo "Setup tasks completed!"
    ) &
    
    # Start PHP-FPM immediately in foreground (this is what nginx connects to)
    echo "Starting PHP-FPM..."
    exec php-fpm -F
else
    exec "$@"
fi


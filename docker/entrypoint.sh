#!/bin/sh
set -eu

if [ ! -f storage/oauth-private.key ]; then
    php artisan passport:keys --force
fi

php artisan config:cache
php artisan route:cache
php artisan view:cache

exec "$@"

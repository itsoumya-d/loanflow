#!/bin/sh
set -eu

if [ ! -f storage/oauth-private.key ]; then
    php artisan passport:keys --force
fi

chmod 600 storage/oauth-private.key
chmod 600 storage/oauth-public.key

php artisan config:cache
php artisan route:cache
php artisan view:cache

exec "$@"

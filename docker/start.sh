#!/bin/sh
set -e

. /app/docker/launch-mode.sh
launch_mode_apply

php artisan storage:link || true

# Cache config, events, and views once per boot. Route closures cannot be cached.
case "${APP_ENV:-}" in
    production|staging)
        php artisan package:discover --ansi
        php artisan config:cache
        php artisan event:cache
        php artisan view:cache
        ;;
esac

# Railway sets PORT. FrankenPHP binds whatever SERVER_NAME says.
export SERVER_NAME=":${PORT:-8080}"

exec frankenphp run --config /etc/caddy/Caddyfile

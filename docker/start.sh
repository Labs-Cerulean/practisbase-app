#!/bin/sh
set -e

php artisan storage:link

# Railway sets PORT. FrankenPHP binds whatever SERVER_NAME says.
export SERVER_NAME=":${PORT:-8080}"

exec frankenphp run --config /etc/caddy/Caddyfile

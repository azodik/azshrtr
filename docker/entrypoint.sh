#!/usr/bin/env sh
set -eu

cd /var/www/html

if [ -z "${APP_KEY:-}" ]; then
    echo "APP_KEY is required (set it in .env or compose environment)." >&2
    exit 1
fi

CONF=/etc/supervisor.d/azshrtr.ini

# Nightwatch agent is off by default; enable only with token + flag.
if [ "${NIGHTWATCH_ENABLED:-false}" = "true" ] && [ -n "${NIGHTWATCH_TOKEN:-}" ]; then
    sed -i \
        -e 's/^autostart=false$/autostart=true/' \
        -e 's/^autorestart=false$/autorestart=true/' \
        "$CONF"
fi

# Public disk symlink (safe if it already exists).
php artisan storage:link --force >/dev/null 2>&1 || true

php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan migrate --force

if [ "${AZSHRTR_SEED:-false}" = "true" ]; then
    php artisan db:seed --force
fi

exec "$@"

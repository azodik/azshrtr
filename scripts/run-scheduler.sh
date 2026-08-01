#!/usr/bin/env bash
#
# Run Laravel's scheduler once. Intended for shared-hosting control-panel cron,
# which often does not run commands through a shell (so "cd", "&&", and ">>" break).
#
# Control-panel command (every minute — * * * * *):
#   /bin/bash /home/USER/domains/azshrtr.com/scripts/run-scheduler.sh
#
# PHP binary: PHP_CLI env, else path written to storage/scheduler-php-cli
# (optional; create that file with the absolute php path), else `php` on PATH.
#
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT"

PHP_BIN="${PHP_CLI:-}"
if [[ -z "${PHP_BIN}" && -f "${ROOT}/storage/scheduler-php-cli" ]]; then
    PHP_BIN="$(tr -d '[:space:]' < "${ROOT}/storage/scheduler-php-cli")"
fi
if [[ -z "${PHP_BIN}" ]]; then
    if command -v php >/dev/null 2>&1; then
        PHP_BIN="$(command -v php)"
    else
        echo "ERROR: php not found; set PHP_CLI or write storage/scheduler-php-cli" >&2
        exit 1
    fi
fi

mkdir -p storage/logs
"${PHP_BIN}" artisan schedule:run >> storage/logs/scheduler.log 2>&1

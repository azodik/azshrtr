#!/usr/bin/env bash
#
# Production deploy for Azshrtr on shared hosting (Laravel).
# Usage: from the project root after clone, or pass DEPLOY_ROOT.
#
#   ./deploy.sh
#   DEPLOY_ROOT=/home/USER/domains/azshrtr.com ./deploy.sh
#
#   SKIP_PUBLIC_HTML_SYNC=1 ./deploy.sh   # skip public/ → public_html sync
#
# Optional: prepare scheduler wrapper + try SSH crontab (every minute).
# Some shared hosting ignores SSH crontab — use the printed control-panel command.
#
#   SKIP_SCHEDULER_CRON=1 ./deploy.sh   # skip crontab attempt (wrapper still prepared unless SKIP_SCHEDULER=1)
#   SKIP_SCHEDULER=1 ./deploy.sh        # skip wrapper + crontab entirely
#   PHP_CLI=/opt/alt/php85/usr/bin/php ./deploy.sh
#
# One-off full Composer reinstall (avoid under traffic; can cause brief 500s):
#   CLEAN_VENDOR=1 ./deploy.sh
#
set -euo pipefail

ROOT="${DEPLOY_ROOT:-$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)}"
ROOT="${ROOT/#\~/$HOME}"
cd "$ROOT"

echo "==> Deploy root: $ROOT"

resolve_php_cli() {
    local candidate

    if [[ -n "${PHP_CLI:-}" && -x "${PHP_CLI}" ]]; then
        echo "${PHP_CLI}"

        return 0
    fi

    for candidate in \
        "$(command -v php 2>/dev/null || true)" \
        /opt/alt/php85/usr/bin/php \
        /usr/bin/php \
        /usr/local/bin/php; do
        if [[ -n "${candidate}" && -x "${candidate}" ]]; then
            echo "${candidate}"

            return 0
        fi
    done

    return 1
}

PHP_BIN="$(resolve_php_cli || true)"
if [[ -z "${PHP_BIN}" || ! -x "${PHP_BIN}" ]]; then
    echo "ERROR: PHP CLI not found. Set PHP_CLI (e.g. /opt/alt/php85/usr/bin/php)." >&2
    exit 1
fi

echo "==> PHP CLI: ${PHP_BIN}"

artisan() {
    "${PHP_BIN}" artisan "$@"
}

install_scheduler() {
    local marker="# DEPLOY_SCRIPT: azshrtr scheduler"
    local log_file="${ROOT}/storage/logs/scheduler.log"
    local php_cli_file="${ROOT}/storage/scheduler-php-cli"
    local wrapper="${ROOT}/scripts/run-scheduler.sh"
    local tmp cron_line

    if [[ ! -f "${wrapper}" ]]; then
        echo "ERROR: missing ${wrapper} (git pull the repo, then re-run deploy)." >&2

        return 1
    fi

    mkdir -p "$(dirname "${log_file}")"
    printf '%s\n' "${PHP_BIN}" > "${php_cli_file}"
    chmod +x "${wrapper}"
    chmod +x "${ROOT}/scripts/sync-public-to-public-html.sh" 2>/dev/null || true

    echo "==> Scheduler wrapper ready"
    echo "    PHP: ${PHP_BIN}"
    echo "    Script: ${wrapper}"
    echo "    Log: ${log_file}"
    echo "    Control-panel cron (every minute * * * * *) — use this command only:"
    echo "      /bin/bash ${wrapper}"

    if [[ "${SKIP_SCHEDULER_CRON:-0}" == "1" ]]; then
        echo "==> Skipping SSH crontab (SKIP_SCHEDULER_CRON=1); configure the control panel if needed"

        return 0
    fi

    if ! command -v crontab >/dev/null 2>&1; then
        echo "==> No crontab on PATH — add the control-panel command above"

        return 0
    fi

    tmp="$(mktemp)"
    crontab -l 2>/dev/null | awk -v m="${marker}" '
        $0 == m { getline; next }
        { print }
    ' > "${tmp}" || true

    # Call the wrapper only — some panels cannot use cd/&&/>> in the cron line.
    cron_line="* * * * * /bin/bash ${wrapper}"
    {
        cat "${tmp}"
        echo "${marker}"
        echo "${cron_line}"
    } | crontab - || true
    rm -f "${tmp}"

    if crontab -l 2>/dev/null | grep -F "${wrapper}" >/dev/null 2>&1; then
        echo "==> SSH crontab installed (every minute → ${wrapper})"
    else
        echo "WARN: SSH crontab did not persist (common on some shared hosting). Add the control-panel command above." >&2
    fi

    artisan schedule:list --no-interaction
}

NEW_ENV=0
if [[ ! -f .env ]]; then
    echo "==> No .env file; copying .env.example → .env"
    cp .env.example .env
    NEW_ENV=1
fi

if [[ -d .git ]]; then
    echo "==> git pull"
    git pull

    # Drop leftover untracked files (stale Vite hashes, etc.).
    # Ignored paths (.env, vendor/, public_html/, …) are kept.
    echo "==> Discarding untracked files (git clean -fd)"
    git clean -fd
else
    echo "==> Skipping git pull (not a git checkout)"
fi

# Remove orphaned hashed assets left behind by older builds (git or zip deploy).
if [[ -f public/build/manifest.json && -d public/build/assets ]]; then
    echo "==> Pruning stale public/build assets"
    "${PHP_BIN}" -r '
$manifest = json_decode((string) file_get_contents("public/build/manifest.json"), true);
if (! is_array($manifest)) {
    fwrite(STDERR, "WARN: could not parse public/build/manifest.json\n");
    exit(0);
}
$keep = [];
foreach ($manifest as $entry) {
    if (! is_array($entry)) {
        continue;
    }
    if (isset($entry["file"]) && is_string($entry["file"])) {
        $keep[basename($entry["file"])] = true;
    }
    foreach (($entry["css"] ?? []) as $css) {
        if (is_string($css)) {
            $keep[basename($css)] = true;
        }
    }
}
$dir = "public/build/assets";
foreach (scandir($dir) ?: [] as $name) {
    if ($name === "." || $name === "..") {
        continue;
    }
    $path = $dir."/".$name;
    if (! is_file($path)) {
        continue;
    }
    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
    if (! in_array($ext, ["js", "css"], true)) {
        continue;
    }
    if (! isset($keep[$name])) {
        unlink($path);
        echo "    removed {$path}\n";
    }
}
'
fi

echo "==> composer install (--no-dev)"
if [[ "${CLEAN_VENDOR:-0}" == "1" ]]; then
    echo "==> CLEAN_VENDOR=1 → removing vendor/ for full reinstall"
    rm -rf vendor
fi

composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist

if [[ "$NEW_ENV" -eq 1 ]]; then
    echo "==> key:generate (new .env from .env.example)"
    artisan key:generate --no-interaction
    echo "==> Review .env for DB and other production values"
fi

echo "==> migrate"
artisan migrate --force --no-interaction

echo "==> db:seed (billing plans + platform domain; idempotent)"
artisan db:seed --class=Database\\Seeders\\BillingPlanSeeder --force --no-interaction
artisan db:seed --class=Database\\Seeders\\PlatformDomainSeeder --force --no-interaction

echo "==> storage link (Laravel public path → storage/app/public)"
artisan storage:link --force --no-interaction 2>/dev/null \
    || artisan storage:link --no-interaction

echo "==> Laravel caches (optimize, config, routes, events, views)"
artisan optimize --no-interaction
artisan config:cache --no-interaction
artisan route:cache --no-interaction
artisan event:cache --no-interaction
artisan view:cache --no-interaction

if [[ "${SKIP_PUBLIC_HTML_SYNC:-0}" != "1" ]]; then
    DEPLOY_ROOT="$ROOT" ./scripts/sync-public-to-public-html.sh
fi

if [[ "${SKIP_SCHEDULER:-0}" != "1" ]]; then
    install_scheduler
fi

echo "==> launch-check"
artisan azshrtr:launch-check --no-interaction || true

echo "==> Done."

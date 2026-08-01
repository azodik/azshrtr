#!/usr/bin/env bash
#
# On some shared hosting the web root is public_html; Laravel assets live in public/.
# Run from the application root after public/ is up to date.
#
set -euo pipefail

ROOT="${DEPLOY_ROOT:-$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)}"
cd "$ROOT"

if [[ ! -d public ]]; then
    echo "ERROR: public/ not found under ${ROOT}" >&2
    exit 1
fi

echo "==> Sync public → public_html"
rm -rf public_html
cp -a public public_html

echo "==> public_html/storage → storage/app/public"
if [[ -e public_html/storage ]]; then
    rm -rf public_html/storage
fi
ln -sfn ../storage/app/public public_html/storage

echo "==> public_html ready"

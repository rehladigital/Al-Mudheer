#!/usr/bin/env bash
set -euo pipefail

APP_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
PHP_BIN="${PHP_BIN:-/opt/alt/php83/usr/bin/php}"
GIT_REMOTE="${GIT_REMOTE:-origin}"
GIT_BRANCH="${GIT_BRANCH:-main}"

echo "==> App root: ${APP_ROOT}"
echo "==> Pulling latest code (${GIT_REMOTE}/${GIT_BRANCH})"
cd "${APP_ROOT}"
git pull "${GIT_REMOTE}" "${GIT_BRANCH}"

echo "==> Installing PHP dependencies"
"${PHP_BIN}" composer.phar install --no-dev --prefer-dist -o --ignore-platform-reqs

echo "==> Setting permissions"
chmod -R 775 storage bootstrap/cache
find storage bootstrap/cache -type d -exec chmod 775 {} \;
find storage bootstrap/cache -type f -exec chmod 664 {} \;

echo "==> Clearing cached bootstrap files"
rm -f bootstrap/cache/*.php bootstrap/cache/*.json
"${PHP_BIN}" bin/leantime config:clear

echo "==> Done"
echo "Open your site and hard refresh (Ctrl+F5)."

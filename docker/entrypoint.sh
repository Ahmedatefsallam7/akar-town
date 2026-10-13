#!/bin/bash

# Do NOT use set -e - the container should survive migration errors
# and always reach php-fpm at the end.

echo ""
echo "========================================="
echo "  Akar Town App Starting..."
echo "========================================="
echo ""

echo "[0/5] Syncing dependencies..."
# vendor/ and node_modules/ live in volumes that outlive image rebuilds,
# so reinstall whenever the lock files change.
if ! cmp -s composer.lock vendor/.composer.lock; then
  composer install --no-interaction --optimize-autoloader \
    && cp composer.lock vendor/.composer.lock
fi
if ! cmp -s package-lock.json node_modules/.package-lock.copy.json; then
  npm ci \
    && cp package-lock.json node_modules/.package-lock.copy.json
fi
echo ""

echo "[1/5] Building frontend assets..."
npm run build || echo "      WARNING: Frontend build failed (non-fatal)."
echo ""

echo "[2/5] Waiting for database connection..."
until php artisan db:monitor 2>/dev/null; do
  echo "      MySQL not ready yet - retrying in 1s..."
  sleep 1
done
echo "      Database is up."
echo ""

echo "[3/5] Running migrations..."
if php artisan migrate --force 2>&1; then
  echo "      Migrations complete."
else
  echo "      WARNING: Some migrations failed. Continuing..."
fi
echo ""

echo "[4/5] Linking storage..."
php artisan storage:link 2>/dev/null || true
echo ""

echo "[5/5] Refreshing framework caches..."
php artisan optimize:clear 2>/dev/null || true
php artisan filament:optimize 2>/dev/null || true
echo ""

echo "========================================="
echo "  App ready. Starting PHP-FPM..."
echo "========================================="
echo ""

exec php-fpm

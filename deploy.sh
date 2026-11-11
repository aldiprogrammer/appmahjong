#!/bin/bash
set -e

APP_DIR="/home/u107214145/domains/fabricoasia.com/mahjong"
WEB_DIR="/home/u107214145/domains/fabricoasia.com/public_html/mahjong"
BRANCH="main"

echo "   DEPLOY LARAVEL"
echo "======================================"

cd "$APP_DIR"

echo "==> Update dari Git"
git fetch origin
git reset --hard "origin/$BRANCH"

echo "==> Install dependency PHP"
composer install \
    --no-dev \
    --optimize-autoloader \
    --no-interaction

echo "==> Clear cache Laravel"
php artisan optimize:clear

echo "==> Cache konfigurasi Laravel"
php artisan config:cache

echo "==> Cache view Laravel"
php artisan view:cache

if command -v npm >/dev/null 2>&1; then

    echo "==> Build Vite"

    npm ci
    npm run build

else

    echo "==> npm tidak tersedia, skip build"

fi

echo "==> Pastikan folder storage tersedia"

mkdir -p "$APP_DIR/storage/app/public"

echo "==> Set permission storage"

chmod -R 775 "$APP_DIR/storage"
chmod -R 775 "$APP_DIR/bootstrap/cache"

echo "==> Sync public ke public_html"

rsync -av --delete \
    --exclude='index.php' \
    --exclude='storage' \
    "$APP_DIR/public/" "$WEB_DIR/"

echo "==> Membuat symbolic link storage"

# Hapus storage lama jika ada
if [ -L "$WEB_DIR/storage" ] || [ -e "$WEB_DIR/storage" ]; then
    rm -rf "$WEB_DIR/storage"
fi

# Buat symbolic link
ln -s "$APP_DIR/storage/app/public" "$WEB_DIR/storage"

echo "==> Cek symbolic link"

ls -lah "$WEB_DIR/storage"

echo "==> Membuat index.php custom"

cat > "$WEB_DIR/index.php" <<'PHP'
<?php

use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

require __DIR__.'/../../mahjong/vendor/autoload.php';

$app = require_once __DIR__.'/../../mahjong/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

$response = $kernel->handle(
    $request = Request::capture()
)->send();

$kernel->terminate($request, $response);
PHP

echo "==> Deployment selesai"

echo "======================================"
echo "   DEPLOY BERHASIL"
echo "======================================"
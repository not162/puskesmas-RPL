#!/usr/bin/env bash
# Deploy / pembaruan Sistem Informasi Puskesmas di VPS (Ubuntu).
# Pemakaian (sebagai pengguna dengan sudo, di folder aplikasi):
#   bash deploy/deploy.sh            -> pembaruan dari Git (branch main)
#   bash deploy/deploy.sh --pertama  -> instalasi pertama (tanpa git pull, membuat kunci aplikasi)
set -euo pipefail

APP_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
PHP_FPM="${PHP_FPM:-php8.4-fpm}"
WEB_USER="${WEB_USER:-www-data}"
PERTAMA=0
[[ "${1:-}" == "--pertama" ]] && PERTAMA=1

cd "$APP_DIR"
echo "==> Folder aplikasi: $APP_DIR"

if [[ ! -f .env ]]; then
  echo "!! .env belum ada. Salin deploy/env.production.example ke .env lalu isi nilainya." >&2
  exit 1
fi
if grep -qE '^APP_DEBUG=true' .env; then
  echo "!! APP_DEBUG=true di .env. Ubah ke false untuk produksi." >&2
  exit 1
fi

echo "==> Mode perawatan (pengguna melihat halaman 'sedang diperbarui')"
php artisan down --retry=30 --refresh=15 || true
trap 'php artisan up || true' EXIT

if [[ $PERTAMA -eq 0 ]]; then
  echo "==> Ambil kode terbaru"
  git fetch --prune origin
  git reset --hard origin/main
fi

echo "==> Pustaka PHP (tanpa paket pengembangan)"
composer install --no-dev --prefer-dist --no-interaction --optimize-autoloader

if [[ $PERTAMA -eq 1 ]] && ! grep -qE '^APP_KEY=base64:' .env; then
  php artisan key:generate --force
fi

echo "==> Cadangan basis data sebelum migrasi"
php artisan puskesmas:cadangkan --simpan=14 || echo "   (lewati: basis data belum ada)"

echo "==> Migrasi basis data"
php artisan migrate --force
if [[ $PERTAMA -eq 1 ]]; then
  php artisan db:seed --force
fi

echo "==> Cache konfigurasi, rute, tampilan, event"
php artisan optimize:clear
php artisan optimize
php artisan storage:link || true

echo "==> Izin folder"
sudo chown -R "$WEB_USER":"$WEB_USER" storage bootstrap/cache
sudo find storage bootstrap/cache -type d -exec chmod 775 {} \;
sudo chmod 640 .env && sudo chown "$WEB_USER":"$WEB_USER" .env

echo "==> Muat ulang layanan"
sudo systemctl reload "$PHP_FPM"
php artisan queue:restart
if command -v supervisorctl >/dev/null; then
  sudo supervisorctl restart 'puskesmas-reverb' || true
fi

php artisan up
trap - EXIT
echo "==> Pemeriksaan kesiapan"
php artisan puskesmas:cek || true
echo "==> Selesai."


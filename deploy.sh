#!/usr/bin/env bash
# deploy.sh — dijalankan DI SERVER (cPanel shared hosting dengan SSH) setelah arsip diunggah & diekstrak.
# Lokasi default: ~/netbill (document root subdomain harus diarahkan ke ~/netbill/public)
#
# Cara pakai:
#   1) Upload netbill-collector.tar.gz ke home server (~)
#   2) ssh user@domain
#   3) tar -xzf netbill-collector.tar.gz -C ~/netbill   # atau pindahkan folder hasil ekstrak ke ~/netbill
#   4) cd ~/netbill && bash deploy.sh
#
# Pastikan: Anda sudah membuat database + user MySQL di cPanel, dan APP_KEY/.env sudah diisi.

set -e

echo "==> NetBill Collector — deploy.sh"
cd "$(dirname "$0")"
APP_DIR="$(pwd)"
echo "   App dir: $APP_DIR"

# 1) Salin .env.production -> .env jika belum ada
if [ ! -f "$APP_DIR/.env" ]; then
  if [ -f "$APP_DIR/.env.production" ]; then
    cp "$APP_DIR/.env.production" "$APP_DIR/.env"
    echo "   .env dibuat dari .env.production — EDIT SEKARANG: nano .env (isi DB & APP_URL)"
  else
    cp "$APP_DIR/.env.example" "$APP_DIR/.env"
    echo "   .env dibuat dari .env.example — EDIT SEKARANG: nano .env"
  fi
  echo "   Tekan Enter setelah selesai mengedit .env..."; read -r
fi

# 2) Generate APP_KEY jika kosong
if ! grep -q "^APP_KEY=base64:" "$APP_DIR/.env"; then
  echo "==> Generate APP_KEY..."
  php artisan key:generate --force
fi

# 3) Optimasi paths (shared hosting: gunakan php versi 8.2+ dari cPanel)
PHP_BIN="${PHP_BIN:-php}"
echo "==> PHP: $("$PHP_BIN" -v | head -1)"

# 4) Bersihkan & cache konfigurasi
echo "==> Cache config/route/view/event..."
$PHP_BIN artisan config:cache
$PHP_BIN artisan route:cache
$PHP_BIN artisan view:cache
$PHP_BIN artisan event:cache

# 5) Migrasi database
echo "==> Jalankan migrasi database..."
$PHP_BIN artisan migrate --force

# (Opsional) Seed hanya jika database masih kosong — HAPUS KOMENTAR jika perlu
# $PHP_BIN artisan db:seed --force

# 6) Storage symlink & permission
echo "==> Set permission storage & bootstrap/cache..."
chmod -R 775 storage bootstrap/cache
find storage -type d -exec chmod 775 {} \;
find storage -type f -exec chmod 664 {} \;

# 7) Public storage link (jika filesystem_disk local)
$PHP_BIN artisan storage:link 2>/dev/null || true

echo ""
echo "==> SELESAI."
echo "   Pastikan di cPanel: Document Root subdomain = $APP_DIR/public"
echo "   Akses: URL dari APP_URL di .env"
echo "   Login admin awal: buat via 'php artisan tinker' jika belum ada (lihat DEPLOY.md bagian 5)."

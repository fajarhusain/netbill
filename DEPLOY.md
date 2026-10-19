# Dokumentasi Instalasi & Deployment — NetBill Collector

Aplikasi penagihan RT/RW Net berbasis barcode. Dibangun dengan Laravel 11, MySQL/MariaDB, Blade + Bootstrap 5, dan QR Code.

---

## 1. Persyaratan Sistem

- PHP **8.2+** (dengan ekstensi: pdo_mysql, mbstring, bcmath, gd, ctype, json, openssl, tokenizer, xml, fileinfo, zip, curl)
- Composer 2.x
- MySQL 8.x / MariaDB 10.4+
- Node.js 18+ & npm (untuk kompilasi aset frontend)
- HTTPS pada server publik (wajib untuk akses kamera HP)

---

## 2. Instalasi Lokal (Pengembangan)

```bash
# 1. Clone & masuk direktori
git clone <repo> netbill-collector
cd netbill-collector

# 2. Pasang dependensi
composer install
npm install

# 3. Salin konfigurasi & generate key
cp .env.example .env
php artisan key:generate

# 4. Buat database & user MySQL
mysql -u root -p <<'SQL'
CREATE DATABASE netbill_collector CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'netbill'@'localhost' IDENTIFIED BY 'netbillpass';
GRANT ALL PRIVILEGES ON netbill_collector.* TO 'netbill'@'localhost';
FLUSH PRIVILEGES;
SQL

# 5. Edit .env sesuai kredensial DB Anda
#    DB_DATABASE=netbill_collector
#    DB_USERNAME=netbill
#    DB_PASSWORD=netbillpass

# 6. Jalankan migrasi & isi data awal
php artisan migrate:fresh --seed

# 7. Build aset frontend
npm run build   # atau: npm run dev (hot-reload)

# 8. Jalankan server
php artisan serve --host=0.0.0.0 --port=8000
```

Buka `http://localhost:8000/login`.

---

## 3. Akun Default (hasil seeder)

| Username    | Password   | Role     |
| ----------- | ---------- | -------- |
| `admin`     | `password` | Admin    |
| `petugas01` | `password` | Petugas  |
| `petugas02` | `password` | Petugas  |

> **Penting:** Ganti kata sandi setelah login pertama melalui menu Pengguna.

---

## 4. Konfigurasi `.env`

Variabel penting:

| Variabel | Nilai | Keterangan |
| -------- | ----- | ---------- |
| `APP_NAME` | `NetBill Collector` | Nama aplikasi pada bukti pembayaran |
| `APP_TIMEZONE` | `Asia/Jakarta` | Waktu transaksi ditampilkan WIB |
| `APP_LOCALE` | `id` | Bahasa antarmuka |
| `APP_URL` | `https://domain-anda` | URL publik (HTTPS untuk kamera) |
| `DB_*` | — | Koneksi MySQL |
| `SESSION_DRIVER` | `database` | Sesi persisten (diperlukan untuk login) |
| `CACHE_STORE` | `database` | Cache & queue |
| `QUEUE_CONNECTION` | `database` | Untuk proses async (opsional) |

---

## 5. Membuat Admin Awal (tanpa seeder)

Jika ingin membuat admin tanpa menjalankan seluruh seeder:

```bash
php artisan tinker
```
```php
\App\Models\User::create([
    'name' => 'Administrator',
    'username' => 'admin',
    'password' => bcrypt('katasandi-kuat'),
    'role' => 'admin',
    'is_active' => true,
]);
```

Atau gunakan perintah artisan (bila tersedia):
```bash
php artisan netbill:create-admin --name="Admin" --username=admin --password=...
```

---

## 6. Menjalankan Aplikasi

```bash
# Server pengembangan
php artisan serve

# Dengan queue worker & vite (opsional)
composer dev

# Produksi: gunakan web server (Nginx/Apache) yang mengarah ke public/
```

Untuk menguji kamera scan di HP: akses melalui **HTTPS** atau `localhost`/`127.0.0.1` (browser membatasi kamera pada non-HTTPS). Gunakan `php artisan serve --host=0.0.0.0` dan akses via IP lokal HP, atau gunakan tunnel HTTPS seperti `ngrok`.

---

## 7. Backup Database

```bash
# Backup harian
mysqldump -u netbill -p netbill_collector > backup_$(date +%Y%m%d).sql

# Restore
mysql -u netbill -p netbill_collector < backup_20261019.sql
```

Disarankan: cron harian + simpan ke storage off-site (S3/Dropbox). Uji prosedur restore secara berkala.

---

## 8. Deployment Produksi

### 8.A. cPanel Shared Hosting + SSH + Subdomain (paling umum)

Paket siap unggah sudah disiapkan: `netbill-collector.tar.gz` (8 MB) berisi `vendor/`, `public/build/`, `deploy.sh`, `.env.production` — **tanpa** `node_modules`/`tests`/`.env` lokal, sehingga tidak perlu `composer`/`npm` di server.

#### Langkah 1 — Di cPanel: buat database & subdomain
1. **MySQL Databases**: buat database (mis. `user_dbnetbill`), buat user (mis. `user_dbuser`) + password kuat, lalu *Add User to Database* dengan **ALL PRIVILEGES**.
2. **Subdomains**: buat subdomain (mis. `netbill.domain.com`). **Document Root** arahkan ke `netbill/public` (jadi: `/home/user/netbill/public`). Jika cPanel memaksa root di `public_html/`, lihat catatan di bawah.

#### Langkah 2 — Unggah arsip
**Via SSH (rsync/scp):**
```bash
# dari komputer lokal
scp netbill-collector.tar.gz user@domain-anda:~/
ssh user@domain-anda
tar -xzf netbill-collector.tar.gz -C ~/
# pindahkan jika perlu agar path = ~/netbill-collector -> ~/netbill
mv ~/netbill-collector ~/netbill   # sesuaikan dengan document root langkah 1
```
**Via cPanel File Manager** (tanpa SSH): unggah `netbill-collector.tar.gz` ke `/home/user/`, klik kanan → *Extract*. Pindahkan folder hasil ke `/home/user/netbill`.

#### Langkah 3 — Konfigurasi di server (via SSH)
```bash
cd ~/netbill
cp .env.production .env
nano .env            # isi: APP_URL, DB_DATABASE, DB_USERNAME, DB_PASSWORD
php artisan key:generate --force
bash deploy.sh       # jalankan migrasi + cache + permission
```
> Jika `php` di CLI versi lama, cPanel menyediakan `php82`/`php83`/`php84`. Ganti: `PHP_BIN=php84 bash deploy.sh`. Pastikan versi ≥ 8.2.

#### Langkah 4 — Buat admin awal (jika tidak seeding)
```bash
php artisan tinker
```
```php
\App\Models\User::create([
  'name'=>'Administrator','username'=>'admin',
  'password'=>bcrypt('KataSandiKuat'),'role'=>'admin','is_active'=>true,
]);
```

#### Langkah 5 — Selesai & uji
Buka `https://netbill.domain.com/login`. Login admin. Pastikan kamera scan diakses via **HTTPS** (kamera memerlukan HTTPS atau localhost).

#### Catatan document root `public_html/`
Jika cPanel **tidak** mengizinkan document root di luar `public_html`, letakkan folder aplikasi di `public_html/netbill` dan buat `.htaccess` di `public_html/netbill/public` sudah disediakan Laravel. Pastikan subdomain document root = `public_html/netbill/public`. File `.env`, `app/`, `vendor/` tetap tidak dapat diakses publik karena document root ada di `public/`.

**Alternatif .htaccess** (jika subdomain harus menunjuk `public_html/netbill`): buat `public_html/netbill/.htaccess`:
```apache
<IfModule mod_rewrite.c>
RewriteEngine On
RewriteRule ^(.*)$ public/$1 [L]
</IfModule>
```

---

### 8.B. VPS / Server Dedicated (Nginx)


```bash
# 1. Tarik kode & dependensi
git pull
composer install --no-dev --optimize-autoloader
npm ci && npm run build

# 2. Konfigurasi .env produksi (APP_ENV=production, APP_DEBUG=false, HTTPS URL)
php artisan key:generate   # sekali saja

# 3. Migrasi tanpa seed
php artisan migrate --force

# 4. Optimasi
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# 5. Set permission storage & bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache
```

### Nginx (contoh)
```nginx
server {
    listen 443 ssl http2;
    server_name domain-anda;
    root /var/www/netbill-collector/public;
    index index.php;

    ssl_certificate     /path/fullchain.pem;
    ssl_certificate_key /path/privkey.pem;

    location / { try_files $uri $uri/ /index.php?$query_string; }
    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php8.2-fpm.sock;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
    }
    location ~ /\.(?!well-known).* { deny all; }
}
```

### Scheduler (opsional, untuk pengingat tagihan tahap 2)
```cron
* * * * * cd /var/www/netbill-collector && php artisan schedule:run >> /dev/null 2>&1
```

---

## 9. Pengujian

```bash
# Buat database testing
mysql -u root -e "CREATE DATABASE netbill_collector_testing; GRANT ALL ON netbill_collector_testing.* TO 'netbill'@'localhost';"

# Jalankan seluruh test
php artisan test
```

46 test mencakup: scan QR valid/tidak valid, pelanggan nonaktif, tagihan belum lunas, pembayaran penuh/sebagian/multi-periode, cegah tagihan ganda, idempotency (anti duplikasi), penolakan kelebihan bayar, pembatalan & koreksi, hak akses admin/petugas, QR Code, dashboard, dan bukti PDF.

---

## 10. Keputusan Bisnis yang Telah Ditentukan

1. **Nama usaha default:** `RT/RW NET ANDA` (ubah pada `resources/views/payments/receipt.blade.php` & `customers/card.blade.php`).
2. **Tarif & paket contoh:** Hemat 5 Mbps Rp100.000, Reguler 10 Mbps Rp150.000, Premium 20 Mbps Rp200.000 (dapat diubah admin).
3. **Tanggal jatuh tempo:** per pelanggan via field `due_day` (tgl 1–28).
4. **Pembayaran sebagian:** **diizinkan**.
5. **Bayar beberapa periode sekaligus:** **diizinkan** (alokasi otomatis ke tagihan terpilih, urut periode terlama).
6. **Denda keterlamatan:** **belum diimplementasikan** (bisa ditambahkan dengan menambah kolom penalty pada invoices).
7. **Metode pembayaran tahap awal:** **Tunai** (cash). Transfer & QRIS tersedia di enum tapi belum diverifikasi otomatis.
8. **Wilayah penagihan per petugas:** **belum** (penugasan pelanggan→petugas adalah roadmap tahap 2).
9. **Bukti pembayaran:** dapat ditampilkan, dicetak, diunduh PDF. Kirim WhatsApp tahap 3.
10. **Mode offline:** **belum**. Transaksi memerlukan koneksi server agar dianggap final.

---

## 11. Fitur yang Sudah Berjalan (MVP)

- Login admin & petugas dengan role middleware + cek akun aktif
- Dashboard statistik real-time (pelanggan, tagihan, penerimaan, tunggakan, kas)
- CRUD pelanggan + generate kode unik (`RTW-0001`) & token barcode
- Generate QR Code SVG + cetak kartu pelanggan + buat ulang barcode
- CRUD paket internet
- Manajemen tagihan: buat massal/per pelanggan, cegah duplikat periode, daftar tunggakan, batalkan dengan alasan
- Scan QR via kamera HP (html5-qrcode) + input manual
- Detail pelanggan & tunggakan setelah scan (scan tidak mengubah status)
- Konfirmasi pembayaran tunai: pilih tagihan, input nominal, validasi server, **DB transaction**, **idempotency key** anti duplikasi
- Pembayaran penuh & sebagian + multi-periode (alokasi)
- Penolakan kelebihan bayar (kebijakan admin)
- Bukti pembayaran: tampil, cetak, PDF (DomPDF)
- Riwayat transaksi dengan filter (tanggal, petugas, metode, status)
- Kunjungan penagihan (collection_visits) dengan hasil & catatan
- Setoran petugas & rekonsiliasi kas (selisih, konfirmasi admin)
- Audit log otomatis (create/cancel/cancel invoice/dll)
- Pembatalan transaksi oleh admin dengan alasan + revert status tagihan + audit trail
- Laporan keuangan + ekspor CSV

## 12. Roadmap (Tahap 2 & 3)

- Penugasan pelanggan ke petugas/wilayah
- Pengingat tagihan otomatis (scheduler + WhatsApp)
- Integrasi WhatsApp bukti pembayaran
- Pembayaran digital (transfer/QRIS) dengan verifikasi otomatis
- PWA + dukungan offline dengan sinkronisasi aman
- Denda keterlambatan
- Analisis tren pembayaran & tunggakan

---

## 13. Struktur Kode Penting

| Path | Keterangan |
| ---- | ---------- |
| `app/Services/PaymentService.php` | Inti: proses pembayaran + idempotency + DB transaction |
| `app/Services/InvoiceService.php` | Generate tagihan bulanan + cegah duplikat + batalkan |
| `app/Services/QrCodeService.php` | Generate QR Code SVG (bacon/bacon-qr-code) |
| `app/Services/CodeGeneratorService.php` | Kode pelanggan, nomor transaksi, idempotency key |
| `app/Http/Middleware/EnsureRole.php` | Otorisasi role admin/collector |
| `app/Enums/*.php` | Role, CustomerStatus, InvoiceStatus, PaymentStatus, PaymentMethod, VisitResult |
| `database/migrations/` | 9 tabel utama + foreign key + indeks unik |
| `resources/views/scan/` | Halaman scan + konfirmasi pembayaran |

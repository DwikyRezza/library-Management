# LibraFlow - Modern Library Management System

LibraFlow adalah aplikasi web untuk mengelola perpustakaan. Aplikasi ini
memiliki katalog buku publik, pendaftaran anggota, persetujuan anggota,
pengelolaan buku dan eksemplar, peminjaman, pengembalian, status terlambat,
riwayat transaksi, laporan, export CSV, serta pembaca buku digital privat untuk
member. PDF tidak dikirim langsung ke browser: server merendernya menjadi
gambar per halaman dan menambahkan watermark identitas pembaca.

Dokumen ini ditulis untuk pemula. Ikuti langkahnya dari atas ke bawah. Jangan
melewati langkah kecuali tertulis opsional.

## Langsung Jalankan Sekarang

Pada komputer dan folder project ini, dependency, database, dan asset frontend
sudah tersedia. Untuk membuka web sekarang:

1. Buka PowerShell pertama.
2. Jalankan web server:

```powershell
cd "D:\Rezza\Self Project\library-management"
php artisan serve
```

3. Jangan tutup PowerShell tersebut.
4. Buka PowerShell kedua, lalu jalankan queue worker:

```powershell
cd "D:\Rezza\Self Project\library-management"
php artisan queue:work --tries=3 --timeout=660
```

Queue worker wajib hidup agar PDF yang di-upload admin dapat dirender menjadi
gambar halaman.

5. Buka browser dan kunjungi:

```text
http://127.0.0.1:8000
```

6. Untuk masuk sebagai admin, buka:

```text
http://127.0.0.1:8000/login
```

Gunakan:

```text
Email    : admin@libraflow.test
Username : admin
Password : password
```

Jika perintah di atas menghasilkan error, lanjutkan membaca panduan dari
bagian [Program yang Harus Dipasang](#2-program-yang-harus-dipasang).

## Daftar Isi

1. [Istilah Dasar](#1-istilah-dasar)
2. [Program yang Harus Dipasang](#2-program-yang-harus-dipasang)
3. [Memeriksa Program yang Sudah Terpasang](#3-memeriksa-program-yang-sudah-terpasang)
4. [Menjalankan Project untuk Pertama Kali](#4-menjalankan-project-untuk-pertama-kali)
5. [Membuka dan Login ke Aplikasi](#5-membuka-dan-login-ke-aplikasi)
6. [Cara Menjalankan Lagi Besok](#6-cara-menjalankan-lagi-besok)
7. [Cara Menghentikan Aplikasi](#7-cara-menghentikan-aplikasi)
8. [Mode Development](#8-mode-development)
9. [Perintah Penting](#9-perintah-penting)
10. [Mengatasi Error Umum](#10-mengatasi-error-umum)
11. [Deployment ke Server Cloud](#11-deployment-ke-server-cloud)
12. [Update Aplikasi di Server](#12-update-aplikasi-di-server)
13. [Backup dan Keamanan](#13-backup-dan-keamanan)
14. [Struktur dan Arsitektur Project](#14-struktur-dan-arsitektur-project)
15. [Cara Menggunakan Buku Digital](#15-cara-menggunakan-buku-digital)

---

## 1. Istilah Dasar

Sebelum mulai, kenali beberapa istilah berikut:

- **Terminal** adalah aplikasi untuk mengetik perintah.
- **PowerShell** adalah terminal bawaan Windows.
- **PHP** adalah bahasa pemrograman utama aplikasi ini.
- **Laravel** adalah framework PHP yang digunakan LibraFlow.
- **Composer** memasang dependency atau library PHP.
- **Node.js dan npm** memasang serta membangun CSS dan JavaScript.
- **SQLite** adalah database berbentuk satu file. Ini dipakai untuk development
  lokal karena mudah digunakan.
- **MySQL** adalah database yang lebih disarankan untuk server production.
- **`.env`** adalah file konfigurasi pribadi mesin atau server. File ini tidak
  boleh dibagikan atau dimasukkan ke Git.

Semua perintah dalam panduan lokal dijalankan dari folder project:

```text
D:\Rezza\Self Project\library-management
```

## 2. Program yang Harus Dipasang

Untuk menjalankan LibraFlow di Windows, siapkan:

1. **PHP 8.2 atau lebih baru**
2. **Composer 2**
3. **Node.js versi LTS**
4. **Git**, opsional jika project sudah tersedia di komputer

### Pilihan Mudah untuk PHP di Windows

Cara termudah untuk pemula adalah memasang **XAMPP**. LibraFlow tidak wajib
dijalankan melalui Apache XAMPP; kita hanya memakai PHP yang terdapat di XAMPP.

Setelah XAMPP dipasang, lokasi PHP biasanya:

```text
C:\xampp\php
```

Tambahkan folder tersebut ke `PATH` Windows:

1. Buka Start Menu.
2. Cari **Environment Variables**.
3. Pilih **Edit the system environment variables**.
4. Klik **Environment Variables**.
5. Pada bagian user variables, pilih `Path`, lalu klik **Edit**.
6. Klik **New** dan masukkan `C:\xampp\php`.
7. Simpan semua jendela dengan tombol **OK**.
8. Tutup dan buka kembali PowerShell.

Pasang Composer dari situs resmi Composer. Saat installer meminta lokasi PHP,
pilih:

```text
C:\xampp\php\php.exe
```

Pasang Node.js versi LTS. npm akan ikut terpasang bersama Node.js.

## 3. Memeriksa Program yang Sudah Terpasang

Buka PowerShell, lalu jalankan satu per satu:

```powershell
php -v
composer --version
node --version
npm.cmd --version
```

Hasilnya harus menampilkan nomor versi dan bukan pesan
`is not recognized as the name of a cmdlet`.

Project ini memerlukan ekstensi PHP SQLite. Periksa dengan:

```powershell
php -m | Select-String "pdo_sqlite"
```

Jika muncul tulisan `pdo_sqlite`, SQLite sudah siap.

Jika tidak muncul, buka file `php.ini` yang dipakai PHP:

```powershell
php --ini
```

Cari baris berikut di `php.ini`, lalu hapus tanda titik koma `;` di depannya:

```ini
extension=pdo_sqlite
extension=sqlite3
```

Simpan file, tutup PowerShell, lalu buka kembali.

## 4. Menjalankan Project untuk Pertama Kali

Bagian ini hanya perlu dilakukan saat pertama kali menyiapkan project atau
setelah project dipindahkan ke komputer baru.

### Langkah 1 - Buka Folder Project

Buka PowerShell, lalu masuk ke folder project:

```powershell
cd "D:\Rezza\Self Project\library-management"
```

Tanda kutip wajib digunakan karena nama folder mengandung spasi.

Untuk memastikan foldernya benar, jalankan:

```powershell
Get-ChildItem
```

Anda seharusnya melihat file seperti `artisan`, `composer.json`,
`package.json`, dan folder `app`.

### Langkah 2 - Pasang Dependency PHP

Jalankan:

```powershell
composer install
```

Tunggu sampai selesai. Composer akan membuat folder `vendor`.

Jangan menutup terminal saat proses masih berjalan.

### Langkah 3 - Buat File Konfigurasi `.env`

Jalankan:

```powershell
Copy-Item .env.example .env
```

Jika muncul pesan bahwa `.env` sudah ada, jangan timpa file tersebut kecuali
Anda memang ingin mengatur ulang konfigurasi lokal.

LibraFlow sudah menggunakan SQLite secara default:

```dotenv
DB_CONNECTION=sqlite
```

Anda tidak perlu mengisi username atau password database untuk penggunaan
lokal.

### Langkah 4 - Buat Application Key

Jalankan:

```powershell
php artisan key:generate
```

Perintah ini akan mengisi `APP_KEY` di dalam `.env`. Key digunakan Laravel
untuk keamanan session dan data terenkripsi.

### Langkah 5 - Buat File Database SQLite

Jalankan:

```powershell
New-Item -ItemType File -Force database\database.sqlite
```

Database lokal tersimpan di:

```text
database\database.sqlite
```

### Langkah 6 - Buat Tabel dan Data Contoh

Jalankan:

```powershell
php artisan migrate --seed
```

Perintah ini:

- membuat seluruh tabel database;
- membuat akun admin;
- membuat kategori buku dan anggota;
- membuat 20 buku beserta eksemplarnya;
- membuat data anggota dan transaksi contoh.

Jika sebelumnya database sudah pernah diisi, perintah ini tidak menghapus data
yang ada.

> **Peringatan:** Jangan menggunakan `php artisan migrate:fresh --seed` jika
> ingin mempertahankan data. Perintah `migrate:fresh` menghapus seluruh tabel
> dan data sebelum membuatnya kembali.

### Langkah 7 - Pasang Dependency Frontend

Jalankan:

```powershell
npm.cmd install
```

Mengapa memakai `npm.cmd`? Beberapa instalasi Windows memblokir `npm.ps1`,
sedangkan `npm.cmd` biasanya tetap dapat dijalankan.

Tunggu sampai folder `node_modules` selesai dibuat.

### Langkah 8 - Build CSS dan JavaScript

Jalankan:

```powershell
npm.cmd run build
```

Jika berhasil, Vite akan membuat asset production di folder `public\build`.

### Langkah 9 - Jalankan Web Server Lokal

Jalankan:

```powershell
php artisan serve
```

Terminal akan menampilkan alamat seperti:

```text
Server running on [http://127.0.0.1:8000]
```

Jangan tutup terminal ini selama aplikasi sedang digunakan.

### Langkah 10 - Jalankan Queue Worker

Buka PowerShell baru. Jangan menghentikan web server pada terminal pertama.

```powershell
cd "D:\Rezza\Self Project\library-management"
php artisan queue:work --tries=3 --timeout=660
```

Biarkan terminal kedua tetap terbuka. Ketika admin meng-upload PDF, worker ini
menjalankan PDF.js untuk membuat PNG privat per halaman.

## 5. Membuka dan Login ke Aplikasi

Buka browser seperti Chrome, Edge, atau Firefox, lalu kunjungi:

```text
http://127.0.0.1:8000
```

Halaman login staf:

```text
http://127.0.0.1:8000/login
```

Akun admin bawaan:

| Data | Nilai |
|---|---|
| Email | `admin@libraflow.test` |
| Username | `admin` |
| Password | `password` |

Anda dapat login menggunakan email **atau** username.

Halaman penting:

| Alamat | Fungsi |
|---|---|
| `/` | Landing page publik |
| `/books/search` | Pencarian katalog publik |
| `/member/register` | Pendaftaran anggota |
| `/member/login` | Login pembaca menggunakan username atau email |
| `/login` | Login admin atau pustakawan |
| `/admin/dashboard` | Dashboard |
| `/admin/books` | Pengelolaan buku |
| `/admin/members` | Pengelolaan anggota |
| `/admin/circulation` | Peminjaman dan pengembalian |
| `/admin/transactions` | Riwayat transaksi |
| `/admin/reports` | Laporan dan export CSV |
| `/admin/reading-history` | Riwayat baca digital, hanya admin |

Member baru dapat langsung login dan membaca buku digital meskipun statusnya
masih pending. Status approval tetap diperlukan untuk meminjam buku fisik.
Member rejected tidak dapat login atau membaca.

## 6. Cara Menjalankan Lagi Besok

Setelah instalasi pertama selesai, Anda tidak perlu mengulang semua langkah.

Buka dua PowerShell.

Terminal pertama:

```powershell
cd "D:\Rezza\Self Project\library-management"
php artisan serve
```

Terminal kedua:

```powershell
cd "D:\Rezza\Self Project\library-management"
php artisan queue:work --tries=3 --timeout=660
```

Kemudian buka:

```text
http://127.0.0.1:8000
```

Anda hanya perlu menjalankan `composer install` atau `npm.cmd install` lagi
jika dependency project berubah.

## 7. Cara Menghentikan Aplikasi

Kembali ke setiap terminal yang menjalankan web server atau queue worker.

```powershell
php artisan serve
```

Tekan pada masing-masing terminal:

```text
Ctrl + C
```

Menutup server tidak menghapus database atau data.

## 8. Mode Development

Gunakan mode ini jika Anda sedang mengubah tampilan, CSS, atau JavaScript.

Buka **tiga jendela PowerShell**.

Terminal pertama:

```powershell
cd "D:\Rezza\Self Project\library-management"
php artisan serve
```

Terminal kedua:

```powershell
cd "D:\Rezza\Self Project\library-management"
npm.cmd run dev
```

Terminal ketiga:

```powershell
cd "D:\Rezza\Self Project\library-management"
php artisan queue:work --tries=3 --timeout=660
```

Vite akan memantau perubahan file frontend secara otomatis. Biarkan ketiga
terminal tetap terbuka.

Jika hanya ingin menggunakan aplikasinya tanpa mengubah source code, Anda tidak
perlu menjalankan `npm.cmd run dev`. Cukup jalankan `npm.cmd run build` sekali,
lalu `php artisan serve`.

## 9. Perintah Penting

### Menjalankan Test

```powershell
php artisan test
```

### Memeriksa Daftar Route

```powershell
php artisan route:list
```

### Memeriksa Format Kode PHP

```powershell
vendor\bin\pint.bat --test
```

### Membersihkan Cache Laravel

Gunakan ini jika perubahan konfigurasi atau route belum terbaca:

```powershell
php artisan optimize:clear
```

### Membangun Ulang Frontend

```powershell
npm.cmd run build
```

### Mengisi Ulang Database dari Nol

Hanya untuk development dan hanya jika data boleh dihapus:

```powershell
php artisan migrate:fresh --seed
```

## 10. Mengatasi Error Umum

### Error: `php is not recognized`

Artinya folder PHP belum masuk `PATH`.

Pastikan folder berikut sudah masuk Environment Variables:

```text
C:\xampp\php
```

Tutup dan buka ulang PowerShell setelah mengubah `PATH`.

### Error: `composer is not recognized`

Composer belum terpasang atau terminal belum dibuka ulang setelah instalasi.
Pasang Composer, lalu buka PowerShell baru.

### Error: `npm.ps1 cannot be loaded`

Gunakan:

```powershell
npm.cmd install
npm.cmd run build
```

Anda tidak perlu mengubah Execution Policy Windows.

### Error: `could not find driver`

Ekstensi database PHP belum aktif. Jalankan:

```powershell
php -m | Select-String "pdo_sqlite"
```

Jika tidak ada hasil, aktifkan `pdo_sqlite` dan `sqlite3` pada `php.ini`.

### Error: `No application encryption key has been specified`

Jalankan:

```powershell
php artisan key:generate
```

### Error: `Vite manifest not found`

Dependency frontend belum dibangun. Jalankan:

```powershell
npm.cmd install
npm.cmd run build
```

### Error: `database.sqlite does not exist`

Jalankan:

```powershell
New-Item -ItemType File -Force database\database.sqlite
php artisan migrate --seed
```

### Error: `Address already in use`

Port `8000` sedang dipakai aplikasi lain. Gunakan port berbeda:

```powershell
php artisan serve --port=8001
```

Kemudian buka:

```text
http://127.0.0.1:8001
```

### Tampilan Tidak Berubah

Jalankan:

```powershell
php artisan optimize:clear
npm.cmd run build
```

Lalu refresh browser dengan `Ctrl + F5`.

### PDF Terus Berstatus `processing`

Queue worker belum berjalan. Buka PowerShell baru dan jalankan:

```powershell
cd "D:\Rezza\Self Project\library-management"
php artisan queue:work --tries=3 --timeout=660
```

### PDF Berstatus `failed`

Pastikan Node.js dan dependency renderer tersedia:

```powershell
node --version
npm.cmd install
```

Lihat pesan error pada panel **Kelola buku digital** di detail buku. PDF yang
terenkripsi password, rusak, atau tidak valid dapat gagal dirender. Setelah
penyebabnya diperbaiki, upload ulang PDF tersebut.

### Ingin Mengulang Data Contoh

Perintah berikut menghapus semua data lokal:

```powershell
php artisan migrate:fresh --seed
```

Jangan jalankan perintah ini pada server production.

---

## 11. Deployment ke Server Cloud

Bagian ini menjelaskan deployment production ke satu **AWS EC2 Ubuntu**. Nginx,
PHP-FPM, MySQL, queue worker, dan renderer Node.js berjalan pada instance yang
sama.

Untuk production, gunakan:

- Ubuntu 24.04 LTS;
- minimal 2 GB RAM karena MySQL dan renderer PDF berjalan di server yang sama;
- Elastic IP agar alamat publik tidak berubah ketika instance dihentikan;
- Nginx;
- PHP-FPM;
- MySQL;
- HTTPS sebelum login atau data anggota dibuka ke publik.

### 11.1 Buat VPS

Di dashboard penyedia cloud:

1. Buat server baru dengan Ubuntu 24.04 LTS.
2. Pilih lokasi server yang dekat dengan pengguna.
3. Gunakan SSH key jika tersedia.
4. Kaitkan Elastic IP ke instance.
5. Atur Security Group: port 80/443 publik, port 22 hanya dari IP administrator,
   dan jangan membuka port MySQL 3306 ke internet.
6. Jangan membagikan private SSH key atau password server.

### 11.2 Hubungkan Domain

Langkah ini dapat dilewati sementara jika domain belum tersedia. Gunakan
Elastic IP sebagai alamat stabil, tetapi jangan membuka login publik melalui
HTTP tanpa enkripsi.

Di pengelola DNS domain, buat record:

```text
Type: A
Name: @
Value: IP_SERVER
```

Untuk subdomain `www`, buat:

```text
Type: A
Name: www
Value: IP_SERVER
```

Perubahan DNS dapat memerlukan waktu beberapa menit hingga beberapa jam.

### 11.3 Login ke Server

Dari PowerShell komputer Anda:

```powershell
ssh -i C:\path\ke\private-key.pem ubuntu@ELASTIC_IP
```

Sesuaikan path private key dan Elastic IP. Image Ubuntu EC2 umumnya memakai user
`ubuntu`; jangan mengaktifkan login SSH root.

### 11.4 Update Server

Di terminal server:

```bash
sudo apt update
sudo apt upgrade -y
```

### 11.5 Pasang Nginx, PHP, MySQL, dan Git

Ubuntu 24.04 menyediakan PHP 8.3, yang kompatibel dengan project ini.

```bash
sudo apt install -y nginx mysql-server git unzip curl \
    supervisor \
    php8.3-fpm php8.3-cli php8.3-mysql php8.3-sqlite3 \
    php8.3-mbstring php8.3-xml php8.3-curl php8.3-zip php8.3-bcmath
```

Periksa:

```bash
php -v
nginx -v
mysql --version
```

### 11.6 Pasang Composer

Ikuti installer resmi Composer, atau gunakan:

```bash
cd /tmp
curl -sS https://getcomposer.org/installer -o composer-setup.php
php composer-setup.php
sudo mv composer.phar /usr/local/bin/composer
composer --version
```

### 11.7 Pasang Node.js

Pasang Node.js **22.13.0 atau lebih baru** menggunakan metode resmi NodeSource
atau pengelola versi seperti `nvm`. Versi ini dibutuhkan oleh PDF.js yang
terkunci di project. Setelah selesai, periksa:

```bash
node --version
npm --version
```

### 11.8 Buat Database MySQL

Masuk ke MySQL:

```bash
sudo mysql
```

Jalankan SQL berikut. Ganti password contoh dengan password yang kuat dan unik:

```sql
CREATE DATABASE libraflow CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'libraflow_user'@'localhost' IDENTIFIED BY 'GANTI_DENGAN_PASSWORD_KUAT';
GRANT ALL PRIVILEGES ON libraflow.* TO 'libraflow_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

Jangan memakai password contoh secara literal.

### 11.9 Upload Source Code

Contoh menggunakan Git:

```bash
cd /var/www
sudo git clone URL_REPOSITORY_ANDA library-management
sudo chown -R $USER:www-data /var/www/library-management
cd /var/www/library-management
```

Ganti `URL_REPOSITORY_ANDA` dengan URL repository project.

Jika repository bersifat private, gunakan deploy key atau mekanisme autentikasi
yang disediakan Git hosting. Jangan menulis token langsung di README atau
source code.

### 11.10 Pasang Dependency Production

```bash
composer install --no-interaction --prefer-dist --optimize-autoloader
npm ci
npm run build
```

### 11.11 Buat `.env` Production

```bash
cp .env.production.example .env
nano .env
```

Contoh konfigurasi penting:

```dotenv
APP_NAME=LibraFlow
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=https://domainanda.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=libraflow
DB_USERNAME=libraflow_user
DB_PASSWORD=GANTI_DENGAN_PASSWORD_DATABASE

SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database
DB_QUEUE_RETRY_AFTER=720

SESSION_SECURE_COOKIE=true

PDF_RENDER_TIMEOUT=600
PDF_JOB_TIMEOUT=660
```

Ganti:

- `domainanda.com` dengan domain Anda;
- `GANTI_DENGAN_PASSWORD_DATABASE` dengan password MySQL;
- `SESSION_SECURE_COOKIE=true` hanya dipakai setelah HTTPS aktif;
- jangan mengirim isi `.env` kepada orang lain.

Simpan Nano dengan `Ctrl + O`, tekan Enter, lalu keluar dengan `Ctrl + X`.

Buat application key:

```bash
php artisan key:generate
```

### 11.12 Jalankan Migration

```bash
php artisan migrate --force
```

Jangan menjalankan seeder contoh pada production. Buat administrator pertama
secara interaktif:

```bash
php artisan app:create-admin
```

Password tidak ditampilkan di terminal dan harus memiliki minimal 12 karakter,
huruf besar-kecil, angka, serta simbol.

### 11.13 Atur Permission

```bash
sudo chown -R www-data:www-data /var/www/library-management/storage
sudo chown -R www-data:www-data /var/www/library-management/bootstrap/cache
sudo chmod -R 775 /var/www/library-management/storage
sudo chmod -R 775 /var/www/library-management/bootstrap/cache
```

Folder source lain tidak perlu diberi permission `777`.

### 11.14 Pasang Konfigurasi PHP-FPM

Template PHP menyelaraskan batas server dengan validasi upload PDF 50 MB:

```bash
sudo cp deploy/php/99-libraflow.ini /etc/php/8.3/fpm/conf.d/99-libraflow.ini
sudo systemctl restart php8.3-fpm
```

Periksa nilai aktif:

```bash
php-fpm8.3 -i | grep -E "upload_max_filesize|post_max_size"
```

### 11.15 Jalankan Queue Worker dengan Supervisor

PDF diproses asynchronous, jadi production wajib memiliki worker yang selalu
hidup. Pasang template yang sudah disediakan:

```bash
sudo cp deploy/supervisor/libraflow-worker.conf \
    /etc/supervisor/conf.d/libraflow-worker.conf
```

Aktifkan worker:

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start libraflow-worker:*
sudo supervisorctl status
```

Status worker harus `RUNNING`.

`DB_QUEUE_RETRY_AFTER=720` harus lebih besar dari timeout worker 660 detik agar
job render yang panjang tidak diproses dua kali.

### 11.16 Konfigurasi Nginx

```bash
sudo cp deploy/nginx/libraflow.conf /etc/nginx/sites-available/libraflow
sudo nano /etc/nginx/sites-available/libraflow
```

Sesuaikan `server_name`, root project, atau socket PHP-FPM jika berbeda. Template
sudah berisi batas request 64 MB, security headers, dan proteksi file sensitif.
Aktifkan konfigurasi:

```bash
sudo ln -sfn /etc/nginx/sites-available/libraflow /etc/nginx/sites-enabled/libraflow
sudo nginx -t
sudo systemctl reload nginx
```

Jika `nginx -t` menampilkan error, jangan reload sebelum error diperbaiki.

### 11.17 Periksa dan Deploy

```bash
cd /var/www/library-management
bash deploy/scripts/predeploy-check.sh
bash deploy/scripts/deploy.sh
```

Pemeriksaan akan gagal jika `.env` masih memakai placeholder, database tidak
tersambung, Node.js tidak tersedia, permission salah, atau konfigurasi belum
aman. Script deploy menjalankan test, build, migration, cache Laravel, dan
restart queue dalam maintenance mode. Exit trap akan mencoba mengaktifkan
aplikasi kembali jika salah satu langkah gagal. Script tidak melakukan
`git pull`; source code harus sudah diperbarui sebelum script dijalankan.

### 11.18 Aktifkan HTTPS

Pasang Certbot:

```bash
sudo apt install -y certbot python3-certbot-nginx
```

Jalankan:

```bash
sudo certbot --nginx -d domainanda.com -d www.domainanda.com
```

Ikuti petunjuk di layar. Pilih redirect HTTP ke HTTPS jika ditawarkan.

Jika belum memiliki domain, gunakan metode sertifikat IP yang didukung CA/ACME
client Anda. Sertifikat IP dapat berumur sangat pendek, sehingga renewal dan
reload Nginx harus otomatis.

Periksa pembaruan sertifikat:

```bash
sudo certbot renew --dry-run
```

Jangan membuka login publik sebelum HTTPS aktif. Setelah TLS aktif, pastikan
`APP_URL` memakai `https://` dan `SESSION_SECURE_COOKIE=true`, lalu jalankan:

```bash
php artisan optimize
php artisan app:production-check
```

### 11.19 Pemeriksaan Setelah Deployment

Buka:

```text
https://domainanda.com
```

Periksa:

- landing page terbuka;
- HTTPS aktif;
- `https://domainanda.com/up` mengembalikan status 200;
- `https://domainanda.com/health/ready` mengembalikan `{"status":"ready"}`;
- login admin bekerja;
- tambah buku bekerja;
- registrasi dan approval anggota bekerja;
- issue dan return bekerja;
- export CSV bekerja;
- admin dapat upload PDF dan status berubah dari `processing` menjadi `ready`;
- member dapat membaca gambar halaman ber-watermark;
- librarian tidak dapat membuka `/admin/reading-history`;
- worker Supervisor berstatus `RUNNING`;
- `APP_DEBUG=false`;
- `php artisan app:production-check` berhasil;
- tidak ada file `.env` yang dapat dibuka dari browser.

## 12. Update Aplikasi di Server

Ketika source code versi baru sudah tersedia di server:

```bash
cd /var/www/library-management
bash deploy/scripts/deploy.sh
```

Sebelum update production:

1. backup database;
2. pastikan perubahan sudah diuji di lokal;
3. baca migration baru;
4. jangan menggunakan `git reset --hard` atau `migrate:fresh`.

Jika `git pull` gagal karena ada perubahan manual di server, jangan langsung
menghapus perubahan. Periksa dahulu dengan:

```bash
git status
```

## 13. Backup dan Keamanan

### Backup MySQL

```bash
mysqldump -u libraflow_user -p libraflow > libraflow-backup-$(date +%F).sql
```

File backup mengandung data sensitif. Simpan di tempat aman dan jangan masukkan
ke repository Git.

Restore backup:

```bash
mysql -u libraflow_user -p libraflow < nama-file-backup.sql
```

### Backup Buku Digital

Database tidak berisi file PDF dan PNG hasil render. Backup direktori berikut
secara terjadwal ke storage terpisah dari instance:

```text
storage/app/private/digital-books
```

Untuk EC2, gunakan snapshot EBS atau AWS Backup dan tetap simpan salinan di
lokasi berbeda. Uji restore database dan file privat secara berkala.

### Jika Memakai SQLite di Server

SQLite dapat digunakan untuk server kecil, tetapi MySQL lebih disarankan untuk
banyak pengguna dan banyak proses tulis.

File yang harus dibackup:

```text
database/database.sqlite
storage/app/private/digital-books
```

Pastikan file tersebut dapat ditulis oleh user web server, tetapi tidak dapat
diunduh dari internet.

### Checklist Keamanan

- Gunakan `APP_ENV=production`.
- Gunakan `APP_DEBUG=false`.
- Gunakan HTTPS.
- Ganti password admin default.
- Gunakan password database yang kuat.
- Jangan commit `.env`.
- Jangan menyimpan API key, token, SSH key, atau password di source code.
- Batasi akses SSH dan gunakan SSH key.
- Update paket server secara berkala.
- Backup database secara rutin dan uji proses restore.
- Backup `storage/app/private/digital-books` dan uji pemulihannya.
- Jangan memberi permission `777` ke seluruh project.
- Hanya upload PDF yang hak distribusinya dimiliki perpustakaan.
- Batasi akses backup karena berisi data member dan dokumen buku.
- Pantau kapasitas disk, penggunaan memori, error Nginx/PHP, status Supervisor,
  dan respons `/health/ready`; CloudWatch Agent dapat digunakan pada EC2.
- Web tidak dapat menjamin pemblokiran screenshot perangkat. Proteksi
  LibraFlow adalah storage privat, gambar per halaman, watermark identitas,
  penghambat aksi browser umum, dan audit sesi.

## 14. Struktur dan Arsitektur Project

Teknologi utama:

- PHP 8.2+
- Laravel 12
- Blade
- Tailwind CSS 4
- Alpine.js
- Vite
- PDF.js dan `@napi-rs/canvas`
- SQLite untuk development
- MySQL direkomendasikan untuk production

Laravel 11 awalnya diminta pada spesifikasi. Namun, dukungan security Laravel 11
berakhir pada 12 Maret 2026 dan Composer memblokir seri tersebut karena
security advisory aktif. Project menggunakan Laravel 12 agar tetap menerima
perbaikan keamanan.

Struktur penting:

```text
app/
  Contracts/           Kontrak renderer PDF dan watermark
  Jobs/                Job queue render buku digital
  Http/Controllers/    Menerima request dan mengembalikan response
  Http/Middleware/     Memastikan akun staf aktif
  Http/Requests/       Validasi input dari pengguna
  Models/              Model dan relasi database
  Services/            Business logic buku, anggota, sirkulasi, dan laporan
database/
  factories/           Pembuat data untuk test
  migrations/          Struktur tabel dan index database
  seeders/             Data awal dan data contoh
resources/
  views/               Halaman Blade publik dan admin
  css/                 Tailwind CSS
  js/                  Alpine.js, dark mode, dan sidebar
routes/
  web.php              Daftar URL aplikasi
scripts/
  render-pdf.mjs       Mengubah PDF privat menjadi PNG
  watermark-page.mjs   Menambahkan watermark identitas member
tests/
  Feature/LibraFlow/   Automated feature test
```

Alur peminjaman dan pengembalian menggunakan database transaction, row lock,
validasi ulang state terbaru, conditional update, dan rollback. Perlindungan
ini mencegah double-click atau request berulang membuat transaksi ganda dan
counter yang tidak konsisten.

## Checklist Test Manual

Setelah setup lokal atau deployment:

- login menggunakan email;
- login menggunakan username;
- cari buku berdasarkan judul, author, ISBN, dan kategori;
- daftar sebagai anggota;
- approve atau reject anggota;
- tambah buku dan periksa kode eksemplar;
- tambah eksemplar tanpa menghapus eksemplar lama;
- ubah status eksemplar ke maintenance;
- pinjamkan buku kepada anggota approved;
- coba submit peminjaman yang sama dua kali;
- kembalikan buku;
- periksa badge overdue;
- filter riwayat transaksi;
- download CSV buku dan transaksi;
- periksa sidebar di layar mobile;
- periksa dark mode setelah browser dibuka ulang.
- daftar member dengan username dan password;
- login member menggunakan username dan email;
- upload PDF sebagai admin dan jalankan queue worker;
- pastikan tombol baca hanya muncul setelah status `ready`;
- baca beberapa halaman dan periksa watermark;
- pastikan member rejected tidak dapat membaca;
- periksa riwayat baca sebagai admin;
- pastikan librarian mendapat 403 saat membuka riwayat baca.

## 15. Cara Menggunakan Buku Digital

### Sebagai Admin

1. Login melalui `/login`.
2. Buka menu **Books**.
3. Pilih satu buku.
4. Pada panel **Kelola buku digital**, pilih file PDF.
5. Klik **Upload dan render**.
6. Pastikan queue worker sedang berjalan.
7. Tunggu status berubah dari `processing` menjadi `ready`.

PDF asli disimpan di `storage/app/private` dan tidak memiliki URL publik.

### Sebagai Member

1. Daftar melalui `/member/register`.
2. Login melalui `/member/login` dengan username atau email.
3. Buka katalog `/books/search`.
4. Klik **Baca online** pada buku yang siap.
5. Gunakan tombol **Sebelumnya** dan **Berikutnya**.

Setiap sesi menyimpan halaman terakhir, durasi aktif, IP, dan user agent.
Informasi tersebut hanya dapat dilihat admin.

### Tentang Screenshot

JavaScript web dapat menghambat klik kanan, drag, copy, print, dan shortcut
simpan. Namun browser tidak memiliki izin untuk mengunci screenshot Android,
iPhone, Windows, atau kamera eksternal secara mutlak. Karena itu setiap halaman
diberi watermark nama, kode member, email, dan waktu sesi agar penyalahgunaan
dapat ditelusuri.

## Pengembangan Berikutnya

- Role dan policy yang lebih rinci
- Denda keterlambatan
- Reservasi dan waiting list
- Barcode atau QR scanner
- Email atau WhatsApp pengingat jatuh tempo
- Audit log aktivitas staf
- Upload cover buku
- Scheduled overdue synchronization
- Backup otomatis

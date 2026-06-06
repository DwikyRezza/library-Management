# Private Digital Reader Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Menambahkan pembaca buku digital privat berbasis gambar untuk member LibraFlow, lengkap dengan watermark dan riwayat baca khusus admin.

**Architecture:** Guard `member` dipisahkan dari guard staf. PDF disimpan privat, dirender asynchronous menjadi PNG oleh skrip Node, kemudian setiap request halaman menghasilkan gambar ber-watermark yang hanya dapat diakses pemilik sesi. Heartbeat reader menyimpan aktivitas ke `reading_sessions`.

**Tech Stack:** Laravel 12, Blade, Alpine.js, SQLite/MySQL, Laravel Queue, PDF.js, `@napi-rs/canvas`, PHPUnit.

---

### Task 1: Skema dan autentikasi member

**Files:**
- Create: `database/migrations/2026_06_06_000001_add_authentication_to_members_table.php`
- Modify: `app/Models/Member.php`
- Modify: `database/factories/MemberFactory.php`
- Modify: `config/auth.php`
- Modify: `bootstrap/app.php`
- Modify: `app/Http/Requests/StoreMemberRegistrationRequest.php`
- Modify: `app/Services/MemberService.php`
- Test: `tests/Feature/LibraFlow/MemberAuthenticationTest.php`

- [ ] Tulis test registrasi yang mewajibkan username, password, dan konfirmasi password.
- [ ] Jalankan `php artisan test tests/Feature/LibraFlow/MemberAuthenticationTest.php` dan pastikan gagal karena kolom/guard belum ada.
- [ ] Tambahkan kolom username unik, password, dan remember token.
- [ ] Ubah `Member` menjadi `Authenticatable`, hash password melalui cast, dan tambahkan `canReadDigitalBooks()`.
- [ ] Tambahkan provider/guard session `member`.
- [ ] Jalankan test dan pastikan registrasi menyimpan hash, bukan password mentah.

### Task 2: Login, logout, dan middleware member

**Files:**
- Create: `app/Http/Requests/MemberLoginRequest.php`
- Create: `app/Http/Controllers/MemberAuthController.php`
- Create: `app/Http/Middleware/EnsureMemberCanRead.php`
- Create: `resources/views/auth/member-login.blade.php`
- Modify: `routes/web.php`
- Modify: `resources/views/layouts/app.blade.php`
- Test: `tests/Feature/LibraFlow/MemberAuthenticationTest.php`

- [ ] Tulis test login memakai username dan email.
- [ ] Tulis test member rejected ditolak dan sesi member lama dibatalkan middleware.
- [ ] Jalankan test dan pastikan gagal karena route belum tersedia.
- [ ] Implementasikan login dengan regenerasi session, rate limiter, remember me, dan pesan generik.
- [ ] Daftarkan alias middleware dan route member guest/auth.
- [ ] Jalankan test autentikasi sampai hijau.

### Task 3: Metadata digital dan upload admin

**Files:**
- Create: `database/migrations/2026_06_06_000002_create_digital_reader_tables.php`
- Create: `app/Models/DigitalBookAsset.php`
- Create: `app/Models/ReadingSession.php`
- Create: `app/Http/Middleware/EnsureUserIsAdmin.php`
- Create: `app/Http/Requests/StoreDigitalBookRequest.php`
- Create: `app/Http/Controllers/Admin/DigitalBookController.php`
- Create: `app/Services/DigitalBookService.php`
- Modify: `app/Models/Book.php`
- Modify: `app/Models/User.php`
- Modify: `bootstrap/app.php`
- Modify: `routes/web.php`
- Test: `tests/Feature/LibraFlow/AdminDigitalBookTest.php`

- [ ] Tulis test admin dapat upload PDF dan job dirilis.
- [ ] Tulis test librarian mendapat 403 dan file selain PDF ditolak.
- [ ] Jalankan test dan pastikan gagal karena tabel/route belum tersedia.
- [ ] Tambahkan tabel aset digital dan sesi baca beserta index dan foreign key.
- [ ] Implementasikan service transaksi upload/ganti/hapus file privat.
- [ ] Tambahkan middleware khusus admin dan route pengelolaan digital.
- [ ] Jalankan test upload sampai hijau.

### Task 4: Pipeline render PDF

**Files:**
- Create: `app/Contracts/PdfPageRenderer.php`
- Create: `app/Services/NodePdfPageRenderer.php`
- Create: `app/Jobs/RenderDigitalBook.php`
- Create: `scripts/render-pdf.mjs`
- Modify: `app/Providers/AppServiceProvider.php`
- Modify: `config/services.php`
- Modify: `package.json`
- Test: `tests/Feature/LibraFlow/RenderDigitalBookTest.php`

- [ ] Tulis test job mengubah status `processing` menjadi `ready` dan menyimpan jumlah halaman.
- [ ] Tulis test kegagalan renderer mengubah status menjadi `failed`.
- [ ] Jalankan test dan pastikan gagal karena contract/job belum ada.
- [ ] Buat contract renderer agar job dapat diuji tanpa menjalankan Node.
- [ ] Implementasikan renderer Symfony Process dengan argument array, timeout, dan validasi JSON output.
- [ ] Implementasikan skrip PDF.js untuk membuat `page-0001.png` secara atomik.
- [ ] Bind contract pada service provider dan jalankan test job sampai hijau.

### Task 5: Reader, watermark, dan heartbeat

**Files:**
- Create: `app/Contracts/PageWatermarker.php`
- Create: `app/Services/NodePageWatermarker.php`
- Create: `app/Services/ReadingSessionService.php`
- Create: `app/Http/Controllers/MemberReaderController.php`
- Create: `app/Http/Requests/ReadingHeartbeatRequest.php`
- Create: `scripts/watermark-page.mjs`
- Create: `resources/views/member/reader/show.blade.php`
- Modify: `app/Providers/AppServiceProvider.php`
- Modify: `routes/web.php`
- Test: `tests/Feature/LibraFlow/DigitalReaderTest.php`

- [ ] Tulis test guest dialihkan ke login member, pending member boleh membaca, dan rejected member mendapat 403.
- [ ] Tulis test halaman hanya dapat diminta oleh pemilik sesi.
- [ ] Tulis test respons halaman `private, no-store` dan tidak ada route PDF asli.
- [ ] Tulis test heartbeat memperbarui halaman dan durasi dengan batas server.
- [ ] Jalankan test dan pastikan gagal karena reader belum ada.
- [ ] Implementasikan pembukaan sesi UUID, endpoint halaman privat, heartbeat, dan finish.
- [ ] Implementasikan watermark cache privat melalui skrip Node.
- [ ] Buat UI satu halaman dengan navigasi, zoom, heartbeat, dan penghambat shortcut umum.
- [ ] Jalankan test reader sampai hijau.

### Task 6: Riwayat baca khusus admin

**Files:**
- Create: `app/Http/Controllers/Admin/ReadingHistoryController.php`
- Create: `resources/views/admin/reading-history/index.blade.php`
- Create: `resources/views/admin/reading-history/show.blade.php`
- Modify: `routes/web.php`
- Modify: `resources/views/layouts/admin.blade.php`
- Test: `tests/Feature/LibraFlow/ReadingHistoryTest.php`

- [ ] Tulis test admin dapat melihat member, buku, durasi, IP, dan user agent.
- [ ] Tulis test librarian dan member tidak dapat membuka riwayat admin.
- [ ] Jalankan test dan pastikan gagal karena route belum ada.
- [ ] Implementasikan query eager loading, filter, pagination, dan halaman detail.
- [ ] Tambahkan menu admin-only.
- [ ] Jalankan test riwayat sampai hijau.

### Task 7: Integrasi katalog dan halaman admin buku

**Files:**
- Modify: `app/Http/Controllers/PublicBookController.php`
- Modify: `app/Http/Controllers/BookController.php`
- Modify: `resources/views/public/home.blade.php`
- Modify: `resources/views/public/book-search.blade.php`
- Modify: `resources/views/admin/books/show.blade.php`
- Modify: `resources/views/public/member-register.blade.php`
- Test: `tests/Feature/LibraFlow/DigitalReaderTest.php`

- [ ] Tulis test katalog menampilkan tombol baca hanya jika aset `ready`.
- [ ] Jalankan test dan pastikan gagal karena relasi belum dimuat/tombol belum ada.
- [ ] Eager load aset digital dan tampilkan status/tombol yang tepat.
- [ ] Tambahkan panel upload, status render, ganti, dan hapus PDF khusus admin.
- [ ] Perbarui teks registrasi agar membedakan akses baca digital dan approval pinjaman fisik.
- [ ] Jalankan test terkait sampai hijau.

### Task 8: Dokumentasi dan verifikasi

**Files:**
- Modify: `README.md`
- Modify: `.env.example` hanya jika menambah nama variabel non-secret yang aman.

- [ ] Dokumentasikan instalasi Node, queue worker, alur member, upload PDF, dan batasan screenshot dalam Bahasa Indonesia.
- [ ] Dokumentasikan Supervisor untuk worker production dan storage permission.
- [ ] Jalankan `composer test`.
- [ ] Jalankan `vendor/bin/pint --test`.
- [ ] Jalankan `npm run build`.
- [ ] Jalankan `php artisan migrate:fresh --seed`.
- [ ] Jalankan `php artisan route:list`.
- [ ] Uji skrip render dan watermark dengan PDF sampel lokal.
- [ ] Audit route authorization, storage privat, N+1, dan tidak adanya link PDF mentah.

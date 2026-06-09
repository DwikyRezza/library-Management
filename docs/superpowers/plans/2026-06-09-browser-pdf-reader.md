# Browser PDF Reader Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Mengganti reader PNG ber-watermark dengan reader PDF.js yang menampilkan satu halaman PDF privat pada satu waktu.

**Architecture:** Laravel mengotorisasi dan melakukan streaming PDF asli dari storage privat. PDF.js pada bundle Vite reader mengambil dokumen tersebut, merender halaman aktif ke canvas, dan mengirim heartbeat beserta jumlah halaman yang ditemukan browser.

**Tech Stack:** Laravel 12, Blade, Vite, PDF.js, JavaScript Canvas, PHPUnit

---

### Task 1: Endpoint PDF Privat

**Files:**
- Modify: `routes/web.php`
- Modify: `app/Http/Controllers/MemberReaderController.php`
- Modify: `tests/Feature/LibraFlow/DigitalReaderTest.php`

- [ ] **Step 1: Write failing authorization and streaming tests**

Tambahkan tes bahwa pemilik sesi memperoleh `application/pdf` dengan cache
privat, member lain menerima 404, dan disk S3 terkonfigurasi dapat di-stream.

- [ ] **Step 2: Run focused tests and verify failure**

Run:

```bash
php artisan test tests/Feature/LibraFlow/DigitalReaderTest.php
```

Expected: FAIL karena route `member.reader.document` belum ada.

- [ ] **Step 3: Implement the private document endpoint**

Tambahkan route `/reader/{readingSession}/document`; controller memeriksa
kepemilikan sesi, membaca `original_path` dari disk reader, lalu mengirim
stream PDF dengan header `private`, `no-store`, dan `nosniff`.

- [ ] **Step 4: Run focused tests**

Expected: semua tes endpoint dokumen PASS.

### Task 2: Asset Langsung Siap Dibaca

**Files:**
- Modify: `app/Models/DigitalBookAsset.php`
- Modify: `app/Services/DigitalBookService.php`
- Modify: `app/Http/Controllers/Admin/DigitalBookController.php`
- Modify: `app/Http/Requests/ReadingHeartbeatRequest.php`
- Modify: `app/Services/ReadingSessionService.php`
- Modify: `tests/Feature/LibraFlow/AdminDigitalBookTest.php`
- Modify: `tests/Feature/LibraFlow/DigitalReaderTest.php`

- [ ] **Step 1: Write failing upload and page-count tests**

Ubah ekspektasi upload menjadi asset `ready` tanpa job render. Tambahkan tes
heartbeat dengan `total_pages` yang menyimpan jumlah halaman hasil PDF.js.

- [ ] **Step 2: Run focused tests and verify failure**

Run:

```bash
php artisan test tests/Feature/LibraFlow/AdminDigitalBookTest.php tests/Feature/LibraFlow/DigitalReaderTest.php
```

Expected: FAIL karena upload masih `processing` dan masih mengantrekan render.

- [ ] **Step 3: Implement immediate readiness**

Simpan asset baru sebagai `ready` dengan `page_count = 0`, ubah `isReady()` agar
tidak mensyaratkan jumlah halaman, hapus dispatch render dari alur upload, dan
simpan `total_pages` tervalidasi saat heartbeat.

- [ ] **Step 4: Run focused tests**

Expected: seluruh tes upload dan reader PASS.

### Task 3: Reader Satu Halaman dengan PDF.js

**Files:**
- Create: `resources/js/reader.js`
- Modify: `resources/views/member/reader/show.blade.php`
- Modify: `vite.config.js`
- Modify: `resources/views/admin/books/show.blade.php`
- Modify: `tests/Feature/LibraFlow/DigitalReaderTest.php`

- [ ] **Step 1: Write failing reader markup test**

Tes view harus memuat bundle reader, URL dokumen privat, canvas, status error,
dan tombol Sebelumnya/Berikutnya tanpa teks watermark.

- [ ] **Step 2: Run focused test and verify failure**

Run:

```bash
php artisan test tests/Feature/LibraFlow/DigitalReaderTest.php
```

Expected: FAIL karena view masih menggunakan gambar PNG.

- [ ] **Step 3: Implement PDF.js canvas reader**

Bundle `reader.js` mengonfigurasi worker PDF.js, mengambil dokumen privat,
merender satu halaman responsif, menangani zoom/navigasi/keyboard, dan
mengirim heartbeat. Blade menyediakan URL melalui atribut data dan menampilkan
loading/error tanpa watermark.

- [ ] **Step 4: Build frontend and run reader tests**

Run:

```bash
npm run build
php artisan test tests/Feature/LibraFlow/DigitalReaderTest.php
```

Expected: build dan tes PASS.

### Task 4: Regression Verification

**Files:**
- Verify only

- [ ] **Step 1: Run related tests**

```bash
php artisan test tests/Feature/LibraFlow/AdminDigitalBookTest.php tests/Feature/LibraFlow/DigitalReaderTest.php tests/Feature/LibraFlow/ReadingHistoryTest.php
```

- [ ] **Step 2: Run full test suite**

```bash
php artisan test
```

- [ ] **Step 3: Inspect the production diff**

```bash
git diff --check
git status --short
```

Expected: tidak ada whitespace error dan hanya file reader terkait yang berubah.

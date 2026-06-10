# Progressive PDF Batch Reader Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Menampilkan PDF dalam window vertikal berisi maksimal sepuluh halaman dengan render halaman utama lebih dulu, skeleton per halaman, navigasi per window, dan render background maksimal dua task.

**Architecture:** Logika perhitungan window, urutan prioritas, dan cache dipisahkan ke modul JavaScript murni agar dapat diuji dengan Node test runner. Entry reader membangun page surface dinamis, menjadwalkan PDF.js canvas dan text layer, mengelola IntersectionObserver, highlight, heartbeat, pembatalan render, zoom, serta navigasi window.

**Tech Stack:** Laravel 12, Blade, Vite, JavaScript ES modules, PDF.js, Node test runner, PHPUnit

---

### Task 1: Reader Core

**Files:**
- Create: `resources/js/reader-core.js`
- Create: `tests/js/reader-core.test.mjs`
- Modify: `package.json`

- [ ] **Step 1: Write failing tests**

Tambahkan test untuk:

```js
calculateInitialWindow(1, 120) // { start: 1, end: 10 }
calculateInitialWindow(37, 120) // { start: 31, end: 40 }
calculateInitialWindow(118, 120) // { start: 111, end: 120 }
calculateAdjacentWindow({ start: 31, end: 40 }, 120, 1) // 41-50
buildRenderPriority(37, 31, 40) // [37, 38, 36, 39, 35, 40, 34, 33, 32, 31]
pagesOutsideCache(37, [1, 22, 52, 53], 15) // [1, 53]
```

- [ ] **Step 2: Verify RED**

Run:

```bash
npm run test:reader
```

Expected: FAIL karena `resources/js/reader-core.js` belum tersedia.

- [ ] **Step 3: Implement pure helpers**

Implementasikan:

```js
export const WINDOW_SIZE = 10;
export function calculateInitialWindow(page, totalPages) {}
export function calculateAdjacentWindow(windowRange, totalPages, direction) {}
export function buildRenderPriority(targetPage, startPage, endPage) {}
export function pagesOutsideCache(activePage, pageNumbers, radius) {}
```

- [ ] **Step 4: Verify GREEN**

Run `npm run test:reader`.

Expected: seluruh test helper PASS.

### Task 2: Batch Reader Markup

**Files:**
- Modify: `tests/Feature/LibraFlow/DigitalReaderTest.php`
- Modify: `resources/views/member/reader/show.blade.php`
- Modify: `resources/css/reader.css`

- [ ] **Step 1: Write failing markup assertions**

Reader view harus menyediakan:

```text
readerPages
readerPageTemplate
readerRenderStatus
Memuat halaman
reader-page-skeleton
```

dan tidak lagi bergantung pada satu `readerCanvas`.

- [ ] **Step 2: Verify RED**

Run:

```bash
php artisan test tests/Feature/LibraFlow/DigitalReaderTest.php
```

Expected: FAIL pada assertion markup batch.

- [ ] **Step 3: Implement markup and styles**

Blade menyediakan container halaman, template page surface, floating render
status, full-document loading/error, dan popover highlight yang sudah ada.
CSS menyediakan layout vertikal, shimmer, fade, error per halaman, active page,
dan reduced-motion.

- [ ] **Step 4: Verify GREEN**

Run focused PHPUnit test dan pastikan assertion view PASS.

### Task 3: Progressive PDF Scheduler

**Files:**
- Rewrite: `resources/js/reader.js`

- [ ] **Step 1: Build window metadata before rendering**

Setelah PDF terbuka:

```js
const range = calculateInitialWindow(initialPage, totalPages);
await mountWindow(range, initialPage);
```

`mountWindow` mengambil metadata halaman, menghitung viewport pada zoom aktif,
membuat sepuluh skeleton dengan tinggi stabil, lalu auto-scroll ke target.

- [ ] **Step 2: Add concurrency-limited queue**

Gunakan antrean dengan:

```js
const MAX_CONCURRENT_RENDERS = 2;
```

Target dirender pertama. Hasil hanya dipasang jika token generasi, zoom, window,
dan nomor halaman masih aktif. Simpan dan batalkan `renderTask` serta
`TextLayer` saat window atau zoom berubah.

- [ ] **Step 3: Render page layers and failures**

Setiap page state merender canvas dan text layer secara bersamaan, lalu highlight
halaman tersebut. Skeleton fade setelah keduanya selesai. Error hanya mengganti
page surface terkait dan tombol retry mengantrekan ulang halaman itu.

- [ ] **Step 4: Add window navigation and zoom**

Berikutnya/Sebelumnya mengganti window sepuluh halaman. Arrow Right/Left memakai
perilaku yang sama. Zoom didebounce 200 ms, membatalkan render aktif, menghitung
ulang skeleton, lalu merender ulang halaman aktif lebih dulu.

### Task 4: Active Page, Heartbeat, Highlight, and Cache

**Files:**
- Modify: `resources/js/reader.js`

- [ ] **Step 1: Observe the dominant page**

Pasang `IntersectionObserver` dengan banyak threshold. Pilih rasio terbesar,
lalu jarak pusat viewport sebagai tie breaker. Perbarui label dan prioritaskan
render halaman aktif.

- [ ] **Step 2: Debounce reading progress**

Kirim heartbeat setelah halaman aktif stabil, setiap 15 detik, saat window
berubah, saat tab tersembunyi, dan pada `beforeunload`.

- [ ] **Step 3: Preserve page-specific highlights**

Selection mencari text layer pemilik event. Payload dan penghapusan memakai
nomor halaman surface tersebut. Hanya highlight layer halaman terkait yang
dirender ulang.

- [ ] **Step 4: Evict distant page layers**

Desktop memakai radius 15 dan mobile radius 8. Halaman di luar radius
membatalkan task, melepaskan bitmap canvas, text layer, dan highlight DOM tanpa
mengubah ukuran skeleton.

### Task 5: Verification

**Files:**
- Verify only

- [ ] **Step 1: Run JavaScript tests**

```bash
npm run test:reader
```

- [ ] **Step 2: Build production assets**

```bash
npm run build
```

- [ ] **Step 3: Run focused Laravel tests**

```bash
php artisan test tests/Feature/LibraFlow/DigitalReaderTest.php tests/Feature/LibraFlow/ReadingHistoryTest.php
```

- [ ] **Step 4: Run full Laravel tests**

```bash
php artisan test
```

- [ ] **Step 5: Verify in browser**

Buka reader lokal dan periksa skeleton, render prioritas, resume, scroll aktif,
navigasi window, zoom, highlight, retry, mobile, serta tidak ada error console.

- [ ] **Step 6: Inspect final diff**

```bash
git diff --check
git status --short
```

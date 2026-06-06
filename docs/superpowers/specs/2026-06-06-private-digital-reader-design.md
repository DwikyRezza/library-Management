# Private Digital Reader Design

## Ringkasan

LibraFlow akan menyediakan buku digital yang hanya dapat dibaca oleh member terdaftar melalui web. PDF asli disimpan di storage privat dan tidak pernah dikirim langsung ke browser. Worker mengubah PDF menjadi gambar PNG per halaman, lalu server menambahkan watermark identitas member sebelum mengirim satu halaman kepada pembaca.

## Aturan Akses

- Member login menggunakan username atau email dan password.
- Member berstatus pending atau approved boleh membaca.
- Member berstatus rejected tidak boleh login atau membaca.
- Akses membaca tidak bergantung pada transaksi peminjaman buku fisik.
- Staf memakai guard `web`; member memakai guard `member`.
- Hanya admin, bukan librarian, yang boleh mengunggah/menghapus PDF dan melihat riwayat baca.

## Penyimpanan dan Pemrosesan

- PDF asli disimpan di disk `local` pada `storage/app/private/digital-books`.
- Metadata digital disimpan di tabel `digital_book_assets`.
- Upload membuat status `processing` dan mengantrekan `RenderDigitalBook`.
- Worker memanggil skrip Node `scripts/render-pdf.mjs`.
- PDF.js membaca PDF dan `@napi-rs/canvas` menghasilkan PNG per halaman.
- Hasil render tetap berada di storage privat dan tidak memiliki URL publik.
- Jika proses gagal, status aset menjadi `failed` dan pesan kesalahan teknis disimpan untuk admin.

## Pembaca Privat

- Member membuka sesi baca dengan token UUID acak.
- Browser hanya meminta satu gambar halaman melalui route terautentikasi.
- Controller memverifikasi pemilik sesi, status member, status aset, dan batas nomor halaman.
- `WatermarkedPageService` membuat cache gambar khusus member, buku, halaman, dan versi aset.
- Watermark diagonal berulang memuat nama, kode member, email, dan waktu akses.
- Respons gambar menggunakan `Cache-Control: private, no-store`.
- Tidak tersedia route unduh PDF asli.

## Riwayat Baca

Tabel `reading_sessions` mencatat:

- member dan buku;
- waktu mulai, aktivitas terakhir, dan selesai;
- halaman terakhir dan halaman terjauh;
- durasi aktif;
- alamat IP dan user agent.

Reader mengirim heartbeat berkala. Server membatasi tambahan durasi per heartbeat supaya manipulasi request tidak dapat menambahkan durasi tanpa batas.

## Batasan Screenshot

Browser web tidak dapat menjamin pemblokiran screenshot perangkat. LibraFlow akan menghambat tindakan umum seperti klik kanan, drag, copy, print, dan shortcut simpan, tetapi perlindungan utamanya adalah:

- PDF asli tidak pernah dikirim;
- halaman diberikan satu per satu;
- semua halaman ber-watermark identitas;
- seluruh sesi tercatat.

## Keamanan

- Validasi file harus memastikan PDF, ukuran maksimum, dan upload admin.
- Nama file dari user tidak digunakan sebagai path storage.
- Semua pencarian aset menggunakan relasi model dan UUID internal.
- Aksi upload, ganti, hapus, heartbeat, dan selesai menggunakan POST/DELETE dengan CSRF.
- Rate limit diterapkan pada login member dan endpoint gambar/heartbeat.
- Cache watermark berada di storage privat.
- Admin hanya boleh mengunggah materi yang memiliki izin distribusi.

## Deployment

Server memerlukan PHP 8.2+, Composer, Node.js LTS, dan worker queue yang selalu berjalan. README akan menjelaskan instalasi dependency Node, menjalankan `php artisan queue:work`, serta contoh Supervisor untuk VPS.

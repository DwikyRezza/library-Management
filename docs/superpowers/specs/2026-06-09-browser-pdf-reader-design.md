# Browser PDF Reader Design

## Tujuan

Menampilkan buku digital sebagai PDF asli, satu halaman pada satu waktu, tanpa
watermark dan tanpa konversi PDF menjadi PNG di server.

## Arsitektur

- File PDF tetap disimpan privat pada disk yang dikonfigurasi, termasuk S3.
- Member harus login, melengkapi profil, dan memiliki sesi baca miliknya.
- Endpoint PDF privat melakukan streaming `original.pdf` dengan `Content-Type:
  application/pdf`, cache privat, dan tanpa URL S3 publik.
- PDF.js dimuat dari dependency lokal melalui Vite dan merender halaman aktif ke
  elemen canvas.
- Hanya satu halaman ditampilkan. Tombol Sebelumnya/Berikutnya, tombol zoom,
  dan tombol Tutup tetap tersedia.
- Heartbeat yang sudah ada tetap mencatat halaman terakhir, halaman terjauh,
  serta durasi membaca.

## Perubahan Perilaku

- Status buku tidak lagi bergantung pada keberhasilan render PNG. Buku dapat
  dibaca selama PDF asli tersimpan dan asset tidak gagal.
- Proses upload tidak perlu mengantrekan job render PDF menjadi PNG.
- Route gambar per halaman diganti dengan route dokumen PDF privat.
- Pesan watermark dan error render PNG dihapus dari halaman reader.

## Keamanan

- Bucket S3 tetap privat dan endpoint PDF memeriksa kepemilikan sesi baca.
- Response memakai cache privat/no-store dan `X-Content-Type-Options: nosniff`.
- PDF harus dikirim ke browser agar PDF.js dapat merendernya, sehingga member
  yang sah tetap dapat menemukan data PDF melalui developer tools. Pembatasan
  unduh absolut tidak mungkin dilakukan pada reader berbasis browser.

## Penanganan Error

- PDF.js menampilkan status memuat selama dokumen atau halaman diproses.
- Kegagalan mengambil atau membaca PDF menampilkan pesan dan tombol muat ulang.
- Navigasi dinonaktifkan saat halaman pertama/terakhir atau ketika sedang
  merender.

## Pengujian

- Guest dan member lain tidak dapat mengambil PDF sesi.
- Pemilik sesi memperoleh response PDF privat dari disk lokal maupun S3.
- Reader memuat entry PDF.js dan URL PDF privat.
- Upload buku menghasilkan asset yang siap dibaca tanpa menjalankan renderer
  PNG.
- Heartbeat dan riwayat baca tetap lulus seperti sebelumnya.

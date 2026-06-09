# Immersive Reader Highlights Design

## Goal

Membuat reader PDF satu halaman yang menyimpan posisi baca terakhir dan mendukung stabilo teks persisten per pinjaman digital.

## Data Model

- `digital_loans.last_read_page` menjadi sumber posisi resume lintas sesi.
- `book_highlights` dimiliki oleh satu `digital_loan`.
- `serialized_range` menyimpan versi format, anchor teks, dan persegi stabilo ternormalisasi terhadap ukuran halaman.
- Stabilo lama tetap terkait dengan pinjaman lama setelah buku dikembalikan. Pinjaman baru memulai koleksi stabilo baru.

## Backend Flow

- `ReadingSessionService` memulai sesi dari `last_read_page`.
- Setiap heartbeat memperbarui statistik sesi dan memanggil `DigitalLoanService` untuk menyimpan halaman terakhir.
- `DigitalLoanService` memvalidasi kepemilikan serta status aktif pinjaman sebelum membuat atau menghapus stabilo.
- Controller reader dan highlight hanya meneruskan request tervalidasi ke service.
- Reader menerima stabilo pinjaman aktif saat halaman pertama dirender.

## Frontend Flow

- PDF.js tetap memakai ES module worker melalui `GlobalWorkerOptions.workerPort`.
- Canvas, highlight layer, dan text layer berada dalam satu page surface dengan ukuran sama.
- PDF.js 6 memakai kelas publik `TextLayer` untuk membuat teks yang dapat diseleksi.
- Seleksi diubah menjadi anchor span dan persegi relatif `0..1`, sehingga stabilo dapat digambar ulang saat zoom atau resize.
- Klik pada area stabilo yang sudah tersimpan menghapus stabilo tersebut melalui endpoint DELETE.

## Security And Validation

- Semua endpoint berada di grup `auth:member`, `member.profile.complete`, dan `member.reader`.
- Member hanya dapat mengubah stabilo milik pinjaman aktifnya sendiri.
- Warna dibatasi ke kuning `#fef08a`, hijau `#bbf7d0`, dan biru `#bfdbfe`.
- Panjang teks, jumlah persegi, dan nilai koordinat dibatasi oleh Form Request.

## Verification

- Feature tests mencakup resume, heartbeat, create/delete highlight, isolasi antar-member, dan label tombol.
- Laravel Pint memformat PHP.
- Seluruh test suite Laravel dan `npm.cmd run build` harus lulus.

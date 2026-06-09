# Digital Loans, Booklist, and Member UX Design

## Tujuan

Mengubah akses buku digital agar member harus meminjam salinan yang tersedia
sebelum dapat membuka reader. Fitur ini juga menambahkan Booklist, halaman
Borrowed, deskripsi buku, perpanjangan satu kali, pengembalian otomatis, dan
penyempurnaan interaksi antarmuka publik/member.

## Batas Sistem

Pinjaman digital dicatat pada tabel tersendiri agar riwayatnya tidak tercampur
dengan transaksi peminjaman fisik yang dikelola staff. Namun, setiap pinjaman
digital tetap mengunci satu record `book_copies` yang berstatus `available`.
Karena itu angka `available_copies / total_copies` pada katalog tetap menjadi
sumber ketersediaan yang nyata untuk pinjaman fisik maupun digital.

Ketika pinjaman digital dibuat:

- satu `book_copies` tersedia dipilih dengan database lock;
- status copy berubah dari `available` menjadi `borrowed`;
- `books.available_copies` berkurang satu;
- record pinjaman digital aktif dibuat.

Ketika pinjaman digital berakhir:

- status copy kembali menjadi `available`;
- `books.available_copies` bertambah satu;
- record pinjaman disimpan sebagai riwayat dan tidak dihapus.

## Struktur Data

### Digital Loan

Tabel `digital_loans` menyimpan:

- `member_id`;
- `book_id`;
- `book_copy_id`;
- waktu pinjam;
- batas waktu pinjaman;
- waktu perpanjangan, nullable;
- waktu pengembalian, nullable;
- alasan pengembalian: `manual`, `expired`, atau nullable ketika masih aktif;
- timestamps.

Satu member tidak boleh memiliki dua pinjaman digital aktif untuk buku yang
sama. Satu book copy tidak boleh digunakan oleh lebih dari satu transaksi aktif.

### Booklist

Tabel `booklists` menyimpan pasangan unik `member_id` dan `book_id`, beserta
timestamps. Menghapus buku dari Booklist tidak memengaruhi pinjaman atau
riwayat baca.

## Aturan Pinjaman Digital

- Hanya member yang login, profilnya lengkap, tidak ditolak, dan statusnya
  disetujui yang dapat meminjam.
- Kuota digital terpisah dari kuota buku fisik: maksimal tiga pinjaman digital
  aktif per member.
- Buku harus memiliki asset PDF berstatus siap dibaca.
- Buku harus memiliki minimal satu copy berstatus `available`.
- Durasi pinjaman selalu 10 hari.
- Member dapat mengembalikan buku lebih awal dari halaman Borrowed.
- Reader hanya dapat dibuka jika member memiliki pinjaman digital aktif untuk
  buku tersebut.
- Saat pinjaman berakhir, sesi baca yang masih aktif ditutup.

## Perpanjangan

- Perpanjangan hanya dapat dilakukan satu kali.
- Tombol **Extend** muncul ketika waktu tersisa kurang dari atau sama dengan 24
  jam dan pinjaman belum jatuh tempo.
- Perpanjangan menambah 10 hari dari batas waktu lama.
- Setelah diperpanjang, tombol tidak muncul lagi.
- Jika member tidak memperpanjang sampai batas waktu habis, pinjaman otomatis
  dikembalikan.

## Pengembalian Otomatis

Laravel scheduler menjalankan command pengembalian pinjaman kedaluwarsa setiap
menit. Command memakai service yang sama dengan pengembalian manual agar status
copy dan counter buku selalu diperbarui secara transaksional.

EC2 menjalankan:

```cron
* * * * * cd /var/www/html && php8.4 artisan schedule:run >> /dev/null 2>&1
```

Jika scheduler terlambat, setiap aksi katalog, membuka reader, serta halaman
Borrowed melakukan sinkronisasi ringan untuk pinjaman member tersebut. Dengan
demikian pinjaman yang sudah kedaluwarsa tidak dapat dipakai membaca.

## Tombol Kartu Buku

Setiap kartu katalog menyediakan:

- **Description** membuka modal sinopsis/deskripsi tanpa berpindah halaman;
- **Booklist** menambah atau menghapus buku dari daftar pribadi;
- **Borrow** ketika member belum memiliki pinjaman aktif dan copy tersedia;
- **Read** hanya ketika member memiliki pinjaman digital aktif dan PDF siap;
- status tidak tersedia ketika semua copy sedang dipinjam.

Guest tetap dapat membuka Description. Ketika guest menekan Borrow atau
Booklist, aplikasi mengarahkannya ke login member.

## Halaman Member

### Booklist

Menampilkan buku yang disimpan member dengan aksi Description, Remove, Borrow,
atau Read sesuai status terbaru. Buku yang sedang dipinjam tetap dapat berada
di Booklist.

### Borrowed

Menampilkan pinjaman digital aktif dan riwayat pinjaman:

- cover, judul, penulis, dan status;
- waktu pinjam dan batas waktu;
- countdown hari/jam tersisa;
- tombol Read untuk pinjaman aktif;
- tombol Extend ketika memenuhi syarat;
- tombol Return untuk pengembalian lebih awal;
- label Returned atau Expired untuk riwayat.

## Beranda

CTA registrasi tetap tampil untuk guest. Ketika member sudah login, CTA tersebut
diganti panel akses cepat berisi Catalog, Booklist, dan Borrowed beserta jumlah
item yang relevan.

## Navbar

- Tombol tema menggunakan ikon matahari dan bulan dari Lucide, bukan teks
  `Light` atau `Dark`.
- Tombol profil hanya menampilkan ikon user dan memiliki tooltip/label aksesibel.
- Menu member memuat Profile, Booklist, Borrowed, dan Logout.
- Navigasi mobile tetap muat tanpa teks terpotong atau saling menimpa.

## Motion dan Interaksi

- Semua tombol/link interaktif memiliki transisi warna, transform, dan shadow
  yang halus.
- Kartu buku bergerak naik sedikit saat hover tanpa mengubah ukuran layout.
- Konten halaman menggunakan animasi masuk singkat.
- Modal menggunakan fade dan scale ringan.
- `scroll-behavior: smooth` digunakan untuk navigasi dalam halaman.
- Semua motion dinonaktifkan atau dipersingkat saat perangkat memakai
  `prefers-reduced-motion: reduce`.
- Animasi tidak menunda submit form, navigasi, atau feedback error.

## Keamanan dan Konsistensi

- Semua operasi borrow, extend, dan return memakai transaksi database serta
  row lock.
- Controller tidak menerima `book_copy_id` dari member; service memilih copy
  tersedia di server.
- Route reader dan endpoint PDF memeriksa pinjaman aktif selain kepemilikan
  reading session.
- Counter ketersediaan buku dan status copy diperbarui atomik.
- Aksi berulang atau request ganda tidak boleh membuat pinjaman duplikat atau
  counter negatif.

## Pengujian

Pengujian mencakup:

- borrow sukses dan pengurangan satu copy;
- penolakan ketika kuota tiga tercapai, PDF tidak tersedia, atau semua copy
  habis;
- request borrow ganda tidak membuat transaksi duplikat;
- Read hanya tersedia dan dapat dibuka ketika pinjaman aktif;
- return manual dan otomatis mengembalikan copy/counter;
- extend hanya dalam 24 jam terakhir dan hanya satu kali;
- Booklist add/remove idempotent dan terisolasi per member;
- halaman Booklist dan Borrowed hanya dapat dibuka pemilik akun;
- CTA guest/member dan ikon navbar;
- markup Description, hover, motion, dan reduced-motion;
- seluruh test suite lama tetap lulus.

## Deployment

Deployment membutuhkan migration baru dan cron Laravel scheduler. Queue worker
tetap dapat berjalan untuk kebutuhan aplikasi lain, tetapi pengembalian otomatis
bergantung pada `schedule:run`, bukan queue worker.

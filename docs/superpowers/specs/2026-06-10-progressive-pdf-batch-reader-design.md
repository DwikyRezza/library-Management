# Progressive PDF Batch Reader Design

## Tujuan

Mengubah reader PDF satu halaman menjadi reader vertikal yang menyiapkan
sepuluh halaman dalam satu window. Halaman utama harus tampil secepat mungkin,
sedangkan halaman lain dirender bertahap agar navigasi dan scroll terasa cepat
tanpa membebani memori browser.

## Perilaku Utama

### Membaca dari awal

- Reader membuat skeleton halaman 1-10 sebelum mulai merender.
- Halaman 1 menjadi prioritas pertama dan ditampilkan secepat mungkin.
- Setelah halaman 1 siap, halaman 2-10 dirender dengan maksimal dua render
  berjalan bersamaan.
- Tombol Berikutnya berpindah ke window 11-20. Window tujuan dipreload sebelum
  window aktif diganti agar perpindahan tidak menampilkan area kosong.

### Melanjutkan bacaan

- Window awal dihitung dari enam halaman sebelum sampai tiga halaman setelah
  `lastReadPage`.
- Contoh: `lastReadPage` 37 menghasilkan window 31-40.
- Pada batas awal atau akhir dokumen, window digeser agar tetap memuat maksimal
  sepuluh halaman.
- Skeleton seluruh window dibuat lebih dulu dengan tinggi halaman yang stabil.
- Reader auto-scroll ke halaman 37 setelah layout skeleton siap.
- Halaman 37 dirender pertama, lalu halaman sekitar dengan prioritas ke arah
  membaca berikutnya: 38, 36, 39, 35, 40, 34, 33, 32, 31.

## Struktur Halaman

Setiap halaman dalam window memiliki komponen mandiri:

- container dengan nomor halaman dan status render;
- skeleton shimmer dengan teks `Memuat halaman 37...`;
- canvas PDF;
- text layer untuk seleksi teks;
- highlight layer untuk stabilo tersimpan.

Ukuran container ditentukan dari viewport PDF pada zoom aktif sebelum render
dimulai. Skeleton dan hasil render memakai ukuran yang sama agar scroll tidak
meloncat saat canvas muncul.

Saat canvas dan text layer selesai:

1. highlight halaman dirender;
2. layer PDF diberi transisi fade in;
3. skeleton diberi transisi fade out;
4. status halaman berubah menjadi siap.

## Penjadwalan Render

Reader menggunakan antrean render dengan maksimal dua worker pada main thread.
Antrean memiliki prioritas berikut:

1. halaman target saat reader dibuka atau window diganti;
2. halaman aktif yang terdeteksi saat scroll;
3. halaman setelah target;
4. halaman sebelum target;
5. halaman lain dalam window berdasarkan jarak dari target.

Setiap halaman menyimpan `renderTask` PDF.js dan `TextLayer` aktif. Ketika user
berpindah jauh atau window tidak lagi dibutuhkan, task yang belum selesai
dibatalkan dengan `renderTask.cancel()` dan text layer dibatalkan. Pembatalan
bukan error yang ditampilkan kepada user.

Perubahan zoom membatalkan task aktif, mempertahankan halaman aktif, menghitung
ulang ukuran skeleton, lalu merender ulang window dengan halaman aktif sebagai
prioritas pertama.

## Window dan Preload

- Window yang terlihat berisi maksimal sepuluh halaman.
- Saat user menekan Berikutnya atau Sebelumnya, reader menyiapkan metadata dan
  skeleton window tujuan lalu memprioritaskan halaman pertama yang akan dibaca.
- Window aktif tidak dilepas sebelum layout window tujuan siap.
- Saat user mendekati dua halaman terakhir window, metadata window berikutnya
  boleh dipersiapkan lebih awal.
- Saat user mendekati dua halaman pertama, metadata window sebelumnya boleh
  dipersiapkan lebih awal.
- Preload tidak boleh mengambil alih prioritas render halaman aktif.

Tombol Berikutnya tetap memiliki perilaku eksplisit 1-10 ke 11-20, 11-20 ke
21-30, dan seterusnya. Tombol Sebelumnya bergerak ke window sepuluh halaman
sebelumnya. Pada window resume seperti 31-40, navigasi berikutnya adalah 41-50
dan sebelumnya adalah 21-30.

## Halaman Aktif dan Progres

`IntersectionObserver` memantau seluruh container halaman dalam window.
Halaman dengan area terlihat terbesar menjadi halaman aktif. Jika dua halaman
memiliki rasio sama, halaman yang paling dekat dengan pusat viewport dipilih.

Perubahan halaman aktif memperbarui:

- label `Halaman X / Y`;
- heartbeat dan posisi resume;
- target utama antrean render;
- highlight popover dan konteks seleksi teks.

Heartbeat tetap dikirim berkala dan setelah perpindahan window. Perubahan aktif
akibat scroll tidak mengirim request pada setiap pixel; pengiriman didebounce
agar tidak membanjiri endpoint.

## Loading dan Error

- Skeleton per halaman menampilkan `Memuat halaman X...`.
- Floating indicator kecil menampilkan `Merender halaman X dari Y...`.
- Indicator menghilang ketika tidak ada render foreground yang berjalan.
- Kegagalan satu halaman menampilkan status error dan tombol coba lagi hanya
  pada halaman tersebut.
- Kegagalan memuat dokumen tetap menggunakan error state reader penuh.
- Pembatalan render karena navigasi, scroll jauh, atau zoom tidak dianggap
  sebagai kegagalan.

## Memori

- Canvas dan text layer hanya dipertahankan untuk halaman dalam window aktif
  dan window tetangga yang sedang dipreload.
- Cache hasil render dibatasi ke halaman aktif plus maksimal 15 halaman di
  setiap arah.
- Halaman di luar batas cache melepaskan ukuran bitmap canvas, text layer,
  highlight DOM, referensi PDF page, dan task render.
- Metadata nomor, ukuran viewport, dan status skeleton tetap tersedia agar
  posisi scroll stabil bila halaman perlu dirender ulang.
- Window yang tidak lagi aktif atau bertetangga dilepas setelah window tujuan
  siap.

## Highlight

- Highlight tetap disimpan berdasarkan nomor halaman PDF absolut.
- Setiap page surface merender highlight miliknya setelah text layer siap.
- Seleksi dan popover hanya aktif pada text layer halaman yang berinteraksi
  dengan user.
- Menambah atau menghapus highlight hanya memperbarui highlight layer halaman
  terkait.

## Aksesibilitas

- Setiap page surface memiliki label `Halaman X dari Y`.
- Skeleton memakai status yang dapat dibaca screen reader tanpa mengumumkan
  setiap perubahan antrean berulang kali.
- Tombol navigasi dinonaktifkan pada awal atau akhir dokumen.
- Tombol coba lagi per halaman dapat diakses dengan keyboard.
- Transisi fade dan shimmer dihentikan saat user memilih
  `prefers-reduced-motion`.

## Batas Perubahan

- Endpoint PDF privat, otorisasi pinjaman, heartbeat, resume, dan penyimpanan
  highlight tetap memakai kontrak backend yang ada.
- Perubahan utama berada di `resources/js/reader.js`,
  `resources/views/member/reader/show.blade.php`, dan
  `resources/css/reader.css`.
- Backend hanya diubah jika diperlukan untuk menyediakan data awal yang belum
  tersedia; tidak ada perubahan pada penyimpanan PDF atau credential.
- Fitur zoom, keyboard, Tutup, heartbeat, dan stabilo lama tidak dihapus.

## Pengujian

### Otomatis

- View reader menyediakan container window, template page surface, floating
  indicator, dan data halaman awal.
- Fungsi perhitungan window menghasilkan 1-10 untuk halaman 1, 31-40 untuk
  halaman 37, serta rentang valid pada awal dan akhir dokumen.
- Prioritas render mendahulukan halaman target dan membatasi concurrency ke dua.
- Navigasi window, pembatalan render, dan eviction cache memiliki unit test
  JavaScript bila helper dipisahkan menjadi modul murni.
- Test Laravel reader, heartbeat, resume, otorisasi dokumen, dan highlight lama
  tetap lulus.

### Verifikasi Browser

- Halaman 1 tampil sebelum halaman 2-10 selesai.
- Resume halaman 37 membuat layout 31-40 dan auto-scroll tanpa layout jump.
- Skeleton berubah ke PDF dengan fade.
- Floating indicator memakai nomor halaman absolut dokumen.
- Scroll memperbarui halaman aktif dan heartbeat secara stabil.
- Berikutnya berpindah 1-10 ke 11-20 tanpa area kosong.
- Zoom, highlight, retry, keyboard, mobile viewport, dan reduced motion tetap
  bekerja.
- Perpindahan cepat membatalkan task lama dan penggunaan memori tidak terus
  meningkat pada dokumen panjang.

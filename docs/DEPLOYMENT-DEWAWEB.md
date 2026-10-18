# Pemasangan ArsipKU pada Dewaweb Warrior / cPanel

Target: Laravel 13, PHP 8.3 atau lebih baru yang kompatibel dengan `composer.lock`, MySQL/MariaDB, HTTPS, dan direktori dokumen privat. Paket ini telah diuji lokal pada PHP 8.3.35 dengan SQLite. Pengujian MySQL dan pemasangan pada akun hosting kampus belum dilakukan.

## 1. Periksa akun hosting

- Pilih PHP 8.3 atau versi lebih baru yang didukung dependensi. PHP untuk terminal dan domain harus sesuai.
- Aktifkan ekstensi yang diperlukan Laravel, termasuk `ctype`, `curl`, `dom`, `fileinfo`, `filter`, `hash`, `mbstring`, `openssl`, `pcre`, `PDO`, `pdo_mysql`, `session`, `tokenizer`, dan `xml`. `zip` diperlukan bila Composer mengekstrak arsip; `intl` direkomendasikan.
- Periksa kuota disk yang benar-benar tersisa, tidak hanya kapasitas nominal paket. Situs, email, basis data, log, seluruh versi berkas, dan lampiran dapat menggunakan kuota yang sama.
- Pastikan tersedia terminal/SSH, Composer 2, database, HTTPS, dan kemampuan mengarahkan document root ke `public`.

Dokumentasi Dewaweb menjelaskan pilihan versi PHP melalui cPanel. Ketersediaan versi pada akun kampus harus diperiksa langsung: [Versi PHP yang didukung](https://blog.dewaweb.com/knowledge-base/versi-php-apa-yang-di-support-oleh-dewaweb/).

## 2. Tempatkan aplikasi

Contoh tata letak di hosting, dengan `CPANEL_USER` diganti nama akun sebenarnya:

```text
/home/CPANEL_USER/ArsipKU/
  app/
  bootstrap/
  config/
  database/
  public/          <-- document root domain/subdomain
  resources/
  routes/
  storage/         <-- berkas privat, bukan document root
  vendor/
  .env
  artisan
```

Unggah dan ekstrak `ArsipKU-deployment-v1.0.zip` di folder aplikasi tersebut. Paket pemasangan menyertakan dependensi produksi, tetapi tidak memuat `.env` lokal, kunci aplikasi, basis data demo, atau arsip contoh. Jika memakai paket sumber, instal dependensi melalui Composer.

Document root domain/subdomain harus `/home/CPANEL_USER/ArsipKU/public`. Jika konfigurasi domain membatasi perubahan ini, minta bantuan Dewaweb untuk penyesuaiannya. Jangan mengarahkan document root ke akar proyek atau membuka folder arsip ke publik. Tidak diperlukan `storage:link` untuk dokumen privat.

Panduan provider: [Upload Laravel melalui cPanel](https://blog.dewaweb.com/cara-upload-laravel-ke-hosting/). Gunakan perintah dalam dokumen ArsipKU ini untuk pengelolaan aplikasi dan data.

## 3. Siapkan basis data dan environment

1. Buat database dan user database melalui cPanel. Berikan kewenangan user hanya pada database ArsipKU.
2. Salin `.env.hosting.example` menjadi `.env`.
3. Isi `APP_URL` dengan domain HTTPS sebenarnya dan konfigurasi `DB_*` sesuai database hosting.
4. Pastikan `APP_ENV=production`, `APP_DEBUG=false`, `SESSION_SECURE_COOKIE=true`, dan `SESSION_ENCRYPT=true`.
5. Lindungi `.env` dan kunci aplikasi agar hanya dapat diakses oleh pengelola hosting yang berwenang.

## 4. Jalankan pemasangan pertama

Gunakan binary PHP yang sama dengan domain; bila perintah `php` berbeda versi, ganti dengan path binary yang sesuai dari hosting.

```bash
cd /home/CPANEL_USER/ArsipKU
composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction
composer check-platform-reqs --no-dev
php artisan key:generate --force
php artisan migrate --force
php artisan db:seed --force
php artisan arsipku:admin EMAIL_KETUA
php artisan optimize
```

`EMAIL_KETUA` diganti alamat email Ketua. Perintah meminta kata sandi secara interaktif tanpa menampilkannya. Tidak tersedia kata sandi produksi bawaan. Seeder standar hanya membuat enam divisi dan kategori awal; tidak membuat akun maupun dokumen demo.

`key:generate` hanya dijalankan untuk pemasangan pertama dengan kunci kosong. Saat memperbarui aplikasi atau memulihkan backup, pertahankan `APP_KEY` yang sudah ada.

Pastikan `storage` dan `bootstrap/cache` dapat ditulis proses PHP dengan izin seperlunya. Pilihan izin bergantung konfigurasi hosting; hindari membuat seluruh proyek dapat ditulis publik.

## 5. Sesuaikan batas unggahan

Default aplikasi yang dapat diubah pada `.env`:

```dotenv
ARCHIVE_MAX_FILE_KB=20480
ARCHIVE_MAX_ATTACHMENTS=20
ARCHIVE_MAX_PACKAGE_KB=102400
```

Ini berarti 20 MB per berkas, 20 lampiran, dan 100 MB per paket versi. Nilai ini adalah default operasional, bukan batas permanen kebutuhan kampus.

Contoh konfigurasi PHP yang perlu disesuaikan dan disetujui berdasarkan kemampuan hosting:

```ini
upload_max_filesize=20M
post_max_size=128M
max_file_uploads=25
memory_limit=256M
max_execution_time=120
```

Jumlah unggahan PHP harus mencukupi satu berkas utama ditambah seluruh lampiran baru. Jika hosting membatasi konfigurasi di bawah contoh tersebut, turunkan batas aplikasi. Formulir menghitung jumlah lampiran terpilih dan server menolak paket jika jumlah yang diterima berbeda, untuk mencegah hilangnya sebagian lampiran tanpa diketahui.

Setelah mengubah environment:

```bash
php artisan optimize:clear
php artisan optimize
```

Uji batas unggahan pada hosting dengan berkas contoh. Periksa juga batas request pada web server, bukan hanya PHP.

## 6. Buat akun pimpinan

Masuk sebagai Ketua, lalu buka **Pengguna & Divisi**. Buat akun pribadi pimpinan untuk enam divisi. Pengguna wajib mengganti kata sandi sementara saat pertama masuk. Pendaftaran publik dan akun staf tidak tersedia.

Saat pergantian pejabat, nonaktifkan akun lama sebelum membuat akun pimpinan baru untuk divisi tersebut. Riwayat dan arsip tetap disimpan. Berikan kata sandi sementara kepada pemilik akun melalui saluran internal kampus yang sesuai.

## 7. Uji penerimaan di hosting

- Login Ketua dan pimpinan divisi, termasuk logout dan pergantian kata sandi awal.
- Unggah satu dokumen dengan beberapa lampiran, simpan draf, dan ajukan.
- Pastikan pengajuan terkunci dan seluruh lampiran dapat diperiksa Ketua.
- Uji keputusan revisi, pengajuan ulang, persetujuan, dan penolakan.
- Uji versi baru: versi aktif lama tetap tersedia sampai versi baru disetujui.
- Gunakan akun divisi lain untuk mengakses URL detail dan lampiran secara langsung; akses harus ditolak.
- Uji pencarian, ekspor, dashboard, serta notifikasi tanpa kebocoran antar divisi.
- Uji berkas besar dan kuota tidak mencukupi tanpa memakai data resmi.
- Pastikan source, `.env`, backup, dan folder berkas privat tidak dapat diakses dari web.
- Uji backup dan pemulihan pada lingkungan terpisah sebelum memasukkan arsip resmi.

## 8. Backup dan pemulihan

Cadangkan database, seluruh `storage/app/private/archives`, serta konfigurasi/kunci yang diperlukan. Backup disimpan di lokasi terpisah dari document root dan salinannya di luar akun hosting utama. Jadwal dan retensi ditetapkan kampus; usulan awal harian.

Untuk memperoleh snapshot database dan berkas yang konsisten, hentikan sementara perubahan dokumen selama pengambilan backup, misalnya melalui maintenance mode pada jadwal yang disepakati. Gunakan fasilitas database backup dan File Manager/backup hosting. Backup berkas harus mencakup semua versi dan lampiran.

Uji pemulihan pada aplikasi terpisah dengan versi source yang sesuai, database baru, berkas privat, dan `APP_KEY` yang sama. Jalankan pemeriksaan login, jumlah arsip, versi aktif, keputusan, dan unduhan contoh. Catat hasil dan durasi pemulihan. Backup belum dianggap terverifikasi sebelum uji ini berhasil.

## 9. Pembaruan aplikasi


1. Ambil backup konsisten dan pertahankan `.env`, `APP_KEY`, serta berkas privat.
2. Aktifkan maintenance mode sesuai kebutuhan perubahan.
3. Unggah source baru tanpa menimpa data operasional.
4. Jalankan `composer install --no-dev --prefer-dist --optimize-autoloader`, `php artisan migrate --force`, dan `php artisan optimize`.
5. Uji alur penting, kemudian nonaktifkan maintenance mode.

Gunakan `composer install` berdasarkan lock file yang sudah diuji. Jangan memakai `migrate:fresh`, `db:wipe`, atau seeder percobaan pada produksi.

## 10. Pemulihan akun Ketua

Pemegang akses terminal hosting dapat menjalankan:

```bash
php artisan arsipku:reset-password EMAIL_KETUA
```

Kata sandi dimasukkan secara interaktif. Sesi lama dicabut dan pengguna wajib mengganti kata sandi sementara saat masuk. Akses terminal hosting harus dibatasi kepada pihak kampus yang berwenang.

## Batas verifikasi

Dokumen ini merupakan panduan pemasangan. Akses akun Dewaweb, konfigurasi domain, pengujian MySQL/MariaDB, validasi beban produksi, dan uji pemulihan pada hosting kampus belum dilakukan oleh pengembang pada tahap ini.

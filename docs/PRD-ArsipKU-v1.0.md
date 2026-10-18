# Product Requirements Document — ArsipKU

**Institusi:** Sekolah Tinggi Teknologi Nusantara Lampung  
**Versi:** 1.0 — draf konsolidasi kebutuhan  
**Tanggal:** 18 Oktober 2026  
**Platform:** Web berbasis Laravel full stack  
**Hosting:** Dewaweb Warrior dengan cPanel

Dokumen ini menyatukan kebutuhan yang telah disampaikan pengguna. Bagian bertanda **usulan** merupakan rancangan awal yang belum ditetapkan sebagai keputusan kampus. PRD ini menjelaskan kebutuhan produk; bukan pernyataan bahwa aplikasi telah dibangun, diuji, atau dipasang.

## 1. Ringkasan produk

ArsipKU adalah sistem arsip digital internal kampus untuk menyimpan, mengelompokkan, mencari, dan menelusuri dokumen setiap divisi. Pengelolaan dilakukan langsung oleh pimpinan masing-masing divisi dan tidak didelegasikan kepada staf.

Setiap dokumen wajib memperoleh validasi Ketua sebelum menjadi arsip aktif. Ketua sekaligus memegang peran Administrator sehingga dapat melihat seluruh dokumen. Pimpinan divisi hanya dapat mengakses dokumen divisinya sendiri.

Satu dokumen terdiri atas satu berkas utama dan dapat memiliki beberapa lampiran. Berkas utama, metadata, serta seluruh lampiran dalam suatu pengajuan diperiksa sebagai satu paket versi.

## 2. Masalah dan tujuan

Sistem ditujukan untuk mengurangi kesulitan pencarian, ketidakjelasan kepemilikan, dan hilangnya riwayat perubahan dokumen yang dikelola secara terpisah.

Tujuan produk:

- Memusatkan pencatatan dan penyimpanan arsip digital kampus.
- Memastikan setiap arsip aktif sudah divalidasi Ketua.
- Mempermudah pencarian dokumen berdasarkan metadata yang konsisten.
- Memastikan akses dokumen mengikuti batas divisi.
- Menyediakan riwayat pengajuan, revisi, keputusan, dan pengunduhan.
- Mendukung pengoperasian dan pemulihan aplikasi pada hosting kampus.

Ukuran keberhasilan awal adalah kelulusan uji penerimaan: setiap pimpinan divisi dapat membuat, mengajukan, memperbaiki, dan menemukan dokumennya; Ketua dapat menelusuri seluruh dokumen dan menyelesaikan validasi; tidak terjadi kebocoran akses antar divisi. Target waktu pencarian dan kapasitas ditetapkan setelah volume dokumen serta beban penggunaan diketahui.

## 3. Keputusan kebutuhan yang telah dikonfirmasi

| Area | Keputusan |
|---|---|
| Teknologi | Laravel full stack. |
| Deployment | Hosting milik kampus, Dewaweb Warrior dengan cPanel. |
| Pengelola | Pimpinan setiap divisi secara langsung; tidak diwakilkan staf. |
| Validator | Ketua. |
| Administrator | Ketua juga memiliki peran Administrator. |
| Akses lintas divisi | Hanya peran Administrator dapat melihat seluruh dokumen. |
| Akses divisi | Pimpinan divisi tidak dapat melihat dokumen divisi lain. |
| Validasi | Seluruh dokumen wajib divalidasi sebelum menjadi arsip aktif. |
| Lampiran | Satu dokumen dapat memiliki beberapa lampiran sesuai kebutuhan. |

## 4. Pengguna dan struktur organisasi

| Pengguna | Peran aplikasi | Cakupan |
|---|---|---|
| Ketua | Administrator dan Validator | Seluruh dokumen, antrean validasi, dan administrasi sistem. |
| Wakil I Bidang Akademik | Pengelola Divisi | Dokumen bidang akademik. |
| Wakil II Bidang Keuangan dan Kepegawaian | Pengelola Divisi | Dokumen bidang keuangan dan kepegawaian. |
| Wakil III Bidang Kemahasiswaan dan Kerjasama | Pengelola Divisi | Dokumen bidang kemahasiswaan dan kerja sama. |
| Pimpinan LPPM | Pengelola Divisi | Dokumen LPPM. |
| Pimpinan LPM | Pengelola Divisi | Dokumen LPM. |
| Pimpinan Biro Administrasi Akademik Kemahasiswaan | Pengelola Divisi | Dokumen biro. |

Hak Administrator dan Validator dicatat sebagai kewenangan terpisah, meskipun diberikan kepada akun Ketua yang sama. Hak melihat seluruh dokumen berasal dari peran Administrator; hak memberi keputusan berasal dari peran Validator.

**Usulan operasional:** akun bersifat pribadi dan dibuat oleh Ketua melalui menu administrasi; tidak ada pendaftaran publik. Pergantian pejabat menggunakan akun baru dan menonaktifkan akun lama. Kepemilikan arsip tetap melekat pada divisi, sedangkan catatan pelaku tetap menunjuk individu yang melakukan aktivitas.

## 5. Matriks hak akses

| Tindakan | Pimpinan Divisi | Ketua: Administrator dan Validator |
|---|---|---|
| Melihat daftar, metadata, dan berkas divisi sendiri | Ya | Ya, seluruh divisi. |
| Melihat dokumen divisi lain | Tidak | Ya. |
| Membuat dan memperbaiki draf | Divisi sendiri. | Tidak menjadi alur pengunggahan rutin MVP. |
| Mengajukan dokumen | Divisi sendiri. | Tidak menjadi alur pengunggahan rutin MVP. |
| Mengubah pengajuan yang sedang diperiksa | Tidak. | Tidak; perbaikan dikembalikan kepada pengelola. |
| Menyetujui, meminta revisi, atau menolak | Tidak. | Ya. |
| Mengganti langsung berkas yang telah disetujui | Tidak. | Tidak. |
| Mencari, mengunduh, dan mengekspor daftar | Divisi sendiri. | Seluruh divisi. |
| Melihat riwayat dokumen | Divisi sendiri. | Seluruh divisi. |
| Mengelola akun, kategori, dan konfigurasi | Tidak. | Ya. |

Pembatasan berlaku pada antarmuka, URL langsung, endpoint, pencarian, pratinjau, unduhan, ekspor, notifikasi, dan seluruh lampiran. Sistem tidak boleh mengungkap judul atau jumlah dokumen divisi lain melalui hasil pencarian dan dashboard pengguna divisi.

## 6. Ruang lingkup MVP

Termasuk: autentikasi; pengelolaan akun dan divisi; kategori; metadata dokumen; unggah berkas utama dan beberapa lampiran; pengajuan dan validasi; revisi dan versi; pencarian metadata; filter; pratinjau PDF/gambar yang didukung; unduh; notifikasi dalam aplikasi; dashboard; riwayat aktivitas; ekspor daftar CSV; dokumentasi deployment serta backup dan pemulihan.

Di luar MVP: akun staf; delegasi pengelolaan; akses antar divisi; persetujuan oleh Wakil atau pihak lain; validasi bertingkat; persetujuan massal; pendaftaran publik; tautan berbagi publik; OCR dan pencarian isi berkas; integrasi sistem akademik/SSO; tanda tangan elektronik; penyuntingan Office di browser; aplikasi mobile native; pemusnahan otomatis; unggah rutin oleh Ketua.

Validasi ArsipKU merupakan persetujuan internal pengarsipan dan tidak diperlakukan sebagai tanda tangan elektronik.

## 7. Alur utama

1. Ketua membuat akun pimpinan dan menetapkan divisinya.
2. Pimpinan divisi membuat draf, mengisi metadata, dan mengunggah berkas utama serta lampiran jika diperlukan.
3. Sistem memvalidasi kelengkapan data dan keberhasilan setiap unggahan.
4. Pimpinan memilih **Ajukan Validasi**.
5. Paket versi dikunci untuk pemeriksaan dan masuk antrean Ketua.
6. Ketua melihat metadata, berkas utama, seluruh lampiran, dan riwayat pengajuan.
7. Ketua memilih **Setujui**, **Minta Revisi**, atau **Tolak**.
8. Persetujuan menjadikan versi tersebut arsip aktif. Keputusan lain menyimpan catatan wajib.
9. Pimpinan divisi menerima notifikasi dalam aplikasi.

## 8. Status dan keputusan validasi

| Status pengajuan | Arti dan transisi |
|---|---|
| Draf | Dapat diubah oleh pimpinan divisi, lalu diajukan menjadi Menunggu Validasi. |
| Menunggu Validasi | Tidak dapat diedit; Ketua memberi keputusan. |
| Perlu Revisi | Pimpinan memperbaiki data/berkas dan mengajukan kembali; putaran pemeriksaan sebelumnya tetap tercatat. |
| Disetujui | Paket versi menjadi arsip aktif dan isinya tidak dapat ditimpa. |
| Ditolak | Pengajuan ditutup dengan alasan; jika perlu diajukan kembali, dibuat pengajuan baru yang tetap merujuk riwayat sebelumnya. |

Aturan:

- Catatan wajib untuk permintaan revisi dan penolakan.
- Identitas validator, waktu, keputusan, dan versi yang diperiksa tersimpan otomatis.
- Dokumen belum disetujui dapat dilihat divisi pemilik dan Administrator untuk proses pengelolaan, tetapi tidak masuk daftar Arsip Aktif.
- Sistem mencegah keputusan ganda yang bertentangan pada pengajuan yang sama.
- **Usulan:** pengelola dapat menarik pengajuan yang belum diputuskan menjadi draf; tindakan tercatat dan pengajuan tersebut tidak dapat diputuskan lagi sampai diajukan ulang.

## 9. Berkas, lampiran, dan versi

Satu arsip memiliki satu atau lebih versi. Setiap versi mencakup metadata, satu berkas utama, dan nol atau lebih lampiran.

Ketentuan:

- Setiap lampiran memiliki nama/keterangan, nama berkas asli, jenis berkas, ukuran, dan waktu unggah.
- Jumlah lampiran dapat disesuaikan melalui konfigurasi sesuai kebutuhan dan kemampuan hosting; tidak ditetapkan sebagai batas permanen dalam PRD.
- Pengajuan hanya dapat dilakukan setelah semua unggahan yang dipilih berhasil atau unggahan gagal telah dibatalkan oleh pengguna.
- Lampiran mengikuti hak akses dan status pengajuan dokumen induknya.
- Persetujuan berlaku untuk seluruh paket versi; tidak ada persetujuan terpisah per lampiran pada MVP.
- Penambahan, penggantian, atau penghapusan lampiran dari versi yang sudah disetujui harus melalui revisi dan validasi kembali.
- Perubahan metadata atau berkas utama setelah persetujuan juga menghasilkan revisi baru.
- Versi aktif sebelumnya tetap tersedia selama revisi diperiksa. Revisi ditolak tidak menggantikannya.
- Riwayat versi menyimpan susunan berkas yang disetujui saat itu. Versi baru boleh menggunakan kembali berkas yang tidak berubah, selama riwayat lama tetap utuh.

Contoh: versi 1 berisi surat utama dan dua lampiran. Setelah disetujui, penambahan lampiran ketiga dilakukan pada versi 2. Versi 1 tetap aktif sampai versi 2 disetujui.

**Usulan unggahan awal:** PDF, DOCX, XLSX, PPTX, JPG, dan PNG; maksimal 20 MB per berkas. Jumlah lampiran dan ukuran total paket ditetapkan melalui konfigurasi setelah pemeriksaan batas hosting. Dokumen tetap dapat diunduh jika formatnya tidak mendukung pratinjau.

## 10. Metadata

| Data | Ketentuan |
|---|---|
| Kode arsip internal | Otomatis, unik, tetap untuk satu arsip lintas versi. |
| Judul | Wajib. |
| Divisi pemilik | Otomatis berdasarkan akun; tidak dapat dipilih menjadi divisi lain. |
| Kategori | Wajib. |
| Nomor dokumen | Opsional; berbeda dari kode arsip internal. |
| Tanggal dokumen | Wajib; terpisah dari waktu unggah. |
| Deskripsi dan kata kunci | Opsional. |
| Lokasi arsip fisik | Opsional: lemari, rak, map, atau keterangan lokasi. |
| Berkas utama dan daftar lampiran | Berkas utama wajib sebelum pengajuan; lampiran opsional. |
| Nomor versi dan catatan revisi | Otomatis untuk nomor; catatan wajib untuk revisi setelah persetujuan. |
| Pembuat, pengunggah, dan waktu | Otomatis. |
| Status, putaran pengajuan, dan keputusan | Dikelola sistem sesuai alur. |

Sistem memberi peringatan jika nomor dokumen yang sama ditemukan dalam divisi yang sama. Kemiripan tidak langsung memblokir pencatatan karena keputusan mengenai duplikasi dapat memerlukan pemeriksaan manusia. Peringatan tidak mengungkap dokumen divisi lain.

## 11. Kebutuhan fungsional

| ID | Kebutuhan |
|---|---|
| FR-01 | Login, logout, perubahan kata sandi, penonaktifan akun, serta prosedur pemulihan akses. |
| FR-02 | Ketua mengelola akun pimpinan, penugasan divisi, kategori, dan konfigurasi. |
| FR-03 | Pimpinan mengelola draf dan paket berkas divisinya. |
| FR-04 | Pengajuan, penguncian versi, dan antrean validasi Ketua. |
| FR-05 | Keputusan setujui, minta revisi, dan tolak dengan riwayat keputusan. |
| FR-06 | Pengelolaan revisi dan penentuan versi aktif tanpa menimpa versi lama. |
| FR-07 | Pencarian kode, judul, nomor dokumen, dan kata kunci; filter kategori, tanggal, serta status. Filter lintas divisi hanya untuk Administrator. |
| FR-08 | Pratinjau dan unduh berkas utama maupun setiap lampiran yang diizinkan. |
| FR-09 | Notifikasi dalam aplikasi untuk pengajuan baru dan hasil keputusan. |
| FR-10 | Dashboard pimpinan untuk divisinya; dashboard Ketua untuk seluruh divisi. |
| FR-11 | Riwayat pembuatan, perubahan, unggah, pengajuan, keputusan, dan unduhan. |
| FR-12 | Ekspor daftar CSV mengikuti cakupan akses dan filter. |
| FR-13 | Penampilan informasi jumlah/ukuran berkas per dokumen dan pemantauan penggunaan penyimpanan bagi Administrator. |

Usulan menu: Dashboard, Dokumen Divisi/Daftar Dokumen, Tambah Dokumen, Arsip Aktif, Validasi (Ketua), Notifikasi, Riwayat Aktivitas, dan Administrasi (Ketua).

## 12. Keamanan dan kualitas

- Seluruh operasi dokumen membutuhkan autentikasi dan otorisasi sisi server.
- Berkas utama dan lampiran disimpan secara privat di luar direktori publik.
- Aplikasi produksi menggunakan HTTPS dan menonaktifkan debug.
- Unggahan memeriksa ekstensi, MIME, ukuran, dan nama penyimpanan yang aman.
- Kata sandi menggunakan hashing bawaan Laravel dan percobaan login dibatasi.
- Riwayat aktivitas tidak dapat diedit atau dihapus melalui antarmuka, termasuk oleh Administrator.
- Kegagalan unggah tidak menghasilkan pengajuan dengan berkas yang hilang atau versi aktif yang rusak.
- Pencarian dan daftar menggunakan paginasi; proses daftar tidak memuat isi seluruh berkas ke memori.
- Bahasa antarmuka adalah Bahasa Indonesia dan tampilan mendukung komputer maupun ponsel.
- Timestamp disimpan secara konsisten dan ditampilkan dalam zona waktu kampus, Asia/Jakarta.

**Usulan target kinerja:** 95% permintaan daftar dan pencarian metadata selesai dalam tiga detik pada dataset, jumlah pengguna bersamaan, serta koneksi uji yang disepakati. Waktu transfer berkas diukur terpisah. Dataset dan beban uji harus ditetapkan sebelum target ini menjadi syarat penerimaan kontraktual.

## 13. Deployment dan kapasitas

Target deployment yang dikonfirmasi adalah Dewaweb Warrior dengan cPanel. Halaman resmi yang diperiksa pada 18 Oktober 2026 mencantumkan Warrior dengan 15 GB NVMe storage, 2 GB memory, 2 CPU cores, dan 10 MB/s disk I/O. Kuota aktual serta sisa kapasitas akun kampus tetap diperiksa di cPanel sebelum deployment. [Sumber resmi Dewaweb](https://www.dewaweb.com/hosting)

Usulan arsitektur: Laravel dengan Blade dan MySQL/MariaDB. Versi framework, PHP, ekstensi, dan basis data dipilih berdasarkan dukungan keamanan serta kompatibilitas akun hosting saat implementasi.

Kebutuhan deployment:

- Domain atau subdomain diarahkan ke direktori `public` Laravel; source, environment, dan arsip privat tidak terekspos.
- Aset frontend dibangun sebelum deployment jika build tidak tersedia di hosting.
- Proses inti tidak membutuhkan worker yang berjalan terus-menerus.
- Notifikasi dalam aplikasi menjadi kebutuhan MVP; email opsional setelah SMTP dikonfigurasi.
- Cron digunakan hanya jika fitur terjadwal dipakai dan tersedia pada akun.
- Batas per berkas, jumlah unggahan, ukuran request, waktu eksekusi, dan ruang kosong diperiksa sebelum batas aplikasi ditetapkan.
- Kapasitas memperhitungkan seluruh versi, lampiran, basis data, aplikasi, log, serta penggunaan lain pada akun hosting; tidak menganggap seluruh kuota tersedia bagi arsip.
- Pengujian dilakukan pada lingkungan terpisah menggunakan data contoh sebelum produksi.

Dewaweb memiliki panduan deployment Laravel melalui cPanel yang mencakup basis data, environment, versi PHP, dan document root. Panduan implementasi ArsipKU harus disesuaikan dengan versi aplikasi dan tidak menggunakan langkah yang menghapus data produksi. [Panduan resmi](https://blog.dewaweb.com/cara-upload-laravel-ke-hosting/)

## 14. Backup, pemulihan, dan penghapusan

Backup mencakup basis data, seluruh berkas dan lampiran lintas versi, serta konfigurasi yang diperlukan untuk pemulihan. Kunci aplikasi diamankan oleh pengelola berwenang. Backup harus dapat memulihkan hubungan antara arsip, versi, berkas, keputusan, dan pengguna.

**Usulan:** backup harian, salinan pada lokasi terpisah dari akun hosting utama, dan uji pemulihan sebelum serah terima. Jadwal, masa simpan, lokasi, penanggung jawab, serta target waktu pemulihan ditetapkan kampus. Keberadaan backup penyedia tidak menggantikan uji pemulihan aplikasi.

Arsip yang sudah disetujui tidak dihapus permanen melalui MVP. Pengarsipan nonaktif, kebijakan retensi, dan pemusnahan menunggu kebijakan kampus. **Usulan:** draf yang belum pernah diajukan dapat dipindahkan ke tempat sampah dan dipulihkan; pengajuan serta versi dengan riwayat keputusan tetap dipertahankan.

## 15. Kriteria penerimaan

| ID | Skenario dan hasil yang harus dipenuhi |
|---|---|
| AC-01 | Setiap pimpinan dari enam divisi dapat membuat dan mengajukan dokumen divisinya tanpa akun staf. |
| AC-02 | Pimpinan divisi A tidak dapat menemukan metadata, jumlah, berkas, atau lampiran divisi B melalui UI, URL langsung, maupun ekspor. |
| AC-03 | Ketua dengan peran Administrator dapat melihat dokumen seluruh divisi; dengan peran Validator dapat memberi keputusan. |
| AC-04 | Dokumen tanpa persetujuan tidak masuk Arsip Aktif. |
| AC-05 | Dokumen dapat diajukan tanpa lampiran atau dengan beberapa lampiran, dan susunannya tersimpan pada versi yang diperiksa. |
| AC-06 | Pengajuan dikunci; berkas dan metadata tidak dapat diubah saat menunggu keputusan. |
| AC-07 | Permintaan revisi/penolakan mewajibkan alasan dan menghasilkan notifikasi kepada pimpinan pemilik. |
| AC-08 | Revisi berkas utama, metadata, atau lampiran pada arsip disetujui memerlukan validasi ulang; versi aktif lama tetap tersedia sampai revisi disetujui. |
| AC-09 | Versi sebelumnya, lampiran, dan keputusan tetap dapat ditelusuri tanpa perubahan diam-diam. |
| AC-10 | Unggahan gagal atau ruang tidak cukup ditangani dengan pesan jelas, tanpa pengajuan tidak lengkap atau perubahan versi aktif. |
| AC-11 | Keputusan bersamaan tidak menghasilkan dua hasil validasi yang bertentangan. |
| AC-12 | Penonaktifan akun pejabat lama menghentikan akses, mempertahankan riwayat, dan tidak menghilangkan arsip divisi. |
| AC-13 | Pencarian, pratinjau, unduh, dashboard, dan ekspor mengikuti hak akses. |
| AC-14 | Aplikasi berjalan pada akun Dewaweb Warrior kampus dengan HTTPS dan penyimpanan privat. |
| AC-15 | Uji pemulihan berhasil mengembalikan contoh dokumen dengan beberapa lampiran, versi, dan keputusan validasinya. |

UAT melibatkan Ketua dan perwakilan pimpinan keenam divisi. Temuan akses tidak sah, hilangnya berkas, atau perubahan arsip disetujui tanpa validasi harus diselesaikan sebelum produksi.

## 16. Tahapan dan keluaran

1. **Finalisasi kebutuhan:** menyepakati usulan yang tersisa, kategori dokumen, contoh berkas, batas unggahan, dan kebijakan backup.
2. **Desain:** rancangan halaman, struktur basis data, matriks otorisasi, serta alur versi dan validasi.
3. **Pengembangan:** akun, arsip dan lampiran, pengajuan, validasi, pencarian, versi, notifikasi, dan audit.
4. **Verifikasi:** pengujian akses antar divisi, konsistensi paket versi, kegagalan unggah, dan UAT.
5. **Deployment:** pemasangan pada hosting, konfigurasi produksi, uji pemulihan, dan pelatihan pengguna.

Keluaran serah terima: kode sumber, migrasi basis data, konfigurasi contoh tanpa rahasia, panduan deployment, panduan Ketua dan pimpinan divisi, prosedur backup/pemulihan, serta hasil UAT. Estimasi waktu ditetapkan setelah desain dan batas operasional disepakati.

## 17. Keputusan operasional yang masih terbuka

- Kategori dan penamaan dokumen setiap divisi.
- Volume arsip awal, pertumbuhan bulanan, dan ukuran berkas yang umum.
- Batas ukuran berkas, jumlah lampiran, serta total paket yang sesuai kuota aktual.
- Domain/subdomain dan konfigurasi PHP/basis data yang tersedia.
- Prosedur pemulihan akun Ketua agar pengelolaan tidak terhenti saat akses hilang.
- Jadwal, lokasi, masa simpan backup, dan target pemulihan.
- Mekanisme ketika Ketua berhalangan; versi ini belum menyediakan validator pengganti.

Hal-hal tersebut tidak mengubah keputusan inti: pengelola adalah pimpinan divisi, dokumen antar divisi tertutup, Ketua merangkap Administrator dan Validator, dan setiap versi beserta lampirannya harus divalidasi sebelum aktif.

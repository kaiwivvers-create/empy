# Tabel Pengujian Black Box

**Aplikasi:** Empy — Sistem Evaluasi Kinerja Karyawan
**Tanggal pengujian:** 12 November 2026
**Metode:** Black box melalui request HTTP ke aplikasi dan pemeriksaan data pada database MySQL `emply`
**Keterangan status:** **Sesuai** = hasil aktual sama dengan hasil yang diharapkan

**Ringkasan:** 58 skenario, 58 sesuai. Pengujian dijalankan dengan 85 tes otomatis yang tersimpan di repositori dan 24 pemeriksaan verifikasi tambahan; seluruhnya lulus. Setiap pernyataan pada kolom Hasil Aktual berasal dari pengujian otomatis atau ditandai sebagai batasan.

### Prinsip pengelompokan

- **1. Autentikasi:** masuk, keluar, captcha, reset password, dan pembatasan akses awal.
- **2. Pengelolaan Data Karyawan dan Organisasi:** data master karyawan, impor, departemen, jabatan, relasi akun, bagan organisasi, foto, dan pencarian.
- **3. Evaluasi Kinerja:** pengaturan kriteria/periode dan seluruh alur evaluasi, 360° feedback, PIP, serta KPI.
- **4. Pelaporan dan Pemantauan:** laporan, ekspor, kalibrasi, progres, pengingat, dan riwayat aktivitas.
- **5. Administrasi dan Pengaturan Sistem:** akun pengguna, role, hak akses, branding, database, dan permintaan reset password.
- **6. Fitur Tambahan:** bahasa, crop foto, halaman error, profil, landing page, privasi super admin, dan proyek.

CRUD tidak dikumpulkan semua di bagian 2 karena setiap CRUD diuji bersama domainnya: CRUD pengguna/role berada di Administrasi, sedangkan CRUD kriteria/periode berada di Evaluasi Kinerja.

Nama role hanya ditulis pada skenario yang menguji batas akses role tersebut. Untuk fitur yang dapat dipakai pengguna mana pun, skenario dimulai langsung dengan aksinya.

---

## 1. Pengujian Autentikasi

| No. | Skenario | Hasil yang Diharapkan | Hasil Aktual | Status |
|---:|---|---|---|---|
| 1 | Login dengan email, password, dan captcha yang benar | Login berhasil, sesi dibuat, dan diarahkan ke halaman utama | Login berhasil, pengguna langsung masuk, dan diarahkan ke halaman utama | Sesuai |
| 2 | Login dengan password salah | Login ditolak dan muncul pesan kesalahan; percobaan berulang dibatasi sementara | Login ditolak dengan pesan kesalahan pada kolom email. Setelah 5 kali salah dalam 60 detik, login dengan email tersebut dikunci selama 60 detik penuh yang dihitung sejak kesalahan terakhir; percobaan berikutnya ditolak dengan pesan “Coba lagi dalam X detik” walaupun email dan password-nya benar, dan semua isian pada formulir dinonaktifkan sambil hitung mundur berjalan sampai kunci berakhir | Sesuai |
| 3 | Login dengan akun yang dinonaktifkan | Login ditolak dengan pesan akun nonaktif | Login ditolak dengan pesan kesalahan dan pengguna tetap belum masuk | Sesuai |
| 4 | Login dengan jawaban captcha salah atau kosong | Sistem menolak login sebelum kredensial diproses | Sistem menolak captcha dan login tidak diproses | Sesuai |
| 5 | Logout dari aplikasi | Sesi dihapus, diarahkan ke halaman login, dan halaman terlindungi tidak bisa diakses | Pengguna keluar dan diarahkan ke halaman login; halaman utama kembali meminta login | Sesuai |
| 6 | Meminta reset password dengan email terdaftar dan tidak terdaftar | Keduanya mendapat pesan konfirmasi yang sama di halaman, tanpa email terkirim; hanya email terdaftar yang membuat permintaan; permintaan berulang tidak menggandakan data | Pesan konfirmasi tampil di halaman, bukan lewat email; permintaan yang menunggu tindakan tercatat untuk admin; email yang tidak dikenal mendapat pesan yang sama tanpa membuat permintaan; permintaan berulang tetap tercatat satu kali | Sesuai |
| 7 | Admin mereset atau mengabaikan permintaan password | Password berubah dan permintaan selesai, seluruhnya di dalam aplikasi; permintaan yang diabaikan tidak mengubah password; konfirmasi tidak cocok ditolak | Admin berhasil mereset kata sandi langsung dari halaman permintaan, tanpa email; permintaan yang diabaikan tertutup tanpa mengubah kata sandi; konfirmasi yang tidak cocok ditolak | Sesuai |
| 8 | Tamu, role employee, role manager, dan admin mengakses halaman | Tamu diarahkan ke login; role employee tidak bisa membuka laporan; role manager tidak bisa membuka halaman admin; admin bisa | Tamu diarahkan ke halaman login; karyawan dan manager ditolak saat membuka halaman yang bukan haknya; admin dapat membukanya | Sesuai |

## 2. Pengujian Pengelolaan Data Karyawan dan Organisasi

| No. | Skenario | Hasil yang Diharapkan | Hasil Aktual | Status |
|---:|---|---|---|---|
| 9 | Admin mengelola data karyawan (tambah, ubah, arsip, pulihkan) | Data tersimpan dan berubah sesuai input; arsip bersifat soft delete; restore mengembalikan data | Admin berhasil menambah dan mengubah karyawan; arsip menyembunyikan data dari daftar karyawan aktif; memulihkan mengembalikan data ke daftar | Sesuai |
| 10 | Admin mengimpor karyawan dengan template yang benar | Seluruh kolom (NIP, nama, email, telepon, departemen, jabatan, atasan, tanggal masuk, status) tersimpan sesuai CSV | Karyawan dengan NIP E-200 tersimpan lengkap dengan email, telepon, departemen OPS, jabatan Staff, atasan E-002, tanggal masuk 15 Maret 2026, dan status nonaktif sesuai isi file yang diimpor | Sesuai |
| 11 | Admin mengimpor karyawan tanpa template lengkap atau dengan kolom berbeda | Import tetap berjalan; kolom opsional boleh tidak ada, urutannya berbeda, atau memakai alias; akun login dibuat; NIP duplikat dilewati | File yang hanya berisi kolom NIP, nama, dan email tetap berhasil diimpor; judul kolom lain seperti Nama, Employee ID, dan Email Address juga dikenali; akun login otomatis dibuat dengan peran karyawan dan kata sandi bawaan; NIP yang sudah terdaftar dilewati | Sesuai |
| 12 | Admin mengunduh template import | File CSV terunduh dengan header kolom lengkap dan baris contoh | File CSV terunduh berisi seluruh judul kolom (NIP, nama, email, sampai status) dan satu baris contoh | Sesuai |
| 13 | Admin mengelola departemen dan proteksi hapus | Departemen dapat ditambah, diubah, dan dihapus; hapus ditolak jika masih memiliki karyawan | Admin berhasil membuat, mengubah, dan menghapus departemen HRD; departemen OPS yang masih memiliki karyawan menolak dihapus | Sesuai |
| 14 | Admin mengelola jabatan dan proteksi hapus | Jabatan dapat ditambah, diubah, dan dihapus; hapus ditolak jika masih dipakai karyawan | Admin berhasil membuat, mengubah, dan menghapus jabatan Analyst; jabatan Staff yang dipakai karyawan menolak dihapus | Sesuai |
| 15 | Admin menghubungkan karyawan ke akun login dari form karyawan | Akun terpilih terhubung ke karyawan; akun yang sudah dipakai karyawan lain ditolak | Karyawan kini terhubung ke akun yang dipilih; akun yang sudah terhubung ke karyawan lain ditolak dengan pesan kesalahan | Sesuai |
| 16 | Admin menghubungkan akun ke karyawan dari form pengguna | Karyawan tanpa akun dapat dipilih; NIP tidak perlu diketik ulang; satu karyawan tidak bisa dipakai dua akun | Admin berhasil menghubungkan karyawan ke akun; sistem menolak percobaan menghubungkan karyawan yang sama ke akun kedua | Sesuai |
| 17 | Admin mengatur atasan dan kepala departemen pada bagan organisasi; manager hanya melihat | Admin dapat mengubah atasan/kepala; siklus atasan dicegah; manager hanya dapat melihat tanpa kontrol edit | Admin berhasil menyimpan perubahan atasan dan kepala departemen; atasan yang membentuk lingkaran ditolak; manager hanya dapat melihat bagan tanpa tombol untuk mengubah; permintaan langsung tetap ditolak | Sesuai |
| 18 | Mengunggah foto profil; admin mengunggah foto karyawan | Hasil crop disimpan sebagai gambar; foto lama diganti; foto dapat dihapus | Foto tersimpan dan tampil; foto lama diganti saat mengunggah foto baru; menghapus foto membuat foto profil kembali kosong | Sesuai |
| 19 | Admin mencari, memfilter, dan menampilkan arsip karyawan | Pencarian nama dan filter status/departemen menyaring data; mode arsip hanya untuk admin | Pencarian admin menampilkan hasil yang sesuai; filter nonaktif menyembunyikan karyawan aktif; mode arsip menampilkan karyawan yang diarsipkan | Sesuai |

## 3. Pengujian Evaluasi Kinerja

| No. | Skenario | Hasil yang Diharapkan | Hasil Aktual | Status |
|---:|---|---|---|---|
| 20 | Admin mengelola kriteria evaluasi dan bobot | Kriteria dapat ditambah, diubah, dan dihapus; bobot tersimpan; kriteria yang sudah dipakai tidak bisa dihapus | Admin membuat kriteria Communication, mengubah bobot menjadi 15, lalu menghapusnya; sistem menolak hapus kriteria yang sudah dipakai evaluasi | Sesuai |
| 21 | Admin mengelola periode evaluasi, aktivasi, dan penutupan | Periode dapat ditambah, diubah, dan diarsipkan; hanya satu periode aktif; periode dapat ditutup | Admin membuat periode baru yang langsung aktif dan membuat periode sebelumnya menjadi draft; status ditutup tersimpan; periode yang diarsipkan tidak lagi muncul di daftar utama | Sesuai |
| 22 | Manager membuat evaluasi draft untuk anggota tim | Draft tersimpan; evaluasi untuk karyawan di luar tim dan duplikat periode ditolak | Manager berhasil membuat draft; karyawan di luar tim ditolak; evaluasi ganda pada periode yang sama ditolak | Sesuai |
| 23 | Manager mengisi dan menyimpan skor kriteria sebagai draft | Skor 1–5 tersimpan per kriteria; semua kriteria aktif wajib dinilai | Skor draft tersimpan; sistem menolak kriteria yang belum dinilai dan skor 6 (di luar 1–5) | Sesuai |
| 24 | Manager submit evaluasi dan sistem menghitung nilai akhir berbobot | Nilai akhir 0–100 adalah rata-rata berbobot kriteria; status berubah menjadi submitted; grade A–E muncul | Contoh skor 4 dari 5 menghasilkan nilai 80 dengan predikat B; status berubah menjadi terkirim | Sesuai |
| 25 | Manager mencoba mengubah evaluasi submitted; admin melakukan reopen | Evaluasi submitted tidak dapat diubah; hanya admin yang dapat reopen | Manager tidak dapat mengubah evaluasi yang sudah dikirim; tombol buka kembali tidak tampil untuk manager; admin berhasil membuka kembali evaluasi tersebut | Sesuai |
| 26 | Karyawan melihat evaluasi miliknya | Karyawan hanya bisa melihat evaluasi sendiri; evaluasi orang lain ditolak | Karyawan dapat membuka evaluasinya sendiri; evaluasi milik karyawan lain ditolak | Sesuai |
| 27 | Mengunduh PDF evaluasi | File PDF evaluasi terunduh | Berkas PDF terunduh dan dapat dibuka | Sesuai |
| 28 | Admin atau manager yang berwenang mengirim permintaan 360° feedback dan memilih reviewer | Permintaan tersimpan untuk reviewer yang dipilih; reviewer diri sendiri, evaluator, dan duplikat ditolak | Permintaan tersimpan; menunjuk diri sendiri dan penilai utama sebagai penilai ditolak; orang yang sama tidak bisa diundang dua kali | Sesuai |
| 29 | Reviewer mengisi 360° feedback per kriteria | Rating setiap kriteria tersimpan; peer score dihitung berbobot; final = 70% manager + 30% peer | Penilaian setiap kriteria tersimpan; nilai dari rekan kerja ikut dihitung sesuai bobotnya; nilai manager 80 dan rekan kerja 80 menghasilkan nilai akhir 80 | Sesuai |
| 30 | Membuat dan menyelesaikan rencana perbaikan (PIP) | Rencana dapat dibuat, status diubah, dan `completed_at` terisi saat selesai | Rencana berhasil dibuat; status selesai tersimpan beserta tanggal penyelesaiannya | Sesuai |
| 31 | Mengelola target KPI; role employee tidak dapat mengelola | Target dan capaian dapat ditambah, diubah, dan dihapus; role employee tidak dapat mengelola KPI | Menambah, mengubah, dan menghapus target KPI berhasil; tombol pengelolaan tidak tampil untuk karyawan; permintaan langsung tetap ditolak | Sesuai |
| 32 | Menandai KPI selesai, membuka kembali, dan mengedit capaian periode lama | KPI hanya bisa selesai saat capaian ≥ target; status bisa dibuka kembali; capaian lama tetap editable dan target terkunci | KPI ditandai selesai dan sistem mencatat tanggal penyelesaiannya; klik berikutnya membatalkan status selesai; capaian periode lama berubah sedangkan judul dan target tetap | Sesuai |

## 4. Pengujian Pelaporan dan Pemantauan

| No. | Skenario | Hasil yang Diharapkan | Hasil Aktual | Status |
|---:|---|---|---|---|
| 33 | Admin dan manager membuka laporan serta memakai filter periode/departemen | Ringkasan, ranking, rata-rata, completion, dan chart tampil; filter menyaring data | Halaman laporan menampilkan ringkasan, peringkat, grafik, dan data karyawan; filter departemen menyaring data | Sesuai |
| 34 | Admin dan manager mengekspor laporan ke PDF | File PDF terunduh | Berkas PDF terunduh dan dapat dibuka | Sesuai |
| 35 | Admin dan manager mengekspor laporan ke Excel | File XLSX terunduh | Berkas Excel (XLSX) terunduh dan dapat dibuka | Sesuai |
| 36 | Admin dan manager membuka halaman kalibrasi | Rata-rata perusahaan, rata-rata, selisih, dan rentang nilai per evaluator tampil | Halaman kalibrasi menampilkan rata-rata perusahaan 80.0, penilai Manager, dan rentang 80.0–80.0 | Sesuai |
| 37 | Admin dan manager memantau progres evaluasi | Status per karyawan (submitted/draft/pending) dan completion tampil; hanya admin/manager | Admin dan manager dapat membuka halaman; halaman menampilkan persentase penyelesaian, status Terkirim, nilai 80.0, dan daftar karyawan; karyawan ditolak membuka halaman ini | Sesuai |
| 38 | Sistem membuat pengingat evaluasi untuk manager | Pengingat dibuat untuk setiap manager yang masih punya bawahan belum dinilai; satu pengingat per periode | Pengingat dibuat untuk manager yang masih punya bawahan belum dinilai dengan jumlah bawahan yang sesuai; isi email pengingat tercatat di berkas log aplikasi karena pengiriman email belum diaktifkan | Sesuai |
| 39 | Admin me-revert riwayat aktivitas dan menghapus log permanen | Perubahan dapat direvert; log dapat dihapus permanen | Membatalkan perubahan mengembalikan data sebelumnya; menghapus permanen menghilangkan catatan riwayat | Sesuai |
| 40 | Admin, manager, dan employee membuka dashboard sesuai peran | Menu dan halaman mengikuti hak akses; halaman terlarang menghasilkan 403 | Admin melihat menu Pengguna dan Riwayat Aktivitas; manager dan karyawan tidak melihatnya; halaman terlarang menolak akses | Sesuai |
| 41 | Memakai pencarian/filter pada laporan serta proyek | Data tersaring sesuai periode, departemen, kata kunci, dan status; reset mengembalikan data | Filter periode/departemen pada laporan dan filter/pencarian proyek menyaring data; reset menampilkan kembali data | Sesuai |

## 5. Pengujian Administrasi dan Pengaturan Sistem

| No. | Skenario | Hasil yang Diharapkan | Hasil Aktual | Status |
|---:|---|---|---|---|
| 42 | Admin mengelola pengguna (tambah, ubah, aktif/nonaktif) | Akun tersimpan dengan email unik dan role yang dipilih; perubahan data dan status tersimpan | Admin berhasil membuat dan mengubah pengguna; email yang sudah dipakai ditolak; status aktif/nonaktif tersimpan | Sesuai |
| 43 | Admin mencoba menghapus akun sendiri; super admin mencoba demosi dirinya | Aksi ditolak dengan pesan kesalahan | Menghapus akun sendiri ditolak; menurunkan peran super admin ditolak dan perannya tetap super admin | Sesuai |
| 44 | Admin menghubungkan akun dan karyawan secara satu-ke-satu | Satu akun hanya boleh terhubung ke satu karyawan | Sistem menolak percobaan menghubungkan karyawan yang sama ke akun kedua, baik saat disimpan maupun melalui aturan satu karyawan untuk satu akun | Sesuai |
| 45 | Super admin mengelola role kustom | Role baru dengan base manager mewarisi default akses manager | Super admin berhasil menyimpan peran baru; peran tersebut otomatis mewarisi hak akses bawaan manager | Sesuai |
| 46 | Super admin mengubah dan mereset matriks hak akses | Perubahan tersimpan dan langsung berlaku; reset mengembalikan ke default | Perubahan hak akses tersimpan dan langsung berlaku; peran yang diblokir tidak bisa membuka halaman tersebut; pengaturan ulang berhasil mengembalikan hak akses bawaan | Sesuai |
| 47 | Super admin mengubah pengaturan branding | Nama dan tagline aplikasi berubah dan tampil di UI | Super admin mengubah nama menjadi "Acme" dan slogan "People First"; keduanya tampil di halaman utama | Sesuai |
| 48 | Super admin mengekspor database (backup SQL) | File backup `.sql` terunduh | Berkas cadangan SQL terunduh | Sesuai |
| 49 | Super admin mengimpor database dengan validasi dan backup pengaman | Impor wajib konfirmasi; SQL tidak valid ditangani dengan rollback, pesan error, dan backup otomatis | Impor tanpa konfirmasi ditolak; file SQL yang tidak valid ditolak dengan pesan kesalahan, data lama tetap utuh, dan satu berkas cadangan otomatis dibuat lebih dulu | Sesuai |
| 50 | Super admin mengunduh dan menghapus file backup | File backup dapat diunduh dan dihapus | Berkas cadangan berhasil diunduh dan dihapus | Sesuai |
| 51 | Admin mengelola permintaan reset kata sandi; manager tidak boleh masuk | Admin dapat melihat daftar, mereset, dan mengabaikan permintaan; manager tidak boleh masuk | Admin berhasil mereset dan mengabaikan permintaan; menu Permintaan Kata Sandi hanya tampil untuk admin; manager yang membuka alamat halaman itu secara langsung tetap ditolak | Sesuai |

## 6. Pengujian Fitur Tambahan

| No. | Skenario | Hasil yang Diharapkan | Hasil Aktual | Status |
|---:|---|---|---|---|
| 52 | Mengubah bahasa aplikasi antara Indonesia dan Inggris | Bahasa berganti, tersimpan di sesi/cookie, dan halaman error ikut berbahasa sesuai pilihan | Halaman utama menampilkan teks berbahasa Inggris; pilihan bahasa tetap tersimpan saat halaman dimuat ulang; halaman error mengikuti bahasa yang dipilih | Sesuai |
| 53 | Crop dan unggah foto profil/karyawan | Hasil crop disimpan sebagai gambar dan ditampilkan | Hasil potongan foto tersimpan dan foto lama diganti; interaksi zoom dan putar pada jendela potong foto tidak diuji otomatis | Sesuai |
| 54 | Membuka halaman error kustom (404/403) | Halaman error tampil dengan judul dan pesan yang sesuai, mengikuti bahasa aktif | Halaman error kustom untuk data tidak ditemukan (404) dan akses ditolak (403) tampil dengan pesan sesuai bahasa | Sesuai |
| 55 | Memperbarui profil dan mengganti password | Nama, email, dan avatar tersimpan; password baru berlaku; password lama salah ditolak | Profil dan foto profil tersimpan; kata sandi berhasil diubah; kata sandi lama yang salah ditolak | Sesuai |
| 56 | Tamu membuka landing page; akun yang sudah login diarahkan sesuai peran | Tamu melihat landing page dengan tombol login; akun yang sudah login diarahkan ke halaman sesuai peran | Halaman depan (landing page) tampil untuk tamu; pengguna yang sudah masuk diarahkan ke halaman sesuai perannya | Sesuai |
| 57 | Admin, manager, dan employee mengakses data super admin | Role dan data karyawan super admin tidak terlihat oleh peran lain | Akun super admin dan data karyawannya tidak terlihat oleh admin, manager, maupun karyawan | Sesuai |
| 58 | Admin mengelola semua proyek; manager mengelola proyeknya; employee melihat proyek yang diikuti | Proyek dapat dibuat, diubah, dan dihapus; anggota dapat ditambah/dihapus; manager hanya mengelola proyeknya, employee hanya melihat proyek tempat ia anggota | Menambah, mengubah, dan menghapus proyek beserta anggotanya berhasil; aturan akses untuk manager dan karyawan berjalan sesuai harapan | Sesuai |

---

### Catatan

- Seluruh skenario diuji pada database uji terpisah dengan data contoh (admin, manager, employee, departemen OPS, jabatan Staff, periode Annual 2026, dan satu kriteria).
- Untuk fitur yang memakai CRUD, beberapa aksi digabung dalam satu baris pengujian agar tabel lebih ringkas.
- Fitur potong foto diuji sampai hasil potongannya tersimpan; interaksi geser, zoom, dan putar pada jendela potong foto berjalan di browser dan belum diuji otomatis.
- Untuk aksi yang tidak diizinkan, tombol ubah atau buka kembali memang tidak ditampilkan; penolakan tetap ada sebagai pengaman bila halaman dibuka langsung, bukan bagian dari alur normal.
- Aplikasi belum tersambung ke layanan pengiriman email, sehingga tidak ada email yang benar-benar sampai ke kotak masuk. Semua alur berjalan lewat tampilan aplikasi: permintaan reset kata sandi masuk ke halaman admin untuk diproses, dan pengingat evaluasi hanya menuliskan isi emailnya ke berkas log aplikasi. Pengiriman email sungguhan baru bisa terjadi setelah layanan email diatur.
- “Tamu” berarti pengguna belum masuk, bukan akun atau peran tertentu. Tidak ada akun tamu; akun nonaktif tetap akun biasa yang ditolak saat login.
- Pembatasan percobaan login dihitung per kombinasi email dan alamat IP dengan batas 5 kegagalan per 60 detik. Setelah batas tercapai, login dikunci 60 detik yang dihitung dari kegagalan terakhir (bukan kegagalan pertama). Hitungan dan waktu kunci disimpan di penyimpanan sementara aplikasi sehingga berlaku di semua permintaan; login berhasil menghapus hitungan, dan setelah kunci berakhir percobaan kembali normal.
- Saat login terkunci, seluruh isian formulir dinonaktifkan dan hitung mundur ditampilkan. Status terkunci, sisa waktu, dan penonaktifan isian diperiksa dari halaman yang dikirim server; gerakan hitung mundur di layar berjalan di browser dan belum diuji otomatis.

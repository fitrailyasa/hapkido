# HAPKIDO Championship 2026 — Sistem Informasi Pertandingan

Sistem manajemen pertandingan Taekwondo/Hapkido (Hapkido Championship 2026, Lampung).
Dibangun dengan **Laravel 11 + AdminLTE 4 (Bootstrap 5) + SQLite + Spatie Permission + Maatwebsite Excel**.

Alur inti sistem:

```
DATA ATLET → JADWAL → CALLING → SCAN → PEMERIKSAAN → PERLENGKAPAN → SIAP BERTANDING
      → PERTANDINGAN → HASIL → BRACKET / RANKING → JUARA
```

### Terminologi di layar

| Istilah | Arti | Sumber data (tidak berubah) |
|---|---|---|
| **Kelas** | Unit pertandingan, mis. "Dewasa Putra -58kg". Sebelumnya di layar disebut "Kategori". | `categories` (`name`, `gender`, `age_class`/tingkat) |
| **Kategori** | Jenis pertandingan: **Daeryun** atau **Seni**. Sebelumnya di layar disebut "Jenis". | `categories.type`, `schedules.type`, `arenas.match_type` |

> Hanya **teks di layar** yang berubah. Nama tabel, kolom, route, dan file di backend tetap
> seperti semula (`categories`, `category_id`, `type`, ...). Istilah "Jenis Kelamin" (gender)
> dan "Jenis" pada modul perlengkapan tidak ikut berubah.

---

## 1. Login & Hak Akses (Wajib)

| Role | Akses |
|---|---|
| **Operator** | Full back-office: data atlet, jadwal, calling, scan, kesiapan, perlengkapan, daeryun, seni, bracket, hasil, arena, riwayat |
| **Coach** | Read-only: atlet binaan, jadwal & calling, status pertandingan (tidak bisa mengubah data) |
| **Manajer Tim** | Kontingen/asuhan sendiri: atlet, jadwal & calling, hasil/ranking, profil tim |
| **Public Display** | **Tanpa login** → halaman `/display` (layar venue) |
| **Administrator** | Semua akses + manajemen user, role & permission |

- Login: username (email) + password + **pilihan role**.
- User bisa **diaktifkan / dinonaktifkan** (user nonaktif tidak bisa login).
- Hak akses per halaman memakai middleware `permission:` (Spatie).
- Menu sidebar tampil sesuai permission user.

---

## 2. Data Atlet (Wajib)

- Identitas atlet: nama, jenis kelamin, tanggal lahir, no. identitas.
- Kontingen / tim (tabel `contingents`).
- Kelas (tabel `categories`: Daeryun, Hyung, Hosinsul, Nakbop, Lompat Tinggi, Seni Senjata + gender/tingkat).
- Nomor peserta (unik) + **QR code atlet** (untuk scan).
- Status atlet: aktif / tidak aktif.
- CRUD penuh (modal), search + pagination dari backend.

## 3. Jadwal & Match (Wajib)

- Pembuatan jadwal/partai: **nomor match**, kelas, arena, urutan pertandingan, jam mulai/akhir, ronde (Penyisihan → Perempat Final → Semifinal → Final).
- **Ronde memakai dropdown** (`Penyisihan`, `Perempat Final`, `Semifinal`, `Final`) pada form tambah & ubah jadwal. Jadwal Seni memakai ronde `Penampilan`, jadi pilihan ronde mengikuti jenis pertandingan. Nilai yang diimpor dari Excel dinormalisasi ke daftar yang sama.
- Tipe jadwal: **Daeryun** (VS) atau **Seni** (urutan tampil).
- **Export & import Excel** (`maatwebsite/excel`) + download template `.xlsx`.

## 4. Pengaturan Arena (Wajib)

- Arena A / B / C.
- Kategori pertandingan tiap arena (Daeryun / Seni).
- **Perubahan fungsi arena** (ganti kategori/fungsi yang sedang berjalan).
- Status arena: Persiapan / Tampil / Menunggu (untuk dashboard).

## 5. Calling Atlet — Fitur Utama

- Calling **30 menit**, **15 menit**, **5 menit** sebelum tanding.
- Status: `waiting` (menunggu) → `called` (terpanggil) → `ready` (siap).
- Tombol kirim calling per atlet + daftar calling aktif.
- Perubahan status dikirim ke **public display & dashboard** (realtime).
- **Pemilihan tanggal**: halaman memakai jadwal hari ini. Kalau hari ini belum ada jadwal,
  halaman otomatis memakai tanggal terakhir yang punya jadwal (ditandai dengan badge kuning) dan
  tanggal lain bisa dipilih lewat input tanggal.

## 6. Verifikasi / Scan Atlet — Fitur Utama

- **Scan QR / kartu atlet** (input kode QR) atau input manual.
- Konfirmasi kehadiran + waktu kedatangan.
- Status: **Hadir / Belum Hadir**.

## 7. Pemeriksaan Kesiapan — Fitur Utama

- Cek kehadiran → cek atlet → kelengkapan perlengkapan.
- Status akhir: **Siap Bertanding**.

## 8. Manajemen Perlengkapan — Fitur Utama

- Item: **Head Guard**, **Body Protector** (+ warna, ukuran, kode alat).
- Peminjaman & pengembalian (per atlet/partai).
- **Riwayat alat** (riwayat peminjaman & pengembalian).

## 9. Daeryun — Pertandingan VS (Wajib)

- Menampilkan **atlet vs atlet**.
- Mulai pertandingan, status pertandingan, input pemenang, simpan hasil.
- **Otomatis lanjut ke babak berikutnya** (winner masuk bracket berikutnya).

## 10. Bracket Otomatis (Wajib)

- Penyisihan → Perempat Final → Semifinal → Final → **Juara**.
- Generate otomatis dari daftar peserta per kelas, propagasi pemenang.
- **Bracket juga tampil di public display** untuk arena bertipe Daeryun (lihat bagian 14).
- Logika bracket dipakai bersama oleh halaman admin dan display lewat `App\Services\BracketViewerService`
  (`public/vendor/brackets/brackets-viewer.js`, tanpa CDN).

## 11. Cabang Seni (Wajib)

- Hyung, Hosinsul, Nakbop, Lompat Tinggi, Seni Senjata.
- **Nomor urut tampil** (bukan sistem VS — mekanisme terpisah dari Daeryun).

## 12. Input Nilai Juri (Wajib)

- Nilai Juri 1, Juri 2, Juri 3.
- Perhitungan nilai akhir + **ranking otomatis** (untuk cabang seni).

## 13. Hasil & Ranking (Wajib)

- Hasil tiap match, ranking per kelas.
- Juara 1 / 2 / 3 + perolehan medali per kontingen.

## 14. Public Display (Pendukung) — `/display`, tanpa login

- Match yang sedang berlangsung, atlet yang akan bertanding.
- Calling, hasil pertandingan, atlet seni yang akan tampil.
- **Bracket Daeryun per arena**: setiap arena dengan tipe Daeryun menampilkan bracket kelas yang
  sedang dimainkan di arena tersebut. Halaman arena (`/display/arena/{id}`) menampilkannya juga.

## 15. Dashboard Real-Time (Pendukung)

- Status Arena A/B/C, match aktif, status persiapan, status pertandingan.
- **Realtime: AJAX polling tiap 3 detik** (tanpa websocket/queue).

## 16. Riwayat (Pendukung)

- Riwayat pertandingan, riwayat hasil, riwayat peminjaman alat, riwayat pengembalian.

## 17. Manajemen User & Role (Pendukung)

- Tambah user, pilih role, **aktivasi/nonaktif user**.
- **CRUD Role** + halaman **setting permission per role** (centang permission).

## 18. Validasi Konflik Pertandingan (Wajib)

Semua aturan konflik dikumpulkan di satu service: `App\Services\ScheduleGuardService`. Controller cukup
memanggilnya dan menampilkan pesan yang sudah siap pakai (bahasa Indonesia).

| Aturan | Kapan dicek |
|---|---|
| Jadwal/arena belum ditentukan | Mulai pertandingan (Daeryun & Seni) |
| Arena masih dipakai pertandingan/jadwal yang belum selesai | Mulai pertandingan, buat & ubah jadwal |
| Jam selesai lebih awal dari jam mulai | Buat & ubah jadwal |
| Rentang waktu bertumpuk dengan jadwal lain di arena yang sama | Buat & ubah jadwal |
| Jadwal yang sedang berjalan tidak boleh dihapus | Hapus jadwal |
| Atlet yang sama sedang tampil di arena lain | Mulai pertandingan |

Contoh pesan: `Arena A masih dipakai oleh Partai M-01 yang belum selesai. Selesaikan Partai M-01 terlebih dahulu.`

---

## Prioritas Modul

| Modul | Prioritas | Status |
|---|---|---|
| Login & Role | Wajib | [ ] |
| Data Atlet | Wajib | [ ] |
| Jadwal (+ Excel) | Wajib | [ ] |
| Calling Atlet | Fitur utama | [ ] |
| QR/Scan Verifikasi | Fitur utama | [ ] |
| Pemeriksaan Kesiapan | Fitur utama | [ ] |
| Perlengkapan | Fitur utama | [ ] |
| Daeryun | Wajib | [ ] |
| Seni | Wajib | [ ] |
| Input Nilai | Wajib | [ ] |
| Bracket | Wajib | [ ] |
| Ranking | Wajib | [ ] |
| Public Display | Pendukung | [ ] |
| Dashboard Real-Time | Pendukung | [ ] |
| Riwayat | Pendukung | [ ] |
| Manajemen User | Pendukung | [ ] |
| Validasi Konflik | Wajib | [ ] |

---

## Kebijakan Teknis

- **Nama folder & file CRUD berbahasa Inggris** (`app/Http/Controllers/Admin/AthleteController.php`,
  `resources/views/admin/athlete/`, `database/migrations/create_athletes_table.php`).
- **Tampilan / teks UI berbahasa Indonesia.**
- **Kode & komentar dalam bahasa Inggris**, teks yang dilihat user tetap bahasa Indonesia.
- **CRUD berbasis modal** (create/edit/show/delete), table **pagination + search dari backend**.
- **Field wajib ditandai bintang merah** (`<span class="text-danger">*</span>`) pada label form.
- Template **AdminLTE 4** (`public/dist`) untuk table, button, sidebar, card.
- **Tema default LIGHT** (mode dark dimatikan).
- **SweetAlert2 offline** (`public/vendor/sweetalert2`) untuk semua alert sukses/gagal/konfirmasi.
- **Aset 100% lokal/offline**: tidak ada CDN di dalam view. Bootstrap Icons, AdminLTE, SweetAlert2,
  Quagga (scanner), dan brackets-viewer semuanya ada di `public/vendor` & `public/dist`.
- **Realtime: AJAX polling** (dashboard & public display refresh otomatis tiap ~3 detik).

## Menjalankan Sistem

### Manual (PHP lokal)

```bash
composer install
npm install && npm run build
php artisan key:generate
php artisan migrate --seed
php artisan serve
```

Aplikasi: `http://127.0.0.1:8000` · Login demo: `test@example.com` / `password` (Administrator).

### Docker (disarankan untuk venue)

```bash
cp .env.docker.example .env.docker
docker compose up -d --build
```

Aplikasi: `http://localhost:8080`

| Perintah | Fungsi |
|---|---|
| `docker compose up -d --build` | Build image + jalankan container (app + nginx). |
| `docker compose logs -f app` | Lihat log aplikasi. |
| `docker compose exec app php artisan migrate --seed` | Jalankan migrasi & seeder di dalam container. |
| `docker compose down` | Hentikan container (data SQLite tetap aman di volume). |

- `Dockerfile` (multi-stage: composer → vite → php-fpm) + `docker/php/entrypoint.sh` (migrasi otomatis
  saat start) + `docker/nginx/default.conf`.
- Database SQLite disimpan di volume `app_database`, jadi data tetap ada setelah `docker compose down`.
- `APP_KEY` pada `.env.docker` dikosongkan, container mengisinya otomatis saat pertama kali start.
- `.env.docker` sudah masuk `.gitignore`; file contoh `.env.docker.example` ikut di-commit.

## Struktur Modul (file)

```
app/Http/Controllers/Admin/
  DashboardController, AthleteController, ContingentController, CategoryController,
  ScheduleController (+ Excel export/import), ArenaController, CallingController,
  VerificationController, ReadinessController, EquipmentController, EquipmentLoanController,
  MatchController (Daeryun), PerformanceController (Seni), ScoreController,
  BracketController, ResultController, HistoryController, RoleController, AdminUserController
app/Services/
  ScheduleGuardService (validasi konflik jadwal & pertandingan),
  ScheduleDayService (tanggal Calling & public display),
  BracketService (generate bracket), BracketViewerService (data bracket untuk display),
  ArenaLiveService (state live arena)
app/Exports/ScheduleExport.php   app/Imports/ScheduleImport.php
resources/views/admin/{athlete,schedule,arena,calling,verification,readiness,
  equipment,match,performance,score,bracket,result,history,role,user}/
resources/views/display/  (public)
docker/  (php-fpm image, entrypoint, healthcheck, konfigurasi nginx)
```

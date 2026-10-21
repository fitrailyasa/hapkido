# Build Windows EXE (Installer) — Hapkido Championship

Paket ini mengubah project Laravel 11 menjadi **installer EXE** untuk Windows.
Komputer tujuan **tidak perlu** install PHP, Composer, Node, atau Laravel.

| Komponen | Isi |
|---|---|
| Launcher | `Hapkido.exe` (C# / .NET Framework 4.8) |
| Runtime | PHP 8.3 NTS x64 + `php.ini` (SQLite, OpenSSL, GD, Zip, Intl, …) |
| Database | SQLite (`database/database.sqlite`, dibuat saat first run) |
| Installer | `Setup-Hapkido.exe` (Inno Setup 6) |

Alur di mesin pengguna:

```
Setup-Hapkido.exe → install → Hapkido.exe
  → buat .env + APP_KEY
  → buat SQLite + migrate + ProductionSeeder
  → php artisan serve → buka browser
```

---

## Prasyarat di mesin build (developer)

| Tool | Versi / catatan |
|---|---|
| Windows 10/11 x64 | mesin build |
| PHP | 8.2+ di PATH (untuk `composer` di staging) |
| Composer | di PATH |
| Node.js + npm | untuk `npm run build` |
| .NET Framework 4.x | biasanya sudah ada (`csc.exe`) **atau** .NET SDK |
| Inno Setup 6 | untuk `Setup-Hapkido.exe` — [download](https://jrsoftware.org/isdl.php) |
| Visual C++ Redistributable 2015–2022 x64 | dibutuhkan PHP di mesin **pengguna**; biasanya sudah terpasang di Windows 10/11 |

Opsional: set execution policy sekali untuk PowerShell:

```powershell
Set-ExecutionPolicy -Scope CurrentUser RemoteSigned
```

---

## Struktur folder

```
tools/build-exe/
  package.ps1          # script build utama
  build.cmd            # wrapper: double-click / cmd
  installer.iss        # skrip Inno Setup
  env.production       # template .env (disalin jadi .env saat first run)
  php.ini              # php.ini untuk runtime yang di-bundle
  server.txt           # host/port default
  launcher/
    Launcher.cs
    Hapkido.csproj
  cache/               # PHP zip + cacert (di-gitignore)
  dist/                # output build (di-gitignore)
    app/               # folder portable siap jalan
    Setup-Hapkido.exe  # installer
```

---

## Perintah build

Dari root repo:

```powershell
# Lengkap: staging + installer EXE
.\tools\build-exe\package.ps1

# Atau
.\tools\build-exe\build.cmd
```

Variasi:

```powershell
# Hanya folder portable (tanpa Inno Setup)
.\tools\build-exe\package.ps1 -SkipInstaller

# Frontend sudah di-build sebelumnya
.\tools\build-exe\package.ps1 -SkipNpm

# Pakai ISCC di path custom
.\tools\build-exe\package.ps1 -InnoSetupCompiler "C:\Program Files (x86)\Inno Setup 6\ISCC.exe"
```

Build installer saja (setelah staging ada):

```powershell
& "C:\Program Files (x86)\Inno Setup 6\ISCC.exe" .\tools\build-exe\installer.iss
```

---

## Hasil build

| Path | Kegunaan |
|---|---|
| `tools/build-exe/dist/app/Hapkido.exe` | Uji lokal tanpa installer |
| `tools/build-exe/dist/Setup-Hapkido.exe` | Dibagikan ke komputer lain |

Uji cepat portable:

```powershell
cd tools\build-exe\dist\app
.\Hapkido.exe
```

Browser membuka `http://127.0.0.1:8000`.

**Login first run:** `admin@hapkido.local` / `password`  
(ganti segera lewat menu User)

---

## Konfigurasi

### `env.production`

Template `.env` production (SQLite). Launcher menyalinnya ke `.env` jika belum ada, lalu menjalankan `key:generate`.

`DB_DATABASE` dikosongkan → Laravel memakai `database/database.sqlite`.

### `server.txt`

```ini
host=127.0.0.1
port=8000
```

- `host=127.0.0.1` — hanya komputer ini  
- `host=0.0.0.0` — bisa diakses dari LAN (buka port di Windows Firewall)

Atau jalankan: `Hapkido.exe 0.0.0.0 8080`

### `php.ini`

Ekstensi untuk Laravel 11 + Maatwebsite Excel + SQLite. Jangan pindahkan path relatif (`ext`, `logs`, `cacert.pem`).

---

## Instalasi di komputer lain

1. Salin `Setup-Hapkido.exe`
2. Double-click → Next (default: `%LocalAppData%\Programs\Hapkido`)
3. Centang “Jalankan …” atau buka shortcut Desktop / Start Menu
4. Tunggu migrate/seed pertama, browser terbuka
5. Login admin di atas

Tidak perlu install XAMPP, Laragon, Composer, atau Node.

**Backup data:** salin `database\database.sqlite` dan folder `storage\app\public`.

**Update versi:** install ulang `Setup-Hapkido.exe` ke folder yang sama. Database lama tetap (tidak ikut paket). Migrasi baru dijalankan otomatis saat start.

---

## Troubleshooting

| Gejala | Perbaikan |
|---|---|
| `Runtime PHP tidak ditemukan` | Build ulang; pastikan `runtime\php\php.exe` ada di staging |
| `VCRUNTIME140.dll` hilang | Install [VC++ Redistributable x64](https://learn.microsoft.com/en-us/cpp/windows/latest-supported-vc-redist) |
| Port 8000 dipakai | Ubah `port=` di `server.txt` |
| Extensi PHP gagal | Lihat `runtime\php\logs\php-error.log` |
| `ISCC.exe tidak ditemukan` | Install Inno Setup 6, atau pakai `-SkipInstaller` dulu |
| Permission SQLite | Jangan install ke `Program Files`; pakai default LocalAppData |

---

## Catatan teknis

- Target framework launcher: **.NET Framework 4.8** (sudah ada di Windows 10/11 modern) — tidak perlu .NET runtime terpisah.
- PHP diunduh dari [windows.php.net](https://windows.php.net/download/) (NTS x64, VS16).
- Production seed: `database/seeders/ProductionSeeder.php` (role + 1 admin), bukan data demo championship.
- `composer install --no-dev` dijalankan di **staging**, jadi `vendor` dev di mesin developer tidak ikut dihapus.

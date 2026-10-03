<?php

/*
|--------------------------------------------------------------------------
| Daftar permission sistem (dipakai RoleSeeder & halaman setting role)
|--------------------------------------------------------------------------
| Struktur: grup label (Indonesia) => [permission => label fitur]
*/

return [
    'Dashboard' => [
        'dashboard.view' => 'Lihat dashboard',
    ],
    'Data Atlet' => [
        'athletes.view' => 'Lihat data atlet',
        'athletes.create' => 'Tambah atlet',
        'athletes.update' => 'Ubah atlet',
        'athletes.delete' => 'Hapus atlet',
        'contingents.view' => 'Lihat kontingen',
        'contingents.create' => 'Tambah kontingen',
        'contingents.update' => 'Ubah kontingen',
        'contingents.delete' => 'Hapus kontingen',
        'categories.view' => 'Lihat kategori',
        'categories.create' => 'Tambah kategori',
        'categories.update' => 'Ubah kategori',
        'categories.delete' => 'Hapus kategori',
    ],
    'Jadwal' => [
        'schedules.view' => 'Lihat jadwal',
        'schedules.create' => 'Tambah jadwal',
        'schedules.update' => 'Ubah jadwal',
        'schedules.delete' => 'Hapus jadwal',
        'schedules.import' => 'Import jadwal (Excel)',
        'schedules.export' => 'Export jadwal (Excel)',
    ],
    'Pengaturan Arena' => [
        'arenas.view' => 'Lihat arena',
        'arenas.create' => 'Tambah arena',
        'arenas.update' => 'Ubah / ganti fungsi arena',
        'arenas.delete' => 'Hapus arena',
    ],
    'Calling Atlet' => [
        'callings.view' => 'Lihat calling',
        'callings.send' => 'Kirim calling atlet',
    ],
    'Scan / Verifikasi' => [
        'verifications.view' => 'Lihat verifikasi',
        'verifications.create' => 'Scan / konfirmasi atlet',
    ],
    'Pemeriksaan Kesiapan' => [
        'readiness.view' => 'Lihat pemeriksaan kesiapan',
        'readiness.update' => 'Perbarui status siap bertanding',
    ],
    'Perlengkapan' => [
        'equipments.view' => 'Lihat perlengkapan',
        'equipments.create' => 'Tambah perlengkapan',
        'equipments.update' => 'Ubah perlengkapan',
        'equipments.delete' => 'Hapus perlengkapan',
        'equipment-loans.view' => 'Lihat peminjaman',
        'equipment-loans.borrow' => 'Pinjam perlengkapan',
        'equipment-loans.return' => 'Kembalikan perlengkapan',
    ],
    'Daeryun' => [
        'matches.view' => 'Lihat pertandingan daeryun',
        'matches.start' => 'Mulai pertandingan',
        'matches.result' => 'Input pemenang & simpan hasil',
    ],
    'Seni' => [
        'performances.view' => 'Lihat urutan tampil seni',
        'performances.create' => 'Atur urutan tampil',
        'performances.update' => 'Ubah status tampil',
        'scores.view' => 'Lihat nilai juri',
        'scores.create' => 'Input nilai juri',
    ],
    'Bracket' => [
        'brackets.view' => 'Lihat bracket',
        'brackets.generate' => 'Generate bracket otomatis',
    ],
    'Hasil & Ranking' => [
        'results.view' => 'Lihat hasil & ranking',
    ],
    'Public Display' => [
        'display.view' => 'Akses layar publik',
    ],
    'Riwayat' => [
        'history.view' => 'Lihat riwayat',
    ],
    'Manajemen User' => [
        'users.view' => 'Lihat user',
        'users.create' => 'Tambah user',
        'users.update' => 'Ubah user',
        'users.delete' => 'Hapus user',
    ],
    'Manajemen Role' => [
        'roles.view' => 'Lihat role',
        'roles.create' => 'Tambah role',
        'roles.update' => 'Ubah role',
        'roles.delete' => 'Hapus role',
        'roles.permissions' => 'Atur permission role',
    ],
];

# HRIS

Sistem HRIS berbasis Laravel + Filament untuk mengelola absensi, cuti, dan penggajian.

## Fitur utama
- Manajemen karyawan: data profil, status, dan departemen.
- Absensi dengan selfie & lokasi, termasuk rekap hadir/telat/alpha.
- Pengajuan izin/sakit dengan alur persetujuan.
- Payslip dan ringkasan payroll.
- Dashboard admin/manager untuk monitoring singkat.
- Portal karyawan: absensi mandiri, riwayat izin, dan slip gaji.

## Prasyarat
- PHP 8.3+, Composer.
- Node.js + npm (untuk aset front-end).
- MySQL/MariaDB (atau SQLite untuk pengembangan).

## Setup cepat (dev)
```bash
cp .env.example .env
php artisan key:generate
# sesuaikan kredensial DB di .env
php artisan migrate --force
php artisan db:seed --force
npm install
npm run build
php artisan serve
```

Login awal (seed):
- Admin: `admin@example.com / 12345678`
- Employee demo: `employee1@example.com / password123`

## Testing
```bash
php artisan test
```

## Catatan
- Pastikan storage link sudah dibuat: `php artisan storage:link`.
- Atur `APP_URL` dan `ASSET_URL` di `.env` sesuai domain yang dipakai.
- by muyo 
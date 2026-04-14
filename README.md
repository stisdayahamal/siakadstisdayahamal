# SIAKAD STIS Dayah Amal

## Persyaratan
- PHP 7.4+
- MySQL/MariaDB
- Composer
- Web server (Apache/Nginx)

## Instalasi
1. Clone repo ini ke server.
2. Jalankan `composer install` untuk mengunduh dependensi.
3. Copy `.env.example` ke `.env` dan sesuaikan konfigurasi database.
4. Import struktur database dari folder `database/` (file `struktur_siakad.sql` dan lainnya).
5. Pastikan folder `uploads/` dapat ditulis dan file `.htaccess` aktif.
6. Jalankan aplikasi melalui web server.

## Fitur Keamanan
- CSRF protection di semua form POST
- Validasi & sanitasi input user
- Validasi upload file (PDF, Excel, CSV, dll)
- Proteksi eksekusi file di folder uploads
- Contoh penggunaan environment variable

## Backup & Restore
- Backup database secara manual/otomatis ke folder `backups/`
- Restore database dengan import file SQL

## Troubleshooting
- Cek file log jika terjadi error
- Pastikan permission folder benar
- Pastikan konfigurasi .env sesuai

## Kontribusi
Pull request dan issue sangat diterima untuk pengembangan lebih lanjut.

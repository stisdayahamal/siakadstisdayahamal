# Strategi Update Sistem Tanpa Kehilangan Data

Untuk memastikan sistem SIAKAD dapat diperbarui secara berkala namun tetap menjaga keamanan data (Zero Data Loss), ikuti alur kerja berikut:

## 1. Gunakan Version Control (Git)

Jangan pernah memperbarui file langsung di server produksi menggunakan FTP. Gunakan Git.

- **Main Branch**: Kode yang sedang online di server produksi.
- **Development Branch**: Kode yang sedang dikembangkan/diperbaiki.

**Alur Update:**

1. Lakukan perubahan di komputer lokal (Development).
2. Tes seluruh fitur secara manual.
3. Commit & Push ke Git (misal: GitHub/GitLab).
4. Di server produksi, lakukan `git pull origin main`.

## 2. Manajemen Perubahan Database (Migrations)

Jika Anda menambah tabel atau kolom baru:

1. Buat file script SQL di folder `database/` dengan nama yang deskriptif (misal: `2024_04_15_add_column_notif.sql`).
2. Jangan gunakan TUNCATE atau DROP pada data yang sudah berisi informasi asli.
3. Gunakan `INSERT IGNORE` atau `ALTER TABLE ... IF NOT EXISTS` untuk keamanan.
4. Jalankan script tersebut di server produksi setelah `git pull`.

## 3. Prosedur Backup Rutin

Sebelum melakukan update besar:

1. Masuk ke menu **Admin > Keamanan Sistem > Backup DB**.
2. Unduh file SQL hasil backup terbaru.
3. Kompres folder `uploads/` secara berkala.

## 4. Gunakan Environment (.env)

Pastikan berkas `.env` di server produksi TIDAK pernah tertimpa oleh berkas dari server lokal. Tambahkan `.env` ke dalam `.gitignore`.

**Contoh Update Aman:**

```bash
# Di Server Produksi
sudo -u www-data git pull origin main
php tools/update_db.php # Pastikan script ini hanya berisi ALTER, bukan TRUNCATE
```

## 5. Staging Era (Opsional tapi Direkomendasikan)

Untuk ribuan pengguna, siapkan satu VPS kecil tambahan (Staging) sebagai tempat uji coba update sebelum diterapkan ke server utama (Production).

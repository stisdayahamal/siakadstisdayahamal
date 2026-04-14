# Panduan Backup & Restore Database

## Backup Manual
1. Buka phpMyAdmin atau gunakan command line.
2. Pilih database `siakadstisdayahamal`.
3. Ekspor seluruh database ke file SQL.
4. Simpan file hasil backup ke folder `backups/`.

## Backup Otomatis (Linux/Mac)
Contoh cron:
```
0 2 * * * mysqldump -u root -pPASSWORD siakadstisdayahamal > /path/to/backups/backup_$(date +\%F).sql
```

## Restore
1. Buka phpMyAdmin atau command line.
2. Pilih database `siakadstisdayahamal` (atau buat baru).
3. Import file SQL hasil backup dari folder `backups/`.

**Catatan:**
- Pastikan permission folder backups/ dapat ditulis.
- Simpan backup di lokasi aman dan lakukan backup rutin.

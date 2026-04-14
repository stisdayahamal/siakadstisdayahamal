-- Tambahan kolom status_pembayaran pada tabel mahasiswa
ALTER TABLE mahasiswa ADD COLUMN status_pembayaran ENUM('0','1') NOT NULL DEFAULT '0';
-- 0 = Belum Bayar, 1 = Lunas

-- Tambahan kolom kehadiran pada tabel nilai_akhir
ALTER TABLE nilai_akhir ADD COLUMN kehadiran DECIMAL(5,2) DEFAULT 0;

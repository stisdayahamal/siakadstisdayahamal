-- Tambahan kolom status_approve pada tabel krs
ALTER TABLE krs ADD COLUMN status_approve TINYINT(1) NOT NULL DEFAULT 0;
-- 0 = Menunggu Dosen Wali, 1 = Disetujui, 2 = Ditolak

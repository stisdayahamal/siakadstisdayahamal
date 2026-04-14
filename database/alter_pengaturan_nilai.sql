-- Tambahan tabel pengaturan_nilai untuk locking system input nilai dosen
CREATE TABLE pengaturan_nilai (
    id INT AUTO_INCREMENT PRIMARY KEY,
    kode_tahun VARCHAR(10) NOT NULL,
    tanggal_batas DATE NOT NULL
) ENGINE=InnoDB;

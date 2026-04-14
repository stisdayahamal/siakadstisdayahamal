-- Struktur tabel PMB
CREATE TABLE IF NOT EXISTS calon_mhs (
    id_calon INT AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(100) NOT NULL,
    tgl_lahir DATE NOT NULL,
    jk CHAR(1) NOT NULL,
    email VARCHAR(100) NOT NULL,
    no_hp VARCHAR(20) NOT NULL,
    id_prodi INT NOT NULL,
    berkas VARCHAR(255) NOT NULL,
    status ENUM('Proses','Lulus','Tidak Lulus') DEFAULT 'Proses',
    sudah_bayar TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_prodi) REFERENCES prodi(id_prodi)
);
-- Tambahkan folder uploads/ untuk berkas pendaftar

CREATE INDEX idx_mahasiswa_nim ON mahasiswa(nim);
CREATE INDEX idx_mk_kode ON mata_kuliah(kode_mk);

-- Kolom nomor ijazah
ALTER TABLE mahasiswa ADD COLUMN no_ijazah VARCHAR(50) DEFAULT NULL;

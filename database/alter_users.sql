-- Tambahan tabel users untuk login multi-user
CREATE TABLE users (
    id_user INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin','dosen','mahasiswa') NOT NULL,
    id_mhs INT DEFAULT NULL,
    id_dosen INT DEFAULT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    FOREIGN KEY (id_mhs) REFERENCES mahasiswa(id_mhs),
    FOREIGN KEY (id_dosen) REFERENCES dosen(id_dosen)
) ENGINE=InnoDB;

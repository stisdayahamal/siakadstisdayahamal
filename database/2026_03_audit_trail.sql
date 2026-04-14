-- Tabel audit trail aktivitas penting
CREATE TABLE IF NOT EXISTS audit_trail (
    id INT AUTO_INCREMENT PRIMARY KEY,
    waktu TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    user_id INT,
    username VARCHAR(50),
    aksi VARCHAR(100),
    detail TEXT,
    ip_address VARCHAR(45)
);

-- Data Dummy untuk SIAKAD STIS Dayah Amal
-- Prodi
INSERT INTO prodi (nama_prodi) VALUES
('Teknik Informatika'),
('Sistem Informasi'),
('Manajemen'),
('Akuntansi'),
('Pendidikan Bahasa Inggris');

-- Kurikulum
INSERT INTO kurikulum (nama_kurikulum, tahun_mulai, id_prodi) VALUES
('Kurikulum 2024 TI', 2024, 1),
('Kurikulum 2024 SI', 2024, 2),
('Kurikulum 2024 Manajemen', 2024, 3),
('Kurikulum 2024 Akuntansi', 2024, 4),
('Kurikulum 2024 PBI', 2024, 5);

-- Dosen
INSERT INTO dosen (nidn, nama) VALUES
('1001001', 'Dr. Ahmad Fauzi'),
('1001002', 'Dr. Siti Rahma'),
('1001003', 'Dr. Budi Santoso'),
('1001004', 'Dr. Dewi Lestari'),
('1001005', 'Dr. Rizal Hakim');

-- Mahasiswa
INSERT INTO mahasiswa (nim, nik, nama, nama_ibu, jalur_masuk, id_prodi) VALUES
('22010001', '120100010001', 'Andi Saputra', 'Siti Aminah', 'SNMPTN', 1),
('22010002', '120100010002', 'Budi Pratama', 'Nurhayati', 'SBMPTN', 1),
('22020001', '120200010001', 'Citra Dewi', 'Fatimah', 'Mandiri', 2),
('22020002', '120200010002', 'Dewi Lestari', 'Sulastri', 'SNMPTN', 2),
('22030001', '120300010001', 'Eka Putra', 'Rohani', 'SBMPTN', 3),
('22030002', '120300010002', 'Fajar Hidayat', 'Siti Zubaidah', 'Mandiri', 3),
('22040001', '120400010001', 'Gita Sari', 'Nuraini', 'SNMPTN', 4),
('22040002', '120400010002', 'Hadi Saputra', 'Siti Maryam', 'SBMPTN', 4),
('22050001', '120500010001', 'Indah Permata', 'Siti Rahmah', 'Mandiri', 5),
('22050002', '120500010002', 'Joko Susilo', 'Siti Aminah', 'SNMPTN', 5);

-- Tahun Akademik
INSERT INTO tahun_akademik (kode_tahun, nama_tahun) VALUES
('20241', '2024/2025 Ganjil'),
('20242', '2024/2025 Genap'),
('20251', '2025/2026 Ganjil');

-- Mata Kuliah
INSERT INTO mata_kuliah (kode_mk, nama_mk, sks, semester, id_prasyarat, id_kurikulum) VALUES
('TI101', 'Algoritma dan Pemrograman', 3, 1, NULL, 1),
('TI102', 'Matematika Diskrit', 3, 1, NULL, 1),
('TI201', 'Struktur Data', 3, 2, 1, 1),
('SI101', 'Pengantar Sistem Informasi', 3, 1, NULL, 2),
('SI102', 'Manajemen Data', 3, 1, NULL, 2),
('SI201', 'Analisis Sistem', 3, 2, 4, 2),
('MN101', 'Pengantar Manajemen', 3, 1, NULL, 3),
('MN102', 'Manajemen SDM', 3, 1, NULL, 3),
('AK101', 'Akuntansi Dasar', 3, 1, NULL, 4),
('PBI101', 'English Grammar', 3, 1, NULL, 5);

-- Jadwal Kuliah
INSERT INTO jadwal_kuliah (id_mk, id_dosen, hari, jam, ruang, kuota) VALUES
(1, 1, 'Senin', '08:00-10:00', 'A101', 40),
(2, 2, 'Selasa', '10:00-12:00', 'A102', 40),
(3, 3, 'Rabu', '08:00-10:00', 'A103', 40),
(4, 4, 'Kamis', '10:00-12:00', 'B101', 40),
(5, 5, 'Jumat', '08:00-10:00', 'B102', 40),
(6, 1, 'Senin', '13:00-15:00', 'B103', 40),
(7, 2, 'Selasa', '13:00-15:00', 'C101', 40),
(8, 3, 'Rabu', '10:00-12:00', 'C102', 40),
(9, 4, 'Kamis', '13:00-15:00', 'C103', 40),
(10, 5, 'Jumat', '10:00-12:00', 'D101', 40);

-- KRS
INSERT INTO krs (id_mhs, id_jadwal, status_krs) VALUES
(1, 1, 'setuju'),
(2, 2, 'setuju'),
(3, 4, 'setuju'),
(4, 5, 'setuju'),
(5, 7, 'setuju'),
(6, 8, 'setuju'),
(7, 9, 'setuju'),
(8, 10, 'setuju'),
(9, 3, 'setuju'),
(10, 6, 'setuju'),
(1, 2, 'draf'),
(2, 3, 'draf'),
(3, 5, 'draf'),
(4, 6, 'draf'),
(5, 8, 'draf'),
(6, 9, 'draf'),
(7, 10, 'draf'),
(8, 1, 'draf'),
(9, 4, 'draf'),
(10, 7, 'draf');

-- Nilai Akhir
INSERT INTO nilai_akhir (id_krs, tugas, uts, uas, nilai_angka, nilai_huruf, bobot_4_0) VALUES
(1, 80, 85, 90, 85, 'A', 4.0),
(2, 75, 80, 78, 78, 'B+', 3.5),
(3, 70, 75, 80, 75, 'B', 3.0),
(4, 65, 70, 72, 70, 'B-', 2.7),
(5, 60, 65, 68, 65, 'C+', 2.3),
(6, 55, 60, 62, 60, 'C', 2.0),
(7, 50, 55, 58, 55, 'D', 1.0),
(8, 45, 50, 52, 50, 'E', 0.0),
(9, 88, 90, 92, 90, 'A', 4.0),
(10, 78, 80, 82, 80, 'B+', 3.5);

-- Tagihan
INSERT INTO tagihan (id_mhs, jenis, nominal, status_lunas) VALUES
(1, 'SPP', 2500000, 'lunas'),
(2, 'SPP', 2500000, 'belum'),
(3, 'SPP', 2500000, 'lunas'),
(4, 'SPP', 2500000, 'belum'),
(5, 'SPP', 2500000, 'lunas'),
(6, 'SPP', 2500000, 'belum'),
(7, 'SPP', 2500000, 'lunas'),
(8, 'SPP', 2500000, 'belum'),
(9, 'SPP', 2500000, 'lunas'),
(10, 'SPP', 2500000, 'belum'),
(1, 'Registrasi', 500000, 'lunas'),
(2, 'Registrasi', 500000, 'lunas'),
(3, 'Registrasi', 500000, 'lunas'),
(4, 'Registrasi', 500000, 'lunas'),
(5, 'Registrasi', 500000, 'lunas'),
(6, 'Registrasi', 500000, 'lunas'),
(7, 'Registrasi', 500000, 'lunas'),
(8, 'Registrasi', 500000, 'lunas'),
(9, 'Registrasi', 500000, 'lunas'),
(10, 'Registrasi', 500000, 'lunas');

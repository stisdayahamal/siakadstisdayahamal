-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: siakadstisdayahamal
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `absensi_pegawai`
--

DROP TABLE IF EXISTS `absensi_pegawai`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `absensi_pegawai` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `tanggal` date NOT NULL,
  `jam_in` time DEFAULT NULL,
  `jam_out` time DEFAULT NULL,
  `status` enum('Hadir','Sakit','Izin','Alpa') DEFAULT 'Hadir',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `absensi_pegawai`
--

LOCK TABLES `absensi_pegawai` WRITE;
/*!40000 ALTER TABLE `absensi_pegawai` DISABLE KEYS */;
INSERT INTO `absensi_pegawai` VALUES (1,1,'2026-03-26','00:55:16','00:55:20','Hadir');
/*!40000 ALTER TABLE `absensi_pegawai` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `artikel_publikasi`
--

DROP TABLE IF EXISTS `artikel_publikasi`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `artikel_publikasi` (
  `id_artikel` int(11) NOT NULL AUTO_INCREMENT,
  `id_kategori` int(11) DEFAULT NULL,
  `tipe` enum('Berita','Pengumuman','Galeri') NOT NULL,
  `judul` varchar(255) NOT NULL,
  `isi` text NOT NULL,
  `gambar` varchar(255) DEFAULT NULL,
  `penulis` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_artikel`),
  KEY `id_kategori` (`id_kategori`),
  CONSTRAINT `artikel_publikasi_ibfk_1` FOREIGN KEY (`id_kategori`) REFERENCES `kategori_publikasi` (`id_kategori`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `artikel_publikasi`
--

LOCK TABLES `artikel_publikasi` WRITE;
/*!40000 ALTER TABLE `artikel_publikasi` DISABLE KEYS */;
/*!40000 ALTER TABLE `artikel_publikasi` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `audit_trail`
--

DROP TABLE IF EXISTS `audit_trail`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `audit_trail` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `waktu` timestamp NOT NULL DEFAULT current_timestamp(),
  `user_id` int(11) DEFAULT NULL,
  `username` varchar(50) DEFAULT NULL,
  `aksi` varchar(100) DEFAULT NULL,
  `detail` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=29 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `audit_trail`
--

LOCK TABLES `audit_trail` WRITE;
/*!40000 ALTER TABLE `audit_trail` DISABLE KEYS */;
INSERT INTO `audit_trail` VALUES (1,'2026-03-25 15:06:49',NULL,'guest','login_sukses','User: admin','::1'),(2,'2026-03-25 15:11:58',NULL,'guest','test','test',''),(3,'2026-03-25 15:21:14',NULL,'guest','login_sukses','User: admin','::1'),(4,'2026-03-25 15:22:15',NULL,'guest','login_sukses','User: dosen','::1'),(5,'2026-03-25 15:25:41',2,'dosen','login_sukses','User: admin','::1'),(6,'2026-03-25 15:32:39',NULL,'guest','login_gagal','User: dosen','::1'),(7,'2026-03-25 15:32:48',NULL,'guest','login_sukses','User: dosen','::1'),(8,'2026-03-25 15:41:44',NULL,'guest','login_sukses','User: mahasiswa','::1'),(9,'2026-03-25 15:56:11',NULL,'guest','login_sukses','User: admin','::1'),(10,'2026-03-25 16:09:00',NULL,'guest','login_sukses','User: dosen','::1'),(11,'2026-03-25 16:09:30',NULL,'guest','login_sukses','User: mahasiswa','::1'),(12,'2026-03-25 16:15:25',NULL,'guest','login_sukses','User: admin','::1'),(13,'2026-03-25 16:49:44',NULL,'guest','login_sukses','User: admin','::1'),(14,'2026-03-25 16:51:49',NULL,'guest','login_sukses','User: dosen','::1'),(15,'2026-03-25 16:52:54',NULL,'guest','login_sukses','User: admin','::1'),(16,'2026-03-25 17:04:35',NULL,'guest','login_sukses','User: dosen','::1'),(17,'2026-03-25 17:05:08',NULL,'guest','login_sukses','User: mahasiswa','::1'),(18,'2026-03-25 17:12:49',NULL,'guest','login_sukses','User: dosen','::1'),(19,'2026-03-25 17:13:37',NULL,'guest','login_sukses','User: admin','::1'),(20,'2026-03-25 17:41:37',1,'admin','login_sukses','User: admin','::1'),(21,'2026-03-25 17:46:19',1,'admin','backup_db_gagal','Backup gagal','::1'),(22,'2026-03-25 17:51:43',NULL,'guest','login_gagal','User: admin','::1'),(23,'2026-03-25 17:51:49',NULL,'guest','login_sukses','User: admin','::1'),(24,'2026-03-25 18:04:17',NULL,'guest','login_sukses','User: admin','::1'),(25,'2026-03-25 18:21:08',NULL,'guest','login_sukses','User: dosen','::1'),(26,'2026-03-25 18:22:38',2,'dosen','login_sukses','User: dosen','::1'),(27,'2026-03-25 18:23:07',NULL,'guest','login_sukses','User: admin','::1'),(28,'2026-03-25 18:23:19',NULL,'guest','login_sukses','User: mahasiswa','::1');
/*!40000 ALTER TABLE `audit_trail` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `calon_mhs`
--

DROP TABLE IF EXISTS `calon_mhs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `calon_mhs` (
  `id_calon` int(11) NOT NULL AUTO_INCREMENT,
  `nama` varchar(100) NOT NULL,
  `tgl_lahir` date NOT NULL,
  `jk` char(1) NOT NULL,
  `email` varchar(100) NOT NULL,
  `no_hp` varchar(20) NOT NULL,
  `id_prodi` int(11) NOT NULL,
  `berkas` varchar(255) NOT NULL,
  `status` enum('Proses','Lulus','Tidak Lulus') DEFAULT 'Proses',
  `sudah_bayar` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_calon`),
  KEY `id_prodi` (`id_prodi`),
  CONSTRAINT `calon_mhs_ibfk_1` FOREIGN KEY (`id_prodi`) REFERENCES `prodi` (`id_prodi`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `calon_mhs`
--

LOCK TABLES `calon_mhs` WRITE;
/*!40000 ALTER TABLE `calon_mhs` DISABLE KEYS */;
/*!40000 ALTER TABLE `calon_mhs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `dosen`
--

DROP TABLE IF EXISTS `dosen`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `dosen` (
  `id_dosen` int(11) NOT NULL AUTO_INCREMENT,
  `nidn` varchar(20) NOT NULL,
  `nama` varchar(100) NOT NULL,
  PRIMARY KEY (`id_dosen`),
  UNIQUE KEY `nidn` (`nidn`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `dosen`
--

LOCK TABLES `dosen` WRITE;
/*!40000 ALTER TABLE `dosen` DISABLE KEYS */;
INSERT INTO `dosen` VALUES (1,'1001001','Dr. Ahmad Fauzi'),(2,'1001002','Dr. Siti Rahma'),(3,'1001003','Dr. Budi Santoso'),(4,'1001004','Dr. Dewi Lestari'),(5,'1001005','Dr. Rizal Hakim');
/*!40000 ALTER TABLE `dosen` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `fakultas`
--

DROP TABLE IF EXISTS `fakultas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `fakultas` (
  `id_fakultas` int(11) NOT NULL AUTO_INCREMENT,
  `nama_fakultas` varchar(150) NOT NULL,
  PRIMARY KEY (`id_fakultas`),
  UNIQUE KEY `nama_fakultas` (`nama_fakultas`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `fakultas`
--

LOCK TABLES `fakultas` WRITE;
/*!40000 ALTER TABLE `fakultas` DISABLE KEYS */;
/*!40000 ALTER TABLE `fakultas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `izin_cuti`
--

DROP TABLE IF EXISTS `izin_cuti`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `izin_cuti` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `tipe` enum('Cuti','Izin','Sakit') NOT NULL,
  `tgl_mulai` date NOT NULL,
  `tgl_selesai` date NOT NULL,
  `alasan` text NOT NULL,
  `status` enum('Menunggu','Disetujui','Ditolak') DEFAULT 'Menunggu',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `izin_cuti`
--

LOCK TABLES `izin_cuti` WRITE;
/*!40000 ALTER TABLE `izin_cuti` DISABLE KEYS */;
/*!40000 ALTER TABLE `izin_cuti` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `jadwal_kuliah`
--

DROP TABLE IF EXISTS `jadwal_kuliah`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `jadwal_kuliah` (
  `id_jadwal` int(11) NOT NULL AUTO_INCREMENT,
  `id_mk` int(11) NOT NULL,
  `id_dosen` int(11) NOT NULL,
  `hari` varchar(20) NOT NULL,
  `jam` varchar(20) NOT NULL,
  `ruang` varchar(20) NOT NULL,
  `kuota` int(11) NOT NULL,
  PRIMARY KEY (`id_jadwal`),
  KEY `id_mk` (`id_mk`),
  KEY `id_dosen` (`id_dosen`),
  CONSTRAINT `jadwal_kuliah_ibfk_1` FOREIGN KEY (`id_mk`) REFERENCES `mata_kuliah` (`id_mk`),
  CONSTRAINT `jadwal_kuliah_ibfk_2` FOREIGN KEY (`id_dosen`) REFERENCES `dosen` (`id_dosen`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jadwal_kuliah`
--

LOCK TABLES `jadwal_kuliah` WRITE;
/*!40000 ALTER TABLE `jadwal_kuliah` DISABLE KEYS */;
INSERT INTO `jadwal_kuliah` VALUES (1,1,1,'Senin','08:00-10:00','A101',40),(2,2,2,'Selasa','10:00-12:00','A102',40),(3,3,3,'Rabu','08:00-10:00','A103',40),(4,4,4,'Kamis','10:00-12:00','B101',40),(5,5,5,'Jumat','08:00-10:00','B102',40),(6,6,1,'Senin','13:00-15:00','B103',40),(7,7,2,'Selasa','13:00-15:00','C101',40),(8,8,3,'Rabu','10:00-12:00','C102',40),(9,9,4,'Kamis','13:00-15:00','C103',40),(10,10,5,'Jumat','10:00-12:00','D101',40);
/*!40000 ALTER TABLE `jadwal_kuliah` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `jenis_kelas`
--

DROP TABLE IF EXISTS `jenis_kelas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `jenis_kelas` (
  `id_jenis` int(11) NOT NULL AUTO_INCREMENT,
  `nama_jenis` varchar(100) NOT NULL,
  PRIMARY KEY (`id_jenis`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jenis_kelas`
--

LOCK TABLES `jenis_kelas` WRITE;
/*!40000 ALTER TABLE `jenis_kelas` DISABLE KEYS */;
/*!40000 ALTER TABLE `jenis_kelas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `kategori_publikasi`
--

DROP TABLE IF EXISTS `kategori_publikasi`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `kategori_publikasi` (
  `id_kategori` int(11) NOT NULL AUTO_INCREMENT,
  `nama_kategori` varchar(100) NOT NULL,
  PRIMARY KEY (`id_kategori`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `kategori_publikasi`
--

LOCK TABLES `kategori_publikasi` WRITE;
/*!40000 ALTER TABLE `kategori_publikasi` DISABLE KEYS */;
/*!40000 ALTER TABLE `kategori_publikasi` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `krs`
--

DROP TABLE IF EXISTS `krs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `krs` (
  `id_krs` int(11) NOT NULL AUTO_INCREMENT,
  `id_mhs` int(11) NOT NULL,
  `id_jadwal` int(11) NOT NULL,
  `status_krs` enum('draf','setuju','tolak') NOT NULL DEFAULT 'draf',
  `status_approve` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id_krs`),
  KEY `id_mhs` (`id_mhs`),
  KEY `id_jadwal` (`id_jadwal`),
  CONSTRAINT `krs_ibfk_1` FOREIGN KEY (`id_mhs`) REFERENCES `mahasiswa` (`id_mhs`),
  CONSTRAINT `krs_ibfk_2` FOREIGN KEY (`id_jadwal`) REFERENCES `jadwal_kuliah` (`id_jadwal`)
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `krs`
--

LOCK TABLES `krs` WRITE;
/*!40000 ALTER TABLE `krs` DISABLE KEYS */;
INSERT INTO `krs` VALUES (1,1,1,'setuju',0),(2,2,2,'setuju',0),(3,3,4,'setuju',0),(4,4,5,'setuju',0),(5,5,7,'setuju',0),(6,6,8,'setuju',0),(7,7,9,'setuju',0),(8,8,10,'setuju',0),(9,9,3,'setuju',0),(10,10,6,'setuju',0),(11,1,2,'draf',0),(12,2,3,'draf',0),(13,3,5,'draf',0),(14,4,6,'draf',0),(15,5,8,'draf',0),(16,6,9,'draf',0),(17,7,10,'draf',0),(18,8,1,'draf',0),(19,9,4,'draf',0),(20,10,7,'draf',0);
/*!40000 ALTER TABLE `krs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `kuesioner_dosen`
--

DROP TABLE IF EXISTS `kuesioner_dosen`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `kuesioner_dosen` (
  `id_kuesioner` int(11) NOT NULL AUTO_INCREMENT,
  `id_jadwal` int(11) NOT NULL,
  `id_mhs` int(11) NOT NULL,
  `rating` tinyint(1) NOT NULL CHECK (`rating` >= 1 and `rating` <= 5),
  `komentar` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_kuesioner`),
  KEY `id_jadwal` (`id_jadwal`),
  KEY `id_mhs` (`id_mhs`),
  CONSTRAINT `kuesioner_dosen_ibfk_1` FOREIGN KEY (`id_jadwal`) REFERENCES `jadwal_kuliah` (`id_jadwal`) ON DELETE CASCADE,
  CONSTRAINT `kuesioner_dosen_ibfk_2` FOREIGN KEY (`id_mhs`) REFERENCES `mahasiswa` (`id_mhs`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `kuesioner_dosen`
--

LOCK TABLES `kuesioner_dosen` WRITE;
/*!40000 ALTER TABLE `kuesioner_dosen` DISABLE KEYS */;
/*!40000 ALTER TABLE `kuesioner_dosen` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `kurikulum`
--

DROP TABLE IF EXISTS `kurikulum`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `kurikulum` (
  `id_kurikulum` int(11) NOT NULL AUTO_INCREMENT,
  `nama_kurikulum` varchar(100) NOT NULL,
  `tahun_mulai` year(4) NOT NULL,
  `id_prodi` int(11) NOT NULL,
  PRIMARY KEY (`id_kurikulum`),
  KEY `id_prodi` (`id_prodi`),
  CONSTRAINT `kurikulum_ibfk_1` FOREIGN KEY (`id_prodi`) REFERENCES `prodi` (`id_prodi`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `kurikulum`
--

LOCK TABLES `kurikulum` WRITE;
/*!40000 ALTER TABLE `kurikulum` DISABLE KEYS */;
INSERT INTO `kurikulum` VALUES (1,'Kurikulum 2024 TI',2024,1),(2,'Kurikulum 2024 SI',2024,2),(3,'Kurikulum 2024 Manajemen',2024,3),(4,'Kurikulum 2024 Akuntansi',2024,4),(5,'Kurikulum 2024 PBI',2024,5);
/*!40000 ALTER TABLE `kurikulum` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mahasiswa`
--

DROP TABLE IF EXISTS `mahasiswa`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mahasiswa` (
  `id_mhs` int(11) NOT NULL AUTO_INCREMENT,
  `nim` varchar(20) NOT NULL,
  `nik` varchar(20) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `nama_ibu` varchar(100) NOT NULL,
  `jalur_masuk` varchar(50) NOT NULL,
  `status_pembayaran` enum('0','1') NOT NULL DEFAULT '0',
  `jatah_sks` int(11) NOT NULL DEFAULT 24,
  `id_prodi` int(11) NOT NULL,
  `no_ijazah` varchar(50) DEFAULT NULL,
  `status_kuliah` enum('Aktif','Cuti','Lulus','Keluar') NOT NULL DEFAULT 'Aktif',
  PRIMARY KEY (`id_mhs`),
  UNIQUE KEY `nim` (`nim`),
  KEY `id_prodi` (`id_prodi`),
  KEY `idx_mahasiswa_nim` (`nim`),
  CONSTRAINT `mahasiswa_ibfk_1` FOREIGN KEY (`id_prodi`) REFERENCES `prodi` (`id_prodi`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mahasiswa`
--

LOCK TABLES `mahasiswa` WRITE;
/*!40000 ALTER TABLE `mahasiswa` DISABLE KEYS */;
INSERT INTO `mahasiswa` VALUES (1,'22010001','120100010001','Andi Saputra','Siti Aminah','SNMPTN','1',24,1,NULL,'Aktif'),(2,'22010002','120100010002','Budi Pratama','Nurhayati','SBMPTN','1',24,1,NULL,'Aktif'),(3,'22020001','120200010001','Citra Dewi','Fatimah','Mandiri','0',24,2,NULL,'Aktif'),(4,'22020002','120200010002','Dewi Lestari','Sulastri','SNMPTN','0',24,2,NULL,'Aktif'),(5,'22030001','120300010001','Eka Putra','Rohani','SBMPTN','0',24,3,NULL,'Aktif'),(6,'22030002','120300010002','Fajar Hidayat','Siti Zubaidah','Mandiri','0',24,3,NULL,'Aktif'),(7,'22040001','120400010001','Gita Sari','Nuraini','SNMPTN','0',24,4,NULL,'Aktif'),(8,'22040002','120400010002','Hadi Saputra','Siti Maryam','SBMPTN','0',24,4,NULL,'Aktif'),(9,'22050001','120500010001','Indah Permata','Siti Rahmah','Mandiri','0',24,5,NULL,'Aktif'),(10,'22050002','120500010002','Joko Susilo','Siti Aminah','SNMPTN','0',24,5,NULL,'Aktif');
/*!40000 ALTER TABLE `mahasiswa` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mata_kuliah`
--

DROP TABLE IF EXISTS `mata_kuliah`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mata_kuliah` (
  `id_mk` int(11) NOT NULL AUTO_INCREMENT,
  `kode_mk` varchar(20) NOT NULL,
  `nama_mk` varchar(100) NOT NULL,
  `sks` int(11) NOT NULL,
  `semester` int(11) NOT NULL,
  `id_prasyarat` int(11) DEFAULT NULL,
  `id_kurikulum` int(11) NOT NULL,
  PRIMARY KEY (`id_mk`),
  UNIQUE KEY `kode_mk` (`kode_mk`),
  KEY `id_prasyarat` (`id_prasyarat`),
  KEY `id_kurikulum` (`id_kurikulum`),
  KEY `idx_mk_kode` (`kode_mk`),
  CONSTRAINT `mata_kuliah_ibfk_1` FOREIGN KEY (`id_prasyarat`) REFERENCES `mata_kuliah` (`id_mk`),
  CONSTRAINT `mata_kuliah_ibfk_2` FOREIGN KEY (`id_kurikulum`) REFERENCES `kurikulum` (`id_kurikulum`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mata_kuliah`
--

LOCK TABLES `mata_kuliah` WRITE;
/*!40000 ALTER TABLE `mata_kuliah` DISABLE KEYS */;
INSERT INTO `mata_kuliah` VALUES (1,'TI101','Algoritma dan Pemrograman',3,1,NULL,1),(2,'TI102','Matematika Diskrit',3,1,NULL,1),(3,'TI201','Struktur Data',3,2,1,1),(4,'SI101','Pengantar Sistem Informasi',3,1,NULL,2),(5,'SI102','Manajemen Data',3,1,NULL,2),(6,'SI201','Analisis Sistem',3,2,4,2),(7,'MN101','Pengantar Manajemen',3,1,NULL,3),(8,'MN102','Manajemen SDM',3,1,NULL,3),(9,'AK101','Akuntansi Dasar',3,1,NULL,4),(10,'PBI101','English Grammar',3,1,NULL,5);
/*!40000 ALTER TABLE `mata_kuliah` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `nilai_akhir`
--

DROP TABLE IF EXISTS `nilai_akhir`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `nilai_akhir` (
  `id_nilai` int(11) NOT NULL AUTO_INCREMENT,
  `id_krs` int(11) NOT NULL,
  `kehadiran` decimal(5,2) DEFAULT 0.00,
  `tugas` decimal(5,2) DEFAULT 0.00,
  `uts` decimal(5,2) DEFAULT 0.00,
  `uas` decimal(5,2) DEFAULT 0.00,
  `nilai_angka` decimal(5,2) DEFAULT 0.00,
  `nilai_huruf` varchar(2) DEFAULT NULL,
  `bobot_4_0` decimal(3,2) DEFAULT NULL,
  PRIMARY KEY (`id_nilai`),
  KEY `id_krs` (`id_krs`),
  CONSTRAINT `nilai_akhir_ibfk_1` FOREIGN KEY (`id_krs`) REFERENCES `krs` (`id_krs`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `nilai_akhir`
--

LOCK TABLES `nilai_akhir` WRITE;
/*!40000 ALTER TABLE `nilai_akhir` DISABLE KEYS */;
INSERT INTO `nilai_akhir` VALUES (1,1,0.00,80.00,85.00,90.00,85.00,'A',4.00),(2,2,0.00,75.00,80.00,78.00,78.00,'B+',3.50),(3,3,0.00,70.00,75.00,80.00,75.00,'B',3.00),(4,4,0.00,65.00,70.00,72.00,70.00,'B-',2.70),(5,5,0.00,60.00,65.00,68.00,65.00,'C+',2.30),(6,6,0.00,55.00,60.00,62.00,60.00,'C',2.00),(7,7,0.00,50.00,55.00,58.00,55.00,'D',1.00),(8,8,0.00,45.00,50.00,52.00,50.00,'E',0.00),(9,9,0.00,88.00,90.00,92.00,90.00,'A',4.00),(10,10,0.00,78.00,80.00,82.00,80.00,'B+',3.50);
/*!40000 ALTER TABLE `nilai_akhir` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notifikasi`
--

DROP TABLE IF EXISTS `notifikasi`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `notifikasi` (
  `id_notif` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `judul` varchar(255) NOT NULL,
  `pesan` text NOT NULL,
  `link` varchar(255) DEFAULT '#',
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_notif`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notifikasi`
--

LOCK TABLES `notifikasi` WRITE;
/*!40000 ALTER TABLE `notifikasi` DISABLE KEYS */;
/*!40000 ALTER TABLE `notifikasi` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pengaturan_nilai`
--

DROP TABLE IF EXISTS `pengaturan_nilai`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pengaturan_nilai` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `kode_tahun` varchar(10) NOT NULL,
  `tanggal_batas` date NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pengaturan_nilai`
--

LOCK TABLES `pengaturan_nilai` WRITE;
/*!40000 ALTER TABLE `pengaturan_nilai` DISABLE KEYS */;
INSERT INTO `pengaturan_nilai` VALUES (1,'20251','2026-12-31');
/*!40000 ALTER TABLE `pengaturan_nilai` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pmb_gelombang`
--

DROP TABLE IF EXISTS `pmb_gelombang`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pmb_gelombang` (
  `id_gelombang` int(11) NOT NULL AUTO_INCREMENT,
  `id_periode` int(11) NOT NULL,
  `nama_gelombang` varchar(100) NOT NULL,
  `tgl_mulai` date NOT NULL,
  `tgl_selesai` date NOT NULL,
  `biaya` bigint(20) NOT NULL,
  PRIMARY KEY (`id_gelombang`),
  KEY `id_periode` (`id_periode`),
  CONSTRAINT `pmb_gelombang_ibfk_1` FOREIGN KEY (`id_periode`) REFERENCES `pmb_periode` (`id_periode`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pmb_gelombang`
--

LOCK TABLES `pmb_gelombang` WRITE;
/*!40000 ALTER TABLE `pmb_gelombang` DISABLE KEYS */;
/*!40000 ALTER TABLE `pmb_gelombang` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pmb_jalur`
--

DROP TABLE IF EXISTS `pmb_jalur`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pmb_jalur` (
  `id_jalur` int(11) NOT NULL AUTO_INCREMENT,
  `nama_jalur` varchar(100) NOT NULL,
  PRIMARY KEY (`id_jalur`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pmb_jalur`
--

LOCK TABLES `pmb_jalur` WRITE;
/*!40000 ALTER TABLE `pmb_jalur` DISABLE KEYS */;
/*!40000 ALTER TABLE `pmb_jalur` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pmb_periode`
--

DROP TABLE IF EXISTS `pmb_periode`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pmb_periode` (
  `id_periode` int(11) NOT NULL AUTO_INCREMENT,
  `nama_periode` varchar(50) NOT NULL,
  `tahun_ajaran` varchar(20) NOT NULL,
  `status_aktif` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id_periode`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pmb_periode`
--

LOCK TABLES `pmb_periode` WRITE;
/*!40000 ALTER TABLE `pmb_periode` DISABLE KEYS */;
/*!40000 ALTER TABLE `pmb_periode` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `presensi`
--

DROP TABLE IF EXISTS `presensi`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `presensi` (
  `id_presensi` int(11) NOT NULL AUTO_INCREMENT,
  `id_jadwal` int(11) NOT NULL,
  `id_mhs` int(11) NOT NULL,
  `pertemuan_ke` int(11) NOT NULL,
  `status_hadir` enum('H','S','I','A') NOT NULL DEFAULT 'A',
  `tanggal` date NOT NULL,
  PRIMARY KEY (`id_presensi`),
  UNIQUE KEY `uq_presensi` (`id_jadwal`,`id_mhs`,`pertemuan_ke`),
  KEY `id_mhs` (`id_mhs`),
  CONSTRAINT `presensi_ibfk_1` FOREIGN KEY (`id_jadwal`) REFERENCES `jadwal_kuliah` (`id_jadwal`) ON DELETE CASCADE,
  CONSTRAINT `presensi_ibfk_2` FOREIGN KEY (`id_mhs`) REFERENCES `mahasiswa` (`id_mhs`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `presensi`
--

LOCK TABLES `presensi` WRITE;
/*!40000 ALTER TABLE `presensi` DISABLE KEYS */;
/*!40000 ALTER TABLE `presensi` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `prodi`
--

DROP TABLE IF EXISTS `prodi`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `prodi` (
  `id_prodi` int(11) NOT NULL AUTO_INCREMENT,
  `nama_prodi` varchar(100) NOT NULL,
  `id_fakultas` int(11) DEFAULT NULL,
  PRIMARY KEY (`id_prodi`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `prodi`
--

LOCK TABLES `prodi` WRITE;
/*!40000 ALTER TABLE `prodi` DISABLE KEYS */;
INSERT INTO `prodi` VALUES (1,'Teknik Informatika',NULL),(2,'Sistem Informasi',NULL),(3,'Manajemen',NULL),(4,'Akuntansi',NULL),(5,'Pendidikan Bahasa Inggris',NULL);
/*!40000 ALTER TABLE `prodi` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sistem_log_aktivitas`
--

DROP TABLE IF EXISTS `sistem_log_aktivitas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sistem_log_aktivitas` (
  `id_log` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `aksi` varchar(50) NOT NULL,
  `entitas` varchar(100) NOT NULL,
  `entitas_id` varchar(50) DEFAULT NULL,
  `nilai_lama` text DEFAULT NULL,
  `nilai_baru` text DEFAULT NULL,
  `ip_address` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_log`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sistem_log_aktivitas`
--

LOCK TABLES `sistem_log_aktivitas` WRITE;
/*!40000 ALTER TABLE `sistem_log_aktivitas` DISABLE KEYS */;
INSERT INTO `sistem_log_aktivitas` VALUES (1,1,'CREATE','Check-In Absensi',NULL,NULL,NULL,NULL,'2026-03-25 17:55:16'),(2,1,'UPDATE','Check-Out Absensi',NULL,NULL,NULL,NULL,'2026-03-25 17:55:20');
/*!40000 ALTER TABLE `sistem_log_aktivitas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `support_ticket`
--

DROP TABLE IF EXISTS `support_ticket`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `support_ticket` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `subjek` varchar(255) NOT NULL,
  `pesan` text NOT NULL,
  `balasan` text DEFAULT NULL,
  `status` enum('Open','In Progress','Closed') DEFAULT 'Open',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `support_ticket`
--

LOCK TABLES `support_ticket` WRITE;
/*!40000 ALTER TABLE `support_ticket` DISABLE KEYS */;
/*!40000 ALTER TABLE `support_ticket` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tagihan`
--

DROP TABLE IF EXISTS `tagihan`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tagihan` (
  `id_tagihan` int(11) NOT NULL AUTO_INCREMENT,
  `id_mhs` int(11) NOT NULL,
  `kode_tahun` varchar(10) DEFAULT NULL,
  `jenis` varchar(50) NOT NULL,
  `nominal` decimal(12,2) NOT NULL,
  `status_lunas` enum('belum','lunas') NOT NULL DEFAULT 'belum',
  PRIMARY KEY (`id_tagihan`),
  KEY `id_mhs` (`id_mhs`),
  CONSTRAINT `tagihan_ibfk_1` FOREIGN KEY (`id_mhs`) REFERENCES `mahasiswa` (`id_mhs`)
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tagihan`
--

LOCK TABLES `tagihan` WRITE;
/*!40000 ALTER TABLE `tagihan` DISABLE KEYS */;
INSERT INTO `tagihan` VALUES (1,1,'20251','SPP',2500000.00,'lunas'),(2,2,'20251','SPP',2500000.00,'lunas'),(3,3,'20251','SPP',2500000.00,'lunas'),(4,4,'20251','SPP',2500000.00,'belum'),(5,5,'20251','SPP',2500000.00,'lunas'),(6,6,'20251','SPP',2500000.00,'belum'),(7,7,'20251','SPP',2500000.00,'lunas'),(8,8,'20251','SPP',2500000.00,'belum'),(9,9,'20251','SPP',2500000.00,'lunas'),(10,10,'20251','SPP',2500000.00,'belum'),(11,1,'20251','Registrasi',500000.00,'lunas'),(12,2,'20251','Registrasi',500000.00,'lunas'),(13,3,'20251','Registrasi',500000.00,'lunas'),(14,4,'20251','Registrasi',500000.00,'lunas'),(15,5,'20251','Registrasi',500000.00,'lunas'),(16,6,'20251','Registrasi',500000.00,'lunas'),(17,7,'20251','Registrasi',500000.00,'lunas'),(18,8,'20251','Registrasi',500000.00,'lunas'),(19,9,'20251','Registrasi',500000.00,'lunas'),(20,10,'20251','Registrasi',500000.00,'lunas');
/*!40000 ALTER TABLE `tagihan` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tahun_akademik`
--

DROP TABLE IF EXISTS `tahun_akademik`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tahun_akademik` (
  `id_tahun` int(11) NOT NULL AUTO_INCREMENT,
  `kode_tahun` varchar(10) NOT NULL,
  `nama_tahun` varchar(50) NOT NULL,
  `status_aktif` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id_tahun`),
  UNIQUE KEY `kode_tahun` (`kode_tahun`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tahun_akademik`
--

LOCK TABLES `tahun_akademik` WRITE;
/*!40000 ALTER TABLE `tahun_akademik` DISABLE KEYS */;
INSERT INTO `tahun_akademik` VALUES (1,'20241','2024/2025 Ganjil',0),(2,'20242','2024/2025 Genap',0),(3,'20251','2025/2026 Ganjil',0);
/*!40000 ALTER TABLE `tahun_akademik` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tugas_akademik`
--

DROP TABLE IF EXISTS `tugas_akademik`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tugas_akademik` (
  `id_tugas` int(11) NOT NULL AUTO_INCREMENT,
  `id_jadwal` int(11) NOT NULL,
  `judul` varchar(200) NOT NULL,
  `deskripsi` text NOT NULL,
  `lampiran` varchar(255) DEFAULT NULL,
  `batas_waktu` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_tugas`),
  KEY `id_jadwal` (`id_jadwal`),
  CONSTRAINT `tugas_akademik_ibfk_1` FOREIGN KEY (`id_jadwal`) REFERENCES `jadwal_kuliah` (`id_jadwal`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tugas_akademik`
--

LOCK TABLES `tugas_akademik` WRITE;
/*!40000 ALTER TABLE `tugas_akademik` DISABLE KEYS */;
/*!40000 ALTER TABLE `tugas_akademik` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tugas_kumpul`
--

DROP TABLE IF EXISTS `tugas_kumpul`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tugas_kumpul` (
  `id_kumpul` int(11) NOT NULL AUTO_INCREMENT,
  `id_tugas` int(11) NOT NULL,
  `id_mhs` int(11) NOT NULL,
  `file_jawaban` varchar(255) NOT NULL,
  `waktu_kumpul` datetime NOT NULL,
  `nilai` int(11) DEFAULT NULL,
  `catatan_dosen` text DEFAULT NULL,
  PRIMARY KEY (`id_kumpul`),
  KEY `id_tugas` (`id_tugas`),
  KEY `id_mhs` (`id_mhs`),
  CONSTRAINT `tugas_kumpul_ibfk_1` FOREIGN KEY (`id_tugas`) REFERENCES `tugas_akademik` (`id_tugas`) ON DELETE CASCADE,
  CONSTRAINT `tugas_kumpul_ibfk_2` FOREIGN KEY (`id_mhs`) REFERENCES `mahasiswa` (`id_mhs`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tugas_kumpul`
--

LOCK TABLES `tugas_kumpul` WRITE;
/*!40000 ALTER TABLE `tugas_kumpul` DISABLE KEYS */;
/*!40000 ALTER TABLE `tugas_kumpul` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id_user` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','dosen','mahasiswa') NOT NULL,
  `id_mhs` int(11) DEFAULT NULL,
  `id_dosen` int(11) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `nama` varchar(100) DEFAULT 'Pengguna',
  `foto` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id_user`),
  UNIQUE KEY `username` (`username`),
  KEY `id_mhs` (`id_mhs`),
  KEY `id_dosen` (`id_dosen`),
  CONSTRAINT `users_ibfk_1` FOREIGN KEY (`id_mhs`) REFERENCES `mahasiswa` (`id_mhs`),
  CONSTRAINT `users_ibfk_2` FOREIGN KEY (`id_dosen`) REFERENCES `dosen` (`id_dosen`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'admin','$2y$10$Tq.5APw0XKbTc5rsIiHVp.w1X5S4Qlx0p2Eqwrr.ap7rvvrfXWZii','admin',NULL,NULL,1,'Administrator',NULL),(2,'dosen','$2y$10$Tq.5APw0XKbTc5rsIiHVp.w1X5S4Qlx0p2Eqwrr.ap7rvvrfXWZii','dosen',NULL,1,1,'Dr. Ahmad Fauzi',NULL),(3,'mahasiswa','$2y$10$Tq.5APw0XKbTc5rsIiHVp.w1X5S4Qlx0p2Eqwrr.ap7rvvrfXWZii','mahasiswa',1,NULL,1,'Andi Saputra',NULL);
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `waktu_kuliah`
--

DROP TABLE IF EXISTS `waktu_kuliah`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `waktu_kuliah` (
  `id_waktu` int(11) NOT NULL AUTO_INCREMENT,
  `keterangan` varchar(100) NOT NULL,
  PRIMARY KEY (`id_waktu`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `waktu_kuliah`
--

LOCK TABLES `waktu_kuliah` WRITE;
/*!40000 ALTER TABLE `waktu_kuliah` DISABLE KEYS */;
/*!40000 ALTER TABLE `waktu_kuliah` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-03-29 15:00:22

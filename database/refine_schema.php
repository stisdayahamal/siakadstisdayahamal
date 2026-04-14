<?php
define('ACCESS', true);
require_once 'config/db.php';

echo "Starting Database Refinement...\n";

// 1. Tambah kode_prodi ke tabel prodi jika belum ada
$pdo->exec("ALTER TABLE prodi ADD COLUMN IF NOT EXISTS kode_prodi VARCHAR(10) AFTER id_prodi");

// 2. Set kode prodi sesuai permintaan user secara akurat
$pdo->exec("UPDATE prodi SET kode_prodi = '01' WHERE nama_prodi LIKE '%Hukum Keluarga Islam%'");
$pdo->exec("UPDATE prodi SET kode_prodi = '02' WHERE (nama_prodi LIKE '%Hukum Pidana%' OR nama_prodi LIKE '%HPI%')");

// Default lainnya jika ada
$pdo->exec("UPDATE prodi SET kode_prodi = '03' WHERE (kode_prodi IS NULL OR kode_prodi = '') AND id_prodi NOT IN (SELECT id_prodi FROM (SELECT id_prodi FROM prodi WHERE kode_prodi IN ('01','02')) as tmp)");

// 3. Kolom PDDikti untuk MAHASISWA
$missing_cols_mhs = [
    "nisn VARCHAR(20)",
    "tempat_lahir VARCHAR(100)",
    "tgl_lahir DATE",
    "jk CHAR(1)",
    "agama VARCHAR(20)",
    "alamat TEXT",
    "rt VARCHAR(5)",
    "rw VARCHAR(5)",
    "kelurahan VARCHAR(100)",
    "kecamatan VARCHAR(100)",
    "kode_pos VARCHAR(10)",
    "jenis_tinggal VARCHAR(30)",
    "alat_transportasi VARCHAR(50)",
    "nama_ayah VARCHAR(100)",
    "nik_ayah VARCHAR(20)",
    "nik_ibu VARCHAR(20)",
    "pekerjaan_ayah VARCHAR(50)",
    "pekerjaan_ibu VARCHAR(50)",
    "pendidikan_ayah VARCHAR(20)",
    "pendidikan_ibu VARCHAR(20)",
    "penghasilan_ortu VARCHAR(50)",
    "asal_sekolah VARCHAR(100)",
    "tahun_lulus VARCHAR(4)",
    "email VARCHAR(100)",
    "no_hp VARCHAR(20)",
    "tahun_masuk VARCHAR(4)"
];

foreach ($missing_cols_mhs as $col) {
    try {
        $pdo->exec("ALTER TABLE mahasiswa ADD COLUMN $col");
        echo "Added column $col to mahasiswa.\n";
    } catch (Exception $e) {}
}

// Unifikasi data lahir dan JK jika ada kolom ganda
try { $pdo->exec("UPDATE mahasiswa SET tgl_lahir = tanggal_lahir WHERE tgl_lahir IS NULL AND tanggal_lahir IS NOT NULL"); } catch(Exception $e){}
try { $pdo->exec("UPDATE mahasiswa SET jk = jenis_kelamin WHERE jk IS NULL AND jenis_kelamin IS NOT NULL"); } catch(Exception $e){}

// 4. Kolom PDDikti untuk CALON_MHS
$missing_cols_calon = [
    "nisn VARCHAR(20)",
    "tempat_lahir VARCHAR(100)",
    "agama VARCHAR(20)",
    "alamat_detail TEXT",
    "rt VARCHAR(5)",
    "rw VARCHAR(5)",
    "kelurahan VARCHAR(100)",
    "kecamatan VARCHAR(100)",
    "kode_pos VARCHAR(10)",
    "jenis_tinggal VARCHAR(30)",
    "alat_transportasi VARCHAR(50)",
    "nama_ayah VARCHAR(100)",
    "nik_ayah VARCHAR(20)",
    "nik_ibu VARCHAR(20)",
    "pekerjaan_ayah VARCHAR(50)",
    "pekerjaan_ibu VARCHAR(50)",
    "pendidikan_ayah VARCHAR(20)",
    "pendidikan_ibu VARCHAR(20)",
    "penghasilan_ortu VARCHAR(50)",
    "asal_sekolah VARCHAR(100)",
    "tahun_lulus VARCHAR(4)",
    "no_pendaftaran VARCHAR(30)",
    "berkas_ijazah VARCHAR(255)",
    "berkas_ktp VARCHAR(255)",
    "berkas_kk VARCHAR(255)",
    "berkas_foto VARCHAR(255)",
    "berkas_akte VARCHAR(255)"
];

foreach ($missing_cols_calon as $col) {
    try {
        $pdo->exec("ALTER TABLE calon_mhs ADD COLUMN $col");
        echo "Added column $col to calon_mhs.\n";
    } catch (Exception $e) {}
}

echo "Database successfully refined.\n";

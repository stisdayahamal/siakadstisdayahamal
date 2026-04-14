<?php
// database/pmb_dikti_upgrade.sql generator - run once to upgrade schema
define('ACCESS', true);
require_once __DIR__ . '/../config/db.php';

$upgrades = [
    // === UPGRADE calon_mhs - Tambah field wajib DIKTI ===
    "ALTER TABLE calon_mhs ADD COLUMN IF NOT EXISTS nama_ayah VARCHAR(100) NULL AFTER nama_ibu",
    "ALTER TABLE calon_mhs ADD COLUMN IF NOT EXISTS nama_ibu_lengkap VARCHAR(100) NULL AFTER nama_ayah",
    "ALTER TABLE calon_mhs ADD COLUMN IF NOT EXISTS nik_ayah VARCHAR(20) NULL AFTER nama_ibu_lengkap",
    "ALTER TABLE calon_mhs ADD COLUMN IF NOT EXISTS nik_ibu VARCHAR(20) NULL AFTER nik_ayah",
    "ALTER TABLE calon_mhs ADD COLUMN IF NOT EXISTS pekerjaan_ayah VARCHAR(50) NULL AFTER nik_ibu",
    "ALTER TABLE calon_mhs ADD COLUMN IF NOT EXISTS pekerjaan_ibu VARCHAR(50) NULL AFTER pekerjaan_ayah",
    "ALTER TABLE calon_mhs ADD COLUMN IF NOT EXISTS pendidikan_ayah VARCHAR(10) NULL AFTER pekerjaan_ibu",
    "ALTER TABLE calon_mhs ADD COLUMN IF NOT EXISTS pendidikan_ibu VARCHAR(10) NULL AFTER pendidikan_ayah",
    "ALTER TABLE calon_mhs ADD COLUMN IF NOT EXISTS penghasilan_ortu VARCHAR(20) NULL AFTER pendidikan_ibu",
    "ALTER TABLE calon_mhs ADD COLUMN IF NOT EXISTS alamat_detail TEXT NULL AFTER alamat",
    "ALTER TABLE calon_mhs ADD COLUMN IF NOT EXISTS rt VARCHAR(5) NULL AFTER alamat_detail",
    "ALTER TABLE calon_mhs ADD COLUMN IF NOT EXISTS rw VARCHAR(5) NULL AFTER rt",
    "ALTER TABLE calon_mhs ADD COLUMN IF NOT EXISTS kelurahan VARCHAR(100) NULL AFTER rw",
    "ALTER TABLE calon_mhs ADD COLUMN IF NOT EXISTS kecamatan VARCHAR(100) NULL AFTER kelurahan",
    "ALTER TABLE calon_mhs ADD COLUMN IF NOT EXISTS kode_pos VARCHAR(10) NULL AFTER kecamatan",
    "ALTER TABLE calon_mhs ADD COLUMN IF NOT EXISTS jenis_tinggal VARCHAR(30) NULL AFTER kode_pos",
    "ALTER TABLE calon_mhs ADD COLUMN IF NOT EXISTS alat_transportasi VARCHAR(50) NULL AFTER jenis_tinggal",
    "ALTER TABLE calon_mhs ADD COLUMN IF NOT EXISTS jalur_masuk VARCHAR(50) NULL DEFAULT 'Reguler' AFTER alat_transportasi",
    "ALTER TABLE calon_mhs ADD COLUMN IF NOT EXISTS id_gelombang INT(11) NULL AFTER jalur_masuk",
    "ALTER TABLE calon_mhs ADD COLUMN IF NOT EXISTS no_pendaftaran VARCHAR(30) NULL AFTER id_gelombang",
    "ALTER TABLE calon_mhs ADD COLUMN IF NOT EXISTS catatan_admin TEXT NULL AFTER no_pendaftaran",
    "ALTER TABLE calon_mhs ADD COLUMN IF NOT EXISTS berkas_akte VARCHAR(255) NULL AFTER berkas_foto",
    "ALTER TABLE calon_mhs ADD COLUMN IF NOT EXISTS tanggal_daftar DATETIME DEFAULT CURRENT_TIMESTAMP AFTER created_at",
    // === Mahasiswa - field tambahan DIKTI ===
    "ALTER TABLE mahasiswa ADD COLUMN IF NOT EXISTS nama_ayah VARCHAR(100) NULL AFTER nama_ibu",
    "ALTER TABLE mahasiswa ADD COLUMN IF NOT EXISTS nama_ibu_lengkap VARCHAR(100) NULL AFTER nama_ayah",
    "ALTER TABLE mahasiswa ADD COLUMN IF NOT EXISTS pekerjaan_ortu VARCHAR(50) NULL AFTER nama_ibu_lengkap",
    "ALTER TABLE mahasiswa ADD COLUMN IF NOT EXISTS penghasilan_ortu VARCHAR(20) NULL AFTER pekerjaan_ortu",
    "ALTER TABLE mahasiswa ADD COLUMN IF NOT EXISTS rt VARCHAR(5) NULL AFTER alamat",
    "ALTER TABLE mahasiswa ADD COLUMN IF NOT EXISTS rw VARCHAR(5) NULL AFTER rt",
    "ALTER TABLE mahasiswa ADD COLUMN IF NOT EXISTS kelurahan VARCHAR(100) NULL AFTER rw",
    "ALTER TABLE mahasiswa ADD COLUMN IF NOT EXISTS kecamatan VARCHAR(100) NULL AFTER kelurahan",
    "ALTER TABLE mahasiswa ADD COLUMN IF NOT EXISTS kode_pos VARCHAR(10) NULL AFTER kecamatan",
    "ALTER TABLE mahasiswa ADD COLUMN IF NOT EXISTS jenis_tinggal VARCHAR(30) NULL AFTER kode_pos",
    "ALTER TABLE mahasiswa ADD COLUMN IF NOT EXISTS alat_transportasi VARCHAR(50) NULL AFTER jenis_tinggal",
    "ALTER TABLE mahasiswa ADD COLUMN IF NOT EXISTS id_periode_masuk INT(11) NULL AFTER tahun_masuk",
    "ALTER TABLE mahasiswa ADD COLUMN IF NOT EXISTS jalur_masuk_pmb VARCHAR(50) NULL AFTER id_periode_masuk",
    "ALTER TABLE mahasiswa ADD COLUMN IF NOT EXISTS foto_profil VARCHAR(255) NULL AFTER foto", // bila belum ada
    "ALTER TABLE mahasiswa ADD COLUMN IF NOT EXISTS semester_aktif INT(2) DEFAULT 1 AFTER id_periode_masuk",
];

$ok = 0; $skip = 0; $fail = 0;
foreach ($upgrades as $sql) {
    try {
        $pdo->exec($sql);
        echo "✅ OK: " . substr($sql, 0, 80) . "\n";
        $ok++;
    } catch (PDOException $e) {
        // 1060 = column sudah exists
        if (in_array($e->errorInfo[1], [1060, 1091])) {
            echo "⏩ SKIP (sudah ada): " . substr($sql, 12, 60) . "\n";
            $skip++;
        } else {
            echo "❌ FAIL: " . $e->getMessage() . " | SQL: " . substr($sql, 0, 60) . "\n";
            $fail++;
        }
    }
}
echo "\n=== SELESAI: OK={$ok} SKIP={$skip} FAIL={$fail} ===\n";

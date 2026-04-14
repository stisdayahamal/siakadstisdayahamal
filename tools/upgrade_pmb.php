<?php
// tools/upgrade_pmb.php
define('ACCESS', true);
require_once __DIR__ . '/../config/db.php';

try {
    $pdo->beginTransaction();

    // 1. Alter table calon_mhs
    $queries = [
        "ALTER TABLE calon_mhs ADD COLUMN nik VARCHAR(20) DEFAULT NULL AFTER nama",
        "ALTER TABLE calon_mhs ADD COLUMN nisn VARCHAR(20) DEFAULT NULL AFTER nik",
        "ALTER TABLE calon_mhs ADD COLUMN tempat_lahir VARCHAR(100) DEFAULT NULL AFTER nisn",
        "ALTER TABLE calon_mhs ADD COLUMN agama VARCHAR(20) DEFAULT NULL AFTER tgl_lahir",
        "ALTER TABLE calon_mhs ADD COLUMN alamat TEXT DEFAULT NULL AFTER jk",
        "ALTER TABLE calon_mhs ADD COLUMN nama_ibu VARCHAR(100) DEFAULT NULL AFTER alamat",
        "ALTER TABLE calon_mhs ADD COLUMN asal_sekolah VARCHAR(100) DEFAULT NULL AFTER nama_ibu",
        "ALTER TABLE calon_mhs ADD COLUMN tahun_lulus VARCHAR(4) DEFAULT NULL AFTER asal_sekolah",
        "ALTER TABLE calon_mhs CHANGE berkas berkas_ijazah VARCHAR(255) DEFAULT NULL",
        "ALTER TABLE calon_mhs ADD COLUMN berkas_ktp VARCHAR(255) DEFAULT NULL AFTER berkas_ijazah",
        "ALTER TABLE calon_mhs ADD COLUMN berkas_kk VARCHAR(255) DEFAULT NULL AFTER berkas_ktp",
        "ALTER TABLE calon_mhs ADD COLUMN berkas_foto VARCHAR(255) DEFAULT NULL AFTER berkas_kk"
    ];

    foreach ($queries as $q) {
        try {
            $pdo->exec($q);
            echo "Success: $q\n";
        } catch (PDOException $e) {
            echo "Skipped/Error: " . $e->getMessage() . " on $q\n";
        }
    }

    // 2. Alter table mahasiswa to sync with the columns expected by the PMB module and our new additions
    $mhs_queries = [
        "ALTER TABLE mahasiswa ADD COLUMN tempat_lahir VARCHAR(100) DEFAULT NULL AFTER nik",
        "ALTER TABLE mahasiswa ADD COLUMN tanggal_lahir DATE DEFAULT NULL AFTER tempat_lahir",
        "ALTER TABLE mahasiswa ADD COLUMN tgl_lahir DATE DEFAULT NULL AFTER tanggal_lahir", // just in case some legacy queries use tgl_lahir
        "ALTER TABLE mahasiswa ADD COLUMN jenis_kelamin CHAR(1) DEFAULT NULL AFTER tgl_lahir",
        "ALTER TABLE mahasiswa ADD COLUMN jk CHAR(1) DEFAULT NULL AFTER jenis_kelamin", // just in case
        "ALTER TABLE mahasiswa ADD COLUMN agama VARCHAR(20) DEFAULT NULL AFTER jk",
        "ALTER TABLE mahasiswa ADD COLUMN alamat TEXT DEFAULT NULL AFTER agama",
        "ALTER TABLE mahasiswa ADD COLUMN no_hp VARCHAR(20) DEFAULT NULL AFTER alamat",
        "ALTER TABLE mahasiswa ADD COLUMN email VARCHAR(100) DEFAULT NULL AFTER no_hp",
        "ALTER TABLE mahasiswa ADD COLUMN asal_sekolah VARCHAR(100) DEFAULT NULL AFTER nama_ibu",
        "ALTER TABLE mahasiswa ADD COLUMN tahun_lulus VARCHAR(4) DEFAULT NULL AFTER asal_sekolah",
        "ALTER TABLE mahasiswa ADD COLUMN tahun_masuk VARCHAR(4) DEFAULT NULL AFTER id_prodi",
        "ALTER TABLE mahasiswa ADD COLUMN password VARCHAR(255) DEFAULT NULL AFTER no_hp"
    ];

    foreach ($mhs_queries as $mq) {
        try {
            $pdo->exec($mq);
            echo "Success: $mq\n";
        } catch (PDOException $e) {
            echo "Skipped/Error: " . $e->getMessage() . " on $mq\n";
        }
    }

    // Check if the old `calon_mhs` table has `berkas` so we avoid "Column not found"
    // Wait, we already changed it via `CHANGE berkas berkas_ijazah`. So it's fine.

    $pdo->commit();
    echo "Upgrade completed.\n";

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "Fatal Error: " . $e->getMessage() . "\n";
}

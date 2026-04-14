<?php
define('ACCESS', true);
require_once __DIR__ . '/../config/db.php';

try {
    $pdo->beginTransaction();

    // Pastikan fakultas Syari'ah ada
    $hasFakultas = $pdo->query("SHOW TABLES LIKE 'fakultas'")->rowCount();
    $id_fakultas = 1;
    if ($hasFakultas) {
        $cek = $pdo->query("SELECT id_fakultas FROM fakultas LIMIT 1")->fetchColumn();
        if (!$cek) {
            $pdo->prepare("INSERT INTO fakultas (nama_fakultas, dekan) VALUES (?, ?)")->execute(['Syari`ah', 'Dekan Fakultas']);
            $id_fakultas = $pdo->lastInsertId();
        } else {
            $pdo->prepare("UPDATE fakultas SET nama_fakultas = 'Syari`ah' WHERE id_fakultas=1")->execute();
        }
    }

    // Update prodi tanpa hapus
    $stmt1 = $pdo->prepare("SELECT COUNT(*) FROM prodi WHERE id_prodi=1");
    $stmt1->execute();
    if($stmt1->fetchColumn() == 0) {
        $pdo->exec("INSERT INTO prodi (id_prodi, nama_prodi) VALUES (1, 'HKI - Hukum Keluarga Islam (Ahwal Syakhsiyyah)')");
    } else {
        $pdo->exec("UPDATE prodi SET nama_prodi = 'HKI - Hukum Keluarga Islam (Ahwal Syakhsiyyah)' WHERE id_prodi=1");
    }

    $stmt2 = $pdo->prepare("SELECT COUNT(*) FROM prodi WHERE id_prodi=2");
    $stmt2->execute();
    if($stmt2->fetchColumn() == 0) {
        $pdo->exec("INSERT INTO prodi (id_prodi, nama_prodi) VALUES (2, 'HPI - Hukum Pidana Islam (Jinayah)')");
    } else {
        $pdo->exec("UPDATE prodi SET nama_prodi = 'HPI - Hukum Pidana Islam (Jinayah)' WHERE id_prodi=2");
    }

    $pdo->commit();
    echo "BERHASIL_SINKRONISASI_PRODI_STIS_DAYAH_AMAL\n";
} catch (Exception $e) {
    $pdo->rollBack();
    echo "ERROR: " . $e->getMessage() . "\n";
}

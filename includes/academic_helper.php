<?php
/**
 * Academic Helper Functions for SIAKAD
 * Standardized logic for DIKTI compliance
 */

if (!defined('ACCESS')) define('ACCESS', true);

/**
 * Get SKS cap based on GPA (DIKTI Standard)
 */
function get_jatah_sks_by_ipk($ipk) {
    if ($ipk >= 3.00) return 24;
    if ($ipk >= 2.50) return 21;
    if ($ipk >= 2.00) return 18;
    if ($ipk >= 1.50) return 15;
    return 12;
}

/**
 * Get student GPA for the previous active semester
 */
function get_ipk_mahasiswa($pdo, $id_mhs) {
    // Note: IPK usually calculated from cumulative score. 
    // For KRS limit, we often use IPS (Semester GPA) of the previous semester.
    $stmt = $pdo->prepare('
        SELECT SUM(mk.sks * n.bobot_4_0) / SUM(mk.sks) as ipk
        FROM krs k
        JOIN jadwal_kuliah jk ON k.id_jadwal = jk.id_jadwal
        JOIN mata_kuliah mk ON jk.id_mk = mk.id_mk
        JOIN nilai_akhir n ON n.id_krs = k.id_krs
        WHERE k.id_mhs = ? AND k.status_krs = "setuju" AND n.bobot_4_0 IS NOT NULL
    ');
    $stmt->execute([$id_mhs]);
    $res = $stmt->fetch();
    return $res['ipk'] ? round($res['ipk'], 2) : 0.00;
}

/**
 * Get dynamic jatah SKS for a student
 */
function get_jatah_sks_mahasiswa($pdo, $id_mhs) {
    $ipk = get_ipk_mahasiswa($pdo, $id_mhs);
    return get_jatah_sks_by_ipk($ipk);
}

/**
 * Get Current Active Academic Year
 */
function get_tahun_aktif($pdo) {
    $stmt = $pdo->query("SELECT * FROM tahun_akademik WHERE status_aktif = 1 LIMIT 1");
    $res = $stmt->fetch();
    if (!$res) {
        // Fallback to latest
        $stmt = $pdo->query("SELECT * FROM tahun_akademik ORDER BY id_tahun DESC LIMIT 1");
        $res = $stmt->fetch();
    }
    return $res;
}

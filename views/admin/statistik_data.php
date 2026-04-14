<?php
// views/admin/statistik_data.php
require_once '../../middleware/auth.php';
require_once '../../config/db.php';
if ($_SESSION['user']['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Akses ditolak']);
    exit;
}
$ipk_data = $pdo->query("SELECT LEFT(m.nim, 4) as angkatan, IFNULL(ROUND(AVG(x.ipk),2),0) AS avg_ipk FROM (SELECT m.id_mhs, SUM(mk.sks*n.bobot_4_0)/SUM(mk.sks) AS ipk FROM krs k JOIN jadwal_kuliah jk ON k.id_jadwal=jk.id_jadwal JOIN mata_kuliah mk ON jk.id_mk=mk.id_mk LEFT JOIN nilai_akhir n ON n.id_krs=k.id_krs WHERE n.bobot_4_0 IS NOT NULL GROUP BY k.id_mhs) x JOIN mahasiswa m ON x.id_mhs=m.id_mhs GROUP BY LEFT(m.nim, 4) ORDER BY angkatan ASC")->fetchAll();

$res = ['labels'=>[], 'ipk'=>[], 'prodi_labels'=>[], 'prodi_data'=>[]];
foreach($ipk_data as $row){
    $res['labels'][] = 'Angkatan ' . $row['angkatan'];
    $res['ipk'][] = $row['avg_ipk'];
}

$prodi_data = $pdo->query("SELECT p.nama_prodi, COUNT(m.id_mhs) as total FROM prodi p LEFT JOIN mahasiswa m ON p.id_prodi = m.id_prodi GROUP BY p.id_prodi")->fetchAll();
foreach($prodi_data as $row){
    $res['prodi_labels'][] = $row['nama_prodi'];
    $res['prodi_data'][] = $row['total'];
}

header('Content-Type: application/json');
echo json_encode($res);

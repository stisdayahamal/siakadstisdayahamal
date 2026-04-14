<?php
// views/admin/dashboard_realtime_data.php
require_once '../../middleware/auth.php';
require_once '../../config/db.php';
if ($_SESSION['user']['role'] !== 'admin') {
    http_response_code(403);
    die('Akses ditolak.');
}
$total_mhs = $pdo->query("SELECT COUNT(*) FROM mahasiswa WHERE status_pembayaran='1'")->fetchColumn();
$total_dosen = $pdo->query("SELECT COUNT(*) FROM dosen")->fetchColumn();
$ipk_avg = $pdo->query("SELECT ROUND(AVG(ipk),2) FROM (SELECT m.id_mhs, SUM(mk.sks*n.bobot_4_0)/SUM(mk.sks) AS ipk FROM mahasiswa m JOIN krs k ON m.id_mhs=k.id_mhs JOIN jadwal_kuliah jk ON k.id_jadwal=jk.id_jadwal JOIN mata_kuliah mk ON jk.id_mk=mk.id_mk LEFT JOIN nilai_akhir n ON n.id_krs=k.id_krs WHERE n.bobot_4_0 IS NOT NULL GROUP BY m.id_mhs) x")->fetchColumn();
$data = $pdo->query("SELECT m.nama, IFNULL(ROUND(SUM(mk.sks*n.bobot_4_0)/SUM(mk.sks),2),0) AS ipk FROM mahasiswa m JOIN krs k ON m.id_mhs=k.id_mhs JOIN jadwal_kuliah jk ON k.id_jadwal=jk.id_jadwal JOIN mata_kuliah mk ON jk.id_mk=mk.id_mk LEFT JOIN nilai_akhir n ON n.id_krs=k.id_krs WHERE n.bobot_4_0 IS NOT NULL GROUP BY m.id_mhs ORDER BY ipk DESC LIMIT 10")->fetchAll();
$res = ['total_mhs'=>$total_mhs, 'total_dosen'=>$total_dosen, 'ipk_avg'=>$ipk_avg, 'labels'=>[], 'ipk'=>[]];
foreach($data as $row){
    $res['labels'][] = $row['nama'];
    $res['ipk'][] = $row['ipk'];
}
header('Content-Type: application/json');
echo json_encode($res);

<?php
// views/admin/statistik.php
require_once '../../middleware/auth.php';
require_once '../../config/db.php';
if ($_SESSION['user']['role'] !== 'admin') {
    header('Location: dashboard.php');
    exit;
}
$total_mhs = $pdo->query("SELECT COUNT(*) FROM mahasiswa WHERE status_pembayaran='1'")->fetchColumn();
$total_dosen = $pdo->query("SELECT COUNT(*) FROM dosen")->fetchColumn();
$ipk_avg = $pdo->query("SELECT ROUND(AVG(ipk),2) FROM (SELECT m.id_mhs, SUM(mk.sks*n.bobot_4_0)/SUM(mk.sks) AS ipk FROM mahasiswa m JOIN krs k ON m.id_mhs=k.id_mhs JOIN jadwal_kuliah jk ON k.id_jadwal=jk.id_jadwal JOIN mata_kuliah mk ON jk.id_mk=mk.id_mk LEFT JOIN nilai_akhir n ON n.id_krs=k.id_krs WHERE n.bobot_4_0 IS NOT NULL GROUP BY m.id_mhs) x")->fetchColumn();
$tunggakan = $pdo->query("SELECT SUM(nominal) FROM tagihan WHERE status_lunas='belum'")->fetchColumn();
$tunggakan_rp = $tunggakan ? 'Rp ' . number_format($tunggakan, 0, ',', '.') : 'Rp 0';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta charset="UTF-8">
    <title>Statistik Kampus</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- Global UI Perfection -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../public/css/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
</head>
<body>
<div class="container mt-4">
    <h2>Statistik Kampus</h2>
    <div class="row">
        <div class="col-md-3">
            <div class="card text-bg-primary mb-3"><div class="card-body"><h5>Total Mahasiswa Aktif</h5><h2><?= $total_mhs ?></h2></div></div>
        </div>
        <div class="col-md-3">
            <div class="card text-bg-success mb-3"><div class="card-body"><h5>Jumlah Dosen</h5><h2><?= $total_dosen ?></h2></div></div>
        </div>
        <div class="col-md-3">
            <div class="card text-bg-info mb-3"><div class="card-body"><h5>Rata-rata IPK Kampus</h5><h2><?= $ipk_avg ?></h2></div></div>
        </div>
        <div class="col-md-3">
            <div class="card text-bg-danger mb-3"><div class="card-body"><h5>Total Tunggakan Mhs</h5><h4><?= $tunggakan_rp ?></h4></div></div>
        </div>
    </div>
    <div class="row mt-3">
        <div class="col-md-6 text-center">
            <h5>Rata-Rata IPK per Angkatan</h5>
            <canvas id="chartIpk" height="200"></canvas>
        </div>
        <div class="col-md-6 text-center">
            <h5>Jumlah Mahasiswa per Prodi</h5>
            <canvas id="chartProdi" height="200"></canvas>
        </div>
    </div>
</div>
<script>
fetch('statistik_data.php').then(r=>r.json()).then(data=>{
    new Chart(document.getElementById('chartIpk').getContext('2d'), {
        type: 'bar',
        data: {
            labels: data.labels,
            datasets: [{label: 'Rata-Rata IPK', data: data.ipk, backgroundColor: '#0dcaf0'}]
        }
    });

    new Chart(document.getElementById('chartProdi').getContext('2d'), {
        type: 'pie',
        data: {
            labels: data.prodi_labels,
            datasets: [{label: 'Jumlah Mahasiswa', data: data.prodi_data, backgroundColor: ['#0d6efd', '#198754', '#ffc107', '#dc3545', '#6610f2']}]
        }
    });
});
</script>
</body>
</html>

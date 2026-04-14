<?php
// views/admin/dashboard_realtime.php
require_once '../../middleware/auth.php';
require_once '../../config/db.php';
if ($_SESSION['user']['role'] !== 'admin') {
    http_response_code(403);
    die('Akses ditolak.');
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta charset="UTF-8">
    <title>Dashboard Statistik Real-Time</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- Global UI Perfection -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../public/css/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
</head>
<body>
<div class="container mt-4">
    <h2>Dashboard Statistik Real-Time</h2>
    <div class="row">
        <div class="col-md-4">
            <div class="card text-bg-primary mb-3"><div class="card-body"><h5>Total Mahasiswa Aktif</h5><h2 id="total_mhs">...</h2></div></div>
        </div>
        <div class="col-md-4">
            <div class="card text-bg-success mb-3"><div class="card-body"><h5>Jumlah Dosen</h5><h2 id="total_dosen">...</h2></div></div>
        </div>
        <div class="col-md-4">
            <div class="card text-bg-info mb-3"><div class="card-body"><h5>Rata-rata IPK Kampus</h5><h2 id="ipk_avg">...</h2></div></div>
        </div>
    </div>
    <canvas id="chartIpk" height="100"></canvas>
</div>
<script>
function loadStats() {
    fetch('dashboard_realtime_data.php').then(r=>r.json()).then(data=>{
        document.getElementById('total_mhs').textContent = data.total_mhs;
        document.getElementById('total_dosen').textContent = data.total_dosen;
        document.getElementById('ipk_avg').textContent = data.ipk_avg;
        if(window.chartIpk) window.chartIpk.destroy();
        window.chartIpk = new Chart(document.getElementById('chartIpk').getContext('2d'), {
            type: 'bar',
            data: {
                labels: data.labels,
                datasets: [{label: 'IPK', data: data.ipk, backgroundColor: '#0dcaf0'}]
            }
        });
    });
}
loadStats();
setInterval(loadStats, 10000); // refresh tiap 10 detik
</script>
</body>
</html>

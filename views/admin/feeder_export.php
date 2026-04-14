<?php
// views/admin/feeder_export.php
require_once '../../middleware/auth.php';
require_once '../../config/db.php';
require_once '../../vendor/autoload.php'; // PhpSpreadsheet
if ($_SESSION['user']['role'] !== 'admin') {
    http_response_code(403);
    die('Akses ditolak. Hanya Admin yang dapat ekspor Feeder.');
}

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

function export_csv($filename, $header, $rows) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    $f = fopen('php://output', 'w');
    fputcsv($f, $header);
    foreach ($rows as $row) fputcsv($f, $row);
    fclose($f);
    exit;
}

function export_excel($filename, $header, $rows) {
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->fromArray($header, NULL, 'A1');
    $sheet->fromArray($rows, NULL, 'A2');
    $writer = new Xlsx($spreadsheet);
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    $writer->save('php://output');
    exit;
}

// Handle export requests
if (isset($_GET['export'])) {
    $type = $_GET['export'];
    if ($type === 'mahasiswa') {
        // Template: NIM, Nama, Tgl Lahir, Jenis Kelamin, Prodi, Tahun Masuk
        $header = ['NIM','Nama','Tanggal Lahir','Jenis Kelamin','Prodi','Tahun Masuk'];
        $rows = $pdo->query("SELECT m.nim, m.nama, m.tgl_lahir, m.jk, p.nama_prodi, m.tahun_masuk FROM mahasiswa m JOIN prodi p ON m.id_prodi=p.id_prodi")->fetchAll(PDO::FETCH_NUM);
        if ($_GET['format'] === 'csv') export_csv('feeder_mahasiswa.csv', $header, $rows);
        else export_excel('feeder_mahasiswa.xlsx', $header, $rows);
    } elseif ($type === 'kurikulum') {
        // Template: Kode MK, Nama MK, SKS, Semester, Prodi
        $header = ['Kode MK','Nama MK','SKS','Semester','Prodi'];
        $rows = $pdo->query("SELECT mk.kode_mk, mk.nama_mk, mk.sks, mk.semester, p.nama_prodi FROM mata_kuliah mk JOIN prodi p ON mk.id_prodi=p.id_prodi")->fetchAll(PDO::FETCH_NUM);
        if ($_GET['format'] === 'csv') export_csv('feeder_kurikulum.csv', $header, $rows);
        else export_excel('feeder_kurikulum.xlsx', $header, $rows);
    } elseif ($type === 'nilai') {
        // Template: NIM, Kode MK, Nilai Huruf, Nilai Angka, Semester, Tahun
        $header = ['NIM','Kode MK','Nilai Huruf','Nilai Angka','Semester','Tahun'];
        $rows = $pdo->query("SELECT m.nim, mk.kode_mk, n.nilai_huruf, n.nilai_angka, ta.semester, ta.tahun FROM nilai_akhir n JOIN krs k ON n.id_krs=k.id_krs JOIN mahasiswa m ON k.id_mhs=m.id_mhs JOIN jadwal_kuliah jk ON k.id_jadwal=jk.id_jadwal JOIN mata_kuliah mk ON jk.id_mk=mk.id_mk JOIN tahun_akademik ta ON jk.kode_tahun=ta.kode_tahun")->fetchAll(PDO::FETCH_NUM);
        if ($_GET['format'] === 'csv') export_csv('feeder_nilai.csv', $header, $rows);
        else export_excel('feeder_nilai.xlsx', $header, $rows);
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta charset="UTF-8">
    <title>Export Feeder PDDIKTI</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Global UI Perfection -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../public/css/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
</head>
<body>
<div class="container mt-4">
    <h2>Export Data Feeder PDDIKTI</h2>
    <div class="row">
        <div class="col-md-4">
            <div class="card mb-3">
                <div class="card-body">
                    <h5>Data Mahasiswa</h5>
                    <a href="?export=mahasiswa&format=csv" class="btn btn-primary btn-sm">Export CSV</a>
                    <a href="?export=mahasiswa&format=xlsx" class="btn btn-success btn-sm">Export Excel</a>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card mb-3">
                <div class="card-body">
                    <h5>Data Kurikulum</h5>
                    <a href="?export=kurikulum&format=csv" class="btn btn-primary btn-sm">Export CSV</a>
                    <a href="?export=kurikulum&format=xlsx" class="btn btn-success btn-sm">Export Excel</a>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card mb-3">
                <div class="card-body">
                    <h5>Data Nilai</h5>
                    <a href="?export=nilai&format=csv" class="btn btn-primary btn-sm">Export CSV</a>
                    <a href="?export=nilai&format=xlsx" class="btn btn-success btn-sm">Export Excel</a>
                </div>
            </div>
        </div>
    </div>
    <div class="alert alert-info mt-3">Struktur file sesuai template import Feeder PDDIKTI. Silakan cek hasil ekspor sebelum upload ke Feeder.</div>
</div>
</body>
</html>

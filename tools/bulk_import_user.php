<?php
// tools/bulk_import_user.php
session_start();
require_once '../config/db.php';
require_once '../includes/audit_log.php';
require_once '../includes/csrf.php';
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin_utama') {
    http_response_code(403);
    die('Akses ditolak. Hanya Admin Utama yang dapat import user.');
}
$success = $error = '';
csrf_validate();
if (isset($_POST['import'])) {
    if (isset($_FILES['csv']) && $_FILES['csv']['error'] === 0) {
        $file = $_FILES['csv']['tmp_name'];
        $ext = strtolower(pathinfo($_FILES['csv']['name'], PATHINFO_EXTENSION));
        $mime = mime_content_type($file);
        if ($ext !== 'csv' || strpos($mime, 'csv') === false && strpos($mime, 'text') === false) {
            $error = 'File harus format CSV.';
        } elseif ($_FILES['csv']['size'] > 2*1024*1024) {
            $error = 'Ukuran file maksimal 2MB.';
        } else {
            $handle = fopen($file, 'r');
            $header = fgetcsv($handle);
            $pdo->beginTransaction();
            try {
                while (($row = fgetcsv($handle)) !== false) {
                    $data = array_combine($header, $row);
                    // Validasi data
                    $nim = trim($data['nim'] ?? '');
                    $nama = trim($data['nama'] ?? '');
                    $tgl_lahir = $data['tgl_lahir'] ?? '';
                    $jk = $data['jk'] ?? '';
                    $email = trim($data['email'] ?? '');
                    $no_hp = trim($data['no_hp'] ?? '');
                    $id_prodi = $data['id_prodi'] ?? '';
                    $tahun_masuk = $data['tahun_masuk'] ?? '';
                    if (!preg_match('/^[0-9]{8,20}$/', $nim)) throw new Exception('NIM tidak valid');
                    if (!preg_match('/^[a-zA-Z .\-]{3,100}$/u', $nama)) throw new Exception('Nama tidak valid');
                    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $tgl_lahir)) throw new Exception('Tanggal lahir tidak valid');
                    if (!in_array($jk, ['L', 'P'])) throw new Exception('JK tidak valid');
                    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) throw new Exception('Email tidak valid');
                    if (!preg_match('/^[0-9+\- ]{8,20}$/', $no_hp)) throw new Exception('No HP tidak valid');
                    if (!is_numeric($id_prodi)) throw new Exception('Prodi tidak valid');
                    if (!preg_match('/^\d{4}$/', $tahun_masuk)) throw new Exception('Tahun masuk tidak valid');
                    $stmt = $pdo->prepare('INSERT INTO mahasiswa (nim, nama, tgl_lahir, jk, email, no_hp, id_prodi, tahun_masuk) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
                    $stmt->execute([
                        $nim, $nama, $tgl_lahir, $jk, $email, $no_hp, $id_prodi, $tahun_masuk
                    ]);
                }
                $pdo->commit();
                $success = 'Import berhasil!';
                audit_log('import_user', 'Bulk import user mahasiswa dari CSV');
            } catch (Exception $e) {
                $pdo->rollBack();
                $error = 'Gagal import: ' . $e->getMessage();
                audit_log('import_user_gagal', $error);
            }
            fclose($handle);
        }
    } else {
        $error = 'File CSV tidak valid.';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Bulk Import User Mahasiswa</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-4">
    <h2>Bulk Import User Mahasiswa</h2>
    <?php if ($success): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>
    <form method="post" enctype="multipart/form-data">
        <?= csrf_input() ?>
        <div class="mb-2">
            <label>Upload File CSV</label>
            <input type="file" name="csv" class="form-control" accept=".csv" required>
            <small>Header wajib: nim,nama,tgl_lahir,jk,email,no_hp,id_prodi,tahun_masuk</small>
        </div>
        <button type="submit" name="import" class="btn btn-primary">Import</button>
    </form>
</div>
</body>
</html>

<?php
// views/admin/pengaturan.php
require_once '../../middleware/auth.php';
require_once '../../config/db.php';
require_once '../../includes/csrf.php';

if ($_SESSION['user']['role'] !== 'admin') {
    header('Location: dashboard.php'); exit;
}

$msg = '';
$status_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['aksi']) && $_POST['aksi'] === 'simpan_pengaturan') {
    csrf_validate();
    
    try {
        $pdo->beginTransaction();
        foreach ($_POST['s'] as $key => $value) {
            $stmt = $pdo->prepare("UPDATE pengaturan SET _value = ? WHERE _key = ?");
            $stmt->execute([$value, $key]);
        }
        
        // Handle File Upload (Logo)
        if (!empty($_FILES['logo_kampus']['name'])) {
            $file = $_FILES['logo_kampus'];
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['png', 'jpg', 'jpeg', 'svg'])) {
                $filename = 'logo_' . time() . '.' . $ext;
                $dir = '../../public/img/';
                if (!is_dir($dir)) mkdir($dir, 0777, true);
                
                $target = $dir . $filename;
                if (move_uploaded_file($file['tmp_name'], $target)) {
                    $pdo->prepare("UPDATE pengaturan SET _value = ? WHERE _key = 'logo_kampus'")
                        ->execute(['public/img/' . $filename]);
                } else {
                    $msg = "Peringatan: Gagal memindahkan file ke direktori tujuan.";
                    $status_msg = "warning";
                }
            } else {
                $msg = "Format file logo tidak didukung.";
                $status_msg = "warning";
            }
        }
        
        $pdo->commit();
        $msg = "Pengaturan sistem berhasil diperbarui secara permanen.";
        $status_msg = "success";
        
        // Reload settings for current request
        require '../../includes/load_settings.php'; 
    } catch (Exception $e) {
        $pdo->rollBack();
        $msg = "Gagal memperbarui pengaturan: " . $e->getMessage();
        $status_msg = "danger";
    }
}

// Fetch settings grouped by category
$settings_raw = $pdo->query("SELECT * FROM pengaturan ORDER BY category, label")->fetchAll(PDO::FETCH_ASSOC);
$cats = [];
foreach ($settings_raw as $s) {
    $cats[$s['category']][] = $s;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta charset="UTF-8">
    <title>Konfigurasi Sistem - ERP Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../public/css/style.css">
</head>
<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom sticky-top shadow-sm px-3">
    <a class="navbar-brand fw-bold text-primary" href="dashboard.php">
        <img src="../../<?= $sys['logo_kampus'] ?>" alt="Logo" height="30" class="me-2 rounded">
        Konfigurasi <?= htmlspecialchars($sys['nama_kampus']) ?>
    </a>
</nav>

<div class="container-fluid">
    <div class="row">
        <?php include 'includes/sidebar.php'; ?>

        <main class="col-md-10 ms-sm-auto px-md-4 py-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="fw-bold mb-0">Pengaturan & Branding Sistem</h2>
            </div>

            <?php if ($msg): ?>
                <div class="alert alert-<?= $status_msg ?> border-0 shadow-sm alert-dismissible fade show" role="alert">
                    <i class="bi bi-info-circle-fill me-2"></i><?= $msg ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <form method="post" enctype="multipart/form-data" class="row">
                <?= csrf_input() ?>
                <input type="hidden" name="aksi" value="simpan_pengaturan">
                
                <div class="col-md-3 mb-4">
                    <div class="card border-0 shadow-sm rounded-4 sticky-top" style="top: 80px;">
                        <div class="card-body p-3">
                            <div class="nav flex-column nav-pills" id="v-pills-tab" role="tablist">
                                <?php $i=0; foreach($cats as $cat => $items): ?>
                                    <button class="nav-link text-start py-3 <?= $i==0 ? 'active' : '' ?>" id="pills-<?= strtolower($cat) ?>-tab" data-bs-toggle="pill" data-bs-target="#pills-<?= strtolower($cat) ?>" type="button" role="tab">
                                        <i class="bi bi-<?= $cat == 'Identity' ? 'building' : ($cat == 'Finance' ? 'wallet2' : 'grid-1x2') ?> me-2"></i> <?= $cat ?>
                                    </button>
                                <?php $i++; endforeach; ?>
                            </div>
                            <hr>
                            <button type="submit" class="btn btn-primary w-100 py-2 fw-bold rounded-3 shadow">
                                <i class="bi bi-save me-2"></i> Simpan Perubahan
                            </button>
                        </div>
                    </div>
                </div>

                <div class="col-md-9">
                    <div class="tab-content" id="v-pills-tabContent">
                        <?php $i=0; foreach($cats as $cat => $items): ?>
                            <div class="tab-pane fade <?= $i==0 ? 'show active' : '' ?>" id="pills-<?= strtolower($cat) ?>" role="tabpanel">
                                <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
                                    <div class="card-header bg-white border-bottom p-4">
                                        <h5 class="fw-bold mb-0">Konfigurasi <?= $cat ?></h5>
                                        <p class="text-muted small mb-0">Kelola informasi terkait <?= strtolower($cat) ?> sistem.</p>
                                    </div>
                                    <div class="card-body p-4">
                                        <?php foreach($items as $s): ?>
                                            <div class="mb-4">
                                                <label class="form-label fw-bold"><?= htmlspecialchars($s['label']) ?></label>
                                                <?php if($s['type'] == 'text'): ?>
                                                    <input type="text" name="s[<?= $s['_key'] ?>]" class="form-control py-2 shadow-none border-light-subtle bg-light" value="<?= htmlspecialchars($s['_value']) ?>">
                                                <?php elseif($s['type'] == 'textarea'): ?>
                                                    <textarea name="s[<?= $s['_key'] ?>]" class="form-control shadow-none border-light-subtle bg-light" rows="4"><?= htmlspecialchars($s['_value']) ?></textarea>
                                                <?php elseif($s['type'] == 'file'): ?>
                                                    <div class="d-flex align-items-center gap-3">
                                                        <img src="../../<?= $s['_value'] ?>" alt="Preview" class="rounded border bg-white p-1 shadow-sm" style="max-height: 80px;">
                                                        <div class="flex-grow-1">
                                                            <input type="file" name="<?= $s['_key'] ?>" class="form-control shadow-none border-light-subtle bg-light" accept="image/*">
                                                            <small class="text-muted">Rekomendasi format PNG transparan untuk logo.</small>
                                                        </div>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        <?php $i++; endforeach; ?>
                    </div>
                </div>
            </form>
        </main>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

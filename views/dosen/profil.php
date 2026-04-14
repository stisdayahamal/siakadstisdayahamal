<?php
// views/dosen/profil.php
require_once '../../middleware/auth.php';
require_once '../../config/db.php';
if ($_SESSION['user']['role'] !== 'dosen') { header("Location: dashboard.php"); exit; }

$user_id = $_SESSION['user']['id'];
$sukses = $error = '';
$sukses_pwd = $error_pwd = '';

// Data User Baru
$stmt = $pdo->prepare("SELECT * FROM users WHERE id_user = ?");
$stmt->execute([$user_id]);
$currentUser = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_profil'])) {
        $nama = trim($_POST['nama']);
        $username = trim($_POST['username']);
        
        $sql = "UPDATE users SET nama = ?, username = ? WHERE id_user = ?";
        $params = [$nama, $username, $user_id];
        
        // Handle foto upload
        if (!empty($_FILES['foto']['name'])) {
            $dir = '../../public/uploads/avatar/';
            if (!is_dir($dir)) mkdir($dir, 0777, true);
            $ext = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));
            if(in_array($ext, ['jpg','jpeg','png'])) {
                $filename = 'avatar_' . $user_id . '_' . time() . '.' . $ext;
                if (move_uploaded_file($_FILES['foto']['tmp_name'], $dir . $filename)) {
                    if ($currentUser['foto'] && file_exists($dir . $currentUser['foto'])) {
                        @unlink($dir . $currentUser['foto']);
                    }
                    $sql = "UPDATE users SET nama = ?, username = ?, foto = ? WHERE id_user = ?";
                    $params = [$nama, $username, $filename, $user_id];
                    $currentUser['foto'] = $filename;
                }
            } else {
                $error = "Hanya mendukung file JPG/PNG.";
            }
        }
        
        if (!$error) {
            try{
                $pdo->prepare($sql)->execute($params);
                $pdo->prepare("INSERT INTO sistem_log_aktivitas (user_id, aksi, entitas, nilai_lama, nilai_baru) VALUES (?, ?, ?, ?, ?)")
                    ->execute([$user_id, 'UPDATE', 'Profil Dosen', $_SESSION['user']['nama'], $nama]);
                $_SESSION['user']['nama'] = $nama;
                $_SESSION['user']['username'] = $username;
                $sukses = "Biodata berhasil diperbarui.";
            } catch(PDOException $e) {
                $error = "Error: Username / NIDN mungkin duplikat.";
            }
        }
    }
    
    // Ganti password
    if (isset($_POST['ganti_password'])) {
        $pass_lama = $_POST['pass_lama'];
        $pass_baru = $_POST['pass_baru'];
        $konfirmasi = $_POST['konfirmasi_pass'];
        
        if ($pass_baru !== $konfirmasi) {
            $error_pwd = "Konfirmasi password beda.";
        } else {
            if (password_verify($pass_lama, $currentUser['password'])) {
                $new_hash = password_hash($pass_baru, PASSWORD_DEFAULT);
                $pdo->prepare("UPDATE users SET password = ? WHERE id_user = ?")->execute([$new_hash, $user_id]);
                $pdo->prepare("INSERT INTO sistem_log_aktivitas (user_id, aksi, entitas) VALUES (?, ?, ?)")->execute([$user_id, 'UPDATE', 'Password Dosen']);
                $sukses_pwd = "Autentikasi Sandi berhasil diubah!";
            } else {
                $error_pwd = "Sandi keamanan lama Anda keliru.";
            }
        }
    }
}

// Refresh User Data
$stmt->execute([$user_id]);
$currentUser = $stmt->fetch();
$_SESSION['user']['nama'] = $currentUser['nama'];

$logs = $pdo->prepare("SELECT * FROM sistem_log_aktivitas WHERE user_id = ? ORDER BY id_log DESC LIMIT 20");
$logs->execute([$user_id]);
$log_pribadi = $logs->fetchAll();

$avatar_url = $currentUser['foto'] ? "../../public/uploads/avatar/" . $currentUser['foto'] : "https://ui-avatars.com/api/?name=".urlencode($currentUser['nama'])."&background=0d6efd&color=fff";
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta charset="UTF-8">
    <title>Profil Dosen - <?= htmlspecialchars($sys['nama_kampus']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>body{background:#f8f9fa}</style>
    <!-- Global UI Perfection -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../public/css/style.css">
</head>
<body>
<nav class="navbar navbar-expand-lg border-bottom sticky-top bg-white px-3 mb-4 shadow-sm">
    <div class="container-fluid">
        <a class="navbar-brand fw-bold text-primary" href="dashboard.php">
            <i class="bi bi-person-video3 text-warning me-2"></i>SIAKAD Dosen
        </a>
        <span class="navbar-text ms-auto text-dark fw-medium">
            Halo, <?= htmlspecialchars($_SESSION['user']['username'] ?? $_SESSION['user']['nama']) ?> | 
            <a href="../../auth/logout.php" class="text-danger fw-bold text-decoration-none ms-2"><i class="bi bi-box-arrow-right"></i> Logout</a>
        </span>
    </div>
</nav>

<div class="container-fluid">
    <div class="row">
        <?php include 'includes/sidebar.php'; ?>
        
        <main class="col-md-10 ms-sm-auto px-md-4 py-4">
            <h2 class="fw-bold mb-4">Profil & Akun Dosen</h2>
            
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm rounded-4 mb-4 text-center p-4">
                        <img src="<?= $avatar_url ?>" class="rounded-circle mx-auto mb-3 shadow-sm" width="128" height="128" style="object-fit:cover; border:4px solid #fff;">
                        <h4 class="fw-bold"><?= htmlspecialchars($currentUser['nama']) ?></h4>
                        <p class="text-muted mb-2">@<?= htmlspecialchars($currentUser['username']) ?></p>
                        <span class="badge bg-primary px-3 py-2 text-uppercase rounded-pill">Dosen Akademik</span>
                    </div>

                    <div class="card border-0 shadow-sm rounded-4 text-start">
                        <div class="card-header bg-white border-0 pt-3 pb-0">
                            <h6 class="fw-bold mb-0"><i class="bi bi-shield-lock me-2 text-danger"></i>Ubah Password</h6>
                        </div>
                        <div class="card-body">
                            <?php if($sukses_pwd): ?><div class="alert alert-success py-2"><small><?= $sukses_pwd ?></small></div><?php endif; ?>
                            <?php if($error_pwd): ?><div class="alert alert-danger py-2"><small><?= $error_pwd ?></small></div><?php endif; ?>
                            <form method="post">
                                <div class="mb-3">
                                    <label class="form-label small">Password Lama</label>
                                    <input type="password" name="pass_lama" class="form-control" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label small">Password Baru</label>
                                    <input type="password" name="pass_baru" class="form-control" required minlength="6">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label small">Konfirmasi Password</label>
                                    <input type="password" name="konfirmasi_pass" class="form-control" required minlength="6">
                                </div>
                                <button type="submit" name="ganti_password" class="btn btn-danger w-100 fw-bold">Update Sandi</button>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-md-8">
                    <div class="card border-0 shadow-sm rounded-4 mb-4">
                        <div class="card-header bg-white border-0 pt-3 pb-0">
                            <h5 class="fw-bold mb-0 text-primary"><i class="bi bi-person-lines-fill me-2"></i>Informasi Biodata Profil</h5>
                        </div>
                        <div class="card-body">
                            <?php if($sukses): ?><div class="alert alert-success py-2"><?= $sukses ?></div><?php endif; ?>
                            <?php if($error): ?><div class="alert alert-danger py-2"><?= $error ?></div><?php endif; ?>
                            <form method="post" enctype="multipart/form-data">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-semibold">Nama Lengkap & Gelar</label>
                                        <input type="text" name="nama" class="form-control" value="<?= htmlspecialchars($currentUser['nama']) ?>" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-semibold">Username / NIDN</label>
                                        <input type="text" name="username" class="form-control" value="<?= htmlspecialchars($currentUser['username']) ?>" required>
                                    </div>
                                    <div class="col-md-12 mb-3">
                                        <label class="form-label fw-semibold">Ganti Foto Profil (Opsional)</label>
                                        <input type="file" name="foto" class="form-control" accept="image/png, image/jpeg">
                                        <small class="text-muted">Mendukung format JPG/PNG rasio kotak 1:1.</small>
                                    </div>
                                </div>
                                <div class="text-end">
                                    <button type="submit" name="update_profil" class="btn btn-primary px-4 fw-bold">Simpan Biodata</button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="card border-0 shadow-sm rounded-4">
                        <div class="card-header bg-white border-0 pt-3 pb-0">
                            <h5 class="fw-bold mb-0 text-success"><i class="bi bi-activity me-2"></i>Log Aktivitas Mandiri</h5>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light">
                                        <tr><th class="ps-4">Waktu</th><th>Jenis</th><th>Tindakan / Detail</th></tr>
                                    </thead>
                                    <tbody>
                                        <?php if(count($log_pribadi) > 0): ?>
                                            <?php foreach($log_pribadi as $log): ?>
                                            <tr>
                                                <td class="ps-4 text-muted small"><?= date('d M Y H:i', strtotime($log['created_at'])) ?></td>
                                                <td><span class="badge bg-info text-dark"><?= htmlspecialchars($log['aksi']) ?></span></td>
                                                <td><strong class="d-block text-dark"><?= htmlspecialchars($log['entitas']) ?></strong>
                                                    <small class="text-muted"><?= htmlspecialchars($log['nilai_baru'] ?? '') ?></small>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr><td colspan="3" class="text-center text-muted py-4">Belum ada aktivitas.</td></tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

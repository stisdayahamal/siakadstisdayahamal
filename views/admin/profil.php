<?php
// views/admin/profil.php
require_once '../../middleware/auth.php';
require_once '../../config/db.php';
$user = $_SESSION['user'];
$user_id = $user['id'];

$sukses = $error = '';
$sukses_pwd = $error_pwd = '';

// Handle update profil
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profil'])) {
    $nama = trim($_POST['nama']);
    $username = trim($_POST['username']);
    
    // Cek apakah tabel "users" atau "admin" (Sistem ini menggunakan tabel `users` untuk auth standar)
    $stmt = $pdo->prepare("UPDATE users SET nama = ?, username = ? WHERE id = ?");
    if ($stmt->execute([$nama, $username, $user_id])) {
        // Catat di log global
        $pdo->prepare("INSERT INTO sistem_log_aktivitas (user_id, aksi, entitas, nilai_lama, nilai_baru) VALUES (?, ?, ?, ?, ?)")
            ->execute([$user_id, 'UPDATE', 'Profil Sendiri', $_SESSION['user']['nama'], $nama]);

        $_SESSION['user']['nama'] = $nama;
        $_SESSION['user']['username'] = $username;
        $user = $_SESSION['user'];
        $sukses = "Profil berhasil diperbarui.";
    } else {
        $error = "Terjadi kesalahan sistem saat menyimpan profil.";
    }
}

// Handle ganti password
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ganti_password'])) {
    $pass_lama = $_POST['pass_lama'];
    $pass_baru = $_POST['pass_baru'];
    $konfirmasi = $_POST['konfirmasi_pass'];

    if ($pass_baru !== $konfirmasi) {
        $error_pwd = "Konfirmasi password baru tidak cocok.";
    } else {
        $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $hash = $stmt->fetchColumn();
        
        if (password_verify($pass_lama, $hash)) {
            $new_hash = password_hash($pass_baru, PASSWORD_DEFAULT);
            $pdo->prepare("UPDATE users SET password = ? WHERE id = ?")->execute([$new_hash, $user_id]);
            
            // Catat log
            $pdo->prepare("INSERT INTO sistem_log_aktivitas (user_id, aksi, entitas) VALUES (?, ?, ?)")
                ->execute([$user_id, 'UPDATE', 'Password Akun']);

            $sukses_pwd = "Password berhasil diubah. Silakan gunakan password baru pada login berikutnya.";
        } else {
            $error_pwd = "Password lama yang Anda masukkan salah.";
        }
    }
}

// Ambil riwayat aktivitas
$logs = $pdo->prepare("SELECT * FROM sistem_log_aktivitas WHERE user_id = ? ORDER BY id_log DESC LIMIT 20");
$logs->execute([$user_id]);
$log_pribadi = $logs->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta charset="UTF-8">
    <title>Manajemen Profil - ERP Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <!-- Global UI Perfection -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../public/css/style.css">
</head>
<body class="bg-light">

<!-- Top Navbar -->
<nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom sticky-top shadow-sm px-3">
    <a class="navbar-brand fw-bold text-primary" href="dashboard.php"><i class="bi bi-briefcase-fill me-2"></i><?= htmlspecialchars($sys['nama_kampus']) ?></a>
    <div class="ms-auto d-flex align-items-center">
        <div class="dropdown">
            <button class="btn btn-white fw-bold d-flex align-items-center" type="button" data-bs-toggle="dropdown">
                <img src="https://ui-avatars.com/api/?name=<?= urlencode($user['username'] ?? 'A') ?>&background=random" class="rounded-circle me-2" width="32">
                <?= htmlspecialchars($user['username'] ?? $user['nama']) ?>
            </button>
        </div>
    </div>
</nav>

<div class="container-fluid">
    <div class="row">
        <?php include 'includes/sidebar.php'; ?>

        <main class="col-md-10 ms-sm-auto px-md-4 py-4">
            <h2 class="fw-bold mb-4">Pengaturan Profil & Keamanan</h2>

            <div class="row g-4">
                <div class="col-md-4">
                    <!-- Kartu Info -->
                    <div class="card border-0 shadow-sm rounded-4 mb-4 text-center p-4">
                        <img src="https://ui-avatars.com/api/?name=<?= urlencode($user['username'] ?? 'A') ?>&background=random&size=128" class="rounded-circle mx-auto mb-3 shadow-sm" width="128">
                        <h4 class="fw-bold"><?= htmlspecialchars($user['nama'] ?? 'Admin') ?></h4>
                        <p class="text-muted mb-2">@<?= htmlspecialchars($user['username'] ?? 'admin') ?></p>
                        <span class="badge bg-primary px-3 py-2 text-uppercase"><?= htmlspecialchars($user['role'] ?? 'Admin') ?></span>
                    </div>

                    <!-- Ganti Password -->
                    <div class="card border-0 shadow-sm rounded-4">
                        <div class="card-header bg-white border-0 pt-3 pb-0">
                            <h6 class="fw-bold mb-0"><i class="bi bi-shield-lock me-2 text-danger"></i>Keamanan Akun</h6>
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
                                    <label class="form-label small">Konfirmasi Password Baru</label>
                                    <input type="password" name="konfirmasi_pass" class="form-control" required minlength="6">
                                </div>
                                <button type="submit" name="ganti_password" class="btn btn-danger w-100">Ganti Password</button>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-md-8">
                    <!-- Edit Data Pribadi -->
                    <div class="card border-0 shadow-sm rounded-4 mb-4">
                        <div class="card-header bg-white border-0 pt-3 pb-0">
                            <h5 class="fw-bold mb-0"><i class="bi bi-person-lines-fill me-2 text-primary"></i>Informasi Pribadi</h5>
                        </div>
                        <div class="card-body">
                            <?php if($sukses): ?><div class="alert alert-success py-2"><?= $sukses ?></div><?php endif; ?>
                            <?php if($error): ?><div class="alert alert-danger py-2"><?= $error ?></div><?php endif; ?>

                            <form method="post">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Nama Lengkap</label>
                                        <input type="text" name="nama" class="form-control" value="<?= htmlspecialchars($user['nama'] ?? '') ?>" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Username</label>
                                        <input type="text" name="username" class="form-control" value="<?= htmlspecialchars($user['username'] ?? '') ?>" required>
                                        <small class="text-muted">Digunakan untuk login ke sistem.</small>
                                    </div>
                                </div>
                                <hr class="my-4">
                                <div class="d-flex justify-content-end">
                                    <button type="submit" name="update_profil" class="btn btn-primary px-4">Simpan Perubahan</button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Riwayat Aktivitas Saya -->
                    <div class="card border-0 shadow-sm rounded-4">
                        <div class="card-header bg-white border-0 pt-3 pb-0">
                            <h5 class="fw-bold mb-0"><i class="bi bi-clock-history me-2 text-info"></i>Riwayat Aktivitas Diri</h5>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th class="ps-4">Waktu</th>
                                            <th>Aksi</th>
                                            <th>Entitas</th>
                                            <th>Perubahan Baru</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if(count($log_pribadi) > 0): ?>
                                            <?php foreach($log_pribadi as $log): ?>
                                            <tr>
                                                <td class="ps-4 text-muted small"><?= date('d/m/Y H:i', strtotime($log['created_at'])) ?></td>
                                                <td><span class="badge bg-secondary"><?= htmlspecialchars($log['aksi']) ?></span></td>
                                                <td class="fw-bold"><?= htmlspecialchars($log['entitas']) ?></td>
                                                <td class="text-truncate" style="max-width: 250px;"><small><?= htmlspecialchars($log['nilai_baru'] ?? '-') ?></small></td>
                                            </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr><td colspan="4" class="text-center text-muted py-4">Belum ada aktivitas tercatat.</td></tr>
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

<?php
// auth/login.php
define('ACCESS', true);
require_once '../includes/load_settings.php';
session_start();
require_once '../includes/audit_log.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $stmt = $pdo->prepare('SELECT * FROM users WHERE username = ? AND is_active = 1');
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    if ($user && password_verify($password, $user['password'])) {
        audit_log('login_sukses', 'User: ' . $username);
        $_SESSION['user'] = [
            'id' => $user['id_user'] ?? $user['id'] ?? 0,
            'username' => $user['username'],
            'nama' => $user['nama'] ?? $user['username'],
            'role' => $user['role'],
            'id_mhs' => $user['id_mhs'],
            'id_dosen' => $user['id_dosen']
        ];
        // Redirect by role
        if ($user['role'] === 'admin') {
            header('Location: ../views/admin/dashboard.php');
        } elseif ($user['role'] === 'dosen') {
            header('Location: ../views/dosen/dashboard.php');
        } else {
            header('Location: ../views/mahasiswa/dashboard.php');
        }
        exit;
    }
    audit_log('login_gagal', 'User: ' . $username);
    $error = 'Username atau password salah!';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?= htmlspecialchars($sys['nama_kampus']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../public/css/style.css">
</head>
<body class="bg-light">
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-4">
            <div class="card border-0 shadow-lg rounded-4 overflow-hidden">
                <div class="card-header bg-white pt-5 pb-4 border-0 text-center">
                    <img src="../<?= $sys['logo_kampus'] ?>" alt="Logo" width="80" class="mb-3 shadow-sm rounded-circle">
                    <h4 class="fw-bold mb-0 text-dark"><?= htmlspecialchars($sys['nama_kampus']) ?></h4>
                    <p class="text-secondary mb-1 small text-uppercase" style="letter-spacing:1px;">Portal Akademik Terpadu</p>
                    <small class="text-muted d-block" style="font-size:11px;">Presisi &bullet; Transparan &bullet; Akuntabel</small>
                </div>
                <div class="card-body px-4 pb-4">
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger border-0 small py-2"><?= $error ?></div>
                    <?php endif; ?>
                    <form method="post">
                        <div class="mb-3">
                            <label for="username" class="form-label small fw-bold text-muted">Username</label>
                            <input type="text" class="form-control shadow-none border-light-subtle bg-light" id="username" name="username" required autofocus>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label small fw-bold text-muted">Password</label>
                            <input type="password" class="form-control shadow-none border-light-subtle bg-light" id="password" name="password" required>
                        </div>
                        <button type="submit" class="btn btn-primary w-100 fw-bold py-2 shadow-sm rounded-3">
                            Masuk Ke Sistem <i class="bi bi-arrow-right-short ms-1"></i>
                        </button>
                    </form>
                </div>
            </div>
            
            <div class="text-center mt-4">
                <small class="text-muted" style="font-size: 11px; line-height: 1.5; display: block;">
                    &copy; <?= date('Y') ?> <strong><?= htmlspecialchars($sys['nama_kampus']) ?></strong><br>
                    <?= htmlspecialchars($sys['alamat_kampus']) ?>
                </small>
            </div>
        </div>
    </div>
</div>
</body>
</html>

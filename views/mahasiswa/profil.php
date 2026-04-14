<?php
// views/mahasiswa/profil.php
require_once '../../middleware/auth.php';
require_once '../../config/db.php';
require_once '../../includes/csrf.php';

if ($_SESSION['user']['role'] !== 'mahasiswa') { header('Location: dashboard.php'); exit; }
$id_mhs = $_SESSION['user']['id_mhs'] ?? $_SESSION['user']['id'];
$id_user = $_SESSION['user']['id'];

$success = $error = '';
csrf_validate();

// Handle upload foto
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'foto') {
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] === 0) {
        $ext = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg','jpeg','png','gif','webp'];
        if (in_array($ext, $allowed)) {
            $dir = __DIR__ . '/../../public/uploads/profil/';
            if (!is_dir($dir)) mkdir($dir, 0777, true);
            $fname = 'mhs_' . $id_mhs . '_' . time() . '.' . $ext;
            if (move_uploaded_file($_FILES['foto']['tmp_name'], $dir . $fname)) {
                $pdo->prepare('UPDATE mahasiswa SET foto_profil=? WHERE id_mhs=?')->execute([$fname, $id_mhs]);
                $success = 'Foto profil berhasil diperbarui.';
            } else { $error = 'Gagal menyimpan foto.'; }
        } else { $error = 'Format file tidak didukung.'; }
    } else { $error = 'Tidak ada file yang diunggah.'; }
}

// Handle update data pribadi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'data') {
    $email   = trim($_POST['email'] ?? '');
    $no_hp   = trim($_POST['no_hp'] ?? '');
    $alamat  = trim($_POST['alamat'] ?? '');
    try {
        $pdo->prepare('UPDATE mahasiswa SET email=?, no_hp=?, alamat=? WHERE id_mhs=?')->execute([$email, $no_hp, $alamat, $id_mhs]);
        $success = 'Data pribadi berhasil diperbarui.';
    } catch (PDOException $e) { $error = 'Gagal: ' . $e->getMessage(); }
}

// Handle ubah password
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'password') {
    $lama   = $_POST['password_lama'] ?? '';
    $baru   = $_POST['password_baru'] ?? '';
    $konfirm = $_POST['password_konfirm'] ?? '';
    
    $user_data = $pdo->prepare('SELECT password FROM users WHERE id=?');
    $user_data->execute([$id_user]);
    $user_data = $user_data->fetch();
    
    if (!password_verify($lama, $user_data['password'])) {
        $error = 'Password lama tidak sesuai.';
    } elseif ($baru !== $konfirm) {
        $error = 'Konfirmasi password baru tidak cocok.';
    } elseif (strlen($baru) < 6) {
        $error = 'Password baru minimal 6 karakter.';
    } else {
        $pdo->prepare('UPDATE users SET password=? WHERE id=?')->execute([password_hash($baru, PASSWORD_DEFAULT), $id_user]);
        $success = 'Password berhasil diubah. Silakan login ulang.';
    }
}

// Ambil data mahasiswa terbaru
$mhs = $pdo->prepare('SELECT m.*, p.nama_prodi FROM mahasiswa m LEFT JOIN prodi p ON m.id_prodi=p.id_prodi WHERE m.id_mhs=?');
$mhs->execute([$id_mhs]);
$mhs = $mhs->fetch();
$foto_url = !empty($mhs['foto_profil']) ? '../../public/uploads/profil/' . $mhs['foto_profil'] : '../../public/img/default_avatar.png';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta charset="UTF-8">
    <title>Profil Mahasiswa - SIAKAD</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../public/css/style.css">
</head>
<body class="bg-light">
<nav class="navbar navbar-expand-lg border-bottom sticky-top bg-white px-3 mb-4 shadow-sm">
    <div class="container-fluid">
        <a class="navbar-brand fw-bold text-primary" href="dashboard.php"><i class="bi bi-mortarboard-fill text-warning me-2"></i>SIAKAD Mahasiswa</a>
        <span class="navbar-text ms-auto text-dark fw-medium">
            Halo, <?= htmlspecialchars($_SESSION['user']['username'] ?? $_SESSION['user']['nama']) ?> | 
            <a href="../../auth/logout.php" class="text-danger fw-bold text-decoration-none ms-2"><i class="bi bi-box-arrow-right"></i> Logout</a>
        </span>
    </div>
</nav>
<div class="container-fluid">
  <div class="row">
    <?php include 'includes/sidebar.php'; ?>
    <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 mt-3">

    <div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-4 border-bottom">
        <h1 class="h2"><i class="bi bi-person-circle me-2 text-primary"></i>Profil Saya</h1>
    </div>

    <?php if ($success): ?><div class="alert alert-success shadow-sm"><i class="bi bi-check-circle me-2"></i><?= $success ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-danger shadow-sm"><i class="bi bi-x-circle me-2"></i><?= $error ?></div><?php endif; ?>

    <div class="row g-4">
        <!-- Kartu Foto Profil -->
        <div class="col-md-4">
            <div class="card shadow-sm border-0">
                <div class="card-body text-center py-4">
                    <div class="position-relative d-inline-block mb-3">
                        <img src="<?= $foto_url ?>" alt="Foto Profil" class="rounded-circle border border-3 border-primary shadow" width="130" height="130" style="object-fit:cover;" onerror="this.src='https://ui-avatars.com/api/?name=<?= urlencode($mhs['nama'] ?? 'MHS') ?>&background=198754&color=fff&size=130'">
                    </div>
                    <h5 class="fw-bold mb-0"><?= htmlspecialchars($mhs['nama'] ?? '-') ?></h5>
                    <p class="text-muted mb-1"><small><?= htmlspecialchars($mhs['nim'] ?? '-') ?></small></p>
                    <span class="badge bg-success"><?= htmlspecialchars($mhs['nama_prodi'] ?? 'Mahasiswa') ?></span>
                    <hr>
                    <form method="post" enctype="multipart/form-data">
                        <?= csrf_input() ?>
                        <input type="hidden" name="action" value="foto">
                        <label class="form-label text-muted small">Ganti Foto Profil</label>
                        <input type="file" name="foto" class="form-control form-control-sm mb-2" accept="image/*" required>
                        <button type="submit" class="btn btn-primary btn-sm w-100"><i class="bi bi-camera me-1"></i>Upload Foto</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Edit Data Pribadi + Ubah Password -->
        <div class="col-md-8">
            <!-- Data Pribadi -->
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white fw-bold border-0 pt-3"><i class="bi bi-person-badge me-2 text-primary"></i>Edit Data Pribadi</div>
                <div class="card-body">
                    <form method="post">
                        <?= csrf_input() ?>
                        <input type="hidden" name="action" value="data">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Nama Lengkap</label>
                                <input type="text" class="form-control" value="<?= htmlspecialchars($mhs['nama'] ?? '') ?>" disabled>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">NIM</label>
                                <input type="text" class="form-control" value="<?= htmlspecialchars($mhs['nim'] ?? '') ?>" disabled>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Email</label>
                                <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($mhs['email'] ?? '') ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">No. HP / WhatsApp</label>
                                <input type="text" name="no_hp" class="form-control" value="<?= htmlspecialchars($mhs['no_hp'] ?? '') ?>">
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-semibold">Alamat Lengkap</label>
                                <textarea name="alamat" class="form-control" rows="2"><?= htmlspecialchars($mhs['alamat'] ?? '') ?></textarea>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-success mt-3 px-4 fw-bold"><i class="bi bi-save me-2"></i>Simpan Perubahan</button>
                    </form>
                </div>
            </div>

            <!-- Ubah Password -->
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white fw-bold border-0 pt-3"><i class="bi bi-shield-lock me-2 text-warning"></i>Ubah Password</div>
                <div class="card-body">
                    <form method="post">
                        <?= csrf_input() ?>
                        <input type="hidden" name="action" value="password">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Password Lama</label>
                            <input type="password" name="password_lama" class="form-control" required placeholder="Masukkan password saat ini">
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Password Baru</label>
                                <input type="password" name="password_baru" class="form-control" required placeholder="Minimal 6 karakter">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Konfirmasi Password Baru</label>
                                <input type="password" name="password_konfirm" class="form-control" required placeholder="Ulangi password baru">
                            </div>
                        </div>
                        <button type="submit" class="btn btn-warning mt-3 px-4 fw-bold text-white"><i class="bi bi-key me-2"></i>Ubah Password</button>
                    </form>
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

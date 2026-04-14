<?php
// views/admin/pmb_calon.php — Manajemen Calon & Migrasi Induk (Powerfull Version)
require_once '../../middleware/auth.php';
require_once '../../config/db.php';
require_once '../../includes/csrf.php';

if ($_SESSION['user']['role'] !== 'admin') {
    header('Location: dashboard.php');
    exit;
}

$success = $error = '';
csrf_validate();

// 1. Handle Update Status (Lulus/Tolak)
if (isset($_POST['id_calon'], $_POST['status'])) {
    $stmt = $pdo->prepare('UPDATE calon_mhs SET status=? WHERE id_calon=?');
    $stmt->execute([$_POST['status'], $_POST['id_calon']]);
    $success = "Status calon mahasiswa berhasil diperbarui.";
}

// 2. Handle Konfirmasi Pembayaran
if (isset($_POST['id_calon_bayar'])) {
    $stmt = $pdo->prepare('UPDATE calon_mhs SET sudah_bayar=1 WHERE id_calon=?');
    $stmt->execute([$_POST['id_calon_bayar']]);
    $success = "Pembayaran pendaftaran dikonfirmasi.";
}

// 3. Handle Migrasi ke Data Induk (NIM Generation)
if (isset($_POST['proses_nim'])) {
    $id_calon = $_POST['proses_nim'];
    $stmt = $pdo->prepare('SELECT * FROM calon_mhs WHERE id_calon=? AND status="Lulus" AND sudah_bayar=1');
    $stmt->execute([$id_calon]);
    $calon = $stmt->fetch();
    
    if ($calon) {
        // [NIM FORMAT]: 01 (Kampus) + KodeProdi (01/02) + Tahun2Digit + Urut3Digit
        $tahun_full = date('Y');
        $tahun_short = date('y');
        
        $prodi_stmt = $pdo->prepare('SELECT kode_prodi FROM prodi WHERE id_prodi = ?');
        $prodi_stmt->execute([$calon['id_prodi']]);
        $kode_prodi = $prodi_stmt->fetchColumn() ?: '00';
        
        $prefix = "01" . $kode_prodi . $tahun_short;
        $last_nim = $pdo->query("SELECT MAX(nim) FROM mahasiswa WHERE nim LIKE '$prefix%'")->fetchColumn();
        $urut = $last_nim ? (intval(substr($last_nim, -3)) + 1) : 1;
        $nim = $prefix . str_pad($urut, 3, '0', STR_PAD_LEFT);

        try {
            $pdo->beginTransaction();
            
            // Insert ke Mahasiswa (FULL MAPPING 37+ FIELD)
            $stmt_mhs = $pdo->prepare("INSERT INTO mahasiswa (
                nim, nik, nisn, nama, tempat_lahir, tgl_lahir, jk, agama, 
                alamat, rt, rw, kelurahan, kecamatan, kode_pos, 
                jenis_tinggal, alat_transportasi, 
                nama_ibu, nama_ayah, nik_ibu, nik_ayah, 
                pekerjaan_ayah, pekerjaan_ibu, pendidikan_ayah, pendidikan_ibu, penghasilan_ortu, 
                asal_sekolah, tahun_lulus, id_prodi, email, no_hp, tahun_masuk, jalur_masuk
            ) VALUES (
                ?, ?, ?, ?, ?, ?, ?, ?, 
                ?, ?, ?, ?, ?, ?, 
                ?, ?, 
                ?, ?, ?, ?, 
                ?, ?, ?, ?, ?, 
                ?, ?, ?, ?, ?, ?, ?
            )");
            
            $stmt_mhs->execute([
                $nim, $calon['nik'], $calon['nisn'] ?? '', $calon['nama'], $calon['tempat_lahir'] ?? '', $calon['tgl_lahir'], $calon['jk'], $calon['agama'] ?? '',
                $calon['alamat'] ?? '', $calon['rt'] ?? '', $calon['rw'] ?? '', $calon['kelurahan'] ?? '', $calon['kecamatan'] ?? '', $calon['kode_pos'] ?? '',
                $calon['jenis_tinggal'] ?? '', $calon['alat_transportasi'] ?? '',
                $calon['nama_ibu'], $calon['nama_ayah'] ?? '', $calon['nik_ibu'] ?? '', $calon['nik_ayah'] ?? '',
                $calon['pekerjaan_ayah'] ?? '', $calon['pekerjaan_ibu'] ?? '', $calon['pendidikan_ayah'] ?? '', $calon['pendidikan_ibu'] ?? '', $calon['penghasilan_ortu'] ?? '',
                $calon['asal_sekolah'] ?? '', $calon['tahun_lulus'] ?? '', $calon['id_prodi'], $calon['email'], $calon['no_hp'], $tahun_full, $calon['jalur_masuk'] ?? 'Reguler'
            ]);
            
            $id_mhs_baru = $pdo->lastInsertId();
            $pass_default = password_hash($nim, PASSWORD_DEFAULT);
            $pdo->prepare("INSERT INTO users (username, password, nama, role, id_mhs) VALUES (?, ?, ?, 'mahasiswa', ?)")
                ->execute([$nim, $pass_default, $calon['nama'], $id_mhs_baru]);
            
            // Hapus dari calon_mhs (Flag Data Sukses Migrasi)
            $pdo->prepare('DELETE FROM calon_mhs WHERE id_calon=?')->execute([$id_calon]);
            
            $pdo->commit();
            $success = "Sukses! $nim digenerate. Mahasiswa baru telah aktif di sistem.";
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = "Kesalahan Migrasi: " . $e->getMessage();
        }
    }
}

// 4. Handle Hapus Calon
if (isset($_GET['delete'])) {
    $pdo->prepare('DELETE FROM calon_mhs WHERE id_calon=?')->execute([$_GET['delete']]);
    $success = "Data calon berhasil dihapus.";
}

$calon = $pdo->query('SELECT c.*, p.nama_prodi FROM calon_mhs c JOIN prodi p ON c.id_prodi=p.id_prodi ORDER BY c.created_at DESC')->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta charset="UTF-8">
    <title>Manajemen PMB Premium - SIAKAD</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../public/css/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="bg-light">
<nav class="navbar navbar-expand-lg border-bottom sticky-top bg-white px-3 shadow-sm">
    <div class="container-fluid">
        <a class="navbar-brand fw-bold text-primary" href="dashboard.php">
            <i class="bi bi-person-check-fill text-success me-2"></i>PMB Gateway Admin
        </a>
    </div>
</nav>

<div class="container-fluid pb-5">
  <div class="row">
    <?php include 'includes/sidebar.php'; ?>
    <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 mt-4">

        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="fw-bold"><i class="bi bi-people-fill me-2"></i>Verifikasi Calon Mahasiswa</h2>
            <div class="text-end">
                <span class="badge bg-primary px-3 py-2 rounded-pill"><?= count($calon) ?> Pendaftar</span>
            </div>
        </div>

        <?php if ($success): ?><script>Swal.fire({icon:'success',title:'Berhasil',text:'<?= addslashes($success) ?>'});</script><?php endif; ?>
        <?php if ($error): ?><script>Swal.fire({icon:'error',title:'Gagal',text:'<?= addslashes($error) ?>'});</script><?php endif; ?>

        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Biodata Calon</th>
                                <th>Prodi Pilihan</th>
                                <th>Status Admin</th>
                                <th>Keuangan</th>
                                <th>Berkas</th>
                                <th>Aksi Migrasi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($calon as $c): ?>
                            <tr>
                                <td>
                                    <div class="fw-bold"><?= htmlspecialchars($c['nama']) ?></div>
                                    <div class="small text-muted">NIK: <?= htmlspecialchars($c['nik']) ?></div>
                                    <div class="small text-muted"><i class="bi bi-calendar-event me-1"></i><?= date('d M Y', strtotime($c['created_at'])) ?></div>
                                </td>
                                <td>
                                    <div class="badge bg-secondary-subtle text-secondary rounded-pill"><?= htmlspecialchars($c['nama_prodi']) ?></div>
                                </td>
                                <td>
                                    <form method="post" class="d-flex gap-1">
                                        <?= csrf_input() ?>
                                        <input type="hidden" name="id_calon" value="<?= $c['id_calon'] ?>">
                                        <select name="status" class="form-select form-select-sm border-0 bg-light rounded-pill" onchange="this.form.submit()">
                                            <option value="Proses" <?= $c['status']=='Proses'?'selected':'' ?>>Proses</option>
                                            <option value="Lulus" <?= $c['status']=='Lulus'?'selected':'' ?>>Lulus</option>
                                            <option value="Tidak Lulus" <?= $c['status']=='Tidak Lulus'?'selected':'' ?>>Tidak Lulus</option>
                                        </select>
                                    </form>
                                </td>
                                <td>
                                    <?php if ($c['sudah_bayar']): ?>
                                        <span class="text-success small fw-bold"><i class="bi bi-check-circle-fill me-1"></i>LUNAS</span>
                                    <?php else: ?>
                                        <form method="post">
                                            <?= csrf_input() ?>
                                            <input type="hidden" name="id_calon_bayar" value="<?= $c['id_calon'] ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-warning rounded-pill px-3">Konfirmasi</button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="btn-group">
                                        <?php if ($c['berkas_ijazah']): ?><a href="../../<?= htmlspecialchars($c['berkas_ijazah']) ?>" target="_blank" class="btn btn-sm btn-light border" title="Ijazah"><i class="bi bi-file-earmark-text"></i></a><?php endif; ?>
                                        <?php if ($c['berkas_ktp']): ?><a href="../../<?= htmlspecialchars($c['berkas_ktp']) ?>" target="_blank" class="btn btn-sm btn-light border" title="KTP"><i class="bi bi-person-vcard"></i></a><?php endif; ?>
                                        <?php if ($c['berkas_foto']): ?><a href="../../<?= htmlspecialchars($c['berkas_foto']) ?>" target="_blank" class="btn btn-sm btn-light border" title="Foto"><i class="bi bi-image"></i></a><?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <?php if ($c['status']=='Lulus' && $c['sudah_bayar']): ?>
                                    <form method="post">
                                        <?= csrf_input() ?>
                                        <input type="hidden" name="proses_nim" value="<?= $c['id_calon'] ?>">
                                        <button type="submit" class="btn btn-sm btn-success w-100 shadow-sm rounded-pill fw-bold" onclick="return confirm('Generate NIM dan buat data Mahasiswa?')">
                                            Generate NIM
                                        </button>
                                    </form>
                                    <?php else: ?>
                                        <span class="text-muted small">Nunggu Lulus/Bayar</span>
                                    <?php endif; ?>
                                    <hr class="my-1 border-0">
                                    <a href="?delete=<?= $c['id_calon'] ?>" class="text-danger small text-decoration-none" onclick="return confirm('Hapus data?')">Hapus</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($calon)): ?>
                                <tr><td colspan="6" class="text-center py-5 text-muted">Belum ada pendaftaran yang masuk.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>
  </div>
</div>
</body>
</html>

<?php
// views/admin/pmb_seleksi.php
require_once '../../middleware/auth.php';
require_once '../../config/db.php';
require_once '../../includes/csrf.php';

if ($_SESSION['user']['role'] !== 'admin') {
    header('Location: dashboard.php');
    exit;
}

// Update status seleksi
csrf_validate();
if (isset($_POST['id_calon'], $_POST['status'])) {
    $stmt = $pdo->prepare('UPDATE calon_mhs SET status=? WHERE id_calon=?');
    $stmt->execute([$_POST['status'], $_POST['id_calon']]);
}

// Tandai sudah bayar
if (isset($_POST['id_calon_bayar'])) {
    $stmt = $pdo->prepare('UPDATE calon_mhs SET sudah_bayar=1 WHERE id_calon=?');
    $stmt->execute([$_POST['id_calon_bayar']]);
}

// Proses auto-NIM dan migrasi ke mahasiswa
if (isset($_POST['proses_nim'])) {
    $id_calon = $_POST['proses_nim'];
    $stmt = $pdo->prepare('SELECT * FROM calon_mhs WHERE id_calon=? AND status="Lulus" AND sudah_bayar=1');
    $stmt->execute([$id_calon]);
    $calon = $stmt->fetch();
    
    if ($calon) {
        // [NIM FORMAT]: 01 (Campus) + KodeProdi + Tahun (2 Digit) + Urut (3 Digit)
        $tahun_full = date('Y');
        $tahun_short = date('y');
        
        // Ambil kode prodi dari tabel prodi yang sudah di-refine
        $prodi_stmt = $pdo->prepare('SELECT kode_prodi FROM prodi WHERE id_prodi = ?');
        $prodi_stmt->execute([$calon['id_prodi']]);
        $kode_prodi = $prodi_stmt->fetchColumn() ?: '00';
        
        $prefix = "01" . $kode_prodi . $tahun_short;
        $last_nim_stmt = $pdo->prepare("SELECT MAX(nim) FROM mahasiswa WHERE nim LIKE ?");
        $last_nim_stmt->execute([$prefix . '%']);
        $last_nim = $last_nim_stmt->fetchColumn();
        
        $urut = $last_nim ? (intval(substr($last_nim, -3)) + 1) : 1;
        $nim = $prefix . str_pad($urut, 3, '0', STR_PAD_LEFT);

        try {
            $pdo->beginTransaction();
            
            // Migrasi Data Lengkap ke Mahasiswa (PDDikti Compliant)
            $stmt_mhs = $pdo->prepare("INSERT INTO mahasiswa (
                nim, nik, nisn, nama, tempat_lahir, tgl_lahir, jk, agama, 
                alamat, rt, rw, kelurahan, kecamatan, kode_pos, 
                jenis_tinggal, alat_transportasi, 
                nama_ibu, nama_ayah, nik_ibu, nik_ayah, 
                pekerjaan_ayah, pekerjaan_ibu, pendidikan_ayah, pendidikan_ibu, penghasilan_ortu, 
                asal_sekolah, tahun_lulus, id_prodi, email, no_hp, tahun_masuk, jalur_masuk, status_kuliah, berkas_kip_pkh
            ) VALUES (
                ?, ?, ?, ?, ?, ?, ?, ?, 
                ?, ?, ?, ?, ?, ?, 
                ?, ?, 
                ?, ?, ?, ?, 
                ?, ?, ?, ?, ?, 
                ?, ?, ?, ?, ?, ?, ?, 'Aktif', ?
            )");
            
            $stmt_mhs->execute([
                $nim, $calon['nik'], $calon['nisn'] ?? '', $calon['nama'], $calon['tempat_lahir'] ?? '', $calon['tgl_lahir'], $calon['jk'], $calon['agama'] ?? '',
                $calon['alamat'] ?? '', $calon['rt'] ?? '', $calon['rw'] ?? '', $calon['kelurahan'] ?? '', $calon['kecamatan'] ?? '', $calon['kode_pos'] ?? '',
                $calon['jenis_tinggal'] ?? '', $calon['alat_transportasi'] ?? '',
                $calon['nama_ibu'], $calon['nama_ayah'] ?? '', $calon['nik_ibu'] ?? '', $calon['nik_ayah'] ?? '',
                $calon['pekerjaan_ayah'] ?? '', $calon['pekerjaan_ibu'] ?? '', $calon['pendidikan_ayah'] ?? '', $calon['pendidikan_ibu'] ?? '', $calon['penghasilan_ortu'] ?? '',
                $calon['asal_sekolah'] ?? '', $calon['tahun_lulus'] ?? '', $calon['id_prodi'], $calon['email'], $calon['no_hp'], $tahun_full, $calon['jalur_masuk'] ?? 'Reguler',
                $calon['berkas_kip_pkh'] ?? ''
            ]);
            
            $id_mhs_baru = $pdo->lastInsertId();
            
            // Buat Akun Login User
            $pass_default = password_hash($nim, PASSWORD_DEFAULT);
            $pdo->prepare("INSERT INTO users (username, password, nama, role, id_mhs) VALUES (?, ?, ?, 'mahasiswa', ?)")
                ->execute([$nim, $pass_default, $calon['nama'], $id_mhs_baru]);
            
            // Hapus dari calon_mhs agar data tidak duplikat di sistem induksi
            $pdo->prepare('DELETE FROM calon_mhs WHERE id_calon=?')->execute([$id_calon]);
            
            $pdo->commit();
            $success_migrasi = "NIM $nim berhasil digenerate dan data mahasiswa telah dimigrasikan.";
        } catch (Exception $e) {
            $pdo->rollBack();
            $error_migrasi = "Gagal Migrasi: " . $e->getMessage();
        }
    }
}

$calon = $pdo->query('SELECT c.*, p.nama_prodi FROM calon_mhs c JOIN prodi p ON c.id_prodi=p.id_prodi ORDER BY c.created_at DESC')->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta charset="UTF-8">
    <title>Seleksi & Migrasi PMB - SIAKAD</title>
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
            <i class="bi bi-person-check-fill text-success me-2"></i>Seleksi PMB
        </a>
        <a href="dashboard.php" class="btn btn-outline-secondary btn-sm rounded-pill px-3">Kembali ke Dashboard</a>
    </div>
</nav>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="fw-bold h4"><i class="bi bi-people-fill me-2"></i>Daftar Calon Mahasiswa</h2>
        <span class="badge bg-primary rounded-pill"><?= count($calon) ?> Total Pendaftar</span>
    </div>

    <?php if (isset($success_migrasi)): ?>
        <script>Swal.fire('Berhasil', '<?= $success_migrasi ?>', 'success');</script>
    <?php endif; ?>
    <?php if (isset($error_migrasi)): ?>
        <script>Swal.fire('Gagal', '<?= $error_migrasi ?>', 'error');</script>
    <?php endif; ?>

    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Identitas</th>
                            <th>Prodi Pilihan</th>
                            <th>Status Admin</th>
                            <th>Keuangan</th>
                            <th>Berkas</th>
                            <th>Aksi Strategis</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($calon as $c): ?>
                        <tr>
                            <td>
                                <div class="fw-bold"><?= htmlspecialchars($c['nama']) ?></div>
                                <div class="small text-muted">NIK: <?= htmlspecialchars($c['nik']) ?></div>
                                <div class="small text-muted">No: <?= htmlspecialchars($c['no_pendaftaran'] ?? '-') ?></div>
                            </td>
                            <td><?= htmlspecialchars($c['nama_prodi']) ?></td>
                            <td>
                                <form method="post" class="d-flex gap-1">
                                    <?= csrf_input() ?>
                                    <input type="hidden" name="id_calon" value="<?= $c['id_calon'] ?>">
                                    <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                                        <option value="Proses" <?= $c['status']=='Proses'?'selected':'' ?>>Proses</option>
                                        <option value="Lulus" <?= $c['status']=='Lulus'?'selected':'' ?>>Lulus</option>
                                        <option value="Tidak Lulus" <?= $c['status']=='Tidak Lulus'?'selected':'' ?>>Tidak Lulus</option>
                                    </select>
                                </form>
                            </td>
                            <td>
                                <?php if ($c['sudah_bayar']): ?>
                                    <span class="badge bg-success-subtle text-success border border-success-subtle px-3 rounded-pill">Lunas</span>
                                <?php else: ?>
                                    <form method="post">
                                        <?= csrf_input() ?>
                                        <input type="hidden" name="id_calon_bayar" value="<?= $c['id_calon'] ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-warning rounded-pill">Konfirmasi Bayar</button>
                                    </form>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="btn-group">
                                    <?php if ($c['berkas_ijazah']): ?><a href="../../<?= htmlspecialchars($c['berkas_ijazah']) ?>" target="_blank" class="btn btn-sm btn-outline-secondary" title="Ijazah"><i class="bi bi-file-earmark-text"></i></a><?php endif; ?>
                                    <?php if ($c['berkas_ktp']): ?><a href="../../<?= htmlspecialchars($c['berkas_ktp']) ?>" target="_blank" class="btn btn-sm btn-outline-secondary" title="KTP"><i class="bi bi-person-vcard"></i></a><?php endif; ?>
                                    <?php if ($c['berkas_foto']): ?><a href="../../<?= htmlspecialchars($c['berkas_foto']) ?>" target="_blank" class="btn btn-sm btn-outline-secondary" title="Foto"><i class="bi bi-image"></i></a><?php endif; ?>
                                </div>
                            </td>
                            <td>
                                <?php if ($c['status']=='Lulus' && $c['sudah_bayar']): ?>
                                <form method="post">
                                    <?= csrf_input() ?>
                                    <input type="hidden" name="proses_nim" value="<?= $c['id_calon'] ?>">
                                    <button type="submit" class="btn btn-sm btn-success w-100 shadow-sm rounded-pill fw-bold" onclick="return confirm('Generate NIM dan pindahkan data ke Induk Mahasiswa?')">
                                        <i class="bi bi-person-plus-fill me-1"></i>Generate NIM
                                    </button>
                                </form>
                                <?php else: ?>
                                    <span class="text-muted small italic">Selesaikan Seleksi & Bayar</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($calon)): ?>
                            <tr><td colspan="6" class="text-center py-4 text-muted">Belum ada calon mahasiswa dalam antrian.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<footer class="mt-5 py-4 border-top text-center text-muted small">
    &copy; <?= date('Y') ?> SIAKAD STIS Dayah Amal - PMB Management Module
</footer>
</body>
</html>

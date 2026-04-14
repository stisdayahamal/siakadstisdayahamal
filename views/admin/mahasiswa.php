<?php
// views/admin/mahasiswa.php — Super-CRUD Admin (PDDikti Standar)
require_once '../../middleware/auth.php';
require_once '../../config/db.php';
require_once '../../includes/csrf.php';

if ($_SESSION['user']['role'] !== 'admin') {
    header('Location: dashboard.php');
    exit;
}

$success = $error = '';
csrf_validate();

// Handle Create/Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    // Sanitasi Dasar
    $data = $_POST;
    $id_mhs = intval($data['id_mhs'] ?? 0);
    $nim = trim($data['nim'] ?? '');
    
    $fields = [
        'nim', 'nik', 'nisn', 'nama', 'tempat_lahir', 'tgl_lahir', 'jk', 'agama',
        'alamat', 'rt', 'rw', 'kelurahan', 'kecamatan', 'kode_pos',
        'jenis_tinggal', 'alat_transportasi',
        'nama_ibu', 'nama_ayah', 'nik_ibu', 'nik_ayah',
        'pekerjaan_ayah', 'pekerjaan_ibu', 'pendidikan_ayah', 'pendidikan_ibu', 'penghasilan_ortu',
        'asal_sekolah', 'tahun_lulus', 'id_prodi', 'email', 'no_hp', 'tahun_masuk', 'jalur_masuk', 'status_kuliah', 'berkas_kip_pkh'
    ];

    $set_clauses = [];
    $params = [];
    foreach ($fields as $f) {
        $params[] = $data[$f] ?? null;
        $set_clauses[] = "$f = ?";
    }

    if ($data['action'] === 'create') {
        try {
            $pdo->beginTransaction();
            $placeholders = str_repeat('?,', count($fields) - 1) . '?';
            $sql = "INSERT INTO mahasiswa (" . implode(',', $fields) . ") VALUES ($placeholders)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $id_mhs_baru = $pdo->lastInsertId();
            
            // Otomatis buat akun user
            $pass_default = password_hash($nim, PASSWORD_DEFAULT);
            $pdo->prepare("INSERT INTO users (username, password, nama, role, id_mhs) VALUES (?, ?, ?, 'mahasiswa', ?)")
                ->execute([$nim, $pass_default, $data['nama'], $id_mhs_baru]);
            
            $pdo->commit();
            $success = 'Mahasiswa baru dan akun login berhasil ditambahkan secara lengkap.';
        } catch (PDOException $e) {
            $pdo->rollBack();
            $error = 'Gagal menambah: ' . $e->getMessage();
        }
    } elseif ($data['action'] === 'update') {
        try {
            $pdo->beginTransaction();
            // Sync NIM dengan Username
            $old_nim = $pdo->prepare("SELECT nim FROM mahasiswa WHERE id_mhs = ?");
            $old_nim->execute([$id_mhs]);
            $on = $old_nim->fetchColumn();

            $params[] = $id_mhs;
            $sql = "UPDATE mahasiswa SET " . implode(',', $set_clauses) . " WHERE id_mhs = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            
            if ($on !== $nim) {
                $pdo->prepare("UPDATE users SET username = ? WHERE id_mhs = ? AND role = 'mahasiswa'")
                    ->execute([$nim, $id_mhs]);
            }
            
            $pdo->commit();
            $success = 'Seluruh data induk mahasiswa berhasil diperbarui.';
        } catch (PDOException $e) {
            $pdo->rollBack();
            $error = 'Gagal memperbarui: ' . $e->getMessage();
        }
    }
}

// Handle Delete (POST for Security & CSRF)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $id_mhs = intval($_POST['id_mhs']);
    try {
        $pdo->beginTransaction();
        // Log aktivitas sebelum hapus
        $stmt_mhs = $pdo->prepare("SELECT nama, nim FROM mahasiswa WHERE id_mhs = ?");
        $stmt_mhs->execute([$id_mhs]);
        $m_data = $stmt_mhs->fetch();

        $pdo->prepare('DELETE FROM users WHERE id_mhs=? AND role="mahasiswa"')->execute([$id_mhs]);
        $pdo->prepare('DELETE FROM mahasiswa WHERE id_mhs=?')->execute([$id_mhs]);
        
        // Log sistem (Ready to Scale Logging)
        if ($m_data) {
            $pdo->prepare("INSERT INTO sistem_log_aktivitas (aksi, entitas, id_entitas, nilai_lama, user_id) VALUES ('DELETE', 'mahasiswa', ?, ?, ?)")
                ->execute([$id_mhs, json_encode($m_data), $_SESSION['user']['id']]);
        }

        $pdo->commit();
        $success = 'Mahasiswa dan akun terkait telah dihapus permanen.';
    } catch (PDOException $e) {
        $pdo->rollBack();
        $error = 'Gagal menghapus: ' . $e->getMessage();
    }
}

$mahasiswa = $pdo->query('SELECT m.*, p.nama_prodi FROM mahasiswa m LEFT JOIN prodi p ON m.id_prodi = p.id_prodi ORDER BY m.id_mhs DESC')->fetchAll();
$prodi = $pdo->query('SELECT * FROM prodi ORDER BY nama_prodi ASC')->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta charset="UTF-8">
    <title>Super-CRUD Mahasiswa - SIAKAD</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../public/css/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .nav-tabs-custom .nav-link { border: none; color: #64748b; font-weight: 600; padding: 1rem 1.5rem; }
        .nav-tabs-custom .nav-link.active { color: var(--bs-primary); border-bottom: 3px solid var(--bs-primary); background: transparent; }
        .form-label-sm { font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.5px; color: #94a3b8; font-weight: 700; margin-bottom: 0.25rem; }
        .modal-xl { max-width: 1140px; }
    </style>
</head>
<body class="bg-light">
<nav class="navbar navbar-expand-lg border-bottom sticky-top bg-white px-3 mb-4 shadow-sm">
    <div class="container-fluid">
        <a class="navbar-brand fw-bold text-primary" href="dashboard.php">
            <i class="bi bi-shield-check text-warning me-2"></i>SIAKAD Enterprise Admin
        </a>
        <span class="navbar-text ms-auto text-dark fw-medium d-none d-sm-inline">
            <i class="bi bi-person-circle me-1"></i> <?= htmlspecialchars($_SESSION['user']['username']) ?> | 
            <a href="../../auth/logout.php" class="text-danger fw-bold text-decoration-none ms-2"><i class="bi bi-power"></i> Log Keluar</a>
        </span>
    </div>
</nav>

<div class="container-fluid pb-5">
  <div class="row">
    <?php include 'includes/sidebar.php'; ?>
    <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
        
        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
            <div>
                <h2 class="fw-bold mb-0">Master Data Mahasiswa</h2>
                <p class="text-muted small">Manajemen data induktif lengkap sesuai standar pelaporan DIKTI.</p>
            </div>
            <button class="btn btn-primary d-flex align-items-center gap-2 shadow-sm px-4 rounded-pill" data-bs-toggle="modal" data-bs-target="#mhsModal" onclick="resetForm()">
                <i class="bi bi-plus-lg fs-5"></i> <span class="fw-bold">Input Mahasiswa Baru</span>
            </button>
        </div>

        <?php if ($success): ?>
            <script>Swal.fire({ icon: 'success', title: 'Berhasil', text: '<?= $success ?>', timer: 2500, showConfirmButton: false });</script>
        <?php endif; ?>
        <?php if ($error): ?>
            <script>Swal.fire({ icon: 'error', title: 'Gagal', text: '<?= $error ?>' });</script>
        <?php endif; ?>

        <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 datatable-mhs">
                        <thead class="bg-light">
                            <tr>
                                <th class="ps-4">Identitas Mahasiswa</th>
                                <th>Kontak & Alamat</th>
                                <th>Prodi & Jalur</th>
                                <th>Status Akademik</th>
                                <th class="text-center">Aksi Operasional</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($mahasiswa as $m): ?>
                            <tr>
                                <td class="ps-4 py-3">
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="avatar bg-primary-subtle text-primary rounded-circle d-flex align-items-center justify-content-center fw-bold" style="width: 40px; height: 40px;">
                                            <?= substr($m['nama'], 0, 1) ?>
                                        </div>
                                        <div>
                                            <div class="fw-bold text-dark"><?= htmlspecialchars($m['nama']) ?></div>
                                            <div class="small text-primary fw-medium"><?= htmlspecialchars($m['nim']) ?></div>
                                            <div class="small text-muted">NIK: <?= htmlspecialchars($m['nik']) ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="small"><i class="bi bi-envelope-at me-1"></i><?= htmlspecialchars($m['email'] ?: '-') ?></div>
                                    <div class="small"><i class="bi bi-whatsapp me-1"></i><?= htmlspecialchars($m['no_hp'] ?: '-') ?></div>
                                    <div class="small text-muted text-truncate" style="max-width: 200px;"><i class="bi bi-geo-alt me-1"></i><?= htmlspecialchars($m['alamat'] ?: '-') ?></div>
                                </td>
                                <td>
                                    <div class="badge bg-info-subtle text-info border border-info-subtle rounded-pill mb-1"><?= htmlspecialchars($m['nama_prodi']) ?></div>
                                    <div class="small text-muted">Jalur: <?= htmlspecialchars($m['jalur_masuk'] ?: '-') ?></div>
                                </td>
                                <td>
                                    <?php
                                    $status_cls = match($m['status_kuliah']) {
                                        'Aktif' => 'bg-success',
                                        'Cuti' => 'bg-warning text-dark',
                                        'Lulus' => 'bg-primary',
                                        'Keluar' => 'bg-danger',
                                        default => 'bg-secondary'
                                    };
                                    ?>
                                    <span class="badge rounded-pill <?= $status_cls ?> px-3"><?= $m['status_kuliah'] ?></span>
                                </td>
                                <td class="text-center pe-4">
                                    <div class="dropdown">
                                        <button class="btn btn-light btn-sm rounded-circle border shadow-sm" data-bs-toggle="dropdown"><i class="bi bi-three-dots-vertical"></i></button>
                                        <ul class="dropdown-menu shadow-lg border-0 rounded-3">
                                            <li><a class="dropdown-item py-2" href="javascript:void(0)" onclick='editData(<?= json_encode($m) ?>)'><i class="bi bi-pencil-square text-info me-2"></i> Edit Data Lengkap</a></li>
                                            <li><a class="dropdown-item py-2" href="cetak_biodata.php?id=<?= $m['id_mhs'] ?>" target="_blank"><i class="bi bi-printer text-secondary me-2"></i> Cetak Biodata</a></li>
                                            <li><hr class="dropdown-divider"></li>
                                            <li><a class="dropdown-item py-2 text-danger" href="javascript:void(0)" onclick="confirmDelete(<?= $m['id_mhs'] ?>, '<?= htmlspecialchars($m['nama'], ENT_QUOTES) ?>')"><i class="bi bi-trash3 me-2"></i> Hapus Mahasiswa</a></li>
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>
  </div>
</div>

<!-- Modal Super-CRUD -->
<div class="modal fade" id="mhsModal" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <form method="post" class="modal-content border-0 shadow-lg rounded-4">
            <?= csrf_input() ?>
            <div class="modal-header border-bottom-0 pb-0">
                <h5 class="modal-title fw-bold fs-4" id="modalTitle">Tambah Mahasiswa</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body pt-2">
                <input type="hidden" name="action" id="formAction" value="create">
                <input type="hidden" name="id_mhs" id="formIdMhs">

                <!-- Tab Navigation -->
                <ul class="nav nav-tabs nav-tabs-custom mb-4" role="tablist">
                    <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-pribadi" type="button">1. Identitas Pribadi</button></li>
                    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-alamat" type="button">2. Alamat & Kontak</button></li>
                    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-ortu" type="button">3. Orang Tua / Wali</button></li>
                    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-akademik" type="button">4. Data Akademik</button></li>
                </ul>

                <div class="tab-content pt-2">
                    <!-- Tab 1: Identitas Pribadi -->
                    <div class="tab-pane fade show active" id="tab-pribadi">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label-sm">NIM (Digital Identity)</label>
                                <input type="text" name="nim" id="formNim" class="form-control" placeholder="Generate Otomatis dari PMB" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label-sm">NIK (Sesuai KTP)</label>
                                <input type="text" name="nik" id="formNik" class="form-control" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label-sm">NISN</label>
                                <input type="text" name="nisn" id="formNisn" class="form-control">
                            </div>
                            <div class="col-md-12">
                                <label class="form-label-sm">Nama Lengkap Mahasiswa</label>
                                <input type="text" name="nama" id="formNama" class="form-control fw-bold" required>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label-sm text-info"><i class="bi bi-patch-check me-1"></i>Tautan Kartu KIP / KKS / PKH (Opsional)</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-info-subtle border-info-subtle"><i class="bi bi-link-45deg"></i></span>
                                    <input type="text" name="berkas_kip_pkh" id="formKip" class="form-control border-info-subtle" placeholder="Alamat file berkas bantuan sosial">
                                    <button class="btn btn-outline-info" type="button" onclick="viewKip()"><i class="bi bi-eye"></i> Lihat</button>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label-sm">Tempat Lahir</label>
                                <input type="text" name="tempat_lahir" id="formTempatLahir" class="form-control">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label-sm">Tanggal Lahir</label>
                                <input type="date" name="tgl_lahir" id="formTglLahir" class="form-control">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label-sm">Jenis Kelamin</label>
                                <select name="jk" id="formJk" class="form-select">
                                    <option value="L">Laki-laki</option>
                                    <option value="P">Perempuan</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label-sm">Agama</label>
                                <select name="agama" id="formAgama" class="form-select">
                                    <?php foreach(['Islam','Kristen','Katolik','Hindu','Buddha','Konghucu'] as $ag): ?>
                                    <option value="<?= $ag ?>"><?= $ag ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Tab 2: Alamat & Kontak -->
                    <div class="tab-pane fade" id="tab-alamat">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label-sm">Nomor WhatsApp / HP Aktif</label>
                                <input type="text" name="no_hp" id="formNoHp" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label-sm">Email Aktif</label>
                                <input type="email" name="email" id="formEmail" class="form-control">
                            </div>
                            <div class="col-md-12">
                                <label class="form-label-sm">Alamat Tinggal Lengkap (Jalan/Dusun)</label>
                                <input type="text" name="alamat" id="formAlamat" class="form-control">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label-sm">RT / RW</label>
                                <div class="input-group">
                                    <input type="text" name="rt" id="formRt" class="form-control" placeholder="RT">
                                    <input type="text" name="rw" id="formRw" class="form-control" placeholder="RW">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label-sm">Kelurahan</label>
                                <input type="text" name="kelurahan" id="formKel" class="form-control">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label-sm">Kecamatan</label>
                                <input type="text" name="kecamatan" id="formKec" class="form-control">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label-sm">Kode Pos</label>
                                <input type="text" name="kode_pos" id="formKodePos" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label-sm">Jenis Tinggal</label>
                                <select name="jenis_tinggal" id="formJenisTinggal" class="form-select">
                                    <option value="Bersama Orang Tua">Bersama Orang Tua</option>
                                    <option value="Kos/Kontrak">Kos / Kontrak</option>
                                    <option value="Asrama">Asrama</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label-sm">Alat Transportasi</label>
                                <input type="text" name="alat_transportasi" id="formTransport" class="form-control">
                            </div>
                        </div>
                    </div>

                    <!-- Tab 3: Orang Tua -->
                    <div class="tab-pane fade" id="tab-ortu">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label-sm">Nama Ayah Kandung</label>
                                <input type="text" name="nama_ayah" id="formNamaAyah" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label-sm">NIK Ayah</label>
                                <input type="text" name="nik_ayah" id="formNikAyah" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label-sm">Pekerjaan Ayah</label>
                                <input type="text" name="pekerjaan_ayah" id="formKerjaAyah" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label-sm">Pendidikan Terakhir Ayah</label>
                                <select name="pendidikan_ayah" id="formPendAyah" class="form-select">
                                    <option value="">-</option>
                                    <?php foreach(['SD','SMP','SMA','D3','S1','S2','S3','Tidak Sekolah'] as $pnd): ?>
                                    <option value="<?= $pnd ?>"><?= $pnd ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <hr class="my-2">
                            <div class="col-md-6">
                                <label class="form-label-sm">Nama Ibu Kandung</label>
                                <input type="text" name="nama_ibu" id="formNamaIbu" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label-sm">NIK Ibu</label>
                                <input type="text" name="nik_ibu" id="formNikIbu" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label-sm">Pekerjaan Ibu</label>
                                <input type="text" name="pekerjaan_ibu" id="formKerjaIbu" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label-sm">Penghasilan Orang Tua</label>
                                <input type="text" name="penghasilan_ortu" id="formGajiOrtu" class="form-control">
                            </div>
                        </div>
                    </div>

                    <!-- Tab 4: Akademik -->
                    <div class="tab-pane fade" id="tab-akademik">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label-sm">Program Studi</label>
                                <select name="id_prodi" id="formProdi" class="form-select border-primary" required>
                                    <option value="">-- Pilih Prodi --</option>
                                    <?php foreach ($prodi as $p): ?>
                                    <option value="<?= $p['id_prodi'] ?>"><?= htmlspecialchars($p['nama_prodi']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label-sm">Jalur Masuk</label>
                                <input type="text" name="jalur_masuk" id="formJalur" class="form-control">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label-sm">Asal Sekolah</label>
                                <input type="text" name="asal_sekolah" id="formAsalSekolah" class="form-control">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label-sm">Tahun Lulus</label>
                                <input type="text" name="tahun_lulus" id="formThnLulus" class="form-control">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label-sm">Tahun Masuk Kampus</label>
                                <input type="text" name="tahun_masuk" id="formThnMasuk" class="form-control" value="<?= date('Y') ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label-sm">Status Perkuliahan</label>
                                <select name="status_kuliah" id="formStatus" class="form-select fw-bold text-primary">
                                    <option value="Aktif">Aktif</option>
                                    <option value="Cuti">Cuti</option>
                                    <option value="Lulus">Lulus</option>
                                    <option value="Keluar">Keluar</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-top-0 pt-0 pb-4 pe-4">
                <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Batalkan</button>
                <button type="submit" class="btn btn-primary rounded-pill px-5 shadow fw-bold">Simpan Seluruh Data Mahasiswa</button>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
$(document).ready(function() {
    $('.datatable-mhs').DataTable({
        language: { url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/id.json' },
        pageLength: 25
    });
});

function resetForm() {
    document.getElementById('modalTitle').innerText = 'Input Data Induk Mahasiswa Baru';
    document.getElementById('formAction').value = 'create';
    document.getElementById('formIdMhs').value = '';
    
    // Reset all inputs
    const frm = document.getElementById('mhsModal').querySelector('form');
    frm.reset();
    document.getElementById('formThnMasuk').value = new Date().getFullYear();
    
    // Reset to first tab
    bootstrap.Tab.getInstance(document.querySelector('.nav-tabs .active')).show();
}

function editData(d) {
    document.getElementById('modalTitle').innerText = 'Sunting Data ' + d.nama;
    document.getElementById('formAction').value = 'update';
    document.getElementById('formIdMhs').value = d.id_mhs;
    
    // Mapping Data (Super-CRUD)
    const map = {
        'formNim': d.nim, 'formNik': d.nik, 'formNisn': d.nisn, 'formNama': d.nama,
        'formTempatLahir': d.tempat_lahir, 'formTglLahir': d.tgl_lahir, 'formJk': d.jk, 'formAgama': d.agama,
        'formNoHp': d.no_hp, 'formEmail': d.email, 'formAlamat': d.alamat, 'formRt': d.rt, 'formRw': d.rw,
        'formKel': d.kelurahan, 'formKec': d.kecamatan, 'formKodePos': d.kode_pos, 
        'formJenisTinggal': d.jenis_tinggal, 'formTransport': d.alat_transportasi,
        'formNamaAyah': d.nama_ayah, 'formNikAyah': d.nik_ayah, 'formKerjaAyah': d.pekerjaan_ayah, 'formPendAyah': d.pendidikan_ayah,
        'formNamaIbu': d.nama_ibu, 'formNikIbu': d.nik_ibu, 'formKerjaIbu': d.pekerjaan_ibu, 'formGajiOrtu': d.penghasilan_ortu,
        'formProdi': d.id_prodi, 'formJalur': d.jalur_masuk, 'formAsalSekolah': d.asal_sekolah, 'formThnLulus': d.tahun_lulus,
        'formThnMasuk': d.tahun_masuk, 'formStatus': d.status_kuliah, 'formKip': d.berkas_kip_pkh
    };

    for (let id in map) {
        let el = document.getElementById(id);
        if(el) el.value = map[id] || '';
    }

    new bootstrap.Modal(document.getElementById('mhsModal')).show();
}

function viewKip() {
    const val = document.getElementById('formKip').value;
    if (val) {
        window.open('../../' + val, '_blank');
    } else {
        alert('Belum ada berkas KIP/PKH yang diunggah.');
    }
}

function confirmDelete(id, nama) {
    Swal.fire({
        title: 'Hapus Mahasiswa?',
        text: "Anda akan menghapus " + nama + " beserta akun loginnya secara permanen!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Ya, Hapus Permanen!',
        cancelButtonText: 'Batalkan'
    }).then((result) => {
        if (result.isConfirmed) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '';
            
            const actionInp = document.createElement('input');
            actionInp.type = 'hidden';
            actionInp.name = 'action';
            actionInp.value = 'delete';
            form.appendChild(actionInp);
            
            const idInp = document.createElement('input');
            idInp.type = 'hidden';
            idInp.name = 'id_mhs';
            idInp.value = id;
            form.appendChild(idInp);

            // CSRF Token (assuming CSRF inclusion is handled by includes/csrf.php and printed via csrf_input() usually, but here we can grab it if needed or just use the one in the modal form)
            // Since we're creating a form dynamically, let's grab the token from the existing modal form
            const token = document.querySelector('input[name="csrf_token"]');
            if (token) {
                const csrfInp = document.createElement('input');
                csrfInp.type = 'hidden';
                csrfInp.name = 'csrf_token';
                csrfInp.value = token.value;
                form.appendChild(csrfInp);
            }
            
            document.body.appendChild(form);
            form.submit();
        }
    });
}
</script>
</body>
</html>

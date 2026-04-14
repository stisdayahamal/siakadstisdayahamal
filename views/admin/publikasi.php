<?php
// views/admin/publikasi.php
require_once '../../middleware/auth.php';
require_once '../../config/db.php';
$user = $_SESSION['user'];

$sukses = $_SESSION['sukses'] ?? '';
$error = $_SESSION['error'] ?? '';
unset($_SESSION['sukses'], $_SESSION['error']);

// Create directory if not exists
$upload_dir = '../../public/uploads/publikasi/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Kategori
    if (isset($_POST['tambah_kategori'])) {
        $pdo->prepare("INSERT INTO kategori_publikasi (nama_kategori) VALUES (?)")->execute([trim($_POST['nama_kategori'])]);
        $_SESSION['sukses'] = "Kategori berhasil ditambahkan.";
        header("Location: publikasi.php"); exit;
    }
    if (isset($_POST['hapus_kategori'])) {
        $pdo->prepare("DELETE FROM kategori_publikasi WHERE id_kategori = ?")->execute([$_POST['id_kategori']]);
        $_SESSION['sukses'] = "Kategori dihapus.";
        header("Location: publikasi.php"); exit;
    }

    // Artikel
    if (isset($_POST['tambah_artikel'])) {
        $judul = trim($_POST['judul']);
        $tipe = $_POST['tipe'];
        $id_kategori = $_POST['id_kategori'] ?: null;
        $isi = trim($_POST['isi']);
        $penulis = $user['nama'];
        
        $gambar_path = null;
        if (!empty($_FILES['gambar']['name'])) {
            $ext = pathinfo($_FILES['gambar']['name'], PATHINFO_EXTENSION);
            $nama_file = uniqid('pub_') . '.' . $ext;
            if (move_uploaded_file($_FILES['gambar']['tmp_name'], $upload_dir . $nama_file)) {
                $gambar_path = $nama_file;
            }
        }

        $pdo->prepare("INSERT INTO artikel_publikasi (id_kategori, tipe, judul, isi, gambar, penulis) VALUES (?, ?, ?, ?, ?, ?)")
            ->execute([$id_kategori, $tipe, $judul, $isi, $gambar_path, $penulis]);
            
        $_SESSION['sukses'] = "Pos/Artikel $tipe berhasil dipublikasikan.";
        header("Location: publikasi.php"); exit;
    }

    if (isset($_POST['hapus_artikel'])) {
        $id = $_POST['id_artikel'];
        $img = $pdo->query("SELECT gambar FROM artikel_publikasi WHERE id_artikel = $id")->fetchColumn();
        if ($img && file_exists($upload_dir . $img)) { unlink($upload_dir . $img); }
        $pdo->prepare("DELETE FROM artikel_publikasi WHERE id_artikel = ?")->execute([$id]);
        $_SESSION['sukses'] = "Artikel sukses dihapus.";
        header("Location: publikasi.php"); exit;
    }
}

$kategori = $pdo->query("SELECT * FROM kategori_publikasi ORDER BY nama_kategori ASC")->fetchAll();
$artikel = $pdo->query("SELECT a.*, k.nama_kategori FROM artikel_publikasi a LEFT JOIN kategori_publikasi k ON a.id_kategori = k.id_kategori ORDER BY a.created_at DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta charset="UTF-8">
    <title>Manajemen Publikasi Web - ERP Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.ckeditor.com/ckeditor5/39.0.1/classic/ckeditor.js"></script>
    <style>.ck-editor__editable { min-height: 250px; }</style>
    <!-- Global UI Perfection -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../public/css/style.css">
</head>
<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom sticky-top shadow-sm px-3">
    <a class="navbar-brand fw-bold text-primary" href="dashboard.php"><i class="bi bi-briefcase-fill me-2"></i><?= htmlspecialchars($sys['nama_kampus']) ?></a>
</nav>

<div class="container-fluid">
    <div class="row">
        <?php include 'includes/sidebar.php'; ?>

        <main class="col-md-10 ms-sm-auto px-md-4 py-4">
            <h2 class="fw-bold mb-4">CMS Portal Informasi</h2>

            <?php if($sukses): ?><script>Swal.fire({icon:'success',title:'Sukses',text:'<?= addslashes($sukses) ?>'});</script><?php endif; ?>
            <?php if($error): ?><script>Swal.fire({icon:'error',title:'Gagal',text:'<?= addslashes($error) ?>'});</script><?php endif; ?>

            <ul class="nav nav-pills mb-4 gap-2 border-bottom pb-3" id="pubTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active rounded-pill fw-bold" data-bs-toggle="pill" data-bs-target="#tab-artikel"><i class="bi bi-file-earmark-richtext me-2"></i>Artikel & Pos</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link rounded-pill fw-bold text-secondary" data-bs-toggle="pill" data-bs-target="#tab-kategori"><i class="bi bi-tags me-2"></i>Kategori</button>
                </li>
            </ul>

            <div class="tab-content">
                <!-- Tab Artikel -->
                <div class="tab-pane fade show active" id="tab-artikel">
                    <div class="card border-0 shadow-sm rounded-4 mb-4">
                        <div class="card-header bg-white border-0 pt-4 pb-0 d-flex justify-content-between align-items-center">
                            <h5 class="fw-bold text-primary mb-0"><i class="bi bi-pencil-square me-2"></i>Tulis Pos Baru</h5>
                            <button class="btn btn-sm btn-outline-primary" data-bs-toggle="collapse" data-bs-target="#formTulis">Form Editor <i class="bi bi-chevron-down"></i></button>
                        </div>
                        <div class="card-body collapse show" id="formTulis">
                            <form method="post" enctype="multipart/form-data">
                                <div class="row g-3 mb-3">
                                    <div class="col-md-8">
                                        <label class="form-label fw-bold">Judul Postingan</label>
                                        <input type="text" name="judul" class="form-control" placeholder="Tulis judul konten..." required>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-bold">Tipe Konten</label>
                                        <select name="tipe" class="form-select" required>
                                            <option value="Berita">Berita Kampus</option>
                                            <option value="Pengumuman">Pengumuman Resmi</option>
                                            <option value="Galeri">Galeri Visual</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold">Kategori (Opsional)</label>
                                        <select name="id_kategori" class="form-select">
                                            <option value="">-- Tanpa Kategori --</option>
                                            <?php foreach($kategori as $k): ?>
                                            <option value="<?= $k['id_kategori'] ?>"><?= htmlspecialchars($k['nama_kategori']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold">Gambar Cover/Banner (Opsional)</label>
                                        <input type="file" name="gambar" class="form-control" accept="image/*">
                                    </div>
                                </div>
                                <div class="mb-4">
                                    <label class="form-label fw-bold">Isi Konten Publikasi</label>
                                    <textarea name="isi" id="editor" rows="10" placeholder="Tulis isi di sini..."></textarea>
                                </div>
                                <button type="submit" name="tambah_artikel" class="btn btn-primary px-5 fw-bold"><i class="bi bi-send-fill me-2"></i>Publish Sekarang</button>
                            </form>
                        </div>
                    </div>

                    <div class="card border-0 shadow-sm rounded-4">
                        <div class="card-header bg-white border-0 pt-4 pb-2">
                            <h5 class="fw-bold mb-0"><i class="bi bi-list-columns-reverse text-dark me-2"></i>Daftar Publikasi Tersimpan</h5>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0 datatable">
                                    <thead class="table-light">
                                        <tr>
                                            <th class="ps-4" width="10%">Cover</th>
                                            <th width="40%">Judul</th>
                                            <th>Tipe & Kategori</th>
                                            <th>Penulis / Waktu</th>
                                            <th>Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach($artikel as $a): ?>
                                        <tr>
                                            <td class="ps-4">
                                                <?php if($a['gambar']): ?>
                                                    <img src="../../public/uploads/publikasi/<?= $a['gambar'] ?>" class="rounded shadow-sm" style="width: 60px; height: 40px; object-fit: cover;">
                                                <?php else: ?>
                                                    <div class="rounded bg-light text-muted d-flex align-items-center justify-content-center" style="width: 60px; height: 40px;"><i class="bi bi-image"></i></div>
                                                <?php endif; ?>
                                            </td>
                                            <td><strong class="text-primary d-block"><?= htmlspecialchars($a['judul']) ?></strong></td>
                                            <td>
                                                <span class="badge bg-secondary"><?= $a['tipe'] ?></span>
                                                <small class="d-block text-muted"><?= htmlspecialchars($a['nama_kategori'] ?? 'Uncategorized') ?></small>
                                            </td>
                                            <td>
                                                <small class="d-block fw-bold"><i class="bi bi-person me-1"></i><?= htmlspecialchars($a['penulis']) ?></small>
                                                <small class="text-muted"><?= date('d M Y', strtotime($a['created_at'])) ?></small>
                                            </td>
                                            <td>
                                                <form method="post" onsubmit="return confirm('Hapus permanen postingan ini?');">
                                                    <input type="hidden" name="id_artikel" value="<?= $a['id_artikel'] ?>">
                                                    <button type="submit" name="hapus_artikel" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                                </form>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tab Kategori -->
                <div class="tab-pane fade" id="tab-kategori">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card border-0 shadow-sm rounded-4 h-100">
                                <div class="card-header bg-white border-0 pt-4 pb-2">
                                    <h5 class="fw-bold mb-0 text-success"><i class="bi bi-tags me-2"></i>Kelola Kategori</h5>
                                </div>
                                <div class="card-body">
                                    <form method="post" class="d-flex gap-2 mb-4">
                                        <input type="text" name="nama_kategori" class="form-control" placeholder="Cth: Beasiswa" required>
                                        <button type="submit" name="tambah_kategori" class="btn btn-success fw-bold">Tambah</button>
                                    </form>
                                    <ul class="list-group list-group-flush border-top">
                                        <?php foreach($kategori as $k): ?>
                                        <li class="list-group-item d-flex justify-content-between align-items-center bg-transparent px-0 py-2">
                                            <span class="fw-bold text-dark"><i class="bi bi-caret-right-fill text-success me-2"></i><?= htmlspecialchars($k['nama_kategori']) ?></span>
                                            <form method="post" onsubmit="return confirm('Hapus kategori ini?');">
                                                <input type="hidden" name="id_kategori" value="<?= $k['id_kategori'] ?>">
                                                <button type="submit" name="hapus_kategori" class="btn btn-sm text-danger border-0"><i class="bi bi-trash"></i></button>
                                            </form>
                                        </li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </main>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
$(document).ready(function() {
    $('.datatable').DataTable({ language: { search: "Cari Artikel:", lengthMenu: "Tampil _MENU_ data" }, order: [[3, "desc"]]});
});
ClassicEditor.create(document.querySelector('#editor'), {
    toolbar: ['heading', '|', 'bold', 'italic', 'link', 'bulletedList', 'numberedList', 'blockQuote', '|', 'undo', 'redo']
}).catch(error => { console.error(error); });
</script>
</body>
</html>

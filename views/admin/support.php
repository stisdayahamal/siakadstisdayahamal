<?php
// views/admin/support.php
require_once '../../middleware/auth.php';
require_once '../../config/db.php';
$user = $_SESSION['user'];
$user_id = $user['id'];

$sukses = $_SESSION['sukses'] ?? '';
unset($_SESSION['sukses']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['buat_tiket'])) {
        $subjek = $_POST['subjek'];
        $pesan = trim($_POST['pesan']);
        
        $pdo->prepare("INSERT INTO support_ticket (user_id, subjek, pesan) VALUES (?, ?, ?)")
            ->execute([$user_id, $subjek, $pesan]);
            
        $pdo->prepare("INSERT INTO sistem_log_aktivitas (user_id, aksi, entitas) VALUES (?, ?, ?)")
            ->execute([$user_id, 'CREATE', 'Support Ticket']);

        $_SESSION['sukses'] = "Tiket bantuan berhasil dikirimkan.";
        header("Location: support.php"); exit;
    }
    
    if (isset($_POST['balas_tiket']) && ($user['role'] === 'admin' || $user['role'] === 'superadmin')) {
        $id = $_POST['id_tiket'];
        $balasan = trim($_POST['balasan']);
        $status = $_POST['status']; 
        
        $pdo->prepare("UPDATE support_ticket SET balasan = ?, status = ? WHERE id = ?")->execute([$balasan, $status, $id]);
        
        $pdo->prepare("INSERT INTO sistem_log_aktivitas (user_id, aksi, entitas) VALUES (?, ?, ?)")
            ->execute([$user_id, 'UPDATE', "Balasan Status Tiket #$id"]);

        // Opsional: Buat Notifikasi ke penanya
        $penanya = $pdo->query("SELECT user_id FROM support_ticket WHERE id = $id")->fetchColumn();
        if ($penanya) {
            $pdo->prepare("INSERT INTO notifikasi (user_id, judul, pesan, link) VALUES (?, ?, ?, '#')")
                ->execute([$penanya, "Update Tiket #$id", "Tiket Anda telah diperbarui oleh Admin menjadi: $status", "support.php"]);
        }

        $_SESSION['sukses'] = "Tiket berhasil dibalas/diperbarui.";
        header("Location: support.php"); exit;
    }
}

// Tiket buatan sendiri
$tiket_saya = $pdo->prepare("SELECT * FROM support_ticket WHERE user_id = ? ORDER BY created_at DESC");
$tiket_saya->execute([$user_id]);
$tiket_saya = $tiket_saya->fetchAll();

// Tiket global untuk di-solve oleh admin
$semua_tiket = [];
if ($user['role'] === 'admin') {
    $semua_tiket = $pdo->query("SELECT t.*, u.nama, u.role FROM support_ticket t JOIN users u ON t.user_id = u.id_user ORDER BY t.status DESC, t.created_at DESC")->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta charset="UTF-8">
    <title>IT Support Ticket - ERP Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>.chat-box { background: #f8f9fa; border-radius: 12px; padding: 15px; margin-bottom:10px; border-left: 4px solid #0d6efd; }</style>
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
            <h2 class="fw-bold mb-4">Pusat Bantuan (IT Support)</h2>
            <?php if($sukses): ?><script>Swal.fire({icon:'success',title:'Sukses',text:'<?= addslashes($sukses) ?>'});</script><?php endif; ?>

            <div class="row g-4 mb-4">
                <!-- Form Buat Tiket -->
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm rounded-4 h-100">
                        <div class="card-header bg-white border-0 pt-4 pb-2">
                            <h5 class="fw-bold mb-0"><i class="bi bi-envelope-plus text-primary me-2"></i>Kirim Pertanyaan / Keluhan</h5>
                        </div>
                        <div class="card-body">
                            <form method="post">
                                <div class="mb-3">
                                    <label class="form-label small fw-bold">Subjek Masalah</label>
                                    <input type="text" name="subjek" class="form-control" required placeholder="Contoh: Aplikasi Error saat Absen">
                                </div>
                                <div class="mb-4">
                                    <label class="form-label small fw-bold">Penjelasan Detail</label>
                                    <textarea name="pesan" class="form-control" rows="5" required placeholder="Jelaskan kendala Anda selengkap mungkin..."></textarea>
                                </div>
                                <button type="submit" name="buat_tiket" class="btn btn-primary w-100 py-2 fw-bold"><i class="bi bi-send me-2"></i>Kirim Tiket Baru</button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Daftar Tiket -->
                <div class="col-md-8">
                    <?php if($user['role'] === 'admin'): ?>
                    <ul class="nav nav-pills mb-3 gap-2" id="ticketTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active rounded-pill fw-bold" data-bs-toggle="pill" data-bs-target="#inbox" type="button">Kotak Masuk Publik</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link rounded-pill fw-bold text-secondary" data-bs-toggle="pill" data-bs-target="#sent" type="button">Tiket Pribadi Saya</button>
                        </li>
                    </ul>

                    <div class="tab-content" id="myTabContent">
                        <!-- Semua Tiket Publik -->
                        <div class="tab-pane fade show active" id="inbox" role="tabpanel">
                            <div class="card border-0 shadow-sm rounded-4">
                                <div class="card-body p-0">
                                    <div class="table-responsive">
                                        <table class="table table-hover align-middle mb-0">
                                            <thead class="table-light">
                                                <tr>
                                                    <th class="ps-4">Tiket</th>
                                                    <th>Dari</th>
                                                    <th>Status</th>
                                                    <th>Aksi</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach($semua_tiket as $t): ?>
                                                <tr>
                                                    <td class="ps-4">
                                                        <strong class="d-block text-truncate" style="max-width: 200px;"><?= htmlspecialchars($t['subjek']) ?></strong>
                                                        <small class="text-muted"><?= date('d M Y, H:i', strtotime($t['created_at'])) ?></small>
                                                    </td>
                                                    <td><?= htmlspecialchars($t['nama']) ?> <br><small class="text-muted badge bg-secondary"><?= htmlspecialchars($t['role']) ?></small></td>
                                                    <td>
                                                        <?php 
                                                            $badge = $t['status'] == 'Open' ? 'danger' : ($t['status'] == 'In Progress' ? 'warning' : 'success'); 
                                                        ?>
                                                        <span class="badge bg-<?= $badge ?> rounded-pill px-3"><?= $t['status'] ?></span>
                                                    </td>
                                                    <td>
                                                        <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalBalas<?= $t['id'] ?>">Balas / Update</button>
                                                    </td>
                                                </tr>
                                                
                                                <!-- Modal Balas -->
                                                <div class="modal fade" id="modalBalas<?= $t['id'] ?>" tabindex="-1">
                                                    <div class="modal-dialog modal-dialog-centered modal-lg">
                                                        <div class="modal-content border-0 shadow">
                                                            <div class="modal-header border-0 bg-light">
                                                                <h5 class="modal-title fw-bold">Detail Tiket #<?= $t['id'] ?></h5>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                            </div>
                                                            <form method="post">
                                                                <div class="modal-body p-4">
                                                                    <div class="d-flex justify-content-between mb-2">
                                                                        <span class="fw-bold"><i class="bi bi-person-circle me-1"></i> <?= htmlspecialchars($t['nama']) ?></span>
                                                                        <span class="text-muted small"><?= date('d M Y, H:i', strtotime($t['created_at'])) ?></span>
                                                                    </div>
                                                                    <h6 class="fw-bold text-dark mb-3"><?= htmlspecialchars($t['subjek']) ?></h6>
                                                                    <div class="chat-box text-dark mb-4"><?= nl2br(htmlspecialchars($t['pesan'])) ?></div>
                                                                    
                                                                    <hr>
                                                                    <input type="hidden" name="id_tiket" value="<?= $t['id'] ?>">
                                                                    <div class="mb-3">
                                                                        <label class="form-label fw-bold"><i class="bi bi-reply-all me-1"></i>Balasan Anda (Admin Staff)</label>
                                                                        <textarea name="balasan" class="form-control bg-light border-0 px-3 py-3" rows="4" required placeholder="Tulis solusi atau balasan..."><?= htmlspecialchars($t['balasan'] ?? '') ?></textarea>
                                                                    </div>
                                                                    <div class="mb-3">
                                                                        <label class="form-label fw-bold">Set Status Tiket</label>
                                                                        <select name="status" class="form-select">
                                                                            <option value="Open" <?= $t['status'] == 'Open' ? 'selected' : '' ?>>Open (Belum Selesai)</option>
                                                                            <option value="In Progress" <?= $t['status'] == 'In Progress' ? 'selected' : '' ?>>In Progress (Sedang Dikerjakan)</option>
                                                                            <option value="Closed" <?= $t['status'] == 'Closed' ? 'selected' : '' ?>>Closed (Terselesaikan)</option>
                                                                        </select>
                                                                    </div>
                                                                </div>
                                                                <div class="modal-footer border-0 bg-light">
                                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                                                                    <button type="submit" name="balas_tiket" class="btn btn-primary fw-bold px-4">Kirim Balasan</button>
                                                                </div>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>
                                                <?php endforeach; ?>
                                                <?php if(count($semua_tiket)===0): ?><tr><td colspan="4" class="text-center text-muted py-4">Kotak masuk bersih. Tidak ada tiket tersisa.</td></tr><?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Tiket Sendiri -->
                        <div class="tab-pane fade" id="sent" role="tabpanel">
                    <?php endif; ?>

                            <div class="card border-0 shadow-sm rounded-4 h-100">
                                <div class="card-header bg-white border-0 pt-4 pb-2">
                                    <h5 class="fw-bold mb-0"><i class="bi bi-ticket-detailed text-primary me-2"></i>Tiket yang Saya Buat</h5>
                                </div>
                                <div class="card-body p-0">
                                    <div class="table-responsive">
                                        <table class="table table-hover align-middle mb-0">
                                            <thead class="table-light">
                                                <tr>
                                                    <th class="ps-4">Subjek</th>
                                                    <th>Balasan Admin</th>
                                                    <th>Status</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if(count($tiket_saya) > 0): ?>
                                                    <?php foreach($tiket_saya as $t): ?>
                                                    <tr>
                                                        <td class="ps-4">
                                                            <strong class="d-block"><?= htmlspecialchars($t['subjek']) ?></strong>
                                                            <small class="text-muted d-block text-truncate py-1" style="max-width: 250px;"><?= htmlspecialchars($t['pesan']) ?></small>
                                                            <small class="text-primary"><?= date('d/m/Y', strtotime($t['created_at'])) ?></small>
                                                        </td>
                                                        <td>
                                                            <?php if($t['balasan']): ?>
                                                                <small class="text-success"><i class="bi bi-check2-all me-1"></i> <?= htmlspecialchars(substr($t['balasan'], 0, 50)) ?>...</small>
                                                            <?php else: ?>
                                                                <small class="text-muted fst-italic">Belum ada tanggapan.</small>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td>
                                                            <?php 
                                                                $badge = $t['status'] == 'Open' ? 'danger' : ($t['status'] == 'In Progress' ? 'warning text-dark' : 'success'); 
                                                            ?>
                                                            <span class="badge bg-<?= $badge ?> rounded-pill px-3"><?= $t['status'] ?></span>
                                                        </td>
                                                    </tr>
                                                    <?php endforeach; ?>
                                                <?php else: ?>
                                                    <tr><td colspan="3" class="text-center text-muted py-4">Belum ada tiket bantuan yang Anda kirim.</td></tr>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>

                    <?php if($user['role'] === 'admin'): ?>
                        </div> <!-- end tab sent -->
                    </div> <!-- end tab content -->
                    <?php endif; ?>
                </div>
            </div>

        </main>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
// views/admin/backup_db.php
require_once '../../includes/audit_log.php';
require_once '../../middleware/auth.php';
require_once '../../config/db.php';
if ($_SESSION['user']['role'] !== 'admin') {
    http_response_code(403);
    die('Akses ditolak. Hanya Admin Utama yang dapat melakukan backup.');
}
$date = date('Ymd_His');
$backup_dir = realpath(__DIR__ . '/../../backups');
$filename = $backup_dir . '/backup_' . $date . '.sql';

// Konfigurasi koneksi
$host = $host ?? 'localhost';
$dbname = $db ?? '';
$user = $user ?? '';
$pass = $pass ?? '';

// Perintah mysqldump
$command = "mysqldump --user=" . escapeshellarg($user) . " --password=" . escapeshellarg($pass) . " --host=" . escapeshellarg($host) . " --databases " . escapeshellarg($dbname) . " > " . escapeshellarg($filename);

$output = null;
$return_var = null;
exec($command, $output, $return_var);

if ($return_var === 0 && file_exists($filename)) {
    audit_log('backup_db', 'Backup file: ' . basename($filename));
    echo '<div style="padding:2em;font-family:sans-serif"><h2>Backup Berhasil</h2><p>File backup tersimpan: <b>' . htmlspecialchars(basename($filename)) . '</b></p></div>';
} else {
    audit_log('backup_db_gagal', 'Backup gagal');
    echo '<div style="padding:2em;font-family:sans-serif;color:red"><h2>Backup Gagal</h2><p>Periksa konfigurasi database dan hak akses folder backups/.</p></div>';
}

<?php
// tools/backup_before_wipe.php
define('ACCESS', true);
require_once __DIR__ . '/../includes/load_env.php';

$host = $_ENV['DB_HOST'] ?? 'localhost';
$db   = $_ENV['DB_NAME'] ?? 'siakadstisdayahamal';
$user = $_ENV['DB_USER'] ?? 'root';
$pass = $_ENV['DB_PASS'] ?? '';

if (!is_dir(__DIR__ . '/../backups')) {
    mkdir(__DIR__ . '/../backups');
}

$filename = __DIR__ . '/../backups/backup_before_wipe_' . date('Ymd_His') . '.sql';
$cmd = "mysqldump --host=$host --user=$user " . ($pass ? "--password=$pass " : "") . "$db > \"$filename\"";

echo "Running backup command: $cmd\n";
system($cmd, $retval);

if ($retval === 0) {
    echo "Backup successful: $filename\n";
} else {
    echo "Backup failed with code $retval\n";
}

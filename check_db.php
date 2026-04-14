<?php
define('ACCESS', true);
require_once 'config/db.php';
header('Content-Type: text/plain');
echo "--- PRODI ---\n";
$q = $pdo->query("DESCRIBE prodi");
foreach($q->fetchAll(PDO::FETCH_ASSOC) as $row) {
    echo $row['Field'] . " (" . $row['Type'] . ")\n";
}

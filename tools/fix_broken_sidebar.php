<?php
$files = [
    __DIR__ . '/../views/dosen/perwalian.php',
    __DIR__ . '/../views/mahasiswa/krs_input.php'
];

foreach ($files as $file) {
    if (!file_exists($file)) continue;
    $content = file_get_contents($file);
    
    // 1. Hapus kegagalan injeksi di dalam navbar
    $broken_injection = <<<HTML
<div class="container-fluid">
  <div class="row">
    <?php include 'includes/sidebar.php'; ?>
    <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 mt-2">
HTML;
    $content = str_replace($broken_injection, '<div class="container-fluid">', $content);
    
    // 2. Ganti container asli yang berada SETELAH navbar
    // Ganti class container atau container mt-4 asal bukan container-fluid navbar
    $wrapper = <<<HTML
<div class="container-fluid">
  <div class="row">
    <?php include 'includes/sidebar.php'; ?>
    <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 mt-3">
HTML;

    // Pakai regex yang hanya menarget container yang diikuti oleh karakter selain - (mengecualikan container-fluid)
    $content = preg_replace('/<div class="container(\s+mt-\d+)?">/', $wrapper, $content, 1);
    
    file_put_contents($file, $content);
    echo "Fixed: " . basename($file) . "\n";
}
?>

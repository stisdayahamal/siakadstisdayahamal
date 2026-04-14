<?php
function fixBrokenInjection($folder) {
    echo "Memeriksa folder $folder...\n";
    foreach (glob(__DIR__ . "/../views/$folder/*.php") as $file) {
        $content = file_get_contents($file);
        
        $broken_injection = <<<HTML
<div class="container-fluid">
  <div class="row">
    <?php include 'includes/sidebar.php'; ?>
    <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 mt-2">
HTML;

        // Cek apakah file ini mengalami kerusakan (injeksi sidebar menumpuk di dalam navbar)
        // Polanya adalah: <nav...><div class="container-fluid">\n  <div class="row">...
        if (strpos($content, $broken_injection) !== false && strpos($content, '<nav') !== false) {
            
            // 1. Pulihkan bagian dalam Navbar
            $content = str_replace($broken_injection, '<div class="container-fluid">', $content);
            
            // 2. Ganti container luar dengan wrapper sidebar
            $wrapper = <<<HTML
<div class="container-fluid">
  <div class="row">
    <?php include 'includes/sidebar.php'; ?>
    <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 mt-4">
HTML;
            $content = preg_replace('/<div class="container(\s+mt-\d+)?">/', $wrapper, $content, 1);
            
            file_put_contents($file, $content);
            echo "FIXED: " . basename($file) . "\n";
        }
    }
}

fixBrokenInjection('dosen');
fixBrokenInjection('mahasiswa');
echo "Selesai memperbaiki DOM!\n";
?>

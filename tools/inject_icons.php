<?php
// tools/inject_icons.php
$folders = ['admin', 'dosen', 'mahasiswa', 'pmb'];

$iconCDN = '    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">';

foreach ($folders as $folder) {
    if (!is_dir(__DIR__ . "/../views/$folder")) continue;
    
    foreach (glob(__DIR__ . "/../views/$folder/*.php") as $file) {
        $content = file_get_contents($file);
        
        // Jika belum ada bootstrap-icons di file ini
        if (strpos($content, 'bootstrap-icons') === false && strpos($content, '<head>') !== false) {
            
            // Sisipkan CDN di bawah tag <head> atau di atas </head>
            $content = str_replace('</head>', $iconCDN . "\n</head>", $content);
            file_put_contents($file, $content);
            echo "Injected icons.css into: $folder/" . basename($file) . "\n";
            
        }
    }
}
echo "Selesai Inject Icons!\n";
?>

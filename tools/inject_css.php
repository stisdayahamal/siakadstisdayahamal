<?php
// tools/inject_css.php
$font = "    <!-- Global UI Perfection -->\n    <link href=\"https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap\" rel=\"stylesheet\">\n    <link rel=\"stylesheet\" href=\"{PATH}public/css/style.css\">\n";

function inject($dir, $depth) {
    global $font;
    $path_prefix = str_repeat('../', $depth);
    $injection = str_replace('{PATH}', $path_prefix, $font);

    $files = glob($dir . '/*.php');
    foreach ($files as $file) {
        $content = file_get_contents($file);
        // Cek apakah belum ada Inter
        if (strpos($content, 'family=Inter') === false && strpos($content, '</head>') !== false) {
            $content = str_replace('</head>', $injection . "</head>", $content);
            file_put_contents($file, $content);
            echo "Injected: " . basename($dir) . "/" . basename($file) . "\n";
        }
    }
}

// Inject folder secara mendalam
$directories = [
    ['../views/admin', 2],
    ['../views/dosen', 2],
    ['../views/mahasiswa', 2],
    ['../views/pmb', 2],
    ['../auth', 1]
];

foreach ($directories as $d) {
    inject(__DIR__ . '/' . $d[0], $d[1]);
}

// Inject index.php login
$index_file = __DIR__ . '/../index.php';
if (file_exists($index_file)) {
    $content = file_get_contents($index_file);
    $injection = str_replace('{PATH}', './', $font);
    if (strpos($content, 'family=Inter') === false && strpos($content, '</head>') !== false) {
        file_put_contents($index_file, str_replace('</head>', $injection . "</head>", $content));
        echo "Injected: index.php\n";
    }
}
echo "Proses injeksi global selesai.\n";

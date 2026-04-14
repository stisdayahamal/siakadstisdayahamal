<?php
// tools/auto_audit.php
function scanDirectory($dir, &$results) {
    $files = scandir($dir);
    foreach ($files as $file) {
        if ($file === '.' || $file === '..') continue;
        if ($file === 'vendor' || $file === 'backups') continue;
        $path = $dir . DIRECTORY_SEPARATOR . $file;
        if (is_dir($path)) {
            scanDirectory($path, $results);
        } elseif (pathinfo($path, PATHINFO_EXTENSION) === 'php') {
            // Check Syntax
            $cmd = "php -l \"" . $path . "\" 2>&1";
            $lint = shell_exec($cmd);
            if (strpos($lint, 'No syntax errors') === false) {
                $results['syntax_errors'][] = "Syntax Error in $path: " . trim($lint);
            }
            
            $content = file_get_contents($path);
            
            // Check Missing require files
            preg_match_all("/(?:require|include)(?:_once)?\s+['\"]([^'\"]+)['\"]/i", $content, $matches);
            foreach ($matches[1] as $req) {
                // If it's a relative path, evaluate it
                if (substr($req, 0, 1) === '.') {
                    $req_path = realpath(dirname($path) . DIRECTORY_SEPARATOR . $req);
                    if ($req_path === false) {
                        $results['broken_includes'][] = "Broken Include in $path: $req";
                    }
                }
            }
            
            // Vulnerable direct $_POST/$_GET in queries
            if (preg_match('/\$(pdo|conn)->query\([^;]*\$_(POST|GET)/i', $content)) {
                $results['sql_injection'][] = "Possible SQL Injection in query() using POST/GET in $path";
            }
        }
    }
}

$results = [
    'syntax_errors' => [],
    'broken_includes' => [],
    'sql_injection' => []
];

scanDirectory(__DIR__ . '/../', $results);

echo "------------- AUDIT REPORT -------------\n";
echo "Syntax Errors: " . count($results['syntax_errors']) . "\n";
foreach($results['syntax_errors'] as $err) echo "- $err\n";

echo "\nBroken Includes: " . count($results['broken_includes']) . "\n";
foreach($results['broken_includes'] as $err) echo "- $err\n";

echo "\nSQL Injections (Possible): " . count($results['sql_injection']) . "\n";
foreach($results['sql_injection'] as $err) echo "- $err\n";

echo "----------------------------------------\n";
?>

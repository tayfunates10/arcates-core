<?php
$root = dirname(__DIR__);
$failures = [];
$required = ['README.md','CLAUDE.md','.gitignore','config.example.php','CHANGELOG.md','VERSION','musteriler.md'];
foreach ($required as $file) {
    if (!is_file($root . '/' . $file)) {
        $failures[] = "Eksik dosya: {$file}";
    }
}
if (trim((string) @file_get_contents($root . '/VERSION')) !== '0.1.0') {
    $failures[] = 'VERSION 0.1.0 olmalı.';
}
$ignore = (string) @file_get_contents($root . '/.gitignore');
foreach (['config.php','/uploads/*','/logs/*','.DS_Store'] as $pattern) {
    if (!str_contains($ignore, $pattern)) {
        $failures[] = ".gitignore eksik: {$pattern}";
    }
}
if ($failures) {
    fwrite(STDERR, implode(PHP_EOL, $failures) . PHP_EOL);
    exit(1);
}
echo "Arcates testleri: OK\n";

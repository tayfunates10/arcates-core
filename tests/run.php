<?php
declare(strict_types=1);
$root = dirname(__DIR__);
$failures = [];
$required = ['README.md','CLAUDE.md','.gitignore','config.example.php','CHANGELOG.md','VERSION','musteriler.md','bootstrap.php','public/index.php','schema.sql'];
foreach ($required as $file) { if (!is_file($root . '/' . $file)) { $failures[] = "Eksik dosya: {$file}"; } }
$ignore = (string)@file_get_contents($root . '/.gitignore');
foreach (['config.php','/uploads/*','/logs/*','.DS_Store'] as $pattern) { if (!str_contains($ignore, $pattern)) { $failures[] = ".gitignore eksik: {$pattern}"; } }
if ($failures) { fwrite(STDERR, implode(PHP_EOL, $failures) . PHP_EOL); exit(1); }
passthru(PHP_BINARY . ' ' . escapeshellarg(__DIR__ . '/core.php'), $code);
if ($code !== 0) { exit($code); }
echo "Arcates testleri: OK\n";

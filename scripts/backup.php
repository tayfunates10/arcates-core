<?php
declare(strict_types=1);

$configFile = dirname(__DIR__) . '/config.php';
if (!is_file($configFile)) { fwrite(STDERR, "config.php bulunamadı.\n"); exit(1); }
$config = require $configFile;
$root = dirname(__DIR__);
$backupDir = $root . '/backups';
if (!is_dir($backupDir) && !mkdir($backupDir, 0750, true) && !is_dir($backupDir)) { throw new RuntimeException('Backup dizini oluşturulamadı.'); }
$stamp = date('Ymd-His');
$db = $config['db'];
$sqlFile = $backupDir . "/db-{$stamp}.sql";
$uploadFile = $backupDir . "/uploads-{$stamp}.tar.gz";
$env = ['MYSQL_PWD' => (string)$db['pass']];
$dump = sprintf('mysqldump --single-transaction --quick --skip-lock-tables -h %s -P %d -u %s %s > %s', escapeshellarg((string)$db['host']), (int)($db['port'] ?? 3306), escapeshellarg((string)$db['user']), escapeshellarg((string)$db['name']), escapeshellarg($sqlFile));
$process = proc_open($dump, [1 => ['pipe','w'], 2 => ['pipe','w']], $pipes, $root, $env + $_ENV);
if (!is_resource($process) || proc_close($process) !== 0) { @unlink($sqlFile); throw new RuntimeException('Veritabanı yedeği alınamadı.'); }
$tar = sprintf('tar -czf %s -C %s uploads', escapeshellarg($uploadFile), escapeshellarg($root));
exec($tar, $out, $code);
if ($code !== 0) { throw new RuntimeException('Upload yedeği alınamadı.'); }
echo "Yedek tamamlandı: {$sqlFile} + {$uploadFile}\n";

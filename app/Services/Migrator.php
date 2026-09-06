<?php
declare(strict_types=1);
namespace Arcates\Services;
use Arcates\Core\Database;
final class Migrator
{
 public static function run(Database $db): array
 {
  $pdo=$db->pdo();$pdo->exec('CREATE TABLE IF NOT EXISTS schema_migrations (migration VARCHAR(190) PRIMARY KEY, applied_at DATETIME NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
  $applied=[];$files=glob(ARCATES_ROOT.'/db/migrations/*.sql')?:[];sort($files,SORT_STRING);
  foreach($files as $file){$name=basename($file);if($db->fetch('SELECT migration FROM schema_migrations WHERE migration=? LIMIT 1',[$name]))continue;$pdo->exec((string)file_get_contents($file));$db->execute('INSERT INTO schema_migrations(migration,applied_at) VALUES(?,NOW())',[$name]);$applied[]=$name;}
  return $applied;
 }
}

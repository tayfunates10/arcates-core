<?php
declare(strict_types=1);
require dirname(__DIR__).'/bootstrap.php';
use Arcates\Core\App;use Arcates\Services\Migrator;
$applied=Migrator::run(App::db());
echo $applied?"Uygulanan migrationlar:\n- ".implode("\n- ",$applied)."\n":"Yeni migration yok.\n";

<?php
declare(strict_types=1);
require dirname(__DIR__).'/bootstrap.php';
use Arcates\Core\App;
$days=max(1,(int)App::config('security.form_retention_days',180));
$cutoff=(new DateTimeImmutable())->modify('-'.$days.' days')->format('Y-m-d H:i:s');
$deleted=App::db()->execute('DELETE FROM contact_submissions WHERE created_at < ?',[$cutoff]);
echo "Silinen form kaydı: {$deleted}\n";

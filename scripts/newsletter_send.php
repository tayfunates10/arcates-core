<?php
declare(strict_types=1);
require dirname(__DIR__).'/bootstrap.php';
use Arcates\Core\App;use Arcates\Services\NewsletterService;
$limit=(int)($argv[1]??App::config('newsletter.send_batch',50));$result=(new NewsletterService())->sendBatch($limit);echo json_encode($result,JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT).PHP_EOL;

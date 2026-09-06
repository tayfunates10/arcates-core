<?php
declare(strict_types=1);
require dirname(__DIR__).'/bootstrap.php';
use Arcates\Services\EDocumentService;

try{$result=(new EDocumentService())->checkPending(50);echo json_encode($result,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR).PHP_EOL;exit(0);}catch(Throwable $e){fwrite(STDERR,'e-Belge durum hatası: '.$e->getMessage().PHP_EOL);exit(1);}

<?php
declare(strict_types=1);
require dirname(__DIR__).'/bootstrap.php';
use Arcates\Services\MarketplaceSyncService;

$providers=['trendyol','hepsiburada'];$requested=$argv[1]??'all';if($requested!=='all'&&!in_array($requested,$providers,true)){fwrite(STDERR,"Kullanım: php scripts/marketplace_sync.php [all|trendyol|hepsiburada]\n");exit(2);}$targets=$requested==='all'?$providers:[$requested];$service=new MarketplaceSyncService();$failed=false;
foreach($targets as $provider){try{$check=$service->checkPending($provider,10);$sync=$service->sync($provider);echo strtoupper($provider).' kontrol='.json_encode($check,JSON_UNESCAPED_UNICODE).' senkron='.json_encode($sync,JSON_UNESCAPED_UNICODE).PHP_EOL;}catch(Throwable $e){$failed=true;fwrite(STDERR,strtoupper($provider).' hata: '.$e->getMessage().PHP_EOL);}}
exit($failed?1:0);

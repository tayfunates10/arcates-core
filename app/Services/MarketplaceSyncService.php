<?php
declare(strict_types=1);
namespace Arcates\Services;
use Arcates\Core\App;
use Arcates\Core\Database;
use Arcates\Marketplace\MarketplaceFactory;

final class MarketplaceSyncService
{
    public function sync(string $provider): array
    {
        $this->guardQueue($provider);$gateway=MarketplaceFactory::make($provider);$claim=bin2hex(random_bytes(16));$items=$this->claimChanged($provider,$claim,$gateway->maxBatchSize());if($items===[])return ['provider'=>$provider,'submitted'=>0,'batch_id'=>null];
        try{$result=$gateway->submit($items);}catch(\Throwable $e){$this->releaseClaim($claim,$e->getMessage());throw $e;}
        $external=trim((string)($result['batch_id']??''));if($external===''){$this->releaseClaim($claim,'Pazaryeri batch ID döndürmedi.');throw new \RuntimeException('Pazaryeri batch ID döndürmedi.');}
        $batchId=App::db()->transaction(function(Database $db)use($provider,$external,$items,$claim): int{$db->execute('INSERT INTO marketplace_batches(provider,external_batch_id,status,item_count,created_at,updated_at) VALUES(?,?,\'submitted\',?,NOW(),NOW())',[$provider,$external,count($items)]);$batchId=(int)$db->lastInsertId();foreach($items as $index=>$item){$db->execute('INSERT INTO marketplace_batch_items(batch_id,mapping_id,item_index,external_key,payload_hash,status) VALUES(?,?,?,?,?,\'submitted\')',[$batchId,(int)$item['mapping_id'],$index,(string)$item['external_key'],(string)$item['payload_hash']]);$db->execute('UPDATE marketplace_mappings SET pending_payload_hash=?,pending_batch_id=?,claim_token=NULL,claimed_at=NULL,last_status=\'submitted\',last_error=NULL,updated_at=NOW() WHERE id=? AND claim_token=?',[(string)$item['payload_hash'],$batchId,(int)$item['mapping_id'],$claim]);}return $batchId;});
        return ['provider'=>$provider,'submitted'=>count($items),'batch_id'=>$batchId,'external_batch_id'=>$external];
    }
    public function checkPending(string $provider,int $limit=10): array
    {
        $gateway=MarketplaceFactory::make($provider);$rows=array_slice(App::db()->fetchAll('SELECT * FROM marketplace_batches WHERE provider=? AND status IN (\'submitted\',\'processing\') ORDER BY id ASC',[$provider]),0,max(1,min(50,$limit)));$summary=['provider'=>$provider,'checked'=>0,'success'=>0,'failed'=>0,'processing'=>0];
        foreach($rows as $batch){$summary['checked']++;try{$result=$gateway->check((string)$batch['external_batch_id']);$status=(string)($result['status']??'processing');$this->applyResult((int)$batch['id'],$status,$result);$summary[$status]=($summary[$status]??0)+1;}catch(\Throwable $e){App::db()->execute('UPDATE marketplace_batches SET last_error=?,checked_at=NOW(),updated_at=NOW() WHERE id=?',[mb_substr($e->getMessage(),0,1000),(int)$batch['id']]);}}
        return $summary;
    }
    private function guardQueue(string $provider): void
    {
        if($provider!=='hepsiburada')return;$row=App::db()->fetch('SELECT COUNT(*) AS c FROM marketplace_batches WHERE provider=\'hepsiburada\' AND status IN (\'submitted\',\'processing\')');if((int)($row['c']??0)>=5)throw new \RuntimeException('Hepsiburada aynı anda en fazla 5 bekleyen envanter yüklemesine izin verir. Önce batch sonuçlarını kontrol edin.');
    }
    private function claimChanged(string $provider,string $claim,int $max): array
    {
        return App::db()->transaction(function(Database $db)use($provider,$claim,$max): array{$rows=$db->fetchAll('SELECT m.*,v.sku,v.price,v.stock,v.is_active AS variant_active FROM marketplace_mappings m JOIN product_variants v ON v.id=m.variant_id WHERE m.provider=? AND m.is_active=1 AND v.is_active=1 AND m.pending_batch_id IS NULL AND (m.claim_token IS NULL OR m.claimed_at < DATE_SUB(NOW(),INTERVAL 10 MINUTE)) ORDER BY m.id ASC FOR UPDATE',[$provider]);$items=[];foreach($rows as $row){if(count($items)>=$max)break;$item=$this->normalize($row);if($item['payload_hash']===(string)($row['last_payload_hash']??''))continue;$db->execute('UPDATE marketplace_mappings SET claim_token=?,claimed_at=NOW(),last_error=NULL,updated_at=NOW() WHERE id=?',[$claim,(int)$row['id']]);$items[]=$item;}return $items;});
    }
    private function normalize(array $row): array
    {
        $price=round(max(0,(float)$row['price']*(float)$row['price_multiplier']),2);$stock=max(0,(int)$row['stock']-(int)$row['stock_reserve']);$item=['mapping_id'=>(int)$row['id'],'provider'=>(string)$row['provider'],'external_sku'=>(string)$row['external_sku'],'external_product_id'=>(string)($row['external_product_id']??''),'barcode'=>(string)($row['barcode']??''),'stock'=>$stock,'price'=>$price,'list_price'=>$price];$item['external_key']=$item['provider']==='trendyol'?$item['barcode']:$item['external_sku'];if(trim((string)$item['external_key'])==='')throw new \RuntimeException('Pazaryeri eşlemesinde harici anahtar eksik.');if($item['provider']==='hepsiburada'&&trim((string)$item['external_product_id'])==='')throw new \RuntimeException('Hepsiburada eşlemesinde HBSKU eksik.');$item['payload_hash']=hash('sha256',json_encode([$item['provider'],$item['external_sku'],$item['external_product_id'],$item['barcode'],$stock,$price],JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR));return $item;
    }
    private function applyResult(int $batchId,string $status,array $result): void
    {
        if($status==='processing'){App::db()->execute('UPDATE marketplace_batches SET status=\'processing\',checked_at=NOW(),updated_at=NOW() WHERE id=?',[$batchId]);return;}
        $items=App::db()->fetchAll('SELECT * FROM marketplace_batch_items WHERE batch_id=? ORDER BY item_index ASC',[$batchId]);$failedKeys=is_array($result['failed_keys']??null)?$result['failed_keys']:[];$failedIndexes=is_array($result['failed_indexes']??null)?$result['failed_indexes']:[];$batchFailed=$status==='failed'&&$failedKeys===[]&&$failedIndexes===[];
        App::db()->transaction(function(Database $db)use($batchId,$items,$failedKeys,$failedIndexes,$batchFailed,$result): void{$anyFail=false;foreach($items as $item){$key=(string)$item['external_key'];$idx=(int)$item['item_index'];$error=$failedKeys[$key]??$failedIndexes[$idx]??$failedIndexes[$idx+1]??($batchFailed?(string)($result['message']??'Pazaryeri batch hatası'):null);if($error!==null){$anyFail=true;$db->execute('UPDATE marketplace_batch_items SET status=\'failed\',error_message=? WHERE id=?',[mb_substr((string)$error,0,500),(int)$item['id']]);$db->execute('UPDATE marketplace_mappings SET pending_payload_hash=NULL,pending_batch_id=NULL,last_status=\'failed\',last_error=?,updated_at=NOW() WHERE id=?',[mb_substr((string)$error,0,500),(int)$item['mapping_id']]);}else{$db->execute('UPDATE marketplace_batch_items SET status=\'success\',error_message=NULL WHERE id=?',[(int)$item['id']]);$db->execute('UPDATE marketplace_mappings SET last_payload_hash=?,pending_payload_hash=NULL,pending_batch_id=NULL,last_status=\'success\',last_error=NULL,last_synced_at=NOW(),updated_at=NOW() WHERE id=?',[(string)$item['payload_hash'],(int)$item['mapping_id']]);}}$db->execute('UPDATE marketplace_batches SET status=?,last_error=?,checked_at=NOW(),updated_at=NOW() WHERE id=?',[$anyFail?'failed':'success',$anyFail?'Bir veya daha fazla kalem başarısız.':null,$batchId]);});
    }
    private function releaseClaim(string $claim,string $error): void{App::db()->execute('UPDATE marketplace_mappings SET claim_token=NULL,claimed_at=NULL,last_error=?,last_status=\'failed\',updated_at=NOW() WHERE claim_token=?',[mb_substr($error,0,500),$claim]);}
}

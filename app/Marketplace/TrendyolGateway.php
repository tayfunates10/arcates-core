<?php
declare(strict_types=1);
namespace Arcates\Marketplace;
use Arcates\Core\App;

final class TrendyolGateway implements MarketplaceGateway
{
    private array $config;
    public function __construct()
    {
        $this->config=(array)App::config('integrations.marketplace.trendyol',[]);
        foreach(['base_url','seller_id','api_key','api_secret','user_agent'] as $key){if(trim((string)($this->config[$key]??''))==='')throw new \RuntimeException('Trendyol ayarı eksik: '.$key);}
    }
    public function provider(): string{return 'trendyol';}
    public function maxBatchSize(): int{return 1000;}
    public function submit(array $items): array
    {
        if($items===[]||count($items)>$this->maxBatchSize())throw new \RuntimeException('Trendyol batch boyutu geçersiz.');
        $payload=[];foreach($items as $item){$barcode=trim((string)($item['barcode']??''));if($barcode==='')throw new \RuntimeException('Trendyol barkodu gerekli.');$sale=round((float)$item['price'],2);$list=max($sale,round((float)($item['list_price']??$sale),2));$payload[]=['barcode'=>$barcode,'quantity'=>min(20000,max(0,(int)$item['stock'])),'salePrice'=>$sale,'listPrice'=>$list];}
        $data=$this->request('POST','/integration/inventory/sellers/'.rawurlencode((string)$this->config['seller_id']).'/products/price-and-inventory',['items'=>$payload]);
        $id=trim((string)($data['batchRequestId']??''));if($id==='')throw new \RuntimeException('Trendyol batchRequestId döndürmedi.');return ['batch_id'=>$id,'status'=>'submitted'];
    }
    public function check(string $externalBatchId): array
    {
        $data=$this->request('GET','/integration/product/sellers/'.rawurlencode((string)$this->config['seller_id']).'/products/batch-requests/'.rawurlencode($externalBatchId),null);
        $failed=[];$processing=false;$items=is_array($data['items']??null)?$data['items']:[];
        foreach($items as $row){if(!is_array($row))continue;$status=strtoupper((string)($row['status']??''));$req=(array)($row['requestItem']??[]);$product=is_array($req['product']??null)?$req['product']:[];$key=(string)($req['barcode']??$product['barcode']??'');if(in_array($status,['IN_PROGRESS','WAITING','PROCESSING'],true))$processing=true;if(in_array($status,['FAILED','FAILURE','ERROR'],true)||!empty($row['failureReasons']))$failed[$key!==''?$key:'#'.count($failed)]=self::failureMessage($row);}
        $top=strtoupper((string)($data['status']??''));if(in_array($top,['IN_PROGRESS','WAITING','PROCESSING'],true))$processing=true;
        return ['status'=>$processing?'processing':($failed!==[]?'failed':'success'),'failed_keys'=>$failed,'raw'=>$data];
    }
    private function request(string $method,string $path,?array $body): array
    {
        if(!function_exists('curl_init'))throw new \RuntimeException('Trendyol entegrasyonu için cURL gerekli.');$url=rtrim((string)$this->config['base_url'],'/').$path;$ch=curl_init($url);$headers=['Accept: application/json','Content-Type: application/json','Authorization: Basic '.base64_encode((string)$this->config['api_key'].':'.(string)$this->config['api_secret']),'User-Agent: '.(string)$this->config['user_agent']];$store=trim((string)($this->config['storefront_code']??''));if($store!=='')$headers[]='storeFrontCode: '.$store;curl_setopt_array($ch,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_TIMEOUT=>30,CURLOPT_CUSTOMREQUEST=>$method,CURLOPT_HTTPHEADER=>$headers]);if($body!==null)curl_setopt($ch,CURLOPT_POSTFIELDS,json_encode($body,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR));$raw=curl_exec($ch);$status=(int)curl_getinfo($ch,CURLINFO_HTTP_CODE);$error=curl_error($ch);curl_close($ch);if($raw===false||$error!=='')throw new \RuntimeException('Trendyol bağlantı hatası: '.$error);if($status<200||$status>=300)throw new \RuntimeException('Trendyol API HTTP '.$status.': '.mb_substr((string)$raw,0,400));$data=json_decode((string)$raw,true);if(!is_array($data))throw new \RuntimeException('Trendyol geçersiz JSON döndürdü.');return $data;
    }
    private static function failureMessage(array $row): string
    {
        $reasons=$row['failureReasons']??null;if(is_array($reasons))return mb_substr(implode('; ',array_map(static fn($v): string=>is_scalar($v)?(string)$v:json_encode($v,JSON_UNESCAPED_UNICODE),$reasons)),0,500);return mb_substr((string)($row['message']??'Trendyol kalem hatası'),0,500);
    }
}

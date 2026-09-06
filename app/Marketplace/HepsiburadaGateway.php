<?php
declare(strict_types=1);
namespace Arcates\Marketplace;
use Arcates\Core\App;

final class HepsiburadaGateway implements MarketplaceGateway
{
    private array $config;
    public function __construct()
    {
        $this->config=(array)App::config('integrations.marketplace.hepsiburada',[]);
        foreach(['base_url','merchant_id','username','password','user_agent'] as $key){if(trim((string)($this->config[$key]??''))==='')throw new \RuntimeException('Hepsiburada ayarı eksik: '.$key);}
    }
    public function provider(): string{return 'hepsiburada';}
    public function maxBatchSize(): int{return 4000;}
    public function submit(array $items): array
    {
        if($items===[]||count($items)>$this->maxBatchSize())throw new \RuntimeException('Hepsiburada batch boyutu geçersiz.');$xml=['<?xml version="1.0" encoding="UTF-8"?><listings>'];
        foreach($items as $item){$hb=trim((string)($item['external_product_id']??''));$sku=trim((string)($item['external_sku']??''));if($hb===''||$sku==='')throw new \RuntimeException('Hepsiburada HBSKU ve MerchantSku gerekli.');$price=number_format(max(0,(float)$item['price']),2,'.','');$stock=max(0,(int)$item['stock']);$xml[]='<listing><HepsiburadaSku>'.self::x($hb).'</HepsiburadaSku><MerchantSku>'.self::x($sku).'</MerchantSku><Price>'.$price.'</Price><AvailableStock>'.$stock.'</AvailableStock><DispatchTime>'.max(0,(int)($this->config['dispatch_time']??2)).'</DispatchTime><MaximumPurchasableQuantity>'.max(1,(int)($this->config['max_purchasable_quantity']??1000)).'</MaximumPurchasableQuantity></listing>';}$xml[]='</listings>';
        $raw=$this->request('POST','/listings/merchantid/'.rawurlencode((string)$this->config['merchant_id']).'/inventory-uploads',implode('',$xml),'application/xml');$id=self::batchId($raw);if($id==='')throw new \RuntimeException('Hepsiburada inventory upload ID döndürmedi.');return ['batch_id'=>$id,'status'=>'submitted'];
    }
    public function check(string $externalBatchId): array
    {
        $raw=$this->request('GET','/listings/merchantid/'.rawurlencode((string)$this->config['merchant_id']).'/inventory-uploads/id/'.rawurlencode($externalBatchId),null,'application/json');$data=json_decode($raw,true);if(is_array($data)){return self::normalize($data);}
        $status=strtoupper(self::tag($raw,'status'));$processing=in_array($status,['PROCESSING','IN_PROGRESS','WAITING','PENDING'],true);$failed=in_array($status,['FAILED','FAILURE','ERROR'],true);return ['status'=>$processing?'processing':($failed?'failed':'success'),'failed_indexes'=>[],'message'=>mb_substr(strip_tags($raw),0,500),'raw'=>$raw];
    }
    private function request(string $method,string $path,?string $body,string $accept): string
    {
        if(!function_exists('curl_init'))throw new \RuntimeException('Hepsiburada entegrasyonu için cURL gerekli.');$ch=curl_init(rtrim((string)$this->config['base_url'],'/').$path);$headers=['Accept: '.$accept,'Content-Type: '.($body!==null?'application/xml':'application/json'),'Authorization: Basic '.base64_encode((string)$this->config['username'].':'.(string)$this->config['password']),'User-Agent: '.(string)$this->config['user_agent']];curl_setopt_array($ch,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_TIMEOUT=>30,CURLOPT_CUSTOMREQUEST=>$method,CURLOPT_HTTPHEADER=>$headers]);if($body!==null)curl_setopt($ch,CURLOPT_POSTFIELDS,$body);$raw=curl_exec($ch);$status=(int)curl_getinfo($ch,CURLINFO_HTTP_CODE);$error=curl_error($ch);curl_close($ch);if($raw===false||$error!=='')throw new \RuntimeException('Hepsiburada bağlantı hatası: '.$error);if($status<200||$status>=300)throw new \RuntimeException('Hepsiburada API HTTP '.$status.': '.mb_substr((string)$raw,0,400));return (string)$raw;
    }
    private static function normalize(array $data): array
    {
        $status=strtoupper((string)($data['status']??$data['Status']??''));$processing=in_array($status,['PROCESSING','IN_PROGRESS','WAITING','PENDING'],true);$errors=$data['errors']??$data['Errors']??[];$failed=[];if(is_array($errors)){foreach($errors as $err){if(!is_array($err))continue;$index=max(0,(int)($err['elementNo']??$err['ElementNo']??$err['index']??0));$messages=$err['errors']??$err['Errors']??$err['message']??'Hepsiburada kalem hatası';$failed[$index]=mb_substr(is_array($messages)?implode('; ',array_map('strval',$messages)):(string)$messages,0,500);}}
        if($processing)return ['status'=>'processing','failed_indexes'=>[],'raw'=>$data];if($failed!==[])return ['status'=>'failed','failed_indexes'=>$failed,'raw'=>$data];if(in_array($status,['FAILED','FAILURE','ERROR'],true))return ['status'=>'failed','failed_indexes'=>[],'message'=>(string)($data['message']??'Hepsiburada batch hatası'),'raw'=>$data];return ['status'=>'success','failed_indexes'=>[],'raw'=>$data];
    }
    private static function batchId(string $raw): string
    {
        $data=json_decode($raw,true);if(is_array($data)){foreach(['id','inventoryUploadId','trackingId'] as $key){$v=trim((string)($data[$key]??''));if($v!=='')return $v;}}foreach(['id','inventoryUploadId','InventoryUploadId'] as $tag){$v=self::tag($raw,$tag);if($v!=='')return $v;}return trim($raw," \t\n\r\0\x0B\"");
    }
    private static function tag(string $xml,string $tag): string{if(preg_match('/<'.preg_quote($tag,'/').'>\s*([^<]+)\s*<\/'.preg_quote($tag,'/').'>/i',$xml,$m)===1)return trim(html_entity_decode($m[1],ENT_QUOTES|ENT_XML1,'UTF-8'));return '';}
    private static function x(string $value): string{return htmlspecialchars($value,ENT_QUOTES|ENT_XML1,'UTF-8');}
}

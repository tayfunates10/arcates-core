<?php
declare(strict_types=1);
namespace Arcates\Einvoice;
use Arcates\Core\App;

final class UyumsoftGateway implements EinvoiceGateway
{
    private array $config;
    public function __construct()
    {
        $this->config=(array)App::config('integrations.einvoice.uyumsoft',[]);foreach(['wsdl','username','password'] as $key)if(trim((string)($this->config[$key]??''))==='')throw new \RuntimeException('Uyumsoft ayarı eksik: '.$key);
    }
    public function provider(): string{return 'uyumsoft';}
    public function isEInvoiceUser(string $vknTckn,string $alias=''): bool
    {
        self::identity($vknTckn);$r=$this->call((string)($this->config['user_query_operation']??'IsEInvoiceUser'),['vknTckn'=>$vknTckn,'alias'=>$alias]);$this->assertSucceeded($r);$v=$this->find($r,'Value');return filter_var($v,FILTER_VALIDATE_BOOLEAN,FILTER_NULL_ON_FAILURE)??(bool)$v;
    }
    public function send(string $ublXml,array $meta): array
    {
        $vkn=self::identity((string)($meta['vkn_tckn']??''));$alias=trim((string)($meta['alias']??''));$title=trim((string)($meta['title']??''));$local=trim((string)($meta['local_document_id']??''));if($title===''||$local==='')throw new \RuntimeException('Uyumsoft alıcı ünvanı ve local document ID gerekli.');if(!class_exists(\SoapVar::class))throw new \RuntimeException('Uyumsoft entegrasyonu için PHP SOAP eklentisi gerekli.');
        $invoice=['Invoice'=>new \SoapVar($ublXml,XSD_ANYXML),'TargetCustomer'=>['Vkn'=>$vkn,'Alias'=>$alias,'Title'=>$title],'LocalDocumentId'=>$local];$r=$this->call((string)($this->config['send_operation']??'SendInvoice'),['invoices'=>[$invoice]]);$this->assertSucceeded($r);$value=$this->find($r,'Value');$identity=$this->firstAssoc($value);$uuid=(string)($identity['Id']??$identity['ID']??$identity['Uuid']??$identity['UUID']??'');if($uuid==='')throw new \RuntimeException('Uyumsoft gönderim yanıtı UUID döndürmedi.');return ['uuid'=>$uuid,'number'=>(string)($identity['Number']??$identity['InvoiceNumber']??''),'scenario'=>(string)($identity['InvoiceScenario']??$identity['ScenarioType']??''),'message'=>(string)($this->find($r,'Message')??'Gönderim kuyruğa alındı.')];
    }
    public function status(string $externalUuid): array
    {
        if(trim($externalUuid)==='')throw new \RuntimeException('Uyumsoft UUID gerekli.');$r=$this->call((string)($this->config['status_operation']??'QueryOutboxInvoiceStatus'),['invoiceIds'=>[$externalUuid]]);$this->assertSucceeded($r);$row=$this->firstAssoc($this->find($r,'Value'));$code=(string)($row['StatusCode']??$row['Status']??'');$label=(string)($row['Status']??'');return ['status'=>self::normalizeStatus($code,$label),'status_code'=>$code,'message'=>(string)($row['Message']??$this->find($r,'Message')??''),'raw'=>$row];
    }
    private function call(string $operation,array $params): array
    {
        if(!class_exists(\SoapClient::class))throw new \RuntimeException('Uyumsoft entegrasyonu için PHP SOAP eklentisi gerekli.');try{$client=new \SoapClient((string)$this->config['wsdl'],['exceptions'=>true,'trace'=>false,'cache_wsdl'=>WSDL_CACHE_BOTH,'connection_timeout'=>(int)($this->config['timeout']??20)]);$params=['userInfo'=>['Username'=>(string)$this->config['username'],'Password'=>(string)$this->config['password']]]+$params;$r=$client->__soapCall($operation,[$params]);return self::arrayify($r);}catch(\SoapFault $e){throw new \RuntimeException('Uyumsoft SOAP hatası: '.mb_substr($e->getMessage(),0,600),0,$e);}
    }
    private function assertSucceeded(array $r): void
    {
        $v=$this->find($r,'IsSucceded');if($v===null)$v=$this->find($r,'IsSucceeded');if($v!==null&&!filter_var($v,FILTER_VALIDATE_BOOLEAN))throw new \RuntimeException('Uyumsoft işlemi başarısız: '.mb_substr((string)($this->find($r,'Message')??'Bilinmeyen hata'),0,600));
    }
    private function find(mixed $node,string $key): mixed
    {
        if(!is_array($node))return null;foreach($node as $k=>$v){if(strcasecmp((string)$k,$key)===0)return $v;if(is_array($v)){ $found=$this->find($v,$key);if($found!==null)return $found;}}return null;
    }
    private function firstAssoc(mixed $value): array
    {
        if(!is_array($value))return [];$keys=array_keys($value);$isList=$keys===range(0,count($value)-1);if($isList){foreach($value as $v)if(is_array($v))return $v;return [];}foreach($value as $v)if(is_array($v)&&array_keys($v)===range(0,count($v)-1))return is_array($v[0]??null)?$v[0]:[];return $value;
    }
    private static function arrayify(mixed $v): mixed{if(is_object($v))$v=get_object_vars($v);if(is_array($v)){foreach($v as $k=>$item)$v[$k]=self::arrayify($item);}return $v;}
    private static function identity(string $id): string{$id=preg_replace('/\D+/','',$id)??'';if(!in_array(strlen($id),[10,11],true))throw new \RuntimeException('VKN/TCKN 10 veya 11 haneli olmalı.');return $id;}
    private static function normalizeStatus(string $code,string $label): string
    {
        $map=['0'=>'draft','10'=>'cancelled','100'=>'queued','200'=>'processing','300'=>'sent_to_gib','1000'=>'approved','1100'=>'waiting_approval','1200'=>'declined','1300'=>'returned','1400'=>'earchive_cancelled','2000'=>'failed'];if(isset($map[$code]))return $map[$code];$s=strtolower($label);if(str_contains($s,'approved')||str_contains($s,'onay'))return 'approved';if(str_contains($s,'error')||str_contains($s,'hata'))return 'failed';if(str_contains($s,'declin')||str_contains($s,'red'))return 'declined';if(str_contains($s,'cancel')||str_contains($s,'iptal'))return 'cancelled';return 'processing';
    }
}

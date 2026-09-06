<?php
declare(strict_types=1);
namespace Arcates\Shipping;
use Arcates\Core\App;
final class YurticiGateway implements CarrierGateway
{
    private array $config;
    public function __construct(){ $this->config=(array)App::config('integrations.shipping.yurtici',[]); $this->required(['wsdl','username','password']); }
    public function create(array $order,array $package): array
    {
        $reference=self::reference((string)$order['public_code']);$district=trim((string)($package['district']??''));if($district==='')throw new \RuntimeException('Yurtiçi için alıcı ilçesi gerekli.');$payload=$this->auth(false)+['ShippingOrderVO'=>['cargoKey'=>$reference,'invoiceKey'=>$reference,'receiverCustName'=>(string)$order['customer_name'],'receiverAddress'=>(string)$order['address'],'cityName'=>(string)$order['city'],'townName'=>$district,'receiverPhone1'=>(string)$order['phone'],'receiverPhone2'=>'','receiverPhone3'=>'','emailAddress'=>(string)$order['email'],'taxOfficeId'=>'','taxNumber'=>(string)($order['identity_number']??''),'taxOfficeName'=>'','desi'=>(float)$package['desi'],'kg'=>(float)$package['kg'],'cargoCount'=>(int)$package['count'],'waybillNo'=>$reference,'specialField1'=>'','specialField2'=>'','specialField3'=>'','ttInvoiceAmount'=>'','ttDocumentId'=>'','ttCollectionType'=>'','ttDocumentSaveType'=>'','dcSelectedCredit'=>'','dcCreditRule'=>'','description'=>'Arcates sipariş '.$reference,'orgGeoCode'=>'','privilegeOrder'=>'','custProdId'=>'','orgReceiverCustId'=>'']];$op=(string)($this->config['create_operation']??'createShipment');$data=self::arr($this->client()->__soapCall($op,[$payload]));$flag=(string)($data['outFlag']??$data['createShipmentReturn']['outFlag']??'0');if($flag==='1')throw new \RuntimeException('Yurtiçi gönderi oluşturma başarısız: '.self::message($data));return ['reference'=>$reference,'tracking'=>(string)($data['docId']??$data['shippingOrderVO']['docId']??$reference),'status'=>'created','message'=>self::message($data)?:'Yurtiçi gönderisi oluşturuldu.'];
    }
    public function track(string $reference): array
    {
        $op=(string)($this->config['track_operation']??'queryShipment');$payload=$this->auth(true)+['keys'=>[$reference],'keyType'=>0,'addHistoricalData'=>true,'onlyTracking'=>false];$data=self::arr($this->client()->__soapCall($op,[$payload]));return ['status'=>(string)($data['operationCode']??$data['status']??''),'message'=>self::message($data),'events'=>$data];
    }
    public function label(string $reference): array
    {
        $local=App::db()->fetch('SELECT cs.*,o.* FROM carrier_shipments cs JOIN orders o ON o.id=cs.order_id WHERE cs.provider=\'yurtici\' AND cs.reference_code=?',[$reference]);if(!$local)throw new \RuntimeException('Yurtiçi kargo kaydı bulunamadı.');$district=(string)$local['recipient_district'];$payload=$this->auth(false)+['ShippingOrderVO'=>['cargoKey'=>$reference,'invoiceKey'=>$reference,'receiverCustName'=>(string)$local['customer_name'],'receiverAddress'=>(string)$local['address'],'cityName'=>(string)$local['city'],'townName'=>$district,'receiverPhone1'=>(string)$local['phone'],'emailAddress'=>(string)$local['email'],'taxNumber'=>(string)($local['identity_number']??''),'desi'=>(float)$local['desi'],'kg'=>(float)$local['weight_kg'],'cargoCount'=>(int)$local['package_count'],'waybillNo'=>$reference,'description'=>'Arcates sipariş '.$reference]];$op=(string)($this->config['label_operation']??'createShipmentWithDelivery');$data=self::arr($this->client()->__soapCall($op,[$payload]));$zpl=self::findLabel($data);if($zpl==='')throw new \RuntimeException('Yurtiçi etiket verisi dönmedi.');return ['content_type'=>'application/zpl','filename'=>$reference.'.zpl','content'=>$zpl,'tracking'=>(string)($data['docId']??$reference)];
    }
    private function auth(bool $query): array{return ['wsUserName'=>(string)$this->config['username'],'wsPassword'=>(string)$this->config['password'],$query?'wsLanguage':'userLanguage'=>(string)($this->config['language']??'TR')];}
    private function client(): \SoapClient{if(!class_exists('SoapClient'))throw new \RuntimeException('Yurtiçi entegrasyonu için PHP SOAP eklentisi gerekli.');return new \SoapClient((string)$this->config['wsdl'],['exceptions'=>true,'trace'=>false,'cache_wsdl'=>WSDL_CACHE_BOTH,'connection_timeout'=>20]);}
    private function required(array $keys): void{foreach($keys as $key)if(trim((string)($this->config[$key]??''))==='')throw new \RuntimeException('Yurtiçi ayarı eksik: '.$key);}
    private static function arr(mixed $value): array{$data=json_decode(json_encode($value,JSON_THROW_ON_ERROR),true,512,JSON_THROW_ON_ERROR);return is_array($data)?$data:[];}
    private static function reference(string $code): string{return substr(strtoupper(preg_replace('/[^A-Z0-9]/i','',$code)??''),0,20);}
    private static function message(array $data): string{return (string)($data['outResult']??$data['outResultMessage']??$data['message']??'');}
    private static function findLabel(array $data): string{foreach($data as $key=>$value){if(is_string($value)&&in_array(strtolower((string)$key),['zpl','label','labeldata','label_data'],true)&&$value!=='')return $value;if(is_array($value)){ $found=self::findLabel($value); if($found!=='')return $found; }}return '';}
}

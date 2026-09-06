<?php
declare(strict_types=1);
namespace Arcates\Shipping;
use Arcates\Core\App;
final class ArasGateway implements CarrierGateway
{
    private array $config;
    public function __construct(){ $this->config=(array)App::config('integrations.shipping.aras',[]); $this->required(['wsdl','barcode_wsdl','username','password']); }
    public function create(array $order,array $package): array
    {
        $reference=substr(strtoupper(preg_replace('/[^A-Z0-9]/i','',(string)$order['public_code'])??''),0,30);$district=trim((string)($package['district']??''));if($district==='')throw new \RuntimeException('Aras için alıcı ilçesi gerekli.');$soap=$this->client((string)$this->config['wsdl']);$line=['UserName'=>(string)$this->config['username'],'Password'=>(string)$this->config['password'],'TradingWaybillNumber'=>$reference,'InvoiceNumber'=>$reference,'ReceiverName'=>(string)$order['customer_name'],'ReceiverAddress'=>(string)$order['address'],'ReceiverPhone1'=>(string)$order['phone'],'ReceiverPhone2'=>'','ReceiverPhone3'=>'','ReceiverCityName'=>(string)$order['city'],'ReceiverTownName'=>$district,'VolumetricWeight'=>(string)$package['desi'],'Weight'=>(string)$package['kg'],'PieceCount'=>(string)$package['count'],'SpecialField1'=>'','SpecialField2'=>'','SpecialField3'=>'','CodAmount'=>'0','CodCollectionType'=>'0','CodBillingType'=>'0','IntegrationCode'=>$reference,'Description'=>'Arcates sipariş '.$reference,'TaxNumber'=>(string)($order['identity_number']??''),'TtDocumentId'=>'','TaxOffice'=>'','PrivilegeOrder'=>'','Country'=>'TR','CountryCode'=>'TR','CityCode'=>'','TownCode'=>'','ReceiverDistrictName'=>$district,'ReceiverQuarterName'=>'','ReceiverAvenueName'=>'','ReceiverStreetName'=>'','PayorTypeCode'=>'1','IsWorldWide'=>'0','IsCod'=>'0','UnitID'=>'','PieceDetails'=>[],'SenderAccountAddressId'=>(string)($this->config['sender_account_address_id']??'')];$result=$soap->__soapCall('SetOrder',[['orderInfo'=>['Order'=>[$line]],'userName'=>$this->config['username'],'password'=>$this->config['password']]]);$data=self::arr($result);$row=$data['SetOrderResult']['OrderResultInfo']??$data['OrderResultInfo']??[];if(isset($row[0]))$row=$row[0];$code=(string)($row['ResultCode']??'');if($code!==''&&!in_array($code,['0','1'],true))throw new \RuntimeException('Aras gönderi oluşturma hatası: '.(string)($row['ResultMessage']??$code));return ['reference'=>$reference,'tracking'=>(string)($row['InvoiceKey']??$reference),'status'=>'created','message'=>(string)($row['ResultMessage']??'Aras gönderisi oluşturuldu.')];
    }
    public function track(string $reference): array
    {
        $soap=$this->client((string)$this->config['barcode_wsdl']);$result=$soap->__soapCall('GetCargoTransaction',[['username'=>$this->config['username'],'password'=>$this->config['password'],'code'=>$reference,'integrationCode'=>$reference]]);$data=self::arr($result);return ['status'=>'','message'=>'Aras hareket bilgisi alındı.','events'=>$data];
    }
    public function label(string $reference): array
    {
        $soap=$this->client((string)$this->config['barcode_wsdl']);$result=$soap->__soapCall('GetArasBarcode',[['Username'=>$this->config['username'],'Password'=>$this->config['password'],'integrationCode'=>$reference]]);$data=self::arr($result);$root=$data['GetArasBarcodeResult']??$data;$pdf=self::firstString($root['ZebraPdf']['string']??$root['ZebraPdf']??null);if($pdf!==''){return ['content_type'=>'application/pdf','filename'=>$reference.'.pdf','content'=>base64_decode($pdf,true)?:$pdf,'tracking'=>$reference];}$zpl=self::firstString($root['ZebraZpl']['string']??$root['ZebraZpl']??null);if($zpl==='')throw new \RuntimeException('Aras etiket verisi dönmedi.');return ['content_type'=>'application/zpl','filename'=>$reference.'.zpl','content'=>$zpl,'tracking'=>$reference];
    }
    private function client(string $wsdl): \SoapClient{if(!class_exists('SoapClient'))throw new \RuntimeException('Aras entegrasyonu için PHP SOAP eklentisi gerekli.');return new \SoapClient($wsdl,['exceptions'=>true,'trace'=>false,'cache_wsdl'=>WSDL_CACHE_BOTH,'connection_timeout'=>20]);}
    private function required(array $keys): void{foreach($keys as $key)if(trim((string)($this->config[$key]??''))==='')throw new \RuntimeException('Aras ayarı eksik: '.$key);}
    private static function arr(mixed $value): array{$json=json_encode($value,JSON_THROW_ON_ERROR);$data=json_decode($json,true,512,JSON_THROW_ON_ERROR);return is_array($data)?$data:[];}
    private static function firstString(mixed $value): string{if(is_string($value))return $value;if(is_array($value)){foreach($value as $v)if(is_string($v)&&$v!=='')return $v;}return '';}
}

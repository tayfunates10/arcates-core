<?php
declare(strict_types=1);
namespace Arcates\Shipping;
use Arcates\Core\App;
final class MngGateway implements CarrierGateway
{
    private array $config;
    public function __construct(){ $this->config=(array)App::config('integrations.shipping.mng',[]); $this->required(['base_url','client_id','client_secret','customer_number','password']); }
    public function create(array $order,array $package): array
    {
        $reference=self::reference((string)$order['public_code']);$token=$this->token();$phone=self::phone((string)$order['phone']);$tax=preg_replace('/\D+/','',(string)($order['identity_number']??''))??'';$district=trim((string)($package['district']??''));if($district==='')throw new \RuntimeException('MNG için alıcı ilçesi gerekli.');if(strlen($phone)!==10)throw new \RuntimeException('MNG için alıcı telefonu 10 haneli olmalıdır.');if(!in_array(strlen($tax),[10,11],true))throw new \RuntimeException('MNG için gerçek 10/11 haneli vergi veya TC kimlik numarası gerekli.');if(!filter_var((string)$order['email'],FILTER_VALIDATE_EMAIL))throw new \RuntimeException('MNG için geçerli alıcı e-postası gerekli.');
        $payload=['order'=>['referenceId'=>$reference,'barcode'=>$reference,'billOfLandingId'=>'','isCOD'=>0,'codAmount'=>0,'shipmentServiceType'=>1,'packagingType'=>3,'content'=>'Sipariş '.$reference,'smsPreference1'=>0,'smsPreference2'=>0,'smsPreference3'=>0,'paymentType'=>1,'deliveryType'=>1,'description'=>'Arcates sipariş '.$reference,'marketPlaceShortCode'=>'','marketPlaceSaleCode'=>''],'orderPieceList'=>[['barcode'=>$reference.'01','desi'=>(float)$package['desi'],'kg'=>(float)$package['kg'],'content'=>'Sipariş '.$reference]],'recipient'=>['cityCode'=>0,'cityName'=>(string)$order['city'],'districtName'=>$district,'districtCode'=>0,'address'=>(string)$order['address'],'email'=>(string)$order['email'],'taxNumber'=>$tax,'fullName'=>(string)$order['customer_name'],'mobilePhoneNumber'=>$phone]];
        $data=$this->request('POST','/mngapi/api/standardcmdapi/createOrder',$payload,$token);$row=self::first($data);$tracking=(string)($row['shipmentId']??$row['orderInvoiceId']??$reference);return ['reference'=>$reference,'tracking'=>$tracking,'status'=>'created','message'=>(string)($row['message']??$row['resultMessage']??'MNG siparişi oluşturuldu.')];
    }
    public function track(string $reference): array
    {
        $token=$this->token();$reference=rawurlencode($reference);$status=self::first($this->request('GET','/mngapi/api/standardqueryapi/getshipmentstatus/'.$reference,null,$token));$events=$this->request('GET','/mngapi/api/standardqueryapi/trackshipment/'.$reference,null,$token);return ['status'=>(string)($status['shipmentStatusCode']??$status['status']??''),'message'=>(string)($status['shipmentStatusExplanation']??$status['statusDescription']??''),'events'=>is_array($events)?$events:[]];
    }
    public function label(string $reference): array
    {
        $local=App::db()->fetch('SELECT cs.*,o.public_code,o.grand_total FROM carrier_shipments cs JOIN orders o ON o.id=cs.order_id WHERE cs.provider=\'mng\' AND cs.reference_code=?',[$reference]);if(!$local)throw new \RuntimeException('MNG kargo kaydı bulunamadı.');$token=$this->token();$payload=['referenceId'=>$reference,'billOfLandingId'=>'','isCOD'=>0,'codAmount'=>0,'shipmentServiceType'=>1,'packagingType'=>3,'content'=>'Sipariş '.$reference,'printReferenceBarcodeOnError'=>1,'orderPieceList'=>[['barcode'=>$reference.'01','desi'=>(float)$local['desi'],'kg'=>(float)$local['weight_kg'],'content'=>'Sipariş '.$reference]]];$row=self::first($this->request('POST','/mngapi/api/barcodecmdapi/createbarcode',$payload,$token));$barcode=$row['barcodes'][0]??[];$zpl=(string)($barcode['value']??'');if($zpl==='')throw new \RuntimeException('MNG etiket verisi dönmedi.');return ['content_type'=>'application/zpl','filename'=>$reference.'.zpl','content'=>$zpl,'tracking'=>(string)($row['shipmentId']??$barcode['barcode']??'')];
    }
    private function token(): string
    {
        $data=$this->request('POST','/mngapi/api/token',['customerNumber'=>(string)$this->config['customer_number'],'password'=>(string)$this->config['password'],'identityType'=>(int)($this->config['identity_type']??1)],null);$jwt=(string)($data['jwt']??$data['token']??'');if($jwt==='')throw new \RuntimeException('MNG kimlik doğrulaması token döndürmedi.');return $jwt;
    }
    private function request(string $method,string $path,?array $body,?string $token): array
    {
        if(!function_exists('curl_init'))throw new \RuntimeException('MNG entegrasyonu için cURL gerekli.');$url=rtrim((string)$this->config['base_url'],'/').$path;$ch=curl_init($url);$headers=['Accept: application/json','Content-Type: application/json','X-IBM-Client-Id: '.$this->config['client_id'],'X-IBM-Client-Secret: '.$this->config['client_secret']];if($token)$headers[]='Authorization: Bearer '.$token;curl_setopt_array($ch,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_TIMEOUT=>30,CURLOPT_CONNECTTIMEOUT=>10,CURLOPT_CUSTOMREQUEST=>$method,CURLOPT_HTTPHEADER=>$headers]);if($body!==null)curl_setopt($ch,CURLOPT_POSTFIELDS,json_encode($body,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR));$raw=curl_exec($ch);$status=(int)curl_getinfo($ch,CURLINFO_HTTP_CODE);$error=curl_error($ch);curl_close($ch);if($raw===false||$error!=='')throw new \RuntimeException('MNG bağlantı hatası: '.$error);$data=json_decode((string)$raw,true);if($status<200||$status>=300)throw new \RuntimeException('MNG API HTTP '.$status.': '.mb_substr((string)$raw,0,300));if(!is_array($data))throw new \RuntimeException('MNG API geçersiz JSON döndürdü.');return $data;
    }
    private function required(array $keys): void{foreach($keys as $key)if(trim((string)($this->config[$key]??''))==='')throw new \RuntimeException('MNG ayarı eksik: '.$key);}
    private static function first(array $data): array{return array_is_list($data)&&isset($data[0])&&is_array($data[0])?$data[0]:$data;}
    private static function reference(string $code): string{return substr(strtoupper(preg_replace('/[^A-Z0-9]/i','',$code)??''),0,20);}
    private static function phone(string $phone): string{$d=preg_replace('/\D+/','',$phone)??'';if(str_starts_with($d,'90'))$d=substr($d,2);if(str_starts_with($d,'0'))$d=substr($d,1);return substr($d,-10);}
}

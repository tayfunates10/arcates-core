<?php
declare(strict_types=1);
namespace Arcates\Accounting;
use Arcates\Core\App;

final class MikroGateway implements AccountingGateway
{
    private array $config;
    public function __construct(){ $this->config=(array)App::config('integrations.accounting.mikro',[]);foreach(['base_url','api_key','working_year','company_code','user_code','password'] as $k)if(trim((string)($this->config[$k]??''))==='')throw new \RuntimeException('Mikro ayarı eksik: '.$k); }
    public function provider(): string{return 'mikro';}
    public function send(array $payload): array
    {
        if(!isset($payload['evraklar'])||!is_array($payload['evraklar']))throw new \RuntimeException('Mikro profil şablonu evraklar dizisi üretmeli.');$mikro=array_merge($payload,['ApiKey'=>(string)$this->config['api_key'],'CalismaYili'=>(string)$this->config['working_year'],'FirmaKodu'=>(string)$this->config['company_code'],'KullaniciKodu'=>(string)$this->config['user_code'],'Sifre'=>(string)$this->config['password']]);$body=json_encode(['Mikro'=>$mikro],JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR);$endpoint=(string)($this->config['endpoint']??'/Api/apiMethods/MuhasebeFisKaydetV2');$r=HttpTransport::request('POST',rtrim((string)$this->config['base_url'],'/').'/'.ltrim($endpoint,'/'),['Accept: application/json','Content-Type: application/json'],$body,(int)($this->config['timeout']??25));if($r['status']>=500)throw new UncertainTransferException('Mikro API sunucu hatası; kayıt sonucu doğrulanmalı. HTTP '.$r['status']);if($r['status']<200||$r['status']>=300)throw new \RuntimeException('Mikro API HTTP '.$r['status'].': '.mb_substr($r['body'],0,400));$data=json_decode($r['body'],true);$id=self::findId($data);return ['external_id'=>$id,'response'=>$r['body']];
    }
    private static function findId(mixed $data): ?string
    {
        if(!is_array($data))return null;foreach(['Guid','GUID','guid','evrak_guid','EvrakGuid','id'] as $k)if(isset($data[$k])&&is_scalar($data[$k]))return (string)$data[$k];foreach($data as $v){$id=self::findId($v);if($id!==null)return $id;}return null;
    }
}

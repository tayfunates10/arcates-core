<?php
declare(strict_types=1);
namespace Arcates\Accounting;
use Arcates\Core\App;

final class LogoNetsisGateway implements AccountingGateway
{
    private array $config;
    public function __construct(){ $this->config=(array)App::config('integrations.accounting.logo',[]);foreach(['base_url','access_token'] as $k)if(trim((string)($this->config[$k]??''))==='')throw new \RuntimeException('Logo/Netsis ayarı eksik: '.$k); }
    public function provider(): string{return 'logo';}
    public function send(array $payload): array
    {
        $endpoint=(string)($this->config['endpoint']??'/api/v2/GLSlips');$body=json_encode($payload,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR);$r=HttpTransport::request('POST',rtrim((string)$this->config['base_url'],'/').'/'.ltrim($endpoint,'/'),['Accept: application/json','Content-Type: application/json','Authorization: Bearer '.$this->config['access_token']],$body,(int)($this->config['timeout']??25));if($r['status']>=500)throw new UncertainTransferException('Logo/Netsis API sunucu hatası; kayıt sonucu doğrulanmalı. HTTP '.$r['status']);if($r['status']<200||$r['status']>=300)throw new \RuntimeException('Logo/Netsis API HTTP '.$r['status'].': '.mb_substr($r['body'],0,400));$data=json_decode($r['body'],true);$id=is_array($data)?($data['id']??$data['ID']??$data['FisNo']??$data['FISNO']??null):null;return ['external_id'=>$id!==null?(string)$id:null,'response'=>$r['body']];
    }
}

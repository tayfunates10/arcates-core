<?php
declare(strict_types=1);
namespace Arcates\Accounting;
use Arcates\Core\App;

final class ParasutGateway implements AccountingGateway
{
    private array $config;
    public function __construct(){ $this->config=(array)App::config('integrations.accounting.parasut',[]);foreach(['base_url','company_id'] as $k)if(trim((string)($this->config[$k]??''))==='')throw new \RuntimeException('Paraşüt ayarı eksik: '.$k); }
    public function provider(): string{return 'parasut';}
    public function send(array $payload): array
    {
        if(($payload['data']['type']??'')!=='sales_invoices')throw new \RuntimeException('Paraşüt profil şablonu data.type=sales_invoices üretmeli.');$token=$this->token();$url=rtrim((string)$this->config['base_url'],'/').'/'.rawurlencode((string)$this->config['company_id']).'/sales_invoices';$body=json_encode($payload,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR);$r=HttpTransport::request('POST',$url,['Accept: application/vnd.api+json','Content-Type: application/vnd.api+json','Authorization: Bearer '.$token],$body,(int)($this->config['timeout']??25));if($r['status']>=500)throw new UncertainTransferException('Paraşüt API sunucu hatası; fatura sonucu doğrulanmalı. HTTP '.$r['status']);if($r['status']!==201)throw new \RuntimeException('Paraşüt API HTTP '.$r['status'].': '.mb_substr($r['body'],0,400));$data=json_decode($r['body'],true);$id=$data['data']['id']??null;return ['external_id'=>$id!==null?(string)$id:null,'response'=>$r['body']];
    }
    private function token(): string
    {
        $fixed=trim((string)($this->config['access_token']??''));if($fixed!=='')return $fixed;foreach(['client_id','client_secret','username','password'] as $k)if(trim((string)($this->config[$k]??''))==='')throw new \RuntimeException('Paraşüt OAuth ayarı eksik: '.$k);$form=http_build_query(['grant_type'=>'password','client_id'=>(string)$this->config['client_id'],'client_secret'=>(string)$this->config['client_secret'],'username'=>(string)$this->config['username'],'password'=>(string)$this->config['password'],'redirect_uri'=>(string)($this->config['redirect_uri']??'urn:ietf:wg:oauth:2.0:oob')]);$url=(string)($this->config['token_url']??'https://api.parasut.com/oauth/token');try{$r=HttpTransport::request('POST',$url,['Accept: application/json','Content-Type: application/x-www-form-urlencoded'],$form,(int)($this->config['timeout']??25));}catch(UncertainTransferException $e){throw new \RuntimeException('Paraşüt OAuth token alınamadı; fatura isteği gönderilmedi: '.$e->getMessage(),0,$e);}if($r['status']<200||$r['status']>=300)throw new \RuntimeException('Paraşüt OAuth HTTP '.$r['status'].': '.mb_substr($r['body'],0,300));$data=json_decode($r['body'],true);$token=(string)($data['access_token']??'');if($token==='')throw new \RuntimeException('Paraşüt OAuth access_token döndürmedi.');return $token;
    }
}

<?php
declare(strict_types=1);
namespace Arcates\AI;
use Arcates\Core\App;

final class OpenAIResponsesClient
{
    private array $config;
    public function __construct(){ $this->config=(array)App::config('integrations.ai.openai',[]); }
    public function respond(string $instructions,string $input): string
    {
        $key=trim((string)($this->config['api_key']??''));if($key==='')throw new \RuntimeException('AI API anahtarı yapılandırılmamış.');$base=rtrim((string)($this->config['base_url']??'https://api.openai.com/v1'),'/');$model=trim((string)($this->config['model']??'gpt-5.6-luna'));if($model==='')$model='gpt-5.6-luna';
        $payload=json_encode(['model'=>$model,'instructions'=>$instructions,'input'=>$input,'max_output_tokens'=>max(64,min(1200,(int)($this->config['max_output_tokens']??500))),'store'=>false,'text'=>['verbosity'=>'low']],JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR);$ch=curl_init($base.'/responses');if($ch===false)throw new \RuntimeException('AI bağlantısı başlatılamadı.');curl_setopt_array($ch,[CURLOPT_POST=>true,CURLOPT_RETURNTRANSFER=>true,CURLOPT_HTTPHEADER=>['Authorization: Bearer '.$key,'Content-Type: application/json','Accept: application/json'],CURLOPT_POSTFIELDS=>$payload,CURLOPT_CONNECTTIMEOUT=>8,CURLOPT_TIMEOUT=>max(10,min(60,(int)($this->config['timeout']??30)))]);$raw=curl_exec($ch);$status=(int)curl_getinfo($ch,CURLINFO_RESPONSE_CODE);$err=curl_error($ch);curl_close($ch);if($raw===false||$err!=='')throw new \RuntimeException('AI servisine bağlanılamadı.');if($status<200||$status>=300)throw new \RuntimeException('AI servisi isteği tamamlayamadı.');$data=json_decode((string)$raw,true);if(!is_array($data))throw new \RuntimeException('AI servisi geçersiz yanıt verdi.');$text=trim((string)($data['output_text']??''));if($text===''){foreach((array)($data['output']??[]) as $item)foreach((array)($item['content']??[]) as $part)if(($part['type']??'')==='output_text')$text.=($text!==''?"\n":'').(string)($part['text']??'');}$text=trim($text);if($text==='')throw new \RuntimeException('AI servisi metin yanıtı döndürmedi.');return mb_substr($text,0,6000);
    }
}

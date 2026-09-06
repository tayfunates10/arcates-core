<?php
declare(strict_types=1);
namespace Arcates\Accounting;

final class HttpTransport
{
    /** @return array{status:int,body:string} */
    public static function request(string $method,string $url,array $headers,string $body='',int $timeout=25): array
    {
        if(!function_exists('curl_init'))throw new \RuntimeException('Muhasebe entegrasyonu için cURL gerekli.');
        $ch=curl_init($url);curl_setopt_array($ch,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_CUSTOMREQUEST=>$method,CURLOPT_HTTPHEADER=>$headers,CURLOPT_TIMEOUT=>$timeout,CURLOPT_CONNECTTIMEOUT=>10]);if($body!=='')curl_setopt($ch,CURLOPT_POSTFIELDS,$body);$raw=curl_exec($ch);$status=(int)curl_getinfo($ch,CURLINFO_HTTP_CODE);$errno=curl_errno($ch);$error=curl_error($ch);curl_close($ch);
        if($raw===false||$errno!==0)throw new UncertainTransferException('Dış muhasebe servisi bağlantı sonucu belirsiz: '.$error);
        return ['status'=>$status,'body'=>(string)$raw];
    }
}

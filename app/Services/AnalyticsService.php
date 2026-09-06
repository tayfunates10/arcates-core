<?php
declare(strict_types=1);
namespace Arcates\Services;
use Arcates\Core\App;

final class AnalyticsService
{
    public function track(): void
    {
        if(($_SERVER['REQUEST_METHOD']??'GET')!=='GET'||($_SERVER['HTTP_DNT']??'')==='1')return;$ua=strtolower((string)($_SERVER['HTTP_USER_AGENT']??''));if($ua===''||preg_match('/bot|spider|crawler|slurp|bingpreview|facebookexternalhit|headless|monitor|uptime/',$ua))return;$uri=(string)($_SERVER['REQUEST_URI']??'/');$path=(string)(parse_url($uri,PHP_URL_PATH)?:'/');$admin='/'.trim((string)App::config('app.admin_path','yonetim'),'/');foreach([$admin,'/assets/','/uploads/','/install','/sitemap.xml','/robots.txt','/favicon.ico'] as $skip)if($path===$skip||str_starts_with($path,rtrim($skip,'/').'/'))return;$path=mb_substr('/'.ltrim(rawurldecode($path),'/'),0,255);$ref='';$referer=(string)($_SERVER['HTTP_REFERER']??'');if($referer!==''){$host=strtolower((string)(parse_url($referer,PHP_URL_HOST)??''));$appHost=strtolower((string)(parse_url((string)App::config('app.url',''),PHP_URL_HOST)??''));if($host!==''&&$host!==$appHost)$ref=mb_substr($host,0,190);}App::db()->execute('INSERT INTO analytics_daily(day,path,referrer_host,pageviews,created_at,updated_at) VALUES(CURDATE(),?,?,1,NOW(),NOW()) ON DUPLICATE KEY UPDATE pageviews=pageviews+1,updated_at=NOW()',[$path,$ref]);
    }
}

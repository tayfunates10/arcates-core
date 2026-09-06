<?php
declare(strict_types=1);
// Runtime probe 1: pure-logic units, no DB needed
define('ARCATES_ROOT',dirname(__DIR__,2));
spl_autoload_register(static function(string $c): void{
  if(!str_starts_with($c,'Arcates\\'))return;
  $f=ARCATES_ROOT.'/app/'.str_replace('\\','/',substr($c,8)).'.php';
  if(is_file($f))require $f;
});
$GLOBALS['arcates_config']=require ARCATES_ROOT.'/config.example.php';

function t(string $name, callable $fn): void {
  try { $r=$fn(); echo sprintf("%-58s %s\n",$name,$r); }
  catch(\Throwable $e){ echo sprintf("%-58s THROW %s: %s\n",$name,$e::class,$e->getMessage()); }
}

echo "########## A. Text::slug — çok dilli slug üretimi ##########\n";
use Arcates\Core\Text;
foreach ([
  'TR' => 'Şirket Hakkımızda İletişim',
  'EN' => 'About Our Company',
  'DE' => 'Über Größe und Qualität',
  'AR-1'=> 'من نحن',
  'AR-2'=> 'اتصل بنا',
  'AR-3'=> 'خدماتنا',
  'RU'  => 'О компании',
  'EMOJI'=> '🎉 Kampanya',
  'NUM' => '2026 Fiyat Listesi',
] as $k=>$v) { t("slug[$k] ".$v, fn()=>"'".Text::slug($v)."'"); }

echo "\n########## B. Router — metot ve eşleşme davranışı ##########\n";
use Arcates\Core\Router;
$mk=function(): Router{ $r=new Router(); $r->get('/',fn()=>'HOME'); $r->get('/blog/{locale}',fn($l)=>"BLOG:$l"); $r->get('/blog/{locale}/{slug}',fn($l,$s)=>"POST:$l/$s"); $r->post('/iletisim',fn()=>'CONTACT'); return $r; };
foreach ([['GET','/'],['HEAD','/'],['OPTIONS','/'],['get','/'],['GET','//'],['GET','/blog/tr'],['GET','/blog/tr/merhaba'],['GET','/blog/tr/a/b'],['GET','/blog/%2e%2e/x'],['GET','/?a=1'],['GET','/blog/tr#frag']] as [$m,$u]) {
  t("dispatch $m $u", function() use($mk,$m,$u){ ob_start(); $res=$mk()->dispatch($m,$u); $out=ob_get_clean(); return $res===null?('404 (gövde '.strlen($out).' bayt)'):var_export($res,true); });
}
t('dispatch GET bozuk-UTF8 /blog/'."\xC3\x28", function() use($mk){ ob_start(); $res=$mk()->dispatch('GET',"/blog/\xC3\x28"); $out=ob_get_clean(); return $res===null?('404 (gövde '.strlen($out).' bayt)'):var_export($res,true); });

echo "\n########## C. Upload::validateCandidate ##########\n";
use Arcates\Core\Upload;
foreach ([
  ['shell.php','image/jpeg',1000],
  ['shell.jpg','image/jpeg',1000],
  ['shell.jpg.php','image/jpeg',1000],
  ['shell.php.jpg','image/jpeg',1000],
  ['shell.JPG','image/jpeg',1000],
  ['shell.svg','image/svg+xml',1000],
  ['a.jpg','image/jpeg',0],
  ['a.jpg','image/jpeg',5242881],
  ['a.jpg','text/html',1000],
] as [$n,$m,$s]) { t("validateCandidate($n,$m,$s)", fn()=>Upload::validateCandidate($n,$s,$m,5242880)?'KABUL':'RED'); }

echo "\n########## D. UblValidator — XXE / DTD / kodlama ##########\n";
use Arcates\Einvoice\UblValidator;
$inv='<Invoice xmlns="urn:oasis:names:specification:ubl:schema:xsd:Invoice-2"><ProfileID>TICARIFATURA</ProfileID><DocumentCurrencyCode>TRY</DocumentCurrencyCode><UUID>x</UUID></Invoice>';
t('geçerli UBL', fn()=>UblValidator::inspect($inv)['profile_id']);
t('DOCTYPE reddi', fn()=>UblValidator::inspect('<!DOCTYPE f [<!ENTITY x "y">]>'.$inv)?'KABUL(!!)':'?');
t('DOCTYPE boşluklu', fn()=>UblValidator::inspect('<!  DOCTYPE f>'.$inv)?'KABUL(!!)':'?');
$u16=mb_convert_encoding('<?xml version="1.0" encoding="UTF-16"?><!DOCTYPE f [<!ENTITY xx "GIZLI">]>'.str_replace('<UUID>x</UUID>','<UUID>&xx;</UUID>',$inv),'UTF-16','UTF-8');
t('UTF-16 DOCTYPE bypass', function() use($u16){ $r=UblValidator::inspect($u16); return 'KABUL(!!) uuid='.var_export($r['uuid'],true); });
$bomb='<?xml version="1.0"?><!DOCTYPE lolz [<!ENTITY lol "lol"><!ENTITY lol2 "&lol;&lol;&lol;">]><Invoice><ProfileID>&lol2;</ProfileID></Invoice>';
t('billion-laughs (UTF-8)', fn()=>UblValidator::inspect($bomb)?'KABUL(!!)':'?');
t('kök eleman Invoice degil', fn()=>UblValidator::inspect(str_replace('Invoice','Order',$inv))?'KABUL(!!)':'?');

echo "\n########## E. TemplateRenderer — muhasebe şablon motoru ##########\n";
use Arcates\Accounting\TemplateRenderer;
$order=['id'=>7,'total'=>'100.00','customer_name'=>'Ali','vkn'=>'1234567890','payment_ref'=>'SECRET-TOKEN-123'];
$items=[['sku'=>'A1','qty'=>2],['sku'=>'B2','qty'=>1]];
t('basit alan', fn()=>json_encode(TemplateRenderer::render(['no'=>'{{order.id}}'],$order,$items)));
t('$each items', fn()=>json_encode(TemplateRenderer::render(['lines'=>['$each'=>'items','template'=>['s'=>'{{item.sku}}']]],$order,$items)));
t('bilinmeyen alan', fn()=>json_encode(TemplateRenderer::render(['x'=>'{{order.yok}}'],$order,$items)));
t('hassas alan sizintisi', fn()=>json_encode(TemplateRenderer::render(['x'=>'{{order.payment_ref}}'],$order,$items)));
t('anahtar icinde placeholder', fn()=>json_encode(TemplateRenderer::render(['{{order.id}}'=>'v'],$order,$items)));
t('ic ice $each (nested)', fn()=>json_encode(TemplateRenderer::render(['a'=>['$each'=>'items','template'=>['b'=>['$each'=>'items','template'=>'{{item.sku}}']]]],$order,$items)));
$deep='{{order.id}}'; $node=$deep; for($i=0;$i<50000;$i++){$node=['k'=>$node];}
t('50k derinlikte şablon (recursion)', function() use($node,$order,$items){ $r=TemplateRenderer::render($node,$order,$items); return 'KABUL derinlik islendi'; });

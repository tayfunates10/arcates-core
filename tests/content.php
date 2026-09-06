<?php
declare(strict_types=1);
require dirname(__DIR__).'/bootstrap.php';
use Arcates\Core\Locale;use Arcates\Core\Router;use Arcates\Core\Text;use Arcates\Core\Upload;
$fail=[];$assert=static function(bool $c,string $m)use(&$fail){if(!$c)$fail[]=$m;};
$assert(Text::slug('Çeşme Şöleni IĞDIR')==='cesme-soleni-igdir','Türkçe slug başarısız.');$assert(Locale::rtl('ar')&& !Locale::rtl('tr'),'RTL kontrolü başarısız.');$assert(!Upload::validateCandidate('shell.php',10,'application/x-php',1024),'PHP upload reddedilmedi.');$assert(Upload::validateCandidate('foto.jpg',10,'image/jpeg',1024),'Geçerli görsel reddedildi.');
$r=new Router();$captured=[];$r->get('/{locale}/{slug}',static function(string $l,string $s)use(&$captured){$captured=[$l,$s];});ob_start();$r->dispatch('GET','/tr/hakkimizda');ob_end_clean();$assert($captured===['tr','hakkimizda'],'Dinamik router başarısız.');
$src=(string)file_get_contents(dirname(__DIR__).'/app/Controllers/PageAdminController.php');$assert(str_contains($src,'Csrf::requireValid'),'Sayfa POST CSRF eksik.');if($fail){fwrite(STDERR,implode(PHP_EOL,$fail).PHP_EOL);exit(1);}echo "İçerik testleri: OK\n";

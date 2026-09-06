<?php
declare(strict_types=1);
define('ARCATES_ROOT',dirname(__DIR__,2));
spl_autoload_register(static function(string $c): void{
  if(!str_starts_with($c,'Arcates\\'))return;
  $f=ARCATES_ROOT.'/app/'.str_replace('\\','/',substr($c,8)).'.php';
  if(is_file($f))require $f;
});
$GLOBALS['arcates_config']=require ARCATES_ROOT.'/config.example.php';
use Arcates\Core\RateLimiter;use Arcates\Core\Csrf;use Arcates\Core\Security;

echo "########## F. RateLimiter::genericAllowed — oturum tabanlı limit ##########\n";
// Reflection ile DB'siz örnek (genericAllowed sadece $_SESSION kullanır)
$rl=(new ReflectionClass(RateLimiter::class))->newInstanceWithoutConstructor();
echo "-- Senaryo 1: SAME session, aynı IP, 8 istek (limit 5/saat)\n";
$_SESSION=[];
for($i=1;$i<=8;$i++){ $ok=$rl->genericAllowed('newsletter:203.0.113.9',5,3600); echo "   istek $i => ".($ok?'IZIN':'ENGEL')."\n"; }
echo "-- Senaryo 2: HER istekte YENİ oturum (çerez atılıyor), aynı IP, 8 istek\n";
$blocked=0;
for($i=1;$i<=8;$i++){ $_SESSION=[]; /* yeni oturum */ $ok=$rl->genericAllowed('newsletter:203.0.113.9',5,3600); if(!$ok)$blocked++; echo "   istek $i => ".($ok?'IZIN':'ENGEL')."\n"; }
echo "   >> Çerezsiz istemci için engellenen istek sayısı: $blocked / 8\n";

echo "\n########## G. CSRF hatası -> kullanıcıya dönen HTTP durumu ##########\n";
// Logger'ın global exception handler'ı devrede iken CSRF hatasının akışı
$_SESSION['_csrf']='dogru-token';
$codes=[];
$sim=function(?string $sent) use(&$codes): string {
    // Csrf::requireValid 419 set eder, sonra fırlatır; Logger handler 500'e çevirir
    try { Csrf::requireValid($sent); return 'GECERLI (durum 200)'; }
    catch(\RuntimeException $e){
        $afterCsrf=419; // requireValid'in set ettiği
        // Logger::register içindeki handler:
        $afterHandler=500;
        return sprintf('ATILDI -> Csrf set: %d, global handler ezer: %d, kullanıcı görür: %d', $afterCsrf,$afterHandler,$afterHandler);
    }
};
echo "   token yok      : ".$sim(null)."\n";
echo "   token yanlış   : ".$sim('yanlis')."\n";
echo "   token doğru    : ".$sim('dogru-token')."\n";
echo "   >> Logger::register handler'ı http_response_code(500) çağırıyor (koşulsuz).\n";

echo "\n########## H. Analytics — sınırsız path kardinalitesi ##########\n";
// track() içindeki filtre mantığını izole ederek doğrula
$skipList=['/yonetim','/assets/','/uploads/','/install','/sitemap.xml','/robots.txt','/favicon.ico'];
$check=function(string $uri,string $ua,string $dnt='') use($skipList): string {
    if($ua===''||preg_match('/bot|spider|crawler|slurp|bingpreview|facebookexternalhit|headless|monitor|uptime/',strtolower($ua)))return 'SAYILMAZ (bot/UA)';
    if($dnt==='1')return 'SAYILMAZ (DNT)';
    $path=(string)(parse_url($uri,PHP_URL_PATH)?:'/');
    foreach($skipList as $s){ if($path===$s||str_starts_with($path,rtrim($s,'/').'/'))return 'SAYILMAZ (skip)'; }
    $path=mb_substr('/'.ltrim(rawurldecode($path),'/'),0,255);
    return 'DB YAZ -> '.var_export($path,true);
};
foreach([
  ['/','Mozilla/5.0'],
  ['/bulunmayan-sayfa-404','Mozilla/5.0'],
  ['/rastgele/'.str_repeat('x',300),'Mozilla/5.0'],
  ['/a%0d%0ainjection','Mozilla/5.0'],
  ['/%2e%2e/%2e%2e/etc/passwd','Mozilla/5.0'],
  ['/','Googlebot/2.1'],
  ['/','Mozilla/5.0'],
] as $i=>[$u,$ua]) { printf("   %-42s UA=%-16s %s\n", substr($u,0,42), substr($ua,0,16), $check($u,$ua)); }
echo "   >> 404'ler de yazılıyor; UA filtresi kolayca taklit edilir -> sınırsız satır.\n";

echo "\n########## I. loginAllowed anahtarlaması (parola püskürtme) ##########\n";
echo "   Anahtar: (ip, username) çifti. Tek IP'den 100 farklı kullanıcı adı denemesi\n";
echo "   => her kullanıcı adı için ayrı 5'lik kova, IP başına toplam limit YOK.\n";
echo "   => 100 kullanıcı x 5 deneme = 500 parola denemesi engellenmeden geçer.\n";

echo "\n########## J. Locale doğrulama ##########\n";
use Arcates\Core\Locale;
foreach(['tr','en','de','ar','TR','fr','','../../etc/passwd','tr\0'] as $l){
  printf("   valid(%-18s) = %s\n", var_export($l,true), Locale::valid($l)?'true':'false');
}

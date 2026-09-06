<?php
declare(strict_types=1);
echo "########## R. KRİTİK: install.lock hiç yazılamıyor ##########\n";
$fake=sys_get_temp_dir().'/arcates-probe-'.getmypid();
@mkdir($fake,0755,true);
// Depoda 'install/' dizini YOK; Installer::run onu oluşturmuyor.
echo "1) ARCATES_ROOT/install dizini var mı?  : ".(is_dir($fake.'/install')?'EVET':'HAYIR')."\n";
$r=@file_put_contents($fake.'/install/install.lock',date('c')."\n",LOCK_EX);
echo "2) file_put_contents(.../install/install.lock) dönüşü: ".var_export($r,true)." (false = YAZILAMADI)\n";
echo "   PHP uyarısı: ".(error_get_last()['message']??'-')."\n";
echo "3) Installer::locked() -> is_file(.../install/install.lock) = ".(is_file($fake.'/install/install.lock')?'true':'false')."\n";
echo "   >> locked() DAİMA false döner. Installer::run() dönüş değerini kontrol etmiyor,\n";
echo "      bu yüzden kurulum 'başarılı' raporlanır ama kilit hiç oluşmaz.\n";

echo "\n-- İstismar zinciri --\n";
echo "   a) GET  /install          -> locked()=false, form + geçerli CSRF token servis edilir\n";
echo "   b) POST /install          -> locked()=false, CSRF geçerli (a'dan alındı)\n";
echo "   c) Installer::run()       -> schema.sql tamamı 'CREATE TABLE IF NOT EXISTS' => hata yok,\n";
echo "                                Migrator::run() takipli => no-op\n";
echo "   d) INSERT INTO users(...,'admin',1) -> saldırganın e-postası benzersiz => BAŞARILI\n";
echo "   e) /yonetim/giris         -> saldırgan ADMIN olarak oturum açar\n";
echo "   >> Kimlik doğrulamasız uzaktan admin hesabı oluşturma = tam devralma.\n";
echo "   >> README: 'işlem sonrası install/install.lock tekrar kurulumu engeller' -> bu kontrol ÇALIŞMIYOR.\n";

echo "\n########## S. Analitik hatası tüm siteyi düşürüyor ##########\n";
echo "   public/index.php: (new AnalyticsService())->track();  <-- try/catch YOK, dispatch'ten ÖNCE\n";
echo "   AnalyticsService::track(): App::db()->execute('INSERT INTO analytics_daily ...')  <-- try/catch YOK\n";
echo "   App::db(): new PDO(..., ERRMODE_EXCEPTION)  <-- erişilemezse PDOException fırlatır\n";
echo "   Senaryolar:\n";
echo "     - DB kapalı                       -> HER GET isteği 500\n";
echo "     - migrate.php çalıştırılmadı      -> analytics_daily yok -> HER GET isteği 500\n";
echo "   >> İsteğe bağlı bir özellik (ziyaretçi sayacı) tüm siteyi düşürüyor; kademeli\n";
echo "      bozulma (graceful degradation) yok.\n";

echo "\n########## T. Dağıtım: koruma yalnız .htaccess'te ##########\n";
foreach(['Options -Indexes'=>'dizin listeleme','RewriteRule ^config\.php$'=>'config.php erişimi',
         'uploads/.*\.(php'=>'upload PHP çalıştırma','(^|/)\.git'=>'.git ifşası'] as $rule=>$desc){
  printf("   %-28s : yalnızca Apache mod_rewrite ile korunur\n",$desc);
}
echo "   >> Nginx/LiteSpeed dağıtımında bu kuralların HİÇBİRİ uygulanmaz:\n";
echo "      config.php (DB şifresi, iyzico/OpenAI anahtarları) indirilebilir hale gelir.\n";
echo "   >> Depoda örnek Nginx yapılandırması yok.\n";

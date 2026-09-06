<?php
declare(strict_types=1);
$root=dirname(__DIR__);$fail=[];
$read=static fn(string $p): string=>(string)@file_get_contents($root.'/'.$p);
$security=$read('app/Core/Security.php');foreach(['httponly','samesite','Lax','secure','htmlspecialchars','ENT_QUOTES'] as $n)if(!str_contains($security,$n))$fail[]='Oturum/çıktı güvenliği eksik: '.$n;
$auth=$read('app/Core/Auth.php');foreach(['password_verify','session_regenerate_id(true)','session_destroy','loginAllowed','recordLogin'] as $n)if(!str_contains($auth,$n))$fail[]='Auth güvenliği eksik: '.$n;
$logger=$read('app/Core/Logger.php');if(!str_contains($logger,"ini_set('display_errors', $debug ? '1' : '0')"))$fail[]='Canlı hata gizleme sözleşmesi eksik.';
$upload=$read('app/Core/Upload.php');foreach(['EXTENSIONS','MIMES','FILEINFO_MIME_TYPE','max_upload_bytes','Security::randomToken','imagewebp'] as $n)if(!str_contains($upload,$n))$fail[]='Upload güvenliği eksik: '.$n;$uht=$read('uploads/.htaccess');foreach(['FilesMatch','php|phtml|phar','Require all denied'] as $n)if(!str_contains($uht,$n))$fail[]='uploads/.htaccess eksik: '.$n;
$rootHt=$read('.htaccess');foreach(['Options -Indexes','\\.git','config\\.php','uploads/.*\\.(php|phtml|phar'] as $n)if(!str_contains($rootHt,$n))$fail[]='Kök .htaccess koruması eksik: '.$n;
$installer=$read('app/Services/Installer.php').$read('app/Controllers/InstallController.php');foreach(['install/install.lock','Installer::locked()','http_response_code(404)','password_hash','Csrf::requireValid'] as $n)if(!str_contains($installer,$n))$fail[]='Kurulum güvenliği eksik: '.$n;
$contact=$read('app/Controllers/ContactController.php');foreach(['honeypot','website','genericAllowed','kvkk_consent','Csrf::requireValid'] as $n)if(!str_contains($contact,$n)&&$n!=='honeypot')$fail[]='Form/KVKK güvenliği eksik: '.$n;if(str_contains($contact,'kvkk_consent" checked')||str_contains($contact,"kvkk_consent' checked"))$fail[]='KVKK onayı önceden işaretli olamaz.';
if(!is_file($root.'/scripts/purge_forms.php'))$fail[]='Form saklama temizleme scripti eksik.';if(!is_file($root.'/scripts/backup.php'))$fail[]='Yedekleme scripti eksik.';
$ignore=$read('.gitignore');foreach(['config.php','/uploads/*','/logs/*','.DS_Store'] as $n)if(!str_contains($ignore,$n))$fail[]='.gitignore eksik: '.$n;if(is_file($root.'/config.php'))$fail[]='config.php repoda bulunmamalı.';
$config=$read('config.example.php');foreach(["'debug' => false","'force_https' => true"] as $n)if(!str_contains($config,$n))$fail[]='Production config varsayımı eksik: '.$n;
$router=$read('app/Core/Router.php');if(!str_contains($router,'http_response_code(404)'))$fail[]='Router 404 davranışı eksik.';
$theme=$read('themes/default/page.php');foreach(['og:title','meta name="description"','canonical','cookie-notice'] as $n)if(!str_contains($theme,$n))$fail[]='Tema SEO/KVKK öğesi eksik: '.$n;
foreach(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root,FilesystemIterator::SKIP_DOTS)) as $file){if(!$file->isFile()||strtolower($file->getExtension())!=='php'||str_contains($file->getPathname(),DIRECTORY_SEPARATOR.'vendor'.DIRECTORY_SEPARATOR))continue;$lines=count(file($file->getPathname(),FILE_IGNORE_NEW_LINES)?:[]);if($lines>400)$fail[]='400 satır sınırı aşıldı: '.str_replace($root.'/','',$file->getPathname()).' ('.$lines.')';}
$run=$read('tests/run.php');foreach(['core.php','content.php','conversion.php','reservation.php','commerce.php','real_estate.php','qr_menu.php','shipment.php','service_pricing.php','branches.php','carriers.php','marketplace.php','e_document.php','accounting.php','analytics.php','newsletter.php','assistant.php'] as $n)if(!str_contains($run,$n))$fail[]='Regresyon kapısında test eksik: '.$n;
if($fail){fwrite(STDERR,implode(PHP_EOL,$fail).PHP_EOL);exit(1);}echo "Final güvenlik kontrolü: OK\n";

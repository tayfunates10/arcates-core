<?php
declare(strict_types=1);
namespace Arcates\Controllers;
use Arcates\Core\App;use Arcates\Core\Locale;use Arcates\Core\Security;use Arcates\Core\Theme;
final class PublicPageController
{
 public function show(string $locale,string $slug): void{if(!Locale::valid($locale)){http_response_code(404);return;}$page=App::db()->fetch('SELECT * FROM pages WHERE locale=? AND slug=? AND status=? LIMIT 1',[$locale,$slug,'published']);if(!$page){http_response_code(404);echo 'Sayfa bulunamadı.';return;}$page['body_html']=nl2br(Security::escape((string)$page['body']));Theme::page($page,$locale);}
}

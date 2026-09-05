<?php
declare(strict_types=1);
namespace Arcates\Controllers;
use Arcates\Core\App;use Arcates\Core\Security;
final class SeoController
{
 public function sitemap(): void{header('Content-Type: application/xml; charset=UTF-8');$base=rtrim((string)App::config('app.url',''),'/');echo '<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';foreach(App::db()->fetchAll('SELECT locale,slug,updated_at FROM pages WHERE status=?',['published']) as $p){echo '<url><loc>'.Security::escape($base.'/'.$p['locale'].'/'.$p['slug']).'</loc><lastmod>'.date('c',strtotime((string)$p['updated_at'])).'</lastmod></url>';}echo '</urlset>';}
 public function robots(): void{header('Content-Type: text/plain; charset=UTF-8');echo "User-agent: *\nAllow: /\nDisallow: /".App::config('app.admin_path','yonetim')."/\nDisallow: /install\nSitemap: ".rtrim((string)App::config('app.url',''),'/')."/sitemap.xml\n";}
}

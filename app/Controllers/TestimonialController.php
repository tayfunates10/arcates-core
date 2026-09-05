<?php
declare(strict_types=1);
namespace Arcates\Controllers;
use Arcates\Core\App;use Arcates\Core\Security;
final class TestimonialController
{
 public function index(): void{echo '<!doctype html><html lang="tr"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Referanslar</title><link rel="stylesheet" href="/assets/css/theme.css"><body><main class="container"><h1>Referanslar</h1>';foreach(App::db()->fetchAll('SELECT name,company,quote FROM testimonials WHERE is_published=1 ORDER BY sort_order,id') as $t)echo '<blockquote><p>'.Security::escape($t['quote']).'</p><footer>'.Security::escape($t['name']).($t['company']?' · '.Security::escape($t['company']):'').'</footer></blockquote>';echo '</main></body></html>';}
}

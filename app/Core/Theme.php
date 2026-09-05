<?php
declare(strict_types=1);
namespace Arcates\Core;
final class Theme
{
    public static function page(array $page,string $locale): void
    {
        $dir=Locale::rtl($locale)?'rtl':'ltr';$title=Security::escape($page['meta_title']?:$page['title']);$description=Security::escape($page['meta_description']??'');$canonical=rtrim((string)App::config('app.url',''),'/').'/'.$locale.'/'.rawurlencode((string)$page['slug']);$template=ARCATES_ROOT.'/themes/default/page.php';require $template;
    }
}

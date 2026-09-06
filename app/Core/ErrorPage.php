<?php
declare(strict_types=1);

namespace Arcates\Core;

final class ErrorPage
{
    public static function render(int $status, ?string $debugMessage = null): void
    {
        $locale = Translator::requestLocale();
        $dir = Locale::rtl($locale) ? 'rtl' : 'ltr';
        $titleKey = match ($status) {
            404 => 'error_404_title',
            419 => 'error_419_title',
            default => 'error_500_title',
        };
        $messageKey = match ($status) {
            404 => 'error_404_message',
            419 => 'error_419_message',
            default => 'error_500_message',
        };
        $title = Security::escape(Translator::t($titleKey, $locale));
        $message = Security::escape($debugMessage ?: Translator::t($messageKey, $locale));
        $home = Security::escape(Translator::t('home_link', $locale));

        if (!headers_sent()) {
            header('Content-Type: text/html; charset=UTF-8');
        }
        http_response_code($status);
        echo '<!doctype html><html lang="' . Security::escape($locale) . '" dir="' . $dir . '">'
            . '<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">'
            . '<title>' . $title . '</title><link rel="stylesheet" href="/assets/css/theme.css"></head>'
            . '<body><main class="container"><section class="card"><h1>' . $title . '</h1>'
            . '<p>' . $message . '</p><p><a href="/">' . $home . '</a></p></section></main></body></html>';
    }
}

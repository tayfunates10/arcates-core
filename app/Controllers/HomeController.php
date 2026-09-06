<?php
declare(strict_types=1);

namespace Arcates\Controllers;

use Arcates\Core\AssistantWidget;use Arcates\Core\Security;

final class HomeController
{
    public function index(): void
    {
        $name=Security::escape((string)($GLOBALS['arcates_config']['app']['name']??'Arcates Core'));echo '<!doctype html><html lang="tr"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>'.$name.'</title><link rel="stylesheet" href="/assets/css/theme.css"><body><main class="container"><h1>'.$name.'</h1><p>Sistem hazır.</p></main>'.AssistantWidget::render('tr').'</body></html>';
    }
}

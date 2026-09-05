<?php
declare(strict_types=1);

namespace Arcates\Controllers;

use Arcates\Core\Security;

final class HomeController
{
    public function index(): void
    {
        $name = Security::escape((string)($GLOBALS['arcates_config']['app']['name'] ?? 'Arcates Core'));
        echo '<!doctype html><html lang="tr"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>' . $name . '</title><body><main><h1>' . $name . '</h1><p>Sistem hazır.</p></main></body></html>';
    }
}

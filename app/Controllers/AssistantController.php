<?php
declare(strict_types=1);

namespace Arcates\Controllers;

use Arcates\Core\App;
use Arcates\Core\Csrf;
use Arcates\Core\Logger;
use Arcates\Core\RateLimiter;
use Arcates\Core\Security;
use Arcates\Services\SiteAssistantService;

final class AssistantController
{
    public function ask(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        try {
            Csrf::requireValid($_POST['_csrf'] ?? null);
            $limiter = new RateLimiter(App::db());
            $bucket = 'site-assistant:' . Security::clientIp();
            if (!$limiter->genericAllowed($bucket, 12, 300)) {
                http_response_code(429);
                echo json_encode(
                    ['error' => 'Çok sık soru gönderildi. Lütfen kısa süre sonra tekrar deneyin.'],
                    JSON_UNESCAPED_UNICODE
                );
                return;
            }

            $locale = trim((string) ($_POST['locale'] ?? 'tr'));
            $question = trim((string) ($_POST['question'] ?? ''));
            $answer = (new SiteAssistantService())->answer($locale, $question);
            echo json_encode(['answer' => $answer], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        } catch (\InvalidArgumentException $e) {
            http_response_code(422);
            echo json_encode(['error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
        } catch (\Throwable $e) {
            $current = http_response_code();
            if ($current === 419) {
                echo json_encode(
                    ['error' => 'Oturum doğrulaması geçersiz. Sayfayı yenileyip tekrar deneyin.'],
                    JSON_UNESCAPED_UNICODE
                );
                return;
            }
            Logger::error('Assistant request failed', [
                'type' => $e::class,
                'message' => $e->getMessage(),
            ]);
            http_response_code(503);
            echo json_encode(
                ['error' => 'AI asistanı şu anda kullanılamıyor.'],
                JSON_UNESCAPED_UNICODE
            );
        }
    }
}

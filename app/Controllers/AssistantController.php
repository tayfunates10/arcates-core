<?php
declare(strict_types=1);
namespace Arcates\Controllers;
use Arcates\Core\App;use Arcates\Core\Csrf;use Arcates\Core\RateLimiter;use Arcates\Services\SiteAssistantService;

final class AssistantController
{
    public function ask(): void
    {
        header('Content-Type: application/json; charset=utf-8');try{Csrf::requireValid($_POST['_csrf']??null);$limiter=new RateLimiter(App::db());if(!$limiter->genericAllowed('site-assistant',12,300)){http_response_code(429);echo json_encode(['error'=>'Çok sık soru gönderildi. Lütfen kısa süre sonra tekrar deneyin.'],JSON_UNESCAPED_UNICODE);return;}$locale=trim((string)($_POST['locale']??'tr'));$question=trim((string)($_POST['question']??''));$answer=(new SiteAssistantService())->answer($locale,$question);echo json_encode(['answer'=>$answer],JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR);}catch(\Throwable $e){$code=http_response_code();if($code<400)$code=str_contains($e->getMessage(),'yapılandırılmamış')?503:422;http_response_code($code);echo json_encode(['error'=>$code===503?'AI asistanı şu anda kullanılamıyor.':$e->getMessage()],JSON_UNESCAPED_UNICODE);}
    }
}

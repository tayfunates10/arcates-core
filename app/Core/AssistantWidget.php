<?php
declare(strict_types=1);
namespace Arcates\Core;

final class AssistantWidget
{
    public static function render(string $locale): string
    {
        if(!(bool)App::config('integrations.ai.enabled',false))return '';$copy=['tr'=>['button'=>'Yardımcı','title'=>'Site Asistanı','placeholder'=>'Sorunuzu yazın…','send'=>'Gönder','hello'=>'Merhaba! Site içeriği hakkında yardımcı olabilirim.'],'en'=>['button'=>'Assistant','title'=>'Site Assistant','placeholder'=>'Ask a question…','send'=>'Send','hello'=>'Hello! I can help with information published on this site.'],'de'=>['button'=>'Assistent','title'=>'Website-Assistent','placeholder'=>'Frage eingeben…','send'=>'Senden','hello'=>'Hallo! Ich helfe mit veröffentlichten Informationen dieser Website.'],'ar'=>['button'=>'المساعد','title'=>'مساعد الموقع','placeholder'=>'اكتب سؤالك…','send'=>'إرسال','hello'=>'مرحبًا! يمكنني المساعدة بالمعلومات المنشورة على هذا الموقع.']];$c=$copy[$locale]??$copy['tr'];$e=static fn(string $v): string=>Security::escape($v);return '<div class="ai-assistant" data-ai-assistant data-locale="'.$e($locale).'" data-csrf="'.$e(Csrf::token()).'"><button class="ai-assistant__toggle" type="button" aria-expanded="false">'.$e($c['button']).'</button><section class="ai-assistant__panel" hidden aria-label="'.$e($c['title']).'"><header><strong>'.$e($c['title']).'</strong><button type="button" data-ai-close aria-label="Kapat">×</button></header><div class="ai-assistant__messages" aria-live="polite"><p class="ai-assistant__bot">'.$e($c['hello']).'</p></div><form data-ai-form><label class="sr-only" for="ai-question">'.$e($c['placeholder']).'</label><textarea id="ai-question" name="question" maxlength="1000" required placeholder="'.$e($c['placeholder']).'"></textarea><button type="submit">'.$e($c['send']).'</button></form></section></div><script src="/assets/js/assistant.js" defer></script>';
    }
}

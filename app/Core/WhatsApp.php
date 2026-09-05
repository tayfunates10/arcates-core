<?php
declare(strict_types=1);
namespace Arcates\Core;
final class WhatsApp
{
    public static function link(string $message=''): ?string
    {
        $phone=preg_replace('/\D+/','',(string)App::config('contact.whatsapp_phone',''))??'';
        if($phone==='')return null;
        $message=$message!==''?$message:(string)App::config('contact.whatsapp_message','Merhaba, bilgi almak istiyorum.');
        return 'https://wa.me/'.$phone.'?text='.rawurlencode($message);
    }
    public static function button(string $message=''): string
    {
        $link=self::link($message);if($link===null)return '';
        return '<a class="whatsapp-float" rel="noopener noreferrer" target="_blank" href="'.Security::escape($link).'" aria-label="WhatsApp ile iletişim">WhatsApp</a>';
    }
}

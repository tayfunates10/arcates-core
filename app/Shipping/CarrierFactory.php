<?php
declare(strict_types=1);
namespace Arcates\Shipping;
use Arcates\Core\App;
final class CarrierFactory
{
    public static function make(?string $provider=null): CarrierGateway
    {
        $provider=strtolower(trim($provider??(string)App::config('integrations.shipping.provider','')));return match($provider){'mng'=>new MngGateway(),'aras'=>new ArasGateway(),'yurtici'=>new YurticiGateway(),default=>throw new \RuntimeException('Kargo sağlayıcısı yapılandırılmadı veya desteklenmiyor.')};
    }
    public static function supported(): array{return ['mng','aras','yurtici'];}
}

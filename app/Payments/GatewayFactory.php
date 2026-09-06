<?php
declare(strict_types=1);
namespace Arcates\Payments;
use Arcates\Core\App;
final class GatewayFactory
{
    public static function make(): PaymentGateway
    {
        return match((string)App::config('integrations.payment_provider','')){'iyzico'=>new IyzicoGateway(),default=>throw new \RuntimeException('Desteklenen resmi ödeme sağlayıcısı yapılandırılmadı.')};
    }
}

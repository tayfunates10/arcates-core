<?php
declare(strict_types=1);
namespace Arcates\Accounting;

final class AccountingFactory
{
    public static function make(string $provider): AccountingGateway
    {
        return match($provider){'logo'=>new LogoNetsisGateway(),'mikro'=>new MikroGateway(),'parasut'=>new ParasutGateway(),default=>throw new \RuntimeException('Desteklenmeyen muhasebe sağlayıcısı.')};
    }
}

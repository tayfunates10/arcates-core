<?php
declare(strict_types=1);
namespace Arcates\Einvoice;
use Arcates\Core\App;

final class EinvoiceFactory
{
    public static function make(): EinvoiceGateway
    {
        return match((string)App::config('integrations.einvoice.provider','uyumsoft')){'uyumsoft'=>new UyumsoftGateway(),default=>throw new \InvalidArgumentException('Desteklenmeyen e-Belge sağlayıcısı.')};
    }
}

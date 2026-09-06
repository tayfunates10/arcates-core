<?php
declare(strict_types=1);
namespace Arcates\Marketplace;

final class MarketplaceFactory
{
    public static function make(string $provider): MarketplaceGateway
    {
        return match($provider){'trendyol'=>new TrendyolGateway(),'hepsiburada'=>new HepsiburadaGateway(),default=>throw new \InvalidArgumentException('Desteklenmeyen pazaryeri: '.$provider)};
    }
}

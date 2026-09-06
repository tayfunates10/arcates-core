<?php
declare(strict_types=1);
namespace Arcates\Shipping;
interface CarrierGateway
{
    public function create(array $order,array $package): array;
    public function track(string $reference): array;
    public function label(string $reference): array;
}

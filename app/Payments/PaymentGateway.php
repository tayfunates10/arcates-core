<?php
declare(strict_types=1);
namespace Arcates\Payments;
interface PaymentGateway
{
    public function initialize(array $order,array $items,string $callbackUrl): array;
    public function retrieve(string $token,string $conversationId): array;
}

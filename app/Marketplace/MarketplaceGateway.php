<?php
declare(strict_types=1);
namespace Arcates\Marketplace;

interface MarketplaceGateway
{
    public function provider(): string;
    public function maxBatchSize(): int;
    /** @param array<int,array<string,mixed>> $items */
    public function submit(array $items): array;
    public function check(string $externalBatchId): array;
}

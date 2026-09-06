<?php
declare(strict_types=1);
namespace Arcates\Accounting;

interface AccountingGateway
{
    public function provider(): string;
    /** @return array{external_id:?string,response:string} */
    public function send(array $payload): array;
}

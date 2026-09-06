<?php
declare(strict_types=1);
namespace Arcates\Einvoice;

interface EinvoiceGateway
{
    public function provider(): string;
    public function isEInvoiceUser(string $vknTckn,string $alias=''): bool;
    public function send(string $ublXml,array $meta): array;
    public function status(string $externalUuid): array;
}

<?php
declare(strict_types=1);

namespace Arcates\Services;

use Arcates\Accounting\AccountingFactory;
use Arcates\Accounting\TemplateRenderer;
use Arcates\Accounting\UncertainTransferException;
use Arcates\Core\App;
use Arcates\Core\Database;

final class AccountingExportService
{
    public function prepare(int $orderId, int $profileId): int
    {
        $order = App::db()->fetch(
            'SELECT id,public_code,customer_name,identity_number,email,phone,address,city,postal_code,'
            . 'subtotal,discount_total,shipping_total,grand_total,coupon_code,payment_status,status,created_at '
            . 'FROM orders WHERE id=?',
            [$orderId]
        );
        if (!$order) {
            throw new \RuntimeException('Sipariş bulunamadı.');
        }
        if ((string) $order['payment_status'] !== 'paid' || (string) $order['status'] === 'cancelled') {
            throw new \RuntimeException(
                'Muhasebe aktarımı yalnız ödenmiş ve iptal edilmemiş sipariş için hazırlanabilir.'
            );
        }

        $profile = App::db()->fetch(
            'SELECT * FROM accounting_profiles WHERE id=? AND is_active=1',
            [$profileId]
        );
        if (!$profile) {
            throw new \RuntimeException('Aktif muhasebe profili bulunamadı.');
        }

        $template = json_decode((string) $profile['template_json'], true, 512, JSON_THROW_ON_ERROR);
        if (!is_array($template)) {
            throw new \RuntimeException('Muhasebe profil şablonu JSON nesnesi olmalı.');
        }

        $items = App::db()->fetchAll(
            'SELECT id,product_id,variant_id,sku,name,unit_price,quantity,line_total '
            . 'FROM order_items WHERE order_id=? ORDER BY id ASC',
            [$orderId]
        );
        $payload = TemplateRenderer::render($template, $order, $items);
        $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        $hash = hash('sha256', $json);

        return App::db()->transaction(function (Database $db) use (
            $orderId,
            $profileId,
            $profile,
            $json,
            $hash
        ): int {
            $row = $db->fetch(
                'SELECT * FROM accounting_exports WHERE order_id=? AND profile_id=? FOR UPDATE',
                [$orderId, $profileId]
            );
            if ($row && !in_array((string) $row['status'], ['prepared', 'failed'], true)) {
                throw new \RuntimeException('Gönderilmiş/işlemde muhasebe aktarımı değiştirilemez.');
            }

            if ($row) {
                $db->execute(
                    "UPDATE accounting_exports SET provider=?,payload_json=?,payload_sha256=?,"
                    . "external_id=NULL,response_sha256=NULL,status='prepared',claim_token=NULL,"
                    . 'claimed_at=NULL,last_error=NULL,sent_at=NULL,updated_at=NOW() WHERE id=?',
                    [(string) $profile['provider'], $json, $hash, (int) $row['id']]
                );
                return (int) $row['id'];
            }

            $db->execute(
                'INSERT INTO accounting_exports(order_id,profile_id,provider,payload_json,payload_sha256,status,'
                . "created_at,updated_at) VALUES(?,?,?,?,?,'prepared',NOW(),NOW())",
                [$orderId, $profileId, (string) $profile['provider'], $json, $hash]
            );
            return (int) $db->lastInsertId();
        });
    }

    public function send(int $exportId): array
    {
        $claim = bin2hex(random_bytes(16));
        $row = App::db()->transaction(function (Database $db) use ($exportId, $claim): array {
            $record = $db->fetch('SELECT * FROM accounting_exports WHERE id=? FOR UPDATE', [$exportId]);
            if (!$record) {
                throw new \RuntimeException('Muhasebe aktarımı bulunamadı.');
            }
            if (!in_array((string) $record['status'], ['prepared', 'failed'], true)) {
                throw new \RuntimeException('Muhasebe aktarımı zaten gönderilmiş veya işlemde.');
            }
            if ($record['claimed_at'] && strtotime((string) $record['claimed_at']) > time() - 600) {
                throw new \RuntimeException('Muhasebe aktarımı başka bir işlem tarafından gönderiliyor.');
            }
            $db->execute(
                "UPDATE accounting_exports SET status='sending',claim_token=?,claimed_at=NOW(),"
                . 'last_error=NULL,updated_at=NOW() WHERE id=?',
                [$claim, $exportId]
            );
            return $record;
        });

        $gateway = AccountingFactory::make((string) $row['provider']);
        $payload = json_decode((string) $row['payload_json'], true, 512, JSON_THROW_ON_ERROR);

        try {
            $result = $gateway->send($payload);
            $response = (string) ($result['response'] ?? '');
            App::db()->execute(
                "UPDATE accounting_exports SET status='sent',external_id=?,response_sha256=?,claim_token=NULL,"
                . 'claimed_at=NULL,last_error=NULL,sent_at=NOW(),updated_at=NOW() WHERE id=? AND claim_token=?',
                [
                    mb_substr((string) ($result['external_id'] ?? ''), 0, 255) ?: null,
                    $response !== '' ? hash('sha256', $response) : null,
                    $exportId,
                    $claim,
                ]
            );
            return $result;
        } catch (UncertainTransferException $e) {
            App::db()->execute(
                "UPDATE accounting_exports SET status='send_unknown',claim_token=NULL,claimed_at=NULL,"
                . 'last_error=?,updated_at=NOW() WHERE id=? AND claim_token=?',
                [mb_substr($e->getMessage(), 0, 1000), $exportId, $claim]
            );
            throw $e;
        } catch (\Throwable $e) {
            App::db()->execute(
                "UPDATE accounting_exports SET status='failed',claim_token=NULL,claimed_at=NULL,"
                . 'last_error=?,updated_at=NOW() WHERE id=? AND claim_token=?',
                [mb_substr($e->getMessage(), 0, 1000), $exportId, $claim]
            );
            throw $e;
        }
    }

    public function reconcile(int $id, string $externalId): void
    {
        $externalId = trim($externalId);
        if ($externalId === '') {
            throw new \RuntimeException('Dış kayıt ID gerekli.');
        }
        $updated = App::db()->execute(
            "UPDATE accounting_exports SET status='sent',external_id=?,last_error=NULL,"
            . "sent_at=COALESCE(sent_at,NOW()),updated_at=NOW() WHERE id=? AND status='send_unknown'",
            [mb_substr($externalId, 0, 255), $id]
        );
        if ($updated !== 1) {
            throw new \RuntimeException('Yalnız sonucu belirsiz aktarım uzlaştırılabilir.');
        }
    }

    public function resetUnknown(int $id, bool $confirmedAbsent): void
    {
        if (!$confirmedAbsent) {
            throw new \RuntimeException('Dış sistemde kayıt olmadığı açıkça onaylanmalı.');
        }
        $updated = App::db()->execute(
            "UPDATE accounting_exports SET status='failed',"
            . "last_error='Dış sistemde kayıt olmadığı yönetici tarafından doğrulandı; tekrar gönderime açıldı.',"
            . "updated_at=NOW() WHERE id=? AND status='send_unknown'",
            [$id]
        );
        if ($updated !== 1) {
            throw new \RuntimeException('Yalnız sonucu belirsiz aktarım sıfırlanabilir.');
        }
    }
}

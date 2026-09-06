<?php
declare(strict_types=1);

namespace Arcates\Controllers;

use Arcates\Core\App;
use Arcates\Core\Csrf;
use Arcates\Core\Locale;
use Arcates\Core\Logger;
use Arcates\Core\Security;
use Arcates\Core\Translator;
use Arcates\Payments\GatewayFactory;
use Arcates\Payments\PaymentVerifier;
use Arcates\Services\Mailer;

final class PaymentController
{
    public function start(): void
    {
        Csrf::requireValid($_POST['_csrf'] ?? null);
        $code = trim((string) ($_POST['order'] ?? ''));
        $order = App::db()->fetch('SELECT * FROM orders WHERE public_code=?', [$code]);
        if (!$order) {
            http_response_code(404);
            return;
        }
        if ((string) $order['status'] === 'cancelled') {
            throw new \RuntimeException('İptal edilmiş sipariş ödenemez.');
        }
        if ((string) $order['payment_status'] === 'paid') {
            header('Location: /odeme/sonuc?order=' . rawurlencode($code));
            return;
        }

        $items = App::db()->fetchAll(
            'SELECT * FROM order_items WHERE order_id=?',
            [(int) $order['id']]
        );

        try {
            App::db()->execute(
                'UPDATE orders SET payment_provider=?,payment_status=\'pending\',updated_at=NOW() WHERE id=?',
                ['iyzico', (int) $order['id']]
            );
            $gateway = GatewayFactory::make();
            $callback = rtrim((string) App::config('app.url', ''), '/') . '/odeme/sonuc';
            $result = $gateway->initialize($order, $items, $callback);
            $status = $result['status'] === 'success' ? 'initialized' : 'failed';
            App::db()->execute(
                'INSERT INTO payment_attempts(order_id,provider,provider_token,status,error_code,created_at) '
                . 'VALUES(?,?,?,?,?,NOW())',
                [
                    (int) $order['id'],
                    'iyzico',
                    $result['token'] ?: null,
                    $status,
                    $result['error_code'] ?: null,
                ]
            );
            if ($result['status'] !== 'success' || $result['page_url'] === '') {
                throw new \RuntimeException('Ödeme başlatılamadı.');
            }
            header('Location: ' . $result['page_url']);
        } catch (\Throwable $e) {
            App::db()->execute(
                "UPDATE orders SET payment_status='failed',updated_at=NOW() WHERE id=? AND payment_status<>'paid'",
                [(int) $order['id']]
            );
            Logger::error('Payment initialize failed', [
                'order_id' => (int) $order['id'],
                'type' => $e::class,
                'message' => $e->getMessage(),
            ]);
            $this->renderResult(false, $code, 'Ödeme başlatılamadı. Lütfen tekrar deneyin.', true);
        }
    }

    public function callback(): void
    {
        $token = trim((string) ($_POST['token'] ?? $_GET['token'] ?? ''));
        if ($token === '') {
            $this->showOrderStatus();
            return;
        }

        $attempt = App::db()->fetch(
            'SELECT pa.id,pa.order_id,o.public_code,o.payment_status,o.grand_total,o.status '
            . 'FROM payment_attempts pa JOIN orders o ON o.id=pa.order_id '
            . 'WHERE pa.provider_token=? ORDER BY pa.id DESC LIMIT 1',
            [$token]
        );
        if (!$attempt) {
            http_response_code(404);
            return;
        }

        $result = GatewayFactory::make()->retrieve($token, (string) $attempt['public_code']);
        $alreadyPaid = (string) $attempt['payment_status'] === 'paid';
        $providerPaid = $result['status'] === 'success'
            && strtoupper((string) $result['payment_status']) === 'SUCCESS';
        $amountMatches = PaymentVerifier::matches((float) $attempt['grand_total'], $result, 'TRY');
        $paid = $alreadyPaid || ($providerPaid && $amountMatches);

        if ($providerPaid && !$amountMatches) {
            Logger::error('Payment amount mismatch', [
                'order_id' => (int) $attempt['order_id'],
                'expected' => (string) $attempt['grand_total'],
                'actual' => (string) ($result['paid_price'] ?? ''),
                'currency' => (string) ($result['currency'] ?? ''),
            ]);
        }

        App::db()->transaction(function ($db) use ($attempt, $result, $paid): void {
            $db->execute(
                'UPDATE payment_attempts SET status=?,error_code=? WHERE id=?',
                [$paid ? 'success' : 'failed', $result['error_code'] ?: null, (int) $attempt['id']]
            );
            if ($paid) {
                $db->execute(
                    "UPDATE orders SET payment_status='paid',payment_reference=?,"
                    . "status=CASE WHEN status='pending' THEN 'confirmed' ELSE status END,updated_at=NOW() WHERE id=?",
                    [$result['payment_id'] ?: null, (int) $attempt['order_id']]
                );
            } else {
                $db->execute(
                    "UPDATE orders SET payment_status='failed',updated_at=NOW() WHERE id=? AND payment_status<>'paid'",
                    [(int) $attempt['order_id']]
                );
            }
        });

        $order = App::db()->fetch('SELECT * FROM orders WHERE id=?', [(int) $attempt['order_id']]);
        if ($order) {
            Mailer::orderStatus($order);
        }
        $message = $paid
            ? 'Ödemeniz doğrulandı.'
            : 'Ödeme doğrulanamadı. Siparişiniz korunuyor; tekrar deneyebilirsiniz.';
        $this->renderResult($paid, (string) $attempt['public_code'], $message, !$paid);
    }

    private function showOrderStatus(): void
    {
        $code = trim((string) ($_GET['order'] ?? ''));
        if ($code === '') {
            http_response_code(400);
            echo Security::escape(Translator::t('invalid_request'));
            return;
        }
        $order = App::db()->fetch(
            'SELECT public_code,payment_status,status FROM orders WHERE public_code=?',
            [$code]
        );
        if (!$order) {
            http_response_code(404);
            return;
        }
        $paid = (string) $order['payment_status'] === 'paid';
        $message = $paid ? 'Ödemeniz doğrulandı.' : 'Siparişiniz ödeme bekliyor.';
        $this->renderResult($paid, $code, $message, !$paid && (string) $order['status'] !== 'cancelled');
    }

    private function renderResult(bool $paid, string $code, string $message, bool $retry): void
    {
        $locale = Translator::requestLocale();
        $dir = Locale::rtl($locale) ? 'rtl' : 'ltr';
        $safeCode = Security::escape($code);
        $safeMessage = Security::escape($message);
        $title = Translator::t('payment_result', $locale);
        $heading = Translator::t($paid ? 'payment_success' : 'payment_status', $locale);

        echo '<!doctype html><html lang="' . Security::escape($locale) . '" dir="' . $dir . '">'
            . '<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">'
            . '<title>' . Security::escape($title) . '</title>'
            . '<link rel="stylesheet" href="/assets/css/theme.css"><body><main class="container">'
            . '<h1>' . Security::escape($heading) . '</h1><p>' . $safeMessage . '</p>'
            . '<p>' . Security::escape(Translator::t('order_code', $locale)) . ': ' . $safeCode . '</p>';
        if ($retry) {
            echo '<form method="post" action="/odeme/baslat">' . Csrf::field()
                . '<input type="hidden" name="order" value="' . $safeCode . '">'
                . '<button>' . Security::escape(Translator::t('payment_retry', $locale)) . '</button></form>';
        }
        echo '</main></body></html>';
    }
}

<?php
declare(strict_types=1);

namespace Arcates\Payments;

use Arcates\Core\App;

final class IyzicoGateway implements PaymentGateway
{
    private object $options;

    public function __construct()
    {
        $bootstrap = (string) App::config('integrations.payment_sdk_path', '');
        if ($bootstrap === '' || !is_file($bootstrap)) {
            throw new \RuntimeException('iyzico resmi SDK yolu yapılandırılmadı.');
        }
        require_once $bootstrap;
        if (class_exists('IyzipayBootstrap')) {
            \IyzipayBootstrap::init();
        }
        if (!class_exists('Iyzipay\\Options')) {
            throw new \RuntimeException('iyzico resmi PHP SDK yüklenemedi.');
        }

        $options = new \Iyzipay\Options();
        $options->setApiKey((string) App::config('integrations.iyzico.api_key', ''));
        $options->setSecretKey((string) App::config('integrations.iyzico.secret_key', ''));
        $options->setBaseUrl((string) App::config(
            'integrations.iyzico.base_url',
            'https://sandbox-api.iyzipay.com'
        ));
        $this->options = $options;
    }

    public function initialize(array $order, array $items, string $callbackUrl): array
    {
        $request = new \Iyzipay\Request\CreatePayWithIyzicoInitializeRequest();
        $request->setLocale(\Iyzipay\Model\Locale::TR);
        $request->setConversationId((string) $order['public_code']);
        $request->setPrice(self::money((float) $order['subtotal']));
        $request->setPaidPrice(self::money((float) $order['grand_total']));
        $request->setCurrency(\Iyzipay\Model\Currency::TL);
        $request->setBasketId((string) $order['public_code']);
        $request->setPaymentGroup(\Iyzipay\Model\PaymentGroup::PRODUCT);
        $request->setCallbackUrl($callbackUrl);

        $parts = preg_split('/\s+/u', trim((string) $order['customer_name'])) ?: [];
        if (count($parts) < 2) {
            throw new \RuntimeException('Ödeme için ad ve soyad gerekli.');
        }
        $surname = (string) array_pop($parts);
        $given = implode(' ', $parts);

        $buyer = new \Iyzipay\Model\Buyer();
        $buyer->setId((string) $order['id']);
        $buyer->setName($given);
        $buyer->setSurname($surname);
        $buyer->setGsmNumber((string) $order['phone']);
        $buyer->setEmail((string) $order['email']);
        $buyer->setIdentityNumber((string) $order['identity_number']);
        $buyer->setRegistrationAddress((string) $order['address']);
        $buyer->setIp((string) ($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1'));
        $buyer->setCity((string) $order['city']);
        $buyer->setCountry('Turkey');
        $request->setBuyer($buyer);

        $address = new \Iyzipay\Model\Address();
        $address->setContactName((string) $order['customer_name']);
        $address->setCity((string) $order['city']);
        $address->setCountry('Turkey');
        $address->setAddress((string) $order['address']);
        $address->setZipCode((string) ($order['postal_code'] ?? ''));
        $request->setShippingAddress($address);
        $request->setBillingAddress($address);

        $basket = [];
        foreach ($items as $row) {
            $item = new \Iyzipay\Model\BasketItem();
            $item->setId((string) $row['sku']);
            $item->setName((string) $row['name']);
            $item->setCategory1('Ürün');
            $item->setItemType(\Iyzipay\Model\BasketItemType::PHYSICAL);
            $item->setPrice(self::money((float) $row['line_total']));
            $basket[] = $item;
        }
        $request->setBasketItems($basket);

        $result = \Iyzipay\Model\PayWithIyzicoInitialize::create($request, $this->options);
        return [
            'status' => (string) $result->getStatus(),
            'token' => (string) $result->getToken(),
            'page_url' => (string) $result->getPayWithIyzicoPageUrl(),
            'error_code' => (string) $result->getErrorCode(),
            'error_message' => (string) $result->getErrorMessage(),
        ];
    }

    public function retrieve(string $token, string $conversationId): array
    {
        $request = new \Iyzipay\Request\RetrievePayWithIyzicoRequest();
        $request->setLocale(\Iyzipay\Model\Locale::TR);
        $request->setConversationId($conversationId);
        $request->setToken($token);
        $result = \Iyzipay\Model\PayWithIyzico::retrieve($request, $this->options);

        return [
            'status' => (string) $result->getStatus(),
            'payment_status' => (string) $result->getPaymentStatus(),
            'payment_id' => (string) $result->getPaymentId(),
            'paid_price' => (string) $result->getPaidPrice(),
            'currency' => (string) $result->getCurrency(),
            'error_code' => (string) $result->getErrorCode(),
        ];
    }

    private static function money(float $value): string
    {
        return number_format($value, 2, '.', '');
    }
}

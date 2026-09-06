<?php
declare(strict_types=1);
$root=dirname(__DIR__);require_once $root.'/bootstrap.php';$fail=[];
foreach(['app/Services/OrderService.php','app/Services/Cart.php','app/Payments/IyzicoGateway.php','app/Payments/GatewayFactory.php','app/Controllers/CheckoutController.php','app/Controllers/PaymentController.php','app/Services/PdfPriceList.php','db/migrations/20260906_005_commerce.sql','db/migrations/20260906_005b_payment_identity.sql'] as $f){if(!is_file($root.'/'.$f))$fail[]='Eksik: '.$f;}
$order=(string)@file_get_contents($root.'/app/Services/OrderService.php');foreach(['FOR UPDATE','stock=stock-?','stock=stock+?','stock_released','coupons','shipping_rules','identity_number'] as $needle){if(!str_contains($order,$needle))$fail[]='OrderService kuralı eksik: '.$needle;}
$gateway=(string)@file_get_contents($root.'/app/Payments/IyzicoGateway.php');foreach(['IyzipayBootstrap','CreatePayWithIyzicoInitializeRequest','PayWithIyzicoInitialize::create','PayWithIyzico::retrieve','identity_number'] as $needle){if(!str_contains($gateway,$needle))$fail[]='Resmi iyzico SDK bağlantısı eksik: '.$needle;}
foreach(['hash_hmac(','curl_init(','11111111111',"setSurname('-')"] as $forbidden){if(str_contains($gateway,$forbidden))$fail[]='Ödeme katmanında yasak/sahte mantık bulundu: '.$forbidden;}
$checkout=(string)@file_get_contents($root.'/app/Controllers/CheckoutController.php');foreach(['sales_terms','identity_number','FILTER_VALIDATE_EMAIL'] as $needle){if(!str_contains($checkout,$needle))$fail[]='Checkout doğrulaması eksik: '.$needle;}
$pdf=\Arcates\Services\PdfPriceList::build('Test',[['sku'=>'SKU1','product_name'=>'Urun','variant_name'=>'Standart','price'=>100]],10);if(!str_starts_with($pdf,'%PDF-1.4'))$fail[]='B2B PDF üretimi başarısız.';
if($fail){fwrite(STDERR,implode(PHP_EOL,$fail).PHP_EOL);exit(1);}echo "Ticaret testleri: OK\n";

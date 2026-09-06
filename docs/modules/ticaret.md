# Ticaret modülü — Faz 5

## Kapsam
Ürün kataloğu, varyant/stok, oturum sepeti, sipariş oluşturma, kargo ücret kuralları, kupon, resmi iyzico PHP SDK üzerinden ödeme, sipariş durum yönetimi/e-postaları, mesafeli satış ve iade şablonları ile giriş korumalı B2B fiyat listesi/PDF sağlar. Arcates kart verisi işlemez, ödeme imzası veya sağlayıcı HTTP istemcisi yazmaz.

## Veri modeli
- `products`, `product_variants`: çok dilli katalog, SKU, fiyat ve stok.
- `shipping_rules`: tutara göre kargo ücreti.
- `coupons`: yüzde/sabit indirim ve kullanım limiti.
- `orders`, `order_items`: müşteri, teslimat, tutarlar ve sipariş kalemleri.
- `payment_attempts`: sağlayıcı tokenı ve ödeme sonucu.
- `b2b_accounts`: hash parola ve bayi iskonto oranı.

## Dosyalar
- `app/Services/Cart.php`, `Shipping.php`, `OrderService.php`, `PdfPriceList.php`
- `app/Payments/PaymentGateway.php`, `GatewayFactory.php`, `IyzicoGateway.php`
- `app/Controllers/CatalogController.php`, `CartController.php`, `CheckoutController.php`, `PaymentController.php`
- `app/Controllers/ProductAdminController.php`, `OrderAdminController.php`, `ShippingAdminController.php`
- `app/Controllers/B2BController.php`, `B2BAdminController.php`
- `db/migrations/20260906_005_commerce.sql`, `20260906_005b_payment_identity.sql`
- `tests/commerce.php`

## Güvenlik ve kritik davranış
- Sipariş sırasında varyant satırı `FOR UPDATE` ile kilitlenir; stok yeterli değilse sipariş oluşmaz.
- İptalde stok yalnız `stock_released=0` iken geri yüklenir.
- Tüm SQL kullanıcı girdileri prepared statement parametreleridir.
- Checkout CSRF, sunucu tarafı veri doğrulaması ve sözleşme onayı ister.
- Ödeme yalnız resmi iyzico SDK ile yapılır; API anahtarları `config.php` içinde tutulur ve repoya girmez.
- B2B parolaları `password_hash` / `password_verify` ile saklanır.

## Ayarlar
`config.php` içinde `integrations.payment_provider`, `integrations.payment_sdk_path` ve `integrations.iyzico.*` alanları doldurulur. Composer kullanılmayan kurulumda resmi iyzico sürümü indirilip `IyzipayBootstrap.php` yolu verilir.

## Test
`php tests/run.php` çalıştırılır. Elle ayrıca gerçek sandbox hesabında başarılı/başarısız ödeme, tekrar callback, aynı stok için eşzamanlı iki checkout, kupon limitleri, sipariş iptali sonrası tek stok iadesi ve PDF indirme senaryoları doğrulanır.

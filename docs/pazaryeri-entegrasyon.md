# Pazaryeri stok/fiyat senkronu

Arcates, ürün varyantlarını Trendyol ve Hepsiburada listingleriyle eşleyip yalnız değişen stok/fiyat değerlerini asenkron batch olarak gönderir. API kullanıcıları, şifreleri ve anahtarları yalnız `config.php` içinde tutulur.

## Trendyol

`config.php > integrations.marketplace.trendyol` altında `seller_id`, `api_key`, `api_secret`, `user_agent` ve gerekiyorsa `storefront_code` girilir. Test için `base_url` stage gateway'e çevrilebilir.

- Stok/fiyat endpoint'i: `/integration/inventory/sellers/{sellerId}/products/price-and-inventory`
- Tek istekte en fazla 1000 kalem.
- Stok ürün başına 20.000 ile sınırlandırılır.
- Aynı istek gövdesi 15 dakika tekrar gönderilmez; Arcates payload hash ile değişmeyen varyantı atlar.
- Başarılı POST sonrası `batchRequestId` kaydedilir ve batch sonucu ayrıca sorgulanır.

## Hepsiburada

`config.php > integrations.marketplace.hepsiburada` altında `merchant_id`, `username`, `password` ve anlamlı bir `user_agent` girilir. SIT için `base_url` değeri `https://listing-external-sit.hepsiburada.com` yapılır.

- Inventory upload: `/listings/merchantid/{merchantId}/inventory-uploads`
- Sonuç sorgusu: `/listings/merchantid/{merchantId}/inventory-uploads/id/{inventoryUploadId}`
- Eşlemede `MerchantSku` ile birlikte HBSKU (`HepsiburadaSku`) zorunludur.
- Tek istekte en fazla 4000 SKU gönderilir.
- Aynı anda 5'ten fazla bekleyen upload açılmaz; Arcates 5 pending batch olduğunda yeni gönderimi durdurur.
- `User-Agent` ve Basic Auth zorunludur.

## Eşleme

Yönetim panelindeki **Pazaryeri** ekranında Arcates varyantı seçilir. Trendyol için dış SKU + barkod, Hepsiburada için dış SKU + HBSKU girilir. `price_multiplier` pazaryeri fiyat katsayısı, `stock_reserve` ise satışa açılmayacak güvenlik stoğudur.

## Cron

Örnek:

```bash
*/5 * * * * /usr/local/bin/php /home/USER/arcates/scripts/marketplace_sync.php all >> /home/USER/arcates/logs/marketplace-cron.log 2>&1
```

Script önce mevcut batch sonuçlarını kontrol eder, sonra yalnız değişen kayıtları gönderir. Aynı eşleme başka bir süreç tarafından seçildiyse `claim_token` duplicate push'ı engeller.

## Canlı kabul

1. Önce sağlayıcının test/SIT hesabını kullanın.
2. Tek bir varyantı eşleyin; stok ve fiyatı bilinen test değerine getirin.
3. `php scripts/marketplace_sync.php trendyol` veya `hepsiburada` çalıştırın.
4. Batch ID'nin panelde oluştuğunu doğrulayın.
5. Bir sonraki çalıştırmada batch sonucunun `success` olduğunu doğrulayın.
6. Sağlayıcı panelinde stok/fiyatı kontrol edin.
7. Hiçbir yerel değeri değiştirmeden scripti tekrar çalıştırın; `submitted=0` beklenir.
8. Stoku değiştirin; yalnız ilgili varyantın yeni batch'e girdiğini doğrulayın.
9. Hatalı barkod/HBSKU ile test edip hata mesajının eşleme üzerinde kaldığını doğrulayın.
10. Test ortamı başarılı olmadan production credential kullanmayın.

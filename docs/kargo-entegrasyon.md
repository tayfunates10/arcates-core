# Kargo entegrasyonu kabul rehberi

## Ortak
- Kimlik bilgilerini yalnız `config.php` içinde tutun; örnek config'te değerler boş bırakılır.
- `php scripts/migrate.php` ile `carrier_shipments` migration'ını uygulayın.
- PHP'de `curl` ve `soap` eklentilerinin açık olduğunu doğrulayın.
- Önce sağlayıcının test/sandbox hesabında gönderi oluşturma, takip, etiket ve hata senaryolarını çalıştırın.

## MNG / DHL eCommerce
Arcates güncel REST akışını kullanır: `/mngapi/api/token`, Standard Command `createOrder`, Standard Query takip uçları ve Barcode Command `createbarcode`. API Zone uygulamasından Client ID/Secret alın; Identity, Standard Command, Standard Query ve Barcode Command ürünlerine yetki verin. Müşteri numarası ve kalıcı hesap şifresi ayrıca gerekir. Alıcı telefonu 10 hane; TC/vergi numarası gerçek 10/11 hane olmalıdır.

Canlı kabul: bir siparişi oluşturun, dönen reference/tracking değerini sağlayıcı panelinde doğrulayın, takip senkronunu çalıştırın ve ZPL etiketini Zebra uyumlu görüntüleyici/yazıcıyla doğrulayın.

## Aras
Arcates SOAP `SetOrder` ile gönderi oluşturur; müşteri web servisinde `GetCargoTransaction` ile takip ve `GetArasBarcode` ile etiket alır. Kurumsal web servis kullanıcı adı/şifresi Aras temsilcisinden alınmalıdır. Kullanılan WSDL adresleri `config.php` üzerinden değiştirilebilir.

Canlı kabul: test gönderisi oluşturun, integration code ile Aras panelinde doğrulayın, hareketleri sorgulayın ve PDF/ZPL barkod çıktısını kontrol edin.

## Yurtiçi
Arcates KOPS `ShippingOrderDispatcherServices` WSDL'ini kullanır. Varsayılan operasyonlar `createShipment`, `queryShipment` ve etiket için `createShipmentWithDelivery` olarak yapılandırılmıştır; sözleşmenizde farklı operasyon adı varsa `config.php` üzerinden değiştirin. Kurumsal kullanıcı adı/şifre ve gerekiyorsa çıkış IP beyaz liste yetkisi Yurtiçi tarafından sağlanır.

Canlı kabul: cargoKey ile test gönderisi oluşturun, `queryShipment` ile takip sonucunu doğrulayın ve sözleşmeniz etiketli operasyonu destekliyorsa ZPL çıktısını kontrol edin.

## Hata yönetimi
Dış API çağrıları veritabanı transaction'ı dışında yapılır. Sağlayıcı başarısız dönerse yerel kargo kaydı oluşturulmaz. Takip güncellemesi başarısız olduğunda mevcut takip kaydı korunur ve hata uygulama loguna düşer. Sağlayıcı erişimi, IP beyaz liste veya sözleşme yetkisi gerektiren testler CI'da sahte credential ile taklit edilmez; gerçek test hesabında kabul edilmelidir.

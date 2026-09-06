# Emlak / ilan modülü

## Kapsam
Satılık ve kiralık gayrimenkul ilanlarını çok dilli yayınlar; ilan tipi, gayrimenkul tipi, şehir, ilçe, oda, fiyat ve m² filtrelerini destekler. İlanda m², oda, kat ve isteğe bağlı koordinat tutulur. Koordinat varsa ayrıntı sayfası OpenStreetMap üzerinde konumu açar. Bu modül harita sağlayıcısına özel müşteri anahtarı gerektirmez.

## Veri modeli
`real_estate_listings`: dil, slug, ilan/gayrimenkul tipi, konum, fiyat, m², oda/kat, koordinat, açıklama, görsel ve yayın durumu.

## Dosyalar
- `db/migrations/20260906_006_real_estate.sql`
- `app/Controllers/RealEstateController.php`
- `app/Controllers/RealEstateAdminController.php`
- `tests/real_estate.php`

## Güvenlik / test
Yönetim yazmaları CSRF doğrulamalıdır; SQL parametreli sorgudur; enlem/boylam aralıkları sunucuda doğrulanır; kullanıcı çıktıları escape edilir. `php tests/run.php` ile modül test kapısı çalışır.

# Arcates Core

Composer ve framework gerektirmeyen, PHP 8.1+ / MySQL 8 / Vanilla JS tabanlı modüler starter kit.

## Gereksinimler
- PHP 8.1+ (`pdo_mysql`, `mbstring`, `fileinfo`, `gd`, `curl`, `soap`)
- MySQL 8+
- Apache + `mod_rewrite` veya eşdeğer temiz URL yönlendirmesi

## Kurulum
1. `config.example.php` dosyasını `config.php` adıyla kopyalayın.
2. Veritabanı bilgilerini, `app.url`, e-posta ve WhatsApp ayarlarını düzenleyin.
3. Web kökünü `public/` klasörüne yönlendirin.
4. `/install` ile ilk admin hesabını oluşturun; işlem sonrası `install/install.lock` tekrar kurulumu engeller.
5. Güncellemelerde `php scripts/migrate.php` çalıştırın.
6. `scripts/backup.php` için günlük cron, `scripts/purge_forms.php` için form saklama politikası cron'u tanımlayın ve geri yükleme testi yapın.

## Çekirdek altyapı
Router/autoloader, PDO prepared statements, admin/editor rolleri, `password_hash`, CSRF, XSS escape, güvenli session cookie, 5 hata/15 dk giriş limiti, hata logu, takipli migration ve yedekleme içerir.

## Admin ve içerik
Mobil uyumlu yönetim kabuğu; çok dilli sayfa CRUD, medya/WebP, menüler, SEO/sitemap/robots, blog, formlar ve diğer modül yönetimlerini içerir.

## Rezervasyon
Oda/masa/seans birimleri, sezonluk fiyat, müsaitlik, `FOR UPDATE` ile çift rezervasyon koruması, e-posta, panel onay/iptal ve iCal desteği içerir.

## Ticaret
Çok dilli ürün/varyant/stok, sepet, sipariş, kargo ücret kuralları, kuponlar, resmi iyzico PHP SDK adaptörü, durum e-postaları, sözleşmeler ve korumalı B2B fiyat listesi/PDF içerir.

### Kargo API entegrasyonu
- MNG / DHL eCommerce: güncel REST API Zone akışı; bearer token + IBM Client ID/Secret, `createOrder`, takip ve `createbarcode`/ZPL etiketi.
- Aras: SOAP `SetOrder`, `GetCargoTransaction`, `GetArasBarcode` üzerinden gönderi, takip ve PDF/ZPL etiketi.
- Yurtiçi: KOPS `ShippingOrderDispatcherServices`; `createShipment`, `queryShipment` ve yapılandırılabilir etiket operasyonu (`createShipmentWithDelivery`).
- Sağlayıcı kullanıcı adı, şifre, müşteri numarası ve API anahtarları yalnız `config.php` içinde tutulur.
- Sipariş taşıyıcıya gönderilirken ilçe, kg, desi ve koli adedi girilir; kargo kaydı `carrier_shipments` tablosunda izlenir.
- Dış API çağrısı DB transaction içinde tutulmaz; başarılı sağlayıcı dönüşünden sonra yerel kayıt atomik yazılır.
- Etiket ve takip işlemleri yönetim panelinde POST + CSRF ile korunur.
- MNG için gerçek 10/11 haneli TC/vergi numarası ve 10 haneli telefon zorunludur; sahte placeholder üretilmez.
- Canlı kullanım öncesi sağlayıcı test hesabı, API aboneliği ve gerekiyorsa IP beyaz liste kabul testi yapılmalıdır.

## Sektörel modüller
- Emlak / ilan: çok dil, satılık/kiralık, filtreler ve OpenStreetMap.
- QR Menü: çok dil/RTL, kategori/ürün ve dış servissiz SVG QR.
- Gönderi Takip: PII göstermeyen public takip, rate limit ve durum zaman çizelgesi.
- Hizmet + Fiyatlandırma: çok dilli hizmet/paket/birim/para birimi tablosu.
- Bayi / Şube Yönetimi: çok dilli şube, çalışma saatleri, koordinat/harita ve şube hizmetleri.

## Dallar
- `main`: kararlı sürüm
- `dev`: entegrasyon dalı
- `feature/*`: tek modül geliştirme dalları

## Güvenlik
`config.php` commit edilmez. POST formları CSRF korumalıdır; kullanıcı çıktıları escape edilir; upload klasöründe PHP çalıştırılmaz. Form kayıt saklama süresi `security.form_retention_days` ile belirlenir. Siparişte stok satırları kilitlenir ve iptal edilen sipariş stokları yalnız bir kez iade edilir.

## Test
```bash
php tests/run.php
```
CI, PHP 8.1/8.2/8.3 üzerinde `pdo_mysql`, `mbstring`, `fileinfo`, `gd`, `curl`, `soap` ile sözdizimi ve testleri çalıştırır.

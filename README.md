# Arcates Core

Composer ve framework gerektirmeyen, PHP 8.1+ / MySQL 8 / Vanilla JS tabanlı modüler starter kit.

## Gereksinimler
- PHP 8.1+ (`pdo_mysql`, `mbstring`, `fileinfo`, `gd`, `curl`)
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
- Mobil uyumlu yönetim kabuğu
- Sayfa CRUD: TR/EN/DE/AR, slug, taslak/yayın, meta title/description ve OG görseli
- Arapça için RTL tema
- Menü yöneticisi
- Görsellerde uzantı+MIME+boyut kontrolü, rastgele ad ve WebP yeniden boyutlandırma
- `/sitemap.xml` ve `/robots.txt`
- `themes/default` mobil öncelikli tema

## Dönüşüm modülleri
- İletişim/teklif formu: CSRF, honeypot, sunucu doğrulaması, rate limit ve önceden işaretlenmeyen KVKK açık rızası
- Form kayıtları yönetim paneli ve e-posta bildirimi
- Yapılandırılabilir, sayfa bağlamlı WhatsApp CTA
- Kategori destekli portföy/galeri
- Çok dilli blog, kategori, etiket ve SEO alanları
- Referans/yorum yönetimi
- KVKK, gizlilik, gerekli çerez bildirimi ve süre sonunda form kayıtlarını silme scripti

## Rezervasyon
- Oda, masa ve seans birimleri; kapasite ve temel fiyat
- Sezonluk fiyat tanımları
- Transaction + birim satırında `FOR UPDATE` ile aynı tarih aralığında çift rezervasyonun yarış koşulunda da engellenmesi
- Müsaitlik endpoint'i, rezervasyon formu ve onay e-postası
- Panelden onay/iptal ve iCal dışa aktarımı

## Ticaret
- Çok dilli ürün kataloğu, varyant/SKU ve stok
- Oturum sepeti, checkout ve transaction içinde stok kilidi
- Tutar bazlı kargo ücret kuralları
- Yüzde/sabit kupon kampanyaları ve kullanım sayaçları
- Sipariş yönetimi, ödeme/sipariş durumları ve durum e-postaları
- Mesafeli satış ve iade sözleşmesi şablonları
- Giriş korumalı B2B fiyat listesi ve PDF çıktısı
- Ödeme katmanı yalnız resmi iyzico PHP SDK'sına bağlanır; Arcates kart işleme, imza veya ödeme HTTP istemcisi yazmaz

### iyzico kurulumu
Composer kullanmadan resmi `iyzico/iyzipay-php` sürümünü indirin ve örneğin `integrations/iyzipay/` altına koyun. `config.php` içinde `integrations.payment_provider` değerini `iyzico` yapın, `payment_sdk_path` değerini resmi `IyzipayBootstrap.php` dosyasına yönlendirin ve API anahtarlarını yalnız `config.php` içinde tanımlayın. Önce sandbox ortamında başarılı/başarısız ödeme ve tekrar callback testleri tamamlanmadan canlı anahtara geçmeyin.

## Emlak / ilan
- TR/EN/DE/AR ilanlar ve satılık/kiralık ayrımı
- Gayrimenkul tipi, şehir, ilçe, oda, fiyat ve m² filtreleri
- Kat, m², oda, fiyat ve koordinat alanları
- Koordinat bulunan ilanlarda OpenStreetMap bağlantısı
- Yönetim panelinden ilan ekleme ve silme

## QR Menü
- Restoran/kafe/otel için ayrı menü slug'ları
- TR/EN/DE/AR kategori ve ürün içerikleri; Arapça görünüm RTL
- Ürün adı, açıklama, fiyat, para birimi ve güvenli WebP görsel yolu
- QR kodu dış servise bağlanmadan PHP içinde SVG olarak üretilir
- `app.url + menu slug + locale` QR kapasitesini aşarsa menü oluşturulurken açık hata verilir
- Örnek public yollar: `/menu/{slug}/{locale}` ve `/menu/{slug}/qr.svg?locale=tr`

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
CI, PHP 8.1/8.2/8.3 üzerinde sözdizimi ve testleri çalıştırır.

# Arcates Core

Composer ve framework gerektirmeyen, PHP 8.1+ / MySQL 8 / Vanilla JS tabanlı modüler starter kit.

## Gereksinimler
- PHP 8.1+ (`pdo_mysql`, `mbstring`, `fileinfo`, `gd`, `curl`, `soap`, `xml`)
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
- MNG / DHL eCommerce: REST API Zone; gönderi, takip ve ZPL barkod.
- Aras: SOAP gönderi/takip/PDF-ZPL barkod.
- Yurtiçi: KOPS SOAP gönderi/takip/yapılandırılabilir etiket.
- Credential'lar yalnız `config.php` içindedir; dış API çağrısı DB transaction içinde tutulmaz.
- Ayrıntılı canlı kabul: `docs/kargo-entegrasyon.md`.

### Pazaryeri stok/fiyat senkronu
- Trendyol ve Hepsiburada gateway adapterleri; varyant ↔ dış SKU/barkod/HBSKU eşlemesi.
- Fiyat katsayısı ve güvenlik stoğu; yalnız değişen SHA-256 payload gönderimi.
- `claim_token` eşzamanlı duplicate push'ı engeller; asenkron batch sonuçları kalem bazında saklanır.
- Trendyol batch sınırı 1000; Hepsiburada 4000 ve en fazla 5 pending upload.
- Panel `/yonetim/pazaryeri`; cron `php scripts/marketplace_sync.php all`.
- Ayrıntılı kabul: `docs/pazaryeri-entegrasyon.md`.

### e-Fatura / e-Arşiv
- Uyumsoft BasicIntegration SOAP adapteri; `IsEInvoiceUser`, `SendInvoice`, `QueryOutboxInvoiceStatus`.
- Arcates vergi/KDV oranı belirlemez ve yasal UBL-TR üretmez; hazır UBL-TR XML'i entegratöre taşır.
- XML iyi biçimli olmalı; DTD/ENTITY reddedilir ve `LIBXML_NONET` kullanılır.
- Siparişte gerçek 10/11 haneli VKN/TCKN ve `paid` ödeme durumu zorunludur.
- Mükellefiyet gerçek zamanlı sorgulanır; e-Arşiv için `ProfileID=EARSIVFATURA` zorunlu, profil Arcates tarafından değiştirilmez.
- UBL XML public dosya sistemine yazılmaz; `e_documents` içinde SHA-256 özetiyle saklanır.
- Uyumsoft UUID, fatura no, senaryo ve durum kodları saklanır; cron `php scripts/edocument_status.php`.
- Ağ sonucu belirsizse `send_unknown` durumu tekrar gönderimi engeller; portal UUID bağlama veya portalda belge olmadığını açık onaylama ile uzlaştırılır.
- Credential'lar yalnız `config.php` içinde tutulur.
- Ayrıntılı test/canlı kabul: `docs/e-belge-entegrasyon.md`.

### Muhasebe aktarımı
- Logo/Netsis NetOpenX REST, Mikro API ve Paraşüt API v4 gateway adapterleri.
- Firma/cari/muhasebe hesabı/kategori/ürün ID'leri kurulumdan kuruluma değiştiği için kod içinde sahte eşleme yoktur; panelde sağlayıcıya özel JSON şablon profili tanımlanır.
- Güvenli şablon motoru yalnız `{{order.*}}`, `{{item.*}}` ve `$each=items` yapısını işler; `eval` veya dinamik PHP çalıştırmaz.
- Logo varsayılanı `POST /api/v2/GLSlips`, Mikro varsayılanı `POST /Api/apiMethods/MuhasebeFisKaydetV2`, Paraşüt `POST /v4/{company_id}/sales_invoices`.
- Paraşüt OAuth2 access token veya resmî password grant desteklenir; diğer tüm credential'lar gibi sırlar yalnız `config.php` içindedir.
- Yalnız `paid` ve iptal edilmemiş siparişler aktarılabilir; payload SHA-256 ile kaydedilir.
- Dış API çağrısı DB transaction dışında; `claim_token` eşzamanlı çift gönderimi engeller.
- cURL timeout/bağlantı kesintisi `send_unknown` olarak kilitlenir; dış sistem kontrol edilmeden tekrar gönderim yapılamaz.
- Panel `/yonetim/muhasebe`; ayrıntılı kabul: `docs/muhasebe-entegrasyon.md`.

### Ziyaretçi istatistikleri
- Birinci taraf, çerezsiz günlük sayfa görüntüleme agregasyonu.
- IP adresi, kullanıcı ID'si, session ID veya tam referrer URL saklanmaz.
- Sorgu string'i atılır; yalnız temizlenmiş path ve dış referrer host tutulur.
- `DNT: 1`, bot/crawler trafiği, admin, asset, upload, install, sitemap ve robots istekleri sayılmaz.
- Ham olay tablosu yerine `analytics_daily` içinde gün/path/referrer bazında sayaç artırılır.
- Panel `/yonetim/istatistik`; 7/30/90 günlük toplam, günlük trafik, popüler sayfalar ve kaynaklar gösterilir.

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
CI, PHP 8.1/8.2/8.3 üzerinde `pdo_mysql`, `mbstring`, `fileinfo`, `gd`, `curl`, `soap`, `xml` ile sözdizimi ve testleri çalıştırır.

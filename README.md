# Arcates Core

Composer ve framework gerektirmeyen, PHP 8.1+ / MySQL 8 / Vanilla JS tabanlı modüler starter kit.

## Gereksinimler
- PHP 8.1+ (`pdo_mysql`, `mbstring`, `fileinfo`, `gd`, `curl`, `soap`, `xml`)
- MySQL 8+
- Apache + `mod_rewrite`, Nginx veya eşdeğer güvenli front-controller yapılandırması

## Güvenli web kökü
Tercih edilen kurulumda uygulama document-root'u **`public/`** klasörüdür. `config.php`, `app/`, `db/`, `tests/`, `scripts/`, `logs/` ve `.git` web kökünün dışında kalmalıdır.

Yüklenen görseller proje kökündeki `uploads/` klasöründe tutulur. `public/` document-root kullanıyorsanız `/uploads/` URL'sini bu klasöre **alias** edin ve PHP/PHTML/PHAR çalıştırmayı kesin olarak reddedin. Nginx örneği: `docs/nginx.conf.example`.

cPanel/shared-hosting ortamında document-root'u `public/` yapamıyorsanız proje kökündeki `.htaccess` fallback'i kullanılabilir. Bu senaryoda Apache/LiteSpeed'in `.htaccess` kurallarını gerçekten uyguladığı doğrulanmalıdır. Nginx/Caddy üzerinde `.htaccess` koruma sağlamaz.

## Kurulum
1. `config.example.php` dosyasını `config.php` adıyla kopyalayın.
2. Veritabanı bilgilerini, `app.url`, e-posta, WhatsApp ve gerekli entegrasyon ayarlarını düzenleyin.
3. Web kökünü yukarıdaki güvenli dağıtım modeline göre yapılandırın.
4. `/install` ile ilk admin hesabını oluşturun. Kurulum gerçek `install/install.lock` dosyasını yazar; kilit yazılamazsa kurulum başarılı sayılmaz.
5. Güncellemelerde `php scripts/migrate.php` çalıştırın.
6. `scripts/backup.php` için günlük cron, `scripts/purge_forms.php` için form saklama politikası cron'u tanımlayın ve geri yükleme testi yapın.
7. Ödenmemiş terk edilmiş sipariş stokları için `php scripts/release_abandoned_orders.php` cron'u tanımlayın. Varsayılan eşik 1440 dakika (24 saat), batch 100'dür.

## Çekirdek altyapı
Router/autoloader, PDO prepared statements, admin/editor rolleri, `password_hash`, CSRF, XSS escape, güvenli session cookie, kalıcı public rate-limit, hesap+IP giriş limiti, hata logu, takipli migration ve yedekleme içerir.

`security.trusted_proxies` yalnız gerçekten kontrol ettiğiniz reverse-proxy IP'lerini içermelidir. `X-Forwarded-For`, `CF-Connecting-IP` ve `X-Forwarded-Proto` yalnız bu IP'lerden geldiğinde güvenilir kabul edilir.

## Admin ve içerik
Mobil uyumlu yönetim kabuğu; çok dilli sayfa CRUD, medya/WebP, menüler, SEO/sitemap/robots, blog, formlar ve diğer modül yönetimlerini içerir. TR/EN/DE/AR çeviri katmanı ve Arapça RTL desteği bulunur.

## Rezervasyon
Oda/masa/seans birimleri, sezonluk fiyat, müsaitlik, `FOR UPDATE` ile çift rezervasyon koruması, e-posta, panel onay/iptal ve iCal desteği içerir.

## Ticaret
Çok dilli ürün/varyant/stok, sepet, sipariş, kargo ücret kuralları, kuponlar, resmi iyzico PHP SDK adaptörü, durum e-postaları, sözleşmeler ve korumalı B2B fiyat listesi/PDF içerir.

- Kupon kodları tek noktada normalize edilir; kullanım limiti küçük/büyük harf veya boşlukla atlatılamaz.
- Sipariş durumları geçiş matrisiyle yönetilir; `cancelled` terminaldir.
- Ödenmiş sipariş refund tamamlanmadan doğrudan iptal edilemez.
- iyzico callback'i sağlayıcıdaki `paidPrice` ve para birimini `orders.grand_total` ile doğrular.
- Ödeme başlatma yalnız POST + CSRF'dir; başarısız ödeme yeniden denenebilir.
- Terk edilmiş sipariş cron'u yalnız eski `pending/failed`, stok bırakılmamış ve **initialized ödeme denemesi bulunmayan** siparişleri iptal eder. Her aday DB satır kilidi altında yeniden doğrulanır; stok ve kupon hakkı yalnız bir kez geri verilir.
- Sağlayıcıda hâlâ `initialized` görünen eski ödeme denemeleri otomatik iptal edilmez; bunlar ödeme sağlayıcısıyla uzlaştırılmalıdır.

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
- Güvenli şablon motoru yalnız izin verilen `order`/`item` alanlarını ve sınırlı `$each=items` yapısını işler; `eval` veya dinamik PHP çalıştırmaz.
- Hassas ödeme referansı muhasebe şablon bağlamına verilmez; iç içe `$each` genişlemesi reddedilir.
- Logo varsayılanı `POST /api/v2/GLSlips`, Mikro varsayılanı `POST /Api/apiMethods/MuhasebeFisKaydetV2`, Paraşüt `POST /v4/{company_id}/sales_invoices`.
- Yalnız `paid` ve iptal edilmemiş siparişler aktarılabilir; payload SHA-256 ile kaydedilir.
- Dış API çağrısı DB transaction dışında; `claim_token` eşzamanlı çift gönderimi engeller.
- cURL timeout/bağlantı kesintisi `send_unknown` olarak kilitlenir; dış sistem kontrol edilmeden tekrar gönderim yapılamaz.
- Panel `/yonetim/muhasebe`; ayrıntılı kabul: `docs/muhasebe-entegrasyon.md`.

### Ziyaretçi istatistikleri
- Birinci taraf, çerezsiz günlük sayfa görüntüleme agregasyonu.
- IP adresi, kullanıcı ID'si, session ID veya tam referrer URL saklanmaz.
- Sorgu string'i atılır; yalnız temizlenmiş path ve dış referrer host tutulur.
- `DNT: 1`, bot/crawler trafiği, admin, asset, upload, install, sitemap, robots ve 4xx/5xx yanıtlar sayılmaz.
- Analitik hatası ana isteği düşürmez; path kardinalitesi `analytics.daily_path_limit` ile sınırlandırılır ve fazlası `/diger` kovasına gider.
- Panel `/yonetim/istatistik`; 7/30/90 günlük toplam, günlük trafik, popüler sayfalar ve kaynaklar gösterilir.

### E-posta bülteni
- Public `/bulten` formu CSRF, honeypot, kalıcı rate-limit ve açık abonelik onayıyla korunur.
- Double opt-in kullanılır; 256-bit rastgele onay token'ının yalnız SHA-256 özeti DB'de tutulur ve bağlantı 48 saat geçerlidir.
- IP, takip çerezi veya düz unsubscribe token saklanmaz.
- Ayrılma linki `newsletter.secret` ile HMAC-SHA256 imzalanır; secret yalnız `config.php` içindedir ve en az 32 karakter olmalıdır.
- Kampanya yalnız `active` abonelere kuyruğa alınır; gönderim anında abonelik durumu tekrar kontrol edilir.
- Toplu gönderim web isteğinde değil `php scripts/newsletter_send.php 50` cron/CLI ile yapılır; başarısız `mail()` çağrısı en fazla 3 kez denenir.

### AI destekli site içi asistan
- TR/EN/DE/AR ziyaretçiler için yayımlanmış site içeriğine dayalı yardımcı widget.
- Bağlam yalnız yayımlanmış sayfa/blog ve aktif hizmet-fiyat kayıtlarından oluşturulur; sipariş, rezervasyon, kullanıcı, form ve admin verisi modele verilmez.
- OpenAI Responses API doğrudan cURL ile kullanılır; Composer bağımlılığı eklenmez.
- API isteği `store=false`; konuşma geçmişi, `previous_response_id`, araç/web erişimi veya Arcates içinde konuşma kaydı yoktur.
- Soru endpointi `POST /asistan/sor`; CSRF, IP tabanlı kalıcı 5 dakikada 12 soru limiti, 1000 karakter soru ve sınırlı bağlam uygulanır.
- Ziyaretçi sorusu ile site içeriği JSON alanlarıyla yapısal olarak ayrılır; yalnız `site_content` factual source kabul edilir.
- Beklenmeyen DB/altyapı istisnaları istemciye ayrıntı sızdırmadan 503 olarak döner.
- Widget çıktıları `textContent` ile basılır; `innerHTML` kullanılmaz. Ayrıntılı kurulum ve kabul: `docs/ai-asistan.md`.

## Sektörel modüller
- Emlak / ilan: çok dil, satılık/kiralık, filtreler ve OpenStreetMap.
- QR Menü: çok dil/RTL, kategori/ürün ve dış servissiz SVG QR.
- Gönderi Takip: PII göstermeyen public takip, kalıcı rate limit ve durum zaman çizelgesi.
- Hizmet + Fiyatlandırma: çok dilli hizmet/paket/birim/para birimi tablosu.
- Bayi / Şube Yönetimi: çok dilli şube, çalışma saatleri, koordinat/harita ve şube hizmetleri.

## Test
Yerel statik/kontrat paketi:

```bash
php tests/run.php
```

CI, PHP 8.1/8.2/8.3 üzerinde:
- tüm PHP dosyalarının syntax kontrolünü,
- statik/kontrat testlerini,
- gerçek MySQL 8 servisiyle runtime davranış testlerini,
- terk edilmiş sipariş stok/kupon iade runtime testini,
- okunabilirlik ratchet kapısını çalıştırır.

Okunabilirlik kapısı yeni dosyalarda 400 karakter/satır üstünü reddeder; mevcut teknik borç `tests/fixtures/readability_baseline.json` ile yalnız azalabilir, büyüyemez.

## Sürüm
Bu dal PR #22 güvenlik/runtime düzeltmelerini **0.7.1** olarak hazırlar.

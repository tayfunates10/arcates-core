# Changelog

## 0.7.2 - 2026-09-06
- Temiz yerel kurulum benzeri HTTP smoke testi eklendi: boş MySQL 8 veritabanında `/install`, kurulum kilidi, admin login/logout, ana sayfa, HEAD/OPTIONS, statik asset ve upload koruması gerçek HTTP üzerinden doğrulanıyor.
- PHP built-in server için güvenli `scripts/dev_router.php` eklendi; yalnız `public/` statik dosyaları ve izinli görsel türlerini `/uploads/` üzerinden servis ediyor, PHP benzeri dosyaları reddediyor.
- Dinamik `/ {locale}/{slug}` sayfası eşleşip kayıt bulunamadığında düz Türkçe çıktı yerine merkezî, temalı ve yerelleştirilmiş `ErrorPage` kullanılacak şekilde düzeltildi.
- CI matrisi PHP 8.1/8.2/8.3 üzerinde clean-install HTTP smoke adımını da çalıştıracak şekilde genişletildi.
- Yerel geliştirme ve tek komutluk built-in server akışı `docs/local-development.md` içinde belgelendi.

## 0.7.1 - 2026-09-06
- PR #22 gelişmiş kullanım/runtime denetiminde bulunan 20 bulgunun tamamı için düzeltme veya kalıcı CI koruması eklendi.
- Kurulum kilidi gerçek dosya sistemi davranışıyla güvenli hale getirildi; kilit dizini oluşturma/yazma hataları fail-closed, mevcut kullanıcı bulunan sistemde yeniden kurulum reddediliyor.
- Public rate-limit kalıcı depoya taşındı; çerez silme ile atlatma kapatıldı ve girişte IP başına password-spray tavanı eklendi.
- Kupon kodu tek noktada normalize ediliyor; kullanım sayacı/raporlama aynı kodu kullanıyor. İptalde kupon hakkı idempotent biçimde geri veriliyor.
- iyzico callback'inde tahsil edilen tutar ve para birimi sipariş toplamıyla doğrulanıyor. Ödeme başlatma POST+CSRF oldu; başarısız ödeme yeniden denenebilir.
- Sipariş durum geçiş matrisi zorunlu; cancelled terminal. Paid sipariş refund tamamlanmadan doğrudan iptal edilemiyor.
- Eski, ödenmemiş ve güvenle serbest bırakılabilir siparişler için `scripts/release_abandoned_orders.php` cron'u eklendi; initialized ödeme denemeleri otomatik iptal edilmiyor.
- Analitik fail-safe hale getirildi; 404/kontrol karakterleri ve sınırsız path kardinalitesi engellendi.
- CSRF 419 durumu korunuyor; temalı/yerelleştirilmiş 404/5xx hata sayfaları ve HEAD/OPTIONS protokol desteği eklendi.
- Unicode slug, merkezî TR/EN/DE/AR çeviri katmanı ve RTL tutarlılığı iyileştirildi.
- AI asistanında ziyaretçi sorusu ve site içeriği yapısal olarak ayrıldı; beklenmeyen DB/altyapı hataları istemciye sızdırılmıyor.
- Muhasebe şablonlarına verilen sipariş verisi izin listesine indirildi ve iç içe `$each` genişlemesi sınırlandı.
- Sepette farklı varyant sayısı 100 ile sınırlandı; güvenilir proxy yapılandırması ile istemci IP çözümü sertleştirildi.
- Nginx dağıtım örneği ve `public/` document-root zorunluluğu belgelendi.
- CI artık PHP 8.1/8.2/8.3 yanında gerçek MySQL 8 runtime davranış testleri ve okunabilirlik ratchet kapısı çalıştırıyor.

## 0.7.0 - 2026-09-06
- Emlak: çok dilli satılık/kiralık ilan, şehir/ilçe/oda/fiyat/m² filtreleri, koordinat ve OpenStreetMap bağlantısı ile yönetim CRUD'u eklendi.
- QR Menü: TR/EN/DE/AR kategori ve ürünler, RTL görünüm, yönetim ekranı, güvenli WebP yolu ve dış servissiz SVG QR üretimi eklendi.
- Gönderi Takip: PII göstermeyen public takip sorgusu, rate limit, admin gönderi/olay yönetimi ve transaction ile tutarlı durum zaman çizelgesi eklendi.
- Hizmet + Fiyatlandırma: çok dilli hizmetler, paket/işlem fiyatları, para birimi, birim etiketi ve öne çıkarma destekli public/admin tablo eklendi.
- Bayi / Şube Yönetimi: çok dilli şube kartları, iletişim/çalışma saati/koordinat alanları, OpenStreetMap ve şubeye özel hizmetler eklendi.
- Kargo API: MNG/DHL eCommerce REST, Aras SOAP ve Yurtiçi KOPS adapterleri; gönderi oluşturma, takip, etiket, panel yönetimi ve credential izolasyonu eklendi.
- Pazaryeri Senkronu: Trendyol + Hepsiburada stok/fiyat adapterleri, varyant eşleme, yalnız değişen payload, duplicate claim koruması, async batch sonucu, cron ve yönetim paneli eklendi.
- e-Fatura / e-Arşiv: Uyumsoft BasicIntegration SOAP adapteri, güvenli UBL-TR doğrulama, e-Fatura mükellef sorgusu, gönderim/durum takibi, UUID/fatura no kaydı, send_unknown çift-fatura koruması, portal uzlaştırması ve cron eklendi.
- Muhasebe Aktarımı: Logo/Netsis NetOpenX, Mikro API ve Paraşüt v4 adapterleri; güvenli JSON şablon profilleri, sipariş/kalem placeholder'ları, credential izolasyonu, SHA-256 payload kaydı ve send_unknown çift-kayıt koruması eklendi.
- Ziyaretçi İstatistikleri: IP/çerez/user-id saklamayan günlük agregasyon, DNT/bot/admin hariç tutma ve 7/30/90 günlük admin raporu eklendi.
- E-posta Bülteni: double opt-in, hashlenmiş onay token'ı, HMAC ayrılma bağlantısı, CSRF/honeypot/rate-limit, kampanya kuyruğu ve cron ile kontrollü toplu gönderim eklendi.
- AI Site Asistanı: TR/EN/DE/AR widget, yalnız yayımlanmış içerikle grounding, OpenAI Responses `store=false`, CSRF/rate-limit, prompt-injection sınırı ve konuşma saklamayan mahremiyet odaklı akış eklendi.
- Final release kapısı: kök güvenlik listesini doğrulayan `tests/security_final.php`, 400 satır sınırı ve canlı ortam kabul rehberi eklendi.

## 0.6.0 - 2026-09-06
- Faz 5: ürün/varyant/stok, sepet ve sipariş akışı, stok kilidi ve iptal stok iadesi, kargo ücret kuralları, kuponlar, resmi iyzico PHP SDK adaptörü, ödeme callback'i, sipariş durum e-postaları, mesafeli satış/iade şablonları ve B2B fiyat listesi/PDF eklendi.

## 0.5.0 - 2026-09-06
- Faz 4: oda/masa/seans birimleri, müsaitlik, `FOR UPDATE` ile yarış koşuluna dayanıklı çakışma engeli, sezonluk fiyat, rezervasyon formu/e-postası, panel onay/iptal, iCal ve takipli migration çalıştırıcısı eklendi.

## 0.4.0 - 2026-09-06
- Faz 3: KVKK/onaylı honeypot ve rate-limit iletişim formu, form kayıt paneli/e-posta bildirimi, WhatsApp CTA, kategorili portföy, kategori/etiketli blog, referanslar, çerez bildirimi ve form saklama temizliği eklendi.

## 0.3.0 - 2026-09-06
- Faz 2: mobil admin paneli, sayfa CRUD, Türkçe slug, güvenli görsel yükleme/WebP, menü yönetimi, TR/EN/DE/AR ve RTL, SEO meta/OG, dinamik sitemap/robots ve varsayılan tema eklendi.

## 0.2.0 - 2026-09-06
- Faz 1: router/bootstrap, PDO prepared statements, kullanıcı girişi, oturum sertleştirme, CSRF, 5/15dk login rate limit, loglama, kurulum kilidi ve yedekleme scripti eklendi.

## 0.1.0 - 2026-09-06
- Faz 0 repo iskeleti, yapılandırma örneği, çalışma disiplini, CI ve kurulum dokümantasyonu eklendi.

# Arcates Core 0.7.0 — Final kabul ve canlıya geçiş

Bu belge kök plandaki güvenlik, test ve teslim kontrol listesini ikiye ayırır: CI ile kod seviyesinde doğrulananlar ve yalnız gerçek hosting/servis hesabında doğrulanabilecekler.

## CI tarafından doğrulanan kod sözleşmeleri

`php tests/run.php` ve PHP 8.1/8.2/8.3 CI matrisi aşağıdaki alanları kapsar:

- PDO prepared statement tabanlı veri erişimi ve SQL injection regresyon testleri.
- `Security::escape()` / `htmlspecialchars` ile XSS güvenliği ve istemci tarafında güvenli DOM yazımı.
- `password_hash` / `password_verify`, login rate limit, login sonrası `session_regenerate_id(true)`, tam logout.
- Session cookie: HttpOnly, Secure yapılandırması, SameSite=Lax ve strict mode.
- Modül POST işlemlerinde CSRF ve ilgili public formlarda sunucu doğrulaması/rate-limit/honeypot kontrolleri.
- Görsel upload: uzantı + gerçek MIME + boyut, rastgele dosya adı, WebP çıktı; `uploads/.htaccess` PHP/PHTML/PHAR çalıştırmayı engeller.
- Kök `.htaccess`: directory listing kapalı, `.git` ve `config.php` engelli, upload PHP savunması.
- Production debug varsayımı kapalı; `Logger` canlıda hata detayını ekrana basmaz.
- `/install` bir kez çalıştıktan sonra `install.lock` ile 404 verir; ilk admin parolası hashlenir.
- KVKK/gizlilik rotaları, açık rıza, gerekli çerez bildirimi ve form saklama temizleme scripti.
- Yedekleme scriptinin repoda bulunması ve modül/regresyon testleri.
- 400 satır dosya uyarı sınırı.
- Rezervasyon çift kayıt, stok yarış koşulu, ödeme/kargo/pazaryeri/e-belge/muhasebe idempotency senaryoları.
- TR/EN/DE/AR + RTL, AI asistan grounding/prompt-injection/XSS sınırları ve bülten double opt-in güvenliği.

## Canlı hostingte zorunlu manuel kabul

Aşağıdaki maddeler CI tarafından doğrulanamaz; müşteri/production ortamında **gerçekten uygulanmadan tamamlandı sayılmaz**:

1. SSL sertifikasını etkinleştirin; HTTP → HTTPS yönlendirmesini tarayıcı ve `curl -I` ile doğrulayın.
2. Canonical host kararını verin (`www` veya non-www) ve ters varyantın tek yönlü 301 yönlendirmesini hosting seviyesinde doğrulayın.
3. `config.php` içinde production DB kullanıcısını yalnız gerekli veritabanı yetkileriyle oluşturun; güçlü panel parolası kullanın.
4. `.git`, `config.php`, `/uploads/test.php` ve kilitli `/install` URL'lerinin dışarıdan erişilemediğini gerçek domain üzerinde doğrulayın.
5. İletişim, rezervasyon, sipariş ve bülten e-postalarını gerçek alıcılara gönderin; gelen kutusu ve spam klasörünü kontrol edin. Shared hosting `mail()` teslim edilebilirliği yetersizse güvenilir SMTP/transactional mail çözümüne geçin.
6. `scripts/backup.php` ile gerçek yedek alın ve ayrı test veritabanı/dizine **geri yükleyin**. Geri yükleme görülmeden yedek kabul edilmez.
7. 360 px mobil, Chrome, Firefox, Safari ve özellikle iOS Safari'de ana akışları elle test edin; Arapça RTL'yi ayrıca kontrol edin.
8. Gerçek domain üzerinde Lighthouse/PageSpeed çalıştırın; ana sayfanın ağ koşullarına göre hedef süre/skoru karşılamasını doğrulayın. Büyük görsellerin WebP/lazy davranışını kontrol edin.
9. iyzico, MNG/Aras/Yurtiçi, Trendyol/Hepsiburada, Uyumsoft, Logo/Mikro/Paraşüt ve OpenAI gibi **kullanılacak** entegrasyonlarda yalnız gerçek test/sandbox credential'larıyla ilgili `docs/*-entegrasyon.md` kabul turunu tamamlayın.
10. AI asistanında API bütçe/rate limitini sağlayıcı hesabında belirleyin; prompt injection, bilinmeyen fiyat/müsaitlik ve TR/EN/DE/AR testlerini gerçek API ile yapın.
11. Search Console/Analytics gibi müşteriye özel dış servisler gerekiyorsa canlı domain için bağlayın; sitemap'i gönderin.
12. Favicon, OG görseli, özel 404, demo içerik temizliği ve müşteriye özel kullanım kılavuzunu gerçek site tesliminde tamamlayın.
13. `musteriler.md` dosyasına ancak gerçek müşteri kurulumu yapıldığında sürüm/PHP/hosting/tarih satırı ekleyin.

## Release engeli sayılan repo yönetimi maddeleri

Kök plan repo görünürlüğünün **private**, `main` dalının da korumalı olmasını ister. Bunlar GitHub repository administration ayarlarıdır; kod değişikliği değildir. Release öncesi repository owner tarafından doğrulanmalıdır.

## Sürüm

Kod release adayı: **0.7.0**. CI başarılı olmadan `main` birleştirmesi yapılmamalıdır. `v0.7.0` tag'i release commit'ine verilmelidir.

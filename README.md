# Arcates Core

Composer ve framework gerektirmeyen, PHP 8.1+ / MySQL 8 / Vanilla JS tabanlı modüler starter kit.

## Gereksinimler
- PHP 8.1+ (`pdo_mysql`, `mbstring`, `fileinfo`, `gd` önerilir)
- MySQL 8+
- Apache + `mod_rewrite` veya eşdeğer temiz URL yönlendirmesi

## Kurulum
1. `config.example.php` dosyasını `config.php` adıyla kopyalayın.
2. Veritabanı bilgilerini, `app.url`, e-posta ve WhatsApp ayarlarını düzenleyin.
3. Web kökünü `public/` klasörüne yönlendirin.
4. `/install` ile ilk admin hesabını oluşturun; işlem sonrası `install/install.lock` tekrar kurulumu engeller.
5. `scripts/backup.php` için günlük cron, `scripts/purge_forms.php` için form saklama politikası cron'u tanımlayın ve geri yükleme testi yapın.

## Çekirdek altyapı
Router/autoloader, PDO prepared statements, admin/editor rolleri, `password_hash`, CSRF, XSS escape, güvenli session cookie, 5 hata/15 dk giriş limiti, hata logu ve yedekleme içerir.

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

## Dallar
- `main`: kararlı sürüm
- `dev`: entegrasyon dalı
- `feature/*`: tek modül geliştirme dalları

## Güvenlik
`config.php` commit edilmez. POST formları CSRF korumalıdır; kullanıcı çıktıları escape edilir; upload klasöründe PHP çalıştırılmaz. Form kayıt saklama süresi `security.form_retention_days` ile belirlenir.

## Test
```bash
php tests/run.php
```
CI, PHP 8.1/8.2/8.3 üzerinde sözdizimi ve testleri çalıştırır.

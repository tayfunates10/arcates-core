# Arcates Core

Composer ve framework gerektirmeyen, PHP 8.1+ / MySQL 8 / Vanilla JS tabanlı modüler starter kit.

## Gereksinimler
- PHP 8.1+ (`pdo_mysql`, `mbstring`, `fileinfo`, `gd` önerilir)
- MySQL 8+
- Apache + `mod_rewrite` veya eşdeğer temiz URL yönlendirmesi

## Kurulum
1. `config.example.php` dosyasını `config.php` adıyla kopyalayın.
2. Veritabanı bilgilerini ve `app.url` değerini düzenleyin.
3. Web kökünü `public/` klasörüne yönlendirin. cPanel'de bu mümkün değilse kök `.htaccess` yönlendirmesini kullanın.
4. Tarayıcıdan `/install` yolunu açın ve kurulum adımlarını tamamlayın.
5. Kurulumdan sonra `install/install.lock` oluşur ve sihirbaz tekrar çalışmaz.
6. `scripts/backup.php` için günlük cron tanımlayın ve bir geri yükleme testi yapın.

## Dallar
- `main`: kararlı sürüm
- `dev`: entegrasyon dalı
- `feature/*`: tek modül geliştirme dalları

## Güvenlik
`config.php` commit edilmez. POST formları CSRF korumalıdır; kullanıcı çıktıları escape edilir; girişte rate limit ve oturum kimliği yenileme kullanılır.

## Test
```bash
php tests/run.php
```
CI, PHP 8.1/8.2/8.3 üzerinde sözdizimi ve testleri çalıştırır.

# Yerel geliştirme

Arcates Core 0.7.2, PHP'nin built-in sunucusuyla Apache/Nginx kurmadan yerelde ayağa kaldırılabilir.

## Gereksinimler

- PHP 8.1+ ve `pdo_mysql`, `mbstring`, `fileinfo`, `gd`, `curl`, `soap`, `xml`
- MySQL 8+
- `config.example.php` kopyasından oluşturulmuş `config.php`

## Hazırlık

1. MySQL'de boş bir veritabanı oluşturun (ör. `arcates`).
2. `config.example.php` dosyasını `config.php` olarak kopyalayın.
3. `db.host`, `db.port`, `db.name`, `db.user`, `db.pass` ve `app.url` değerlerini yerel ortama göre düzenleyin.
4. İlk kurulum öncesinde `install/install.lock` bulunmamalıdır.

## Sunucuyu başlatma

Proje kökünde:

```bash
php -S 127.0.0.1:8080 -t public scripts/dev_router.php
```

Ardından tarayıcıda:

```text
http://127.0.0.1:8080/install
```

adresini açın ve ilk admin hesabını oluşturun.

`scripts/dev_router.php` yalnız `public/` altındaki statik dosyaları doğrudan servis eder. `/uploads/` için yalnız JPG/JPEG/PNG/WebP görsellerine izin verir; PHP/PHTML/PHAR veya başka dosya türlerini çalıştırmaz/servis etmez. Bu router yalnız **yerel geliştirme** içindir; production dağıtımında `docs/nginx.conf.example` veya güvenli Apache/LiteSpeed yapılandırması kullanılmalıdır.

## Yerel testler

Statik ve kontrat testleri:

```bash
php tests/run.php
```

Temiz HTTP smoke testi, boş bir `arcates_http` veritabanı oluşturup gerçek `/install`, admin login/logout, ana sayfa, HEAD/OPTIONS, yerelleştirilmiş 404, statik asset ve `/uploads/` korumasını doğrular:

```bash
bash tests/http_smoke.sh
```

Bu smoke testi MySQL root hesabının CI fixture değerleriyle (`root` / `root`) kullanılabildiği test ortamı içindir. Kendi yerel makinenizde farklı MySQL bilgileri kullanıyorsanız `tests/fixtures/config.http.php` değerlerini yerel test dalında uyarlayın veya aynı akışı manuel çalıştırın.

## Dış entegrasyonlar

Yerel CMS/admin/kurulum akışı için iyzico, OpenAI, kargo, Trendyol/Hepsiburada, Uyumsoft veya muhasebe sağlayıcı credential'ları gerekmez. Bu entegrasyonların gerçek ağ çağrıları yalnız ilgili credential/config sağlandığında çalışır.

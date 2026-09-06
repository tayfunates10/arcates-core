# Elle çalıştırılan doğrulama sondaları

Bu dizindeki betikler **CI paketinin parçası değildir** (`tests/run.php` bunları
çalıştırmaz). `docs/test-raporu-2026-09-06.md` içindeki bulguların yeniden
üretilmesi için kullanılırlar.

Hiçbiri veritabanı sunucusu gerektirmez ve hiçbiri veri yazmaz.

```bash
php tests/manual/probe1.php   # slug, router, upload, UBL doğrulama, şablon motoru
php tests/manual/probe2.php   # hız limiti, CSRF durum kodu, analitik filtresi, locale
php tests/manual/probe3.php   # kupon usage_limit atlatma (SQLite), para aritmetiği
php tests/manual/probe4.php   # AI asistan istem enjeksiyonu, hata mesajı sızıntısı
php tests/manual/probe5.php   # çeviri dosyası bütünlüğü
php tests/manual/probe6.php   # kurulum kilidi, analitik kesintisi, dağıtım korumaları
```

Betikler mevcut davranışı **olduğu gibi** raporlar; bulgular düzeltildikçe çıktıları
değişir. Kalıcı gerileme (regression) koruması için bunlar rapordaki 8.2 bölümünde
önerildiği gibi gerçek assert'li testlere dönüştürülmelidir.

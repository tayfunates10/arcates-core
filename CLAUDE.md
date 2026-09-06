# Arcates Core çalışma kuralları

1. Tek seferde tek modül üzerinde çalışılır.
2. Her modül kendi `feature/*` dalında geliştirilir ve `dev` dalına birleştirilir.
3. Dosyalar değiştirilmeden önce okunur; mevcut davranış körlemesine ezilmez.
4. Stack sabittir: PHP 8.1+, MySQL 8, Vanilla JS. Composer ve framework kullanılmaz.
5. `config.php` asla commit edilmez.
6. Veritabanı değişiklikleri `db/migrations/` altında tarihli migration olarak eklenir; `schema.sql` kurulum anlık görüntüsüdür.
7. Her modül tamamlandığında `CHANGELOG.md` güncellenir.
8. Müşteriye özel kod core'a alınmaz; core özelliği en az iki müşteride tekrar kullanılabilir olmalıdır.

## Modül sırası
Kapsam → veri modeli → dosya listesi → küçük kod adımları → test → doküman.

## Her tur raporu
- Ne değişti ve hangi dosyalarda?
- Neyi kırabilir?
- Hangi senaryolar elle test edilmeli?

## Uyarılar
- 400 satırı aşan dosya bölünür.
- Aynı hata üçüncü kez tekrarlanıyorsa yaklaşım yeniden tasarlanır.
- İşlev azaltan sadeleştirmeler açıkça denetlenir.
- Test sonucu komut/CI çıktısıyla doğrulanmadan başarılı sayılmaz.

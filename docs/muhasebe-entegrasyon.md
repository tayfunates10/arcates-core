# Muhasebe Aktarımı — Logo / Mikro / Paraşüt

Arcates muhasebe/vergi kural motoru değildir. Sipariş verisini hedef ERP/ön muhasebe sisteminin resmî API şemasına taşır. Cari kod, muhasebe hesabı, kategori, ürün ID, KDV/vergi alanları ve firma/dönem eşlemeleri işletmenin mali müşaviri veya ERP sorumlusu tarafından doğrulanmalıdır.

## Ortak akış
1. `config.php` içinde yalnız kullanılan sağlayıcının erişim bilgilerini tanımlayın.
2. `/yonetim/muhasebe` ekranında sağlayıcıya özel bir JSON şablon profili oluşturun.
3. Şablonda `{{order.alan}}`, `{{item.alan}}` kullanabilirsiniz. Kalem tekrarında `{"$each":"items","template":{...}}` yapısını kullanın.
4. Önce tek bir test siparişini `hazırla` ile üretin ve payload'ı hedef API şemasıyla karşılaştırın.
5. Test/stage ortamında gönderin; dış kaydı Logo/Mikro/Paraşüt ekranında doğrulayın.
6. Bağlantı sonucu belirsiz (`send_unknown`) ise otomatik tekrar göndermeyin. Dış sistemde kaydı bulun ve dış ID ile uzlaştırın; kayıt gerçekten yoksa panelde açık onayla tekrar gönderime açın.

## Logo / Netsis NetOpenX REST
- Varsayılan gönderim ucu: `POST /api/v2/GLSlips`.
- Kimlik doğrulama: yapılandırılmış Bearer access token.
- NetOpenX sürümünüzün gerçek GLSlips modelini `GET /api/v2/definitions/GLSlips` ve servis şemasını `GET /api/v2/services/GLSlips?expandLevel=full` üzerinden kontrol edin.
- Arcates GL fişi alanlarını uydurmaz; profil şablonu NetOpenX kurulumunuzun beklediği JSON gövdesini üretmelidir.
- `base_url`, `access_token` ve gerekiyorsa `endpoint` yalnız `config.php` içinde tutulur.

## Mikro API
- Varsayılan gönderim ucu: `POST /Api/apiMethods/MuhasebeFisKaydetV2`.
- Profil şablonu en az `evraklar` dizisini üretmelidir.
- Arcates, `ApiKey`, `CalismaYili`, `FirmaKodu`, `KullaniciKodu`, `Sifre` alanlarını `config.php` üzerinden gövdeye ekler; profil bu credential alanlarını değiştiremez.
- Mikro API sürümünüzdeki `MuhasebeFisKaydetV2` şemasından zorunlu `evraklar/satirlar` alanlarını kontrol edin.
- Üretime geçmeden önce Mikro geliştirme/test servisinde bir fiş oluşturup GUID/fiş numarasını ERP ekranından doğrulayın.

## Paraşüt API v4
- Satış faturası ucu: `POST https://api.parasut.com/v4/{company_id}/sales_invoices`.
- Profil `data.type = sales_invoices` üretmelidir ve JSON:API ilişkilerindeki `contact`, kategori/ürün gibi ID'ler gerçek Paraşüt ID'leri olmalıdır.
- Kimlik doğrulama OAuth2'dir. `access_token` verilebilir; boşsa Arcates resmî password grant ile kısa ömürlü access token alır.
- Paraşüt erişim token'ı yaklaşık 2 saat geçerlidir; credential'lar yalnız `config.php` içindedir.
- Resmî API limiti 10 saniyede 10 istektir; bu modül tek aktarım başına token + fatura çağrısından fazlasını yapmaz.

## Örnek güvenli şablon yapısı
Aşağıdaki yalnız Arcates placeholder mekanizmasını gösterir; hedef sistemin zorunlu muhasebe alanlarını resmî API şemasına göre ekleyin.

```json
{
  "reference": "{{order.public_code}}",
  "total": "{{order.grand_total}}",
  "lines": {
    "$each": "items",
    "template": {
      "sku": "{{item.sku}}",
      "quantity": "{{item.quantity}}",
      "unit_price": "{{item.unit_price}}"
    }
  }
}
```

## Canlı kabul kontrolü
- Test siparişi ödenmiş ve iptal edilmemiş.
- Hedef firma/dönem/cari/hesap/kategori eşlemeleri yetkili kişi tarafından kontrol edildi.
- Aynı sipariş aynı profil ile iki kez hazırlanıp gönderilemiyor.
- Başarılı aktarımda dış kayıt ID/fiş numarası mümkünse `accounting_exports.external_id` alanına dönüyor.
- HTTP 4xx/5xx yanıtı kesin hata olarak `failed`; cURL timeout/bağlantı kesintisi `send_unknown` olarak işleniyor.
- `send_unknown` kaydı dış sistem kontrol edilmeden yeniden gönderilemiyor.
- `config.php` ve gerçek API sırları Git'e commit edilmiyor.

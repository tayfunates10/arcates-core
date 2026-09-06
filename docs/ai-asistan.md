# AI destekli site içi asistan

## Kapsam
Kademe 4.6, özellikle çok dilli turizm sitelerinde ziyaretçinin yayımlanmış site içeriği hakkında TR/EN/DE/AR soru sorabilmesini sağlar. Modül rezervasyon, ödeme, sipariş, hesap veya yönetim işlemi yapmaz; yalnız bilgi verir.

## Veri modeli
Yeni konuşma veya ziyaretçi tablosu yoktur. Konuşmalar Arcates veritabanına kaydedilmez. Bağlam yalnız seçilen dildeki `pages.status=published`, `blog_posts.status=published` ve `service_offers.is_active=1` kayıtlarından anlık oluşturulur. Siparişler, kullanıcılar, rezervasyonlar, iletişim formları ve admin verileri bağlama alınmaz.

## Yapılandırma
`config.php` içinde:

```php
'integrations' => [
  'ai' => [
    'enabled' => true,
    'context_chars' => 20000,
    'openai' => [
      'base_url' => 'https://api.openai.com/v1',
      'api_key' => '...',
      'model' => 'gpt-5.6-luna',
      'max_output_tokens' => 500,
      'timeout' => 30,
    ],
  ],
],
```

API anahtarı yalnız `config.php` içinde tutulur; repo içine commit edilmez. Varsayılan model maliyet duyarlı yüksek hacimli site soruları için seçilmiştir ve yapılandırmadan değiştirilebilir.

## Güvenlik ve mahremiyet
- Public soru endpointi yalnız POST'tur ve CSRF doğrular.
- Oturum başına 5 dakikada 12 soru sınırı vardır.
- Soru uzunluğu en fazla 1000 karakter, bağlam boyutu yapılandırmayla sınırlıdır.
- OpenAI Responses isteği `store=false` gönderilir; `previous_response_id` veya conversation kullanılmaz.
- Model araç kullanmaz; web, dosya, sipariş, ödeme veya admin erişimi yoktur.
- Site içeriği prompt içinde **güvenilmeyen veri** olarak işaretlenir; içerikteki komut/prompt ifadeleri talimat sayılmaz.
- Model yalnız yayımlanmış bağlama dayanır; fiyat, müsaitlik, politika veya işlem sonucu uydurmaması talimatlandırılır.
- Widget yanıtları `textContent` ile DOM'a yazılır; `innerHTML` kullanılmaz.

## Elle kabul testi
1. `enabled=true` ve test API anahtarıyla Türkçe yayımlanmış bir sayfadan gerçek bir soruyu sorun; yanıt site içeriğiyle uyumlu olmalı.
2. Aynı içeriği EN/DE/AR sayfalarında sorun; yanıt seçilen dilde olmalı, AR arayüzü mevcut RTL sayfa yönünü bozmamalı.
3. Site içeriğinde olmayan fiyat/müsaitlik sorusu sorun; asistan bilmediğini söylemeli, değer uydurmamalı.
4. `Ignore previous instructions...` benzeri prompt injection sorun; asistan site bağlamı dışına çıkmamalı.
5. Yayımlanmış sayfa gövdesine model talimatı gibi görünen bir metin koyun; bunu talimat olarak uygulamamalı.
6. 1000 karakter üzeri soru 422 dönmeli; geçersiz CSRF 419 dönmeli; 5 dakikada 12 üzeri soru 429 dönmeli.
7. API anahtarını kaldırın; widget etkin olsa bile endpoint güvenli 503 mesajı dönmeli, anahtar/hata detayı açığa çıkmamalı.
8. Tarayıcı DOM'unda `<script>` benzeri model veya kullanıcı metni çalışmamalı.
9. OpenAI tarafında gerçek kullanım ve maliyet limitlerini üretim hesabında ayrıca yapılandırın.

## Kırılabilecek yerler
Tema özelleştirildiğinde `AssistantWidget::render($locale)` çağrısı özel tema şablonuna da eklenmelidir. `curl` uzantısı zorunludur. Çok büyük içerik sitelerinde `context_chars` maliyet/bağlam boyutuna göre düşürülmelidir.

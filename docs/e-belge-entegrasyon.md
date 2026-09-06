# e-Fatura / e-Arşiv entegrasyonu

Arcates e-Belge modülü Uyumsoft BasicIntegration SOAP servisine UBL-TR belge taşır. Modül vergi oranı seçmez, KDV/muhasebe hesabı yapmaz ve yasal UBL-TR üreticisi değildir. Gönderilecek UBL-TR XML'i işletmenin faturalama/ERP sürecinden hazır ve doğrulanmış olarak gelmelidir.

## Uyumsoft ortamları

Test WSDL:

```text
http://efatura-test.uyumsoft.com.tr/Services/BasicIntegration?wsdl
```

Production WSDL:

```text
http://efatura.uyumsoft.com.tr/Services/BasicIntegration?wsdl
```

`config.php > integrations.einvoice.uyumsoft` altında `wsdl`, `username`, `password` tanımlanır. Kimlik bilgileri repoya commit edilmez.

Kullanılan servis operasyonları yapılandırılabilir; varsayılanlar:

- `IsEInvoiceUser`: alıcının e-Fatura mükellefi olup olmadığını sorgular.
- `SendInvoice`: UBL-TR faturayı Uyumsoft kuyruğuna gönderir.
- `QueryOutboxInvoiceStatus`: gönderilmiş belgenin durumunu UUID ile sorgular.

## Belge hazırlama

1. Sipariş ödenmiş olmalı ve iptal edilmiş olmamalıdır.
2. Siparişte gerçek 10 haneli VKN veya 11 haneli TCKN bulunmalıdır.
3. Yönetim panelinde **e-Belge** ekranından sipariş seçilir.
4. Hazır UBL-TR XML girilir. XML en fazla `integrations.einvoice.max_xml_bytes` kadar olabilir.
5. DTD ve ENTITY içeren XML reddedilir; dış entity çözümleme kapalıdır.
6. Kök eleman `Invoice`, `ProfileID` ve `DocumentCurrencyCode` alanları zorunludur.
7. XML veritabanında public olmayan `e_documents` tablosunda saklanır ve SHA-256 özeti tutulur.

## e-Fatura / e-Arşiv seçimi

Gönderimden hemen önce gerçek VKN/TCKN Uyumsoft `IsEInvoiceUser` ile sorgulanır.

- Mükellef ise belge `efatura` olarak işaretlenir. `EARSIVFATURA` profili reddedilir.
- Mükellef değilse belge `earsiv` olarak işaretlenir ve `ProfileID=EARSIVFATURA` zorunludur.

Arcates profili otomatik değiştirmez. Yanlış yasal profil varsa gönderim durur; doğru UBL yeniden hazırlanmalıdır.

## Gönderim ve durum

`SendInvoice` yanıtındaki UUID, fatura numarası ve senaryo saklanır. Durumlar cron veya panelden sorgulanır.

Örnek cron:

```bash
*/5 * * * * /usr/local/bin/php /home/USER/arcates/scripts/edocument_status.php >> /home/USER/arcates/logs/e-document-cron.log 2>&1
```

Uyumsoft teknik durumları Arcates içinde `draft`, `queued`, `processing`, `sent_to_gib`, `approved`, `waiting_approval`, `declined`, `returned`, `earchive_cancelled`, `failed` gibi okunabilir durumlara eşlenir.

## Çift fatura koruması

Ağ çağrısı sırasında bağlantı koparsa sağlayıcının faturayı kabul edip etmediği bilinmeyebilir. Arcates bu durumda belgeyi `send_unknown` yapar ve otomatik tekrar göndermez.

Uzlaştırma:

1. Uyumsoft portalında `LocalDocumentId` (`ARC-{sipariş kodu}`) ile arama yapılır.
2. Belge bulunduysa portal UUID/fatura numarası **Portal kaydını bağla** formuyla Arcates'e eklenir.
3. Belge kesinlikle yoksa **Portalda bu LocalDocumentId ile belge olmadığını doğruladım** kutusu işaretlenerek belge yeniden gönderime açılır.
4. Portal kontrolü yapılmadan tekrar gönderim yapılmaz.

## Canlı kabul testi

1. Önce test hesabı ve test WSDL kullanın.
2. Uyumsoft test hesabında bir e-Fatura mükellefi VKN/TCKN ile `IsEInvoiceUser` sonucunu doğrulayın.
3. Aynı testi e-Fatura mükellefi olmayan alıcıyla yapın.
4. Geçerli `TEMELFATURA` veya `TICARIFATURA` UBL ile test e-Fatura gönderin.
5. Geçerli `EARSIVFATURA` UBL ile test e-Arşiv gönderin.
6. UUID ve fatura numarasının Arcates'e yazıldığını kontrol edin.
7. Durum cronunu çalıştırın; Uyumsoft portal durumuyla Arcates durumunu karşılaştırın.
8. Test ortamında başarısız/yanlış profil senaryolarını doğrulayın.
9. Ağ kesintisi simülasyonunda `send_unknown` durumunun yeniden gönderimi engellediğini doğrulayın.
10. Test kabulü tamamlanmadan production WSDL/credential kullanılmamalıdır.

> e-Belge mevzuatı, UBL-TR şema/schematron sürümleri ve vergi oranları zamanla değişebilir. Arcates bu kuralları icat etmez; güncel GİB/entegratör doğrulaması ve işletmenin mali müşavir/faturalama süreci esas alınmalıdır.

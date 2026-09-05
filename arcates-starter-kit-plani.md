# Arcates Starter Kit — Modül Listesi ve Yapım Planı

Bu doküman `arcates-core` reposunun kökünde durur. Claude Code her oturumda bunu okur ve sıradaki fazdan devam eder.

**Stack:** PHP 8.1+ / MySQL 8 / Vanilla JS  
**Hedef ortam:** cPanel paylaşımlı hosting  
**Kural:** Composer bağımlılığı yok, framework yok. Paylaşımlı hostingte sürprizsiz çalışmalı.

---

## 1. MODÜL LİSTESİ (Edremit getiri sırasına göre)

### Kademe 0 — Çekirdek (satılmaz ama olmadan hiçbir şey olmaz)

| # | Modül | Açıklama |
|---|-------|----------|
| 0.1 | Router + Bootstrap | Tek giriş noktası, temiz URL |
| 0.2 | Veritabanı katmanı | PDO, prepared statement zorunlu |
| 0.3 | Yetkilendirme | Giriş, oturum, rol (admin / editör) |
| 0.4 | Ayar yönetimi | `config.php` + veritabanı ayarları |
| 0.5 | Güvenlik katmanı | CSRF, XSS temizleme, oturum sertleştirme, rate limit |
| 0.6 | Hata ve log yönetimi | Canlıda ekrana hata basmaz, dosyaya yazar |
| 0.7 | Yedekleme scripti | Veritabanı + upload klasörü, cron ile |

### Kademe 1 — En yüksek getirili modüller

Bunlar Edremit körfezinde parayı getiren modüller. Sırayla yaz.

| # | Modül | Hedef sektör | Neden getirisi yüksek |
|---|-------|--------------|----------------------|
| 1.1 | **Admin panel** | Hepsi | Müşteri kendi içeriğini yönetebilirse destek çağrısı azalır, sen zaman kazanırsın |
| 1.2 | **Çok dilli içerik (TR/EN/DE/AR + RTL)** | Zeytinyağı ihracatçısı, turizm, emlak | Bölgede Alman ve Arap alıcı gerçeği var. Çok dilli site tek dilli siteden belirgin biçimde pahalıya satılır |
| 1.3 | **Sayfa yönetimi + SEO alanları** | Hepsi | Her müşteride satılır. Meta, slug, sitemap, schema.org |
| 1.4 | **İletişim / teklif formu** | Hepsi | KVKK metni, spam koruma, e-posta bildirimi, panelde kayıt |
| 1.5 | **Rezervasyon / randevu** | Pansiyon, butik otel, termal, klinik, restoran | Bölgenin en büyük fırsatı. Sezonluk turizm. Müsaitlik takvimi, oda/masa tipi, kapora |
| 1.6 | **Galeri / portföy** | Tabela, matbaa, inşaat, emlak, düğün | Görsel iş yapan herkes ister. Astek'te zaten kanıtlandı |
| 1.7 | **WhatsApp entegrasyonu** | Hepsi | Türkiye'de dönüşümün çoğu buradan gelir. Kayan buton, ürün/sayfa bazlı hazır mesaj |
| 1.8 | **Blog / haber + SEO** | Hepsi | Bakım aboneliğinin gerekçesi. Aylık içerik = aylık gelir |

### Kademe 2 — Ticaret ve satış

| # | Modül | Hedef sektör | Not |
|---|-------|--------------|-----|
| 2.1 | **Ürün kataloğu** | Zeytinyağı, tabela, mobilya | Ödemesiz vitrin. E-ticaretten çok daha kolay satılır |
| 2.2 | **E-ticaret (sepet + sipariş)** | Zeytinyağı, zeytin, sabun, yerel ürün | Kademe 1 bitmeden başlama |
| 2.3 | **Ödeme entegrasyonu** | E-ticaret müşterileri | iyzico veya PayTR'nin **hazır PHP kütüphanesi** kullanılacak. Kendi ödeme mantığını yazma |
| 2.4 | **Kargo entegrasyonu** | E-ticaret | Yurtiçi / Aras / MNG etiket ve takip |
| 2.5 | **Kupon / kampanya** | E-ticaret | Upsell modülü |
| 2.6 | **B2B fiyat listesi + PDF** | Zeytinyağı toptancısı, ihracatçı | Bayiye özel fiyat, giriş korumalı |

### Kademe 3 — Sektörel modüller

| # | Modül | Hedef sektör |
|---|-------|--------------|
| 3.1 | Emlak / ilan listeleme | Yazlık, arsa, emlakçı — filtre, harita, m², oda sayısı |
| 3.2 | QR menü (çok dilli) | Restoran, kafe, otel — Akçay/Altınoluk yoğunluğu |
| 3.3 | Gönderi takip / sorgulama | Nakliyat, lojistik (Canpolat tipi müşteri) |
| 3.4 | Hizmet + fiyatlandırma tablosu | Klinik, termal, kuaför, spor salonu |
| 3.5 | Bayi / şube yönetimi | Çok noktalı işletmeler |

### Kademe 4 — Entegrasyon ve otomasyon (en kârlı, en son)

| # | Modül | Not |
|---|-------|-----|
| 4.1 | Pazaryeri stok senkronu (Trendyol / Hepsiburada) | Yüksek ücretli iş, düşük rekabet |
| 4.2 | e-Fatura / e-Arşiv entegrasyonu | Entegratör API'si üzerinden. Muhasebe hesabı yapma, sadece veri gönder |
| 4.3 | Muhasebe aktarımı (Logo / Mikro / Paraşüt) | Sipariş → fatura akışı |
| 4.4 | Ziyaretçi istatistik paneli | Bakım aboneliğine değer katar, aylık rapor gönderirsin |
| 4.5 | E-posta bülten | Basit liste + gönderim |
| 4.6 | AI destekli site içi asistan | Çok dilli turizm müşterisinde farklılaştırıcı |

---

## 2. AŞAMA AŞAMA YAPIM PLANI

Her faz bitmeden sonrakine geçilmez. Faz sonunda `git tag` atılır.

### FAZ 0 — Repo hazırlığı
**Çıktı:** Boş ama disiplinli repo

- [ ] `arcates-core` repo oluşturuldu, private
- [ ] `CLAUDE.md` yazıldı (kurallar bölüm 3'te)
- [ ] `.gitignore` yazıldı: `config.php`, `/uploads/*`, `/logs/*`, `.DS_Store`
- [ ] `config.example.php` oluşturuldu
- [ ] `README.md`: kurulum adımları
- [ ] `CHANGELOG.md` ve `VERSION` (0.1.0)
- [ ] `musteriler.md` tablosu oluşturuldu
- [ ] Klasör iskeleti açıldı
- [ ] `main` dalı korumalı, geliştirme `dev` dalında

**Doğrulama:** Repo klonlanınca `config.example.php` kopyalanıp `config.php` yapıldığında hata vermeden açılıyor mu?

### FAZ 1 — Çekirdek altyapı
**Çıktı:** Giriş yapılabilen, güvenli, boş bir sistem

- [ ] Bootstrap + router
- [ ] PDO veritabanı sınıfı
- [ ] `schema.sql` ilk hali
- [ ] Kullanıcı tablosu, `password_hash` ile giriş
- [ ] Oturum sertleştirme
- [ ] CSRF token üretimi ve doğrulaması
- [ ] Giriş denemesi sınırlama (5 hatalı = 15 dk kilit)
- [ ] Hata log sistemi
- [ ] Kurulum sihirbazı (`/install`) — kurulum sonrası kendini kilitler

**Kabul kriteri:** Bölüm 4'teki güvenlik testlerinin tamamı geçmeli. Bu faz zayıf kalırsa üstüne kurulan her şey zayıf olur.

### FAZ 2 — Admin panel + içerik
**Çıktı:** Satılabilir en küçük ürün. Kurumsal site yapılabilir.

- [ ] Panel arayüzü (mobil uyumlu)
- [ ] Sayfa CRUD, slug, taslak/yayın durumu
- [ ] Görsel yükleme + otomatik yeniden boyutlandırma + WebP
- [ ] Menü yöneticisi
- [ ] Çok dil altyapısı, `lang/` dosyaları, RTL desteği
- [ ] SEO alanları: meta title, description, OG etiketleri
- [ ] Otomatik `sitemap.xml` ve `robots.txt`
- [ ] `default` teması, mobil öncelikli

**Kabul kriteri:** Sıfırdan 5 sayfalık çok dilli bir kurumsal site 30 dakikada kurulabiliyor.

### FAZ 3 — Dönüşüm modülleri
**Çıktı:** Site artık müşteriye müşteri kazandırıyor. Fiyatın burada artar.

- [ ] İletişim formu: doğrulama, honeypot, rate limit, KVKK onay kutusu
- [ ] Form kayıtları paneli + e-posta bildirimi
- [ ] WhatsApp butonu ve hazır mesaj
- [ ] Galeri / portföy modülü, kategori destekli
- [ ] Blog modülü, kategori ve etiket
- [ ] Referans / yorum bölümü

### FAZ 4 — Rezervasyon
**Çıktı:** Turizm paketi. Bölgenin en yüksek getirili tek modülü.

- [ ] Birim tanımı (oda, masa, seans)
- [ ] Müsaitlik takvimi
- [ ] Tarih aralığı ve çakışma kontrolü — **çift rezervasyon riski en kritik hata**
- [ ] Sezonluk fiyatlandırma
- [ ] Rezervasyon formu + onay e-postası
- [ ] Panelden onay / iptal
- [ ] iCal dışa aktarım

**Kabul kriteri:** Aynı anda iki kişi aynı odayı aynı tarihe alamaz. Bu senaryo elle test edilecek.

### FAZ 5 — Ticaret
- [ ] Ürün kataloğu, varyant, stok
- [ ] Sepet ve sipariş akışı
- [ ] iyzico/PayTR resmi kütüphanesi ile ödeme
- [ ] Kargo ücreti kuralları
- [ ] Sipariş yönetimi ve durum e-postaları
- [ ] Mesafeli satış ve iade sözleşmesi şablonları

### FAZ 6 — Sektörel paketler ve entegrasyon
- [ ] Emlak modülü
- [ ] QR menü
- [ ] Gönderi takip
- [ ] İstatistik paneli
- [ ] Pazaryeri ve e-fatura entegrasyonları

---

## 3. YAPAY ZEKA ÇALIŞMA DİSİPLİNİ

Bu bölüm `CLAUDE.md` içine de girecek.

### Değişmez kurallar
1. Tek seferde tek modül. Aynı anda iki modüle dokunma.
2. Her modül kendi dalında: `feature/rezervasyon`. Bitince `dev`'e birleştir.
3. Dosyayı değiştirmeden önce **oku**. Var olan dosyanın üstüne kör yazma.
4. Yeni kütüphane ekleme. Stack sabit.
5. `config.php` asla commit edilmez.
6. Veritabanı değişikliği `db/migrations/` altına tarihli dosya olarak yazılır. `schema.sql` doğrudan değiştirilmez.
7. Her modül bitiminde `CHANGELOG.md` güncellenir.
8. Müşteriye özel kod core'a girmez. Core'a girecek şey en az iki müşteride işe yaramalı.

### Her modül için izlenecek sıra
1. **Kapsam yaz** — modül ne yapar, ne yapmaz. Tek paragraf.
2. **Veri modeli** — tablolar ve alanlar. Onaylanmadan koda geçme.
3. **Dosya listesi** — hangi dosyalar oluşacak, hangileri değişecek.
4. **Kod** — küçük parçalar halinde, her parçadan sonra dur ve göster.
5. **Test** — bölüm 4'teki listeyi uygula.
6. **Doküman** — modülün `README` bölümü, ayar seçenekleri.

### Her turda AI'dan istenecek
- Ne değişti, hangi dosyalarda, tek cümleyle
- Bu değişikliğin kırabileceği yerler
- Elle test edilmesi gereken senaryolar

### Uyarı işaretleri (bunları görürsen dur)
- Bir dosya 400 satırı aştıysa böl
- Aynı hatayı üçüncü kez düzeltmeye çalışıyorsa yaklaşım yanlıştır, sıfırdan tarif et
- "Basitleştirmek için şunu kaldırdım" derse ne kaldırdığını mutlaka kontrol et
- Kendi yazdığı kodu "test ettim, çalışıyor" derse inanma, sen çalıştır

---

## 4. GÜVENLİK KONTROL LİSTESİ

Her faz sonunda baştan sona uygulanır. Tek bir madde atlanmaz.

### Veritabanı
- [ ] Tüm sorgular prepared statement. String birleştirmeyle SQL yok
- [ ] Veritabanı kullanıcısı sadece gerekli yetkilere sahip
- [ ] Test: form alanına `' OR '1'='1` yaz, giriş yapılamadığını gör

### Çıktı
- [ ] Kullanıcıdan gelen her veri ekrana basılırken `htmlspecialchars`
- [ ] Test: yorum alanına `<script>alert(1)</script>` yaz, çalışmadığını gör

### Oturum ve şifre
- [ ] `password_hash` / `password_verify`, düz metin şifre yok
- [ ] Girişte `session_regenerate_id(true)`
- [ ] Çerez: `HttpOnly`, `Secure`, `SameSite=Lax`
- [ ] Çıkış oturumu tamamen siliyor

### Formlar
- [ ] Her POST formunda CSRF token
- [ ] Sunucu tarafında doğrulama (JS doğrulaması güvenlik değildir)
- [ ] Honeypot alanı + gönderim sıklığı sınırı

### Dosya yükleme
- [ ] Uzantı beyaz listesi **ve** MIME kontrolü
- [ ] Boyut sınırı
- [ ] Dosya adı yeniden üretiliyor, kullanıcının verdiği ad kullanılmıyor
- [ ] `/uploads/.htaccess` içinde PHP çalıştırma kapalı
- [ ] Test: `.php` uzantılı dosya yüklemeyi dene, reddedilmeli

### Yapılandırma
- [ ] Canlıda `display_errors = 0`
- [ ] `config.php` doğrudan tarayıcıdan açılamıyor
- [ ] `/install` klasörü kurulumdan sonra erişilemez
- [ ] `.git` klasörü web'den erişilemiyor
- [ ] Panel adresi tahmin edilebilir değil, brute force sınırı var

### KVKK
- [ ] Aydınlatma metni ve gizlilik politikası sayfaları
- [ ] Formlarda açık rıza kutusu, önceden işaretli değil
- [ ] Çerez bildirimi
- [ ] Form kayıtlarının saklama süresi ve silme yöntemi tanımlı

---

## 5. TEST PROTOKOLÜ

### Fonksiyon testi
Her modül için senaryo listesi yazılır ve elle uygulanır:
- Doğru veriyle çalışıyor mu
- Boş alanla ne oluyor
- Çok uzun metinle ne oluyor
- Türkçe karakter, emoji, tırnak işareti
- Aynı kaydı iki kez eklemeye çalışınca

### Performans
- [ ] Ana sayfa 2 saniyenin altında açılıyor
- [ ] Görseller WebP, lazy load açık
- [ ] Sayfa başına sorgu sayısı makul (N+1 sorgu yok)
- [ ] PageSpeed mobil skoru 70+

### Uyumluluk
- [ ] 360px genişlikte bozulma yok
- [ ] Chrome, Safari, Firefox
- [ ] iOS Safari ayrıca kontrol edilir, en çok sorun oradan çıkar
- [ ] Arapça dilde RTL düzeni doğru

### Erişilebilirlik (temel)
- [ ] Görsellerde `alt`
- [ ] Form etiketleri bağlı
- [ ] Renk kontrastı yeterli
- [ ] Klavyeyle gezinilebiliyor

### Yedek testi
- [ ] Yedek alınıyor
- [ ] **Yedekten geri yükleme denendi.** Denenmemiş yedek yedek değildir.

---

## 6. TESLİM ÖNCESİ KONTROL LİSTESİ

Her müşteri sitesinde teslimden önce:

- [ ] SSL aktif, `http` → `https` yönlendirme çalışıyor
- [ ] `www` / `www`suz yönlendirme tek yönde sabit
- [ ] Panel şifresi güçlü ve müşteriye güvenli kanaldan iletildi
- [ ] Tüm formlar test edildi, e-posta ulaşıyor (spam klasörü de kontrol edildi)
- [ ] Google Search Console ve Analytics bağlandı
- [ ] `sitemap.xml` gönderildi
- [ ] 404 sayfası düzgün
- [ ] Favicon ve OG görseli var
- [ ] Örnek/demo içerik temizlendi
- [ ] Otomatik yedek cron'u kuruldu ve bir kez test edildi
- [ ] `musteriler.md` tablosuna satır eklendi (sürüm, PHP, hosting, tarih)
- [ ] Müşteriye 1 sayfalık kullanım kılavuzu verildi
- [ ] Sözleşmede kapsam, revizyon sayısı ve bakım şartları yazılı

---

## 7. SÜRÜM TAKİBİ

- Her faz sonunda `VERSION` artır ve `git tag v0.x.0`
- Güvenlik yaması varsa `musteriler.md`'deki **tüm** müşterilere gider, istisnasız
- Özellik güncellemesi isteğe bağlıdır, güvenlik güncellemesi değildir

<?php
declare(strict_types=1);
namespace Arcates\Controllers;
final class LegalController
{
 public function kvkk(): void{$this->page('KVKK Aydınlatma Metni','İletişim formunda ad, e-posta, telefon ve mesaj bilgileri yalnız iletişim talebini yanıtlamak amacıyla işlenir. Kayıtlar yapılandırılmış saklama süresi sonunda silinir. Açık rıza kutusu önceden işaretli değildir.');}
 public function privacy(): void{$this->page('Gizlilik Politikası','Arcates Core yalnız hizmetin çalışması için gerekli verileri işler. Form verileri üçüncü taraf reklam amaçlarıyla paylaşılmaz. Silme talepleri site iletişim kanalı üzerinden iletilebilir.');}
 private function page(string $title,string $text): void{echo '<!doctype html><html lang="tr"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>'.$title.'</title><link rel="stylesheet" href="/assets/css/theme.css"><body><main class="container"><h1>'.$title.'</h1><p>'.$text.'</p></main><div id="cookie" role="status" style="position:fixed;bottom:0;inset-inline:0;background:#111827;color:#fff;padding:1rem">Bu site gerekli oturum çerezlerini kullanır. <button onclick="this.parentElement.remove()">Tamam</button></div></body></html>';}
}

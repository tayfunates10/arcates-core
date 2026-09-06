<?php
declare(strict_types=1);
namespace Arcates\Controllers;
use Arcates\Core\AdminView;use Arcates\Core\App;use Arcates\Core\Csrf;use Arcates\Core\Security;use Arcates\Services\MarketplaceSyncService;

final class MarketplaceAdminController
{
    public function index(): void
    {
        AdminView::header('Pazaryeri Senkronu');$a=Security::escape((string)App::config('app.admin_path','yonetim'));$variants=App::db()->fetchAll('SELECT v.id,v.sku,p.name FROM product_variants v JOIN products p ON p.id=v.product_id WHERE v.is_active=1 ORDER BY v.id DESC');
        echo '<section class="card"><h2>Varyant eşle</h2><form method="post" action="/'.$a.'/pazaryeri/esle">'.Csrf::field().'<select name="variant_id" required>';foreach($variants as $v)echo '<option value="'.(int)$v['id'].'">'.Security::escape($v['sku'].' · '.$v['name']).'</option>';echo '</select><select name="provider"><option value="trendyol">Trendyol</option><option value="hepsiburada">Hepsiburada</option></select><input name="external_sku" required maxlength="190" placeholder="Merchant SKU / dış SKU"><input name="external_product_id" maxlength="190" placeholder="Hepsiburada SKU (HB...)"><input name="barcode" maxlength="190" placeholder="Trendyol barkod"><input type="number" min="0.0001" step="0.0001" name="price_multiplier" value="1"><input type="number" min="0" name="stock_reserve" value="0"><button>Eşlemeyi kaydet</button></form></section>';
        echo '<section class="card"><h2>Senkron</h2>';foreach(['trendyol'=>'Trendyol','hepsiburada'=>'Hepsiburada'] as $p=>$label){echo '<form method="post" action="/'.$a.'/pazaryeri/senkron">'.Csrf::field().'<input type="hidden" name="provider" value="'.$p.'"><button>'.$label.' değişenleri gönder</button></form><form method="post" action="/'.$a.'/pazaryeri/kontrol">'.Csrf::field().'<input type="hidden" name="provider" value="'.$p.'"><button>'.$label.' batch sonuçlarını kontrol et</button></form>';}echo '</section>';
        foreach(App::db()->fetchAll('SELECT m.*,v.sku,v.stock,v.price FROM marketplace_mappings m JOIN product_variants v ON v.id=m.variant_id ORDER BY m.id DESC') as $m){echo '<article class="card"><strong>'.Security::escape(strtoupper((string)$m['provider']).' · '.$m['sku']).'</strong><p>Dış SKU: '.Security::escape($m['external_sku']).' · Stok: '.(int)$m['stock'].' · Fiyat: '.number_format((float)$m['price'],2,',','.').'</p><p>Durum: '.Security::escape((string)($m['last_status']??'bekliyor')).($m['last_error']?'<br>Hata: '.Security::escape($m['last_error']):'').'</p></article>';}
        AdminView::footer();
    }
    public function map(): void
    {
        AdminView::requireUser();Csrf::requireValid($_POST['_csrf']??null);$provider=(string)($_POST['provider']??'');if(!in_array($provider,['trendyol','hepsiburada'],true))throw new \RuntimeException('Pazaryeri geçersiz.');$variant=(int)($_POST['variant_id']??0);$sku=trim((string)($_POST['external_sku']??''));$hb=trim((string)($_POST['external_product_id']??''));$barcode=trim((string)($_POST['barcode']??''));if($variant<1||$sku==='')throw new \RuntimeException('Varyant ve dış SKU gerekli.');if($provider==='trendyol'&&$barcode==='')throw new \RuntimeException('Trendyol barkodu gerekli.');if($provider==='hepsiburada'&&$hb==='')throw new \RuntimeException('Hepsiburada SKU gerekli.');$mult=(float)($_POST['price_multiplier']??1);if($mult<=0||$mult>100)throw new \RuntimeException('Fiyat katsayısı geçersiz.');$reserve=max(0,(int)($_POST['stock_reserve']??0));
        App::db()->execute('INSERT INTO marketplace_mappings(variant_id,provider,external_sku,external_product_id,barcode,price_multiplier,stock_reserve,is_active,created_at,updated_at) VALUES(?,?,?,?,?,?,?,1,NOW(),NOW()) ON DUPLICATE KEY UPDATE external_sku=VALUES(external_sku),external_product_id=VALUES(external_product_id),barcode=VALUES(barcode),price_multiplier=VALUES(price_multiplier),stock_reserve=VALUES(stock_reserve),last_payload_hash=NULL,last_error=NULL,updated_at=NOW()',[$variant,$provider,mb_substr($sku,0,190),$hb!==''?mb_substr($hb,0,190):null,$barcode!==''?mb_substr($barcode,0,190):null,$mult,$reserve]);$this->back();
    }
    public function sync(): void{AdminView::requireUser();Csrf::requireValid($_POST['_csrf']??null);$provider=$this->provider();(new MarketplaceSyncService())->sync($provider);$this->back();}
    public function check(): void{AdminView::requireUser();Csrf::requireValid($_POST['_csrf']??null);$provider=$this->provider();(new MarketplaceSyncService())->checkPending($provider,10);$this->back();}
    private function provider(): string{$p=(string)($_POST['provider']??'');if(!in_array($p,['trendyol','hepsiburada'],true))throw new \RuntimeException('Pazaryeri geçersiz.');return $p;}
    private function back(): void{header('Location: /'.App::config('app.admin_path','yonetim').'/pazaryeri');}
}

<?php
declare(strict_types=1);
namespace Arcates\Controllers;
use Arcates\Core\App;use Arcates\Core\Csrf;use Arcates\Core\Security;use Arcates\Services\Cart;
final class CartController
{
    public function index(): void
    {
        $items=Cart::raw();echo '<!doctype html><html lang="tr"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Sepet</title><link rel="stylesheet" href="/assets/css/theme.css"><body><main class="container"><h1>Sepet</h1>';if(!$items){echo '<p>Sepetiniz boş.</p></main></body></html>';return;}$total=0.0;echo '<form method="post" action="/sepet/guncelle">'.Csrf::field();foreach($items as $id=>$qty){$v=App::db()->fetch('SELECT v.id,v.sku,v.name,v.price,v.stock,p.name product_name FROM product_variants v JOIN products p ON p.id=v.product_id WHERE v.id=?',[(int)$id]);if(!$v)continue;$line=(float)$v['price']*(int)$qty;$total+=$line;echo '<div class="card"><strong>'.Security::escape($v['product_name'].' - '.$v['name']).'</strong><label>Adet<input type="number" min="0" max="'.(int)$v['stock'].'" name="qty['.(int)$id.']" value="'.(int)$qty.'"></label><span>'.number_format($line,2,',','.').' TL</span></div>';}echo '<p><strong>Ara toplam: '.number_format($total,2,',','.').' TL</strong></p><button>Sepeti güncelle</button></form><p><a href="/odeme">Ödemeye geç</a></p></main></body></html>';
    }
    public function add(): void{Csrf::requireValid($_POST['_csrf']??null);Cart::add((int)($_POST['variant_id']??0),(int)($_POST['quantity']??1));header('Location: /sepet');}
    public function update(): void{Csrf::requireValid($_POST['_csrf']??null);foreach((array)($_POST['qty']??[]) as $id=>$qty)Cart::set((int)$id,(int)$qty);header('Location: /sepet');}
}

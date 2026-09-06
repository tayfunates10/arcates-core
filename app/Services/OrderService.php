<?php
declare(strict_types=1);
namespace Arcates\Services;
use Arcates\Core\App;
use Arcates\Core\Database;

final class OrderService
{
    public static function create(array $customer,array $cart,?string $couponCode=null): array
    {
        if(!$cart)throw new \RuntimeException('Sepet boş.');
        return App::db()->transaction(function(Database $db)use($customer,$cart,$couponCode): array{
            $items=[];$subtotal=0.0;
            foreach($cart as $variantId=>$qty){$qty=max(1,(int)$qty);$variant=$db->fetch('SELECT v.id,v.product_id,v.sku,v.name,v.price,v.stock,v.is_active,p.name product_name FROM product_variants v JOIN products p ON p.id=v.product_id WHERE v.id=? FOR UPDATE',[(int)$variantId]);if(!$variant||!(int)$variant['is_active'])throw new \RuntimeException('Ürün varyantı kullanılamıyor.');if((int)$variant['stock']<$qty)throw new \RuntimeException('Yetersiz stok: '.$variant['sku']);$line=(float)$variant['price']*$qty;$subtotal+=$line;$items[]=['product_id'=>(int)$variant['product_id'],'variant_id'=>(int)$variant['id'],'sku'=>$variant['sku'],'name'=>$variant['product_name'].' - '.$variant['name'],'unit_price'=>(float)$variant['price'],'quantity'=>$qty,'line_total'=>$line];}
            $discount=self::couponDiscount($db,$couponCode,$subtotal);$shipping=Shipping::fee(max(0,$subtotal-$discount));$grand=max(0,$subtotal-$discount+$shipping);$code=self::uuid();
            $db->execute('INSERT INTO orders(public_code,customer_name,email,phone,address,city,postal_code,subtotal,discount_total,shipping_total,grand_total,coupon_code,created_at,updated_at) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,NOW(),NOW())',[$code,$customer['name'],$customer['email'],$customer['phone'],$customer['address'],$customer['city'],$customer['postal_code']??null,$subtotal,$discount,$shipping,$grand,$couponCode?:null]);$orderId=(int)$db->lastInsertId();
            foreach($items as $item){$db->execute('INSERT INTO order_items(order_id,product_id,variant_id,sku,name,unit_price,quantity,line_total) VALUES(?,?,?,?,?,?,?,?)',[$orderId,$item['product_id'],$item['variant_id'],$item['sku'],$item['name'],$item['unit_price'],$item['quantity'],$item['line_total']]);$db->execute('UPDATE product_variants SET stock=stock-?,updated_at=NOW() WHERE id=?',[$item['quantity'],$item['variant_id']]);}
            if($discount>0&&$couponCode)$db->execute('UPDATE coupons SET usage_count=usage_count+1 WHERE code=?',[$couponCode]);
            return $db->fetch('SELECT * FROM orders WHERE id=?',[$orderId])??[];
        });
    }
    public static function cancel(int $orderId): void
    {
        App::db()->transaction(function(Database $db)use($orderId): void{$order=$db->fetch('SELECT id,status,stock_released FROM orders WHERE id=? FOR UPDATE',[$orderId]);if(!$order)throw new \RuntimeException('Sipariş bulunamadı.');if(!(int)$order['stock_released']){foreach($db->fetchAll('SELECT variant_id,quantity FROM order_items WHERE order_id=?',[$orderId]) as $item){if($item['variant_id'])$db->execute('UPDATE product_variants SET stock=stock+?,updated_at=NOW() WHERE id=?',[(int)$item['quantity'],(int)$item['variant_id']]);}$db->execute('UPDATE orders SET stock_released=1,status=\'cancelled\',updated_at=NOW() WHERE id=?',[$orderId]);}else{$db->execute('UPDATE orders SET status=\'cancelled\',updated_at=NOW() WHERE id=?',[$orderId]);}});
    }
    public static function setStatus(int $orderId,string $status): void
    {
        $allowed=['pending','confirmed','preparing','shipped','completed','cancelled'];if(!in_array($status,$allowed,true))throw new \RuntimeException('Geçersiz sipariş durumu.');if($status==='cancelled'){self::cancel($orderId);return;}App::db()->execute('UPDATE orders SET status=?,updated_at=NOW() WHERE id=?',[$status,$orderId]);
    }
    private static function couponDiscount(Database $db,?string $code,float $subtotal): float
    {
        if(!$code)return 0.0;$coupon=$db->fetch('SELECT * FROM coupons WHERE code=? AND is_active=1 AND min_total<=? AND (starts_at IS NULL OR starts_at<=NOW()) AND (ends_at IS NULL OR ends_at>=NOW()) AND (usage_limit IS NULL OR usage_count<usage_limit) FOR UPDATE',[strtoupper(trim($code)),$subtotal]);if(!$coupon)return 0.0;$value=(float)$coupon['value'];$discount=$coupon['type']==='percent'?$subtotal*min(100,$value)/100:$value;return min($subtotal,max(0,$discount));
    }
    private static function uuid(): string{$b=random_bytes(16);$b[6]=chr((ord($b[6])&0x0f)|0x40);$b[8]=chr((ord($b[8])&0x3f)|0x80);$h=bin2hex($b);return substr($h,0,8).'-'.substr($h,8,4).'-'.substr($h,12,4).'-'.substr($h,16,4).'-'.substr($h,20);}
}

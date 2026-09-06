<?php
declare(strict_types=1);
echo "########## K. Kupon usage_limit atlatma (OrderService) ##########\n";
$pdo=new PDO('sqlite::memory:',null,null,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
$pdo->exec("CREATE TABLE coupons(id INTEGER PRIMARY KEY,code TEXT,type TEXT,value REAL,min_total REAL DEFAULT 0,is_active INT DEFAULT 1,usage_limit INT,usage_count INT DEFAULT 0,starts_at TEXT,ends_at TEXT)");
$pdo->exec("INSERT INTO coupons(code,type,value,min_total,is_active,usage_limit,usage_count) VALUES('WELCOME10','percent',10,0,1,3,0)");

// OrderService::couponDiscount + usage_count artırımının birebir kopyası
function couponDiscount(PDO $db,?string $code,float $subtotal): float {
    if(!$code)return 0.0;
    $st=$db->prepare("SELECT * FROM coupons WHERE code=? AND is_active=1 AND min_total<=? AND (starts_at IS NULL OR starts_at<=datetime('now')) AND (ends_at IS NULL OR ends_at>=datetime('now')) AND (usage_limit IS NULL OR usage_count<usage_limit)");
    $st->execute([strtoupper(trim($code)),$subtotal]);          // <-- NORMALIZE EDİLİR
    $coupon=$st->fetch(); if(!$coupon)return 0.0;
    $value=(float)$coupon['value'];
    $discount=$coupon['type']==='percent'?$subtotal*min(100,$value)/100:$value;
    return min($subtotal,max(0,$discount));
}
function placeOrder(PDO $db,?string $couponCode,float $subtotal): array {
    $discount=couponDiscount($db,$couponCode,$subtotal);
    if($discount>0&&$couponCode){
        $st=$db->prepare('UPDATE coupons SET usage_count=usage_count+1 WHERE code=?');
        $st->execute([$couponCode]);                             // <-- HAM DEĞER (normalize DEĞİL)
        $affected=$st->rowCount();
    } else { $affected=0; }
    $cnt=$db->query("SELECT usage_count FROM coupons WHERE code='WELCOME10'")->fetch()['usage_count'];
    return [$discount,$affected,$cnt];
}

echo "\n-- Senaryo 1: kupon TAM BÜYÜK HARF girilir ('WELCOME10'), limit=3\n";
for($i=1;$i<=5;$i++){ [$d,$aff,$cnt]=placeOrder($pdo,'WELCOME10',100.0); printf("   sipariş %d: indirim=%5.2f  usage_count=%d  %s\n",$i,$d,$cnt,$d>0?'KUPON UYGULANDI':'kupon reddedildi'); }

$pdo->exec("UPDATE coupons SET usage_count=0 WHERE code='WELCOME10'");
echo "\n-- Senaryo 2: aynı kupon KÜÇÜK HARF girilir ('welcome10'), limit=3\n";
for($i=1;$i<=5;$i++){ [$d,$aff,$cnt]=placeOrder($pdo,'welcome10',100.0); printf("   sipariş %d: indirim=%5.2f  usage_count=%d  UPDATE etkilenen satır=%d  %s\n",$i,$d,$cnt,$aff,$d>0?'KUPON UYGULANDI':'kupon reddedildi'); }

$pdo->exec("UPDATE coupons SET usage_count=0 WHERE code='WELCOME10'");
echo "\n-- Senaryo 3: baştaki/sondaki boşlukla (' WELCOME10 '), limit=3\n";
for($i=1;$i<=4;$i++){ [$d,$aff,$cnt]=placeOrder($pdo,' WELCOME10 ',100.0); printf("   sipariş %d: indirim=%5.2f  usage_count=%d  UPDATE etkilenen satır=%d  %s\n",$i,$d,$cnt,$aff,$d>0?'KUPON UYGULANDI':'kupon reddedildi'); }

echo "\n########## L. Para birimi kayan nokta (float) aritmetiği ##########\n";
$cases=[[19.99,3],[0.1,3],[33.33,3],[1.005,1]];
foreach($cases as [$price,$qty]){
    $line=(float)$price*$qty; $subtotal=$line; $discount=$subtotal*10/100; $grand=max(0,$subtotal-$discount+0);
    printf("   birim=%-7s adet=%d line=%.10f  indirim(%%10)=%.10f  grand=%.10f -> DECIMAL(12,2)=%.2f\n",$price,$qty,$line,$discount,$grand,$grand);
}
$s=0.0; foreach([0.1,0.2,0.3,0.4] as $p)$s+=$p;
printf("   0.1+0.2+0.3+0.4 = %.20f (beklenen 1.0, eşit mi: %s)\n",$s,$s===1.0?'evet':'HAYIR');

echo "\n########## M. setStatus — iptal edilmiş siparişi geri açma ##########\n";
echo "   setStatus(id,'cancelled') -> cancel(): stok iade edilir, stock_released=1\n";
echo "   setStatus(id,'confirmed') -> sadece UPDATE orders SET status='confirmed'\n";
echo "   >> stock_released=1 KALIR; stok yeniden düşülmez -> sipariş yeniden aktif ama stok iade edilmiş.\n";
echo "   >> Durum makinesi yok: cancelled -> completed geçişi serbest.\n";

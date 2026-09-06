<?php
declare(strict_types=1);
namespace Arcates\Controllers;
use Arcates\Core\App;use Arcates\Payments\GatewayFactory;use Arcates\Services\Mailer;
final class PaymentController
{
    public function start(): void
    {
        $code=(string)($_GET['order']??'');$order=App::db()->fetch('SELECT * FROM orders WHERE public_code=?',[$code]);if(!$order){http_response_code(404);return;}$items=App::db()->fetchAll('SELECT * FROM order_items WHERE order_id=?',[(int)$order['id']]);$gateway=GatewayFactory::make();$callback=rtrim((string)App::config('app.url',''),'/').'/odeme/sonuc';$result=$gateway->initialize($order,$items,$callback);App::db()->execute('INSERT INTO payment_attempts(order_id,provider,provider_token,status,error_code,created_at) VALUES(?,?,?,?,?,NOW())',[(int)$order['id'],'iyzico',$result['token']?:null,$result['status']==='success'?'initialized':'failed',$result['error_code']?:null]);if($result['status']!=='success'||$result['page_url']==='')throw new \RuntimeException('Ödeme başlatılamadı: '.($result['error_message']?:$result['error_code']));header('Location: '.$result['page_url']);
    }
    public function callback(): void
    {
        $token=trim((string)($_POST['token']??$_GET['token']??''));if($token===''){http_response_code(400);echo 'Eksik ödeme tokenı.';return;}$attempt=App::db()->fetch('SELECT pa.id,pa.order_id,o.public_code,o.email,o.customer_name FROM payment_attempts pa JOIN orders o ON o.id=pa.order_id WHERE pa.provider_token=? ORDER BY pa.id DESC LIMIT 1',[$token]);if(!$attempt){http_response_code(404);return;}$result=GatewayFactory::make()->retrieve($token,(string)$attempt['public_code']);$paid=$result['status']==='success'&&strtoupper($result['payment_status'])==='SUCCESS';App::db()->transaction(function($db)use($attempt,$result,$paid): void{$db->execute('UPDATE payment_attempts SET status=?,error_code=? WHERE id=?',[$paid?'success':'failed',$result['error_code']?:null,(int)$attempt['id']]);$db->execute('UPDATE orders SET payment_status=?,payment_reference=?,status=CASE WHEN ?=\'paid\' THEN \'confirmed\' ELSE status END,updated_at=NOW() WHERE id=?',[$paid?'paid':'failed',$result['payment_id']?:null,$paid?'paid':'failed',(int)$attempt['order_id']]);});$order=App::db()->fetch('SELECT * FROM orders WHERE id=?',[(int)$attempt['order_id']]);if($paid&&$order)Mailer::orderStatus($order);echo '<!doctype html><html lang="tr"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Ödeme sonucu</title><body><main><h1>'.($paid?'Ödeme başarılı':'Ödeme başarısız').'</h1><p>Sipariş kodu: '.htmlspecialchars((string)$attempt['public_code'],ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8').'</p></main></body></html>';
    }
}

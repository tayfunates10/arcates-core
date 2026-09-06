<?php
declare(strict_types=1);
namespace Arcates\Services;
use Arcates\Core\App;use Arcates\Core\Logger;
final class Mailer
{
 public static function contact(array $data): bool
 {
  $to=self::headerSafe((string)App::config('mail.to',''));if($to==='')return false;$subject='Yeni iletişim formu - '.(string)App::config('app.name','Arcates');$email=self::headerSafe((string)$data['email']);$body="Ad: {$data['name']}\nE-posta: {$email}\nTelefon: {$data['phone']}\n\n{$data['message']}";$headers=['From: '.self::headerSafe((string)App::config('mail.from','noreply@localhost')),'Reply-To: '.$email,'Content-Type: text/plain; charset=UTF-8'];$ok=@mail($to,$subject,$body,implode("\r\n",$headers));if(!$ok)Logger::error('Contact mail failed',['to'=>$to]);return $ok;
 }
 public static function reservation(array $data): bool
 {
  $to=self::headerSafe((string)($data['guest_email']??''));if($to==='')return false;$subject='Rezervasyon isteğiniz alındı';$body="Kod: {$data['public_code']}\nBirim: {$data['unit_name']}\nBaşlangıç: {$data['starts_at']}\nBitiş: {$data['ends_at']}\nTutar: {$data['total_amount']} {$data['currency']}";$headers=['From: '.self::headerSafe((string)App::config('mail.from','noreply@localhost')),'Content-Type: text/plain; charset=UTF-8'];$ok=@mail($to,$subject,$body,implode("\r\n",$headers));if(!$ok)Logger::error('Reservation mail failed',['to'=>$to]);return $ok;
 }
 private static function headerSafe(string $value): string{return trim(str_replace(["\r","\n"],'',$value));}
}

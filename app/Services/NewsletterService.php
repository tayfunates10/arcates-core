<?php
declare(strict_types=1);
namespace Arcates\Services;
use Arcates\Core\App;use Arcates\Core\Database;

final class NewsletterService
{
    public function subscribe(string $email): void
    {
        $email=strtolower(trim($email));if(filter_var($email,FILTER_VALIDATE_EMAIL)===false||strlen($email)>190)throw new \RuntimeException('Geçerli e-posta gerekli.');$raw=bin2hex(random_bytes(32));$hash=hash('sha256',$raw);$send=App::db()->transaction(function(Database $db)use($email,$hash): bool{$row=$db->fetch('SELECT * FROM newsletter_subscribers WHERE email=? FOR UPDATE',[$email]);if($row&&(string)$row['status']==='active')return false;if($row){$db->execute("UPDATE newsletter_subscribers SET status='pending',confirm_token_hash=?,consent_at=NOW(),confirmed_at=NULL,unsubscribed_at=NULL,updated_at=NOW() WHERE id=?",[$hash,(int)$row['id']]);}else{$db->execute("INSERT INTO newsletter_subscribers(email,status,confirm_token_hash,consent_at,created_at,updated_at) VALUES(?,'pending',?,NOW(),NOW(),NOW())",[$email,$hash]);}return true;});if($send){$base=rtrim((string)App::config('app.url',''),'/');Mailer::newsletterConfirmation($email,$base.'/bulten/onay?token='.rawurlencode($raw));}
    }
    public function confirm(string $token): bool
    {
        if(!preg_match('/^[a-f0-9]{64}$/',$token))return false;$hash=hash('sha256',$token);return App::db()->execute("UPDATE newsletter_subscribers SET status='active',confirm_token_hash=NULL,confirmed_at=NOW(),unsubscribed_at=NULL,updated_at=NOW() WHERE status='pending' AND confirm_token_hash=? AND updated_at>=DATE_SUB(NOW(),INTERVAL 48 HOUR)",[$hash])===1;
    }
    public function unsubscribe(int $id,string $signature): bool
    {
        $row=App::db()->fetch('SELECT id,email FROM newsletter_subscribers WHERE id=?',[$id]);if(!$row||!hash_equals($this->signature((int)$row['id'],(string)$row['email']),$signature))return false;App::db()->execute("UPDATE newsletter_subscribers SET status='unsubscribed',confirm_token_hash=NULL,unsubscribed_at=NOW(),updated_at=NOW() WHERE id=?",[$id]);return true;
    }
    public function queue(int $campaignId): int
    {
        return App::db()->transaction(function(Database $db)use($campaignId): int{$c=$db->fetch('SELECT * FROM newsletter_campaigns WHERE id=? FOR UPDATE',[$campaignId]);if(!$c||(string)$c['status']!=='draft')throw new \RuntimeException('Yalnız taslak kampanya kuyruğa alınabilir.');$subs=$db->fetchAll("SELECT id FROM newsletter_subscribers WHERE status='active' ORDER BY id");foreach($subs as $s)$db->execute("INSERT IGNORE INTO newsletter_deliveries(campaign_id,subscriber_id,status,created_at,updated_at) VALUES(?,?,'pending',NOW(),NOW())",[$campaignId,(int)$s['id']]);$db->execute("UPDATE newsletter_campaigns SET status='queued',queued_at=NOW(),updated_at=NOW() WHERE id=?",[$campaignId]);return count($subs);});
    }
    public function sendBatch(int $limit=50): array
    {
        $limit=max(1,min(100,$limit));$claim=bin2hex(random_bytes(16));$ids=App::db()->transaction(function(Database $db)use($limit,$claim): array{$rows=$db->fetchAll("SELECT id FROM newsletter_deliveries WHERE (status='pending' OR (status='failed' AND attempts<3)) AND (claimed_at IS NULL OR claimed_at<DATE_SUB(NOW(),INTERVAL 10 MINUTE)) ORDER BY id ASC FOR UPDATE");$ids=[];foreach(array_slice($rows,0,$limit) as $r){$ids[]=(int)$r['id'];$db->execute("UPDATE newsletter_deliveries SET status='sending',claim_token=?,claimed_at=NOW(),attempts=attempts+1,updated_at=NOW() WHERE id=?",[$claim,(int)$r['id']]);}return $ids;});$sent=0;$failed=0;$skipped=0;foreach($ids as $id){$row=App::db()->fetch('SELECT d.*,s.email,s.status subscriber_status,c.subject,c.body_text FROM newsletter_deliveries d JOIN newsletter_subscribers s ON s.id=d.subscriber_id JOIN newsletter_campaigns c ON c.id=d.campaign_id WHERE d.id=?',[$id]);if(!$row||$row['claim_token']!==$claim)continue;if((string)$row['subscriber_status']!=='active'){App::db()->execute("UPDATE newsletter_deliveries SET status='skipped',claim_token=NULL,claimed_at=NULL,last_error=NULL,updated_at=NOW() WHERE id=?",[$id]);$skipped++;continue;}$url=$this->unsubscribeUrl((int)$row['subscriber_id'],(string)$row['email']);$ok=Mailer::newsletter((string)$row['email'],(string)$row['subject'],(string)$row['body_text'],$url);if($ok){App::db()->execute("UPDATE newsletter_deliveries SET status='sent',claim_token=NULL,claimed_at=NULL,last_error=NULL,sent_at=NOW(),updated_at=NOW() WHERE id=?",[$id]);$sent++;}else{App::db()->execute("UPDATE newsletter_deliveries SET status='failed',claim_token=NULL,claimed_at=NULL,last_error='Yerel mail() çağrısı başarısız.',updated_at=NOW() WHERE id=?",[$id]);$failed++;}}
        $campaigns=App::db()->fetchAll("SELECT id FROM newsletter_campaigns WHERE status IN ('queued','sending')");foreach($campaigns as $c){$pending=App::db()->fetch("SELECT COUNT(*) n FROM newsletter_deliveries WHERE campaign_id=? AND (status IN ('pending','sending') OR (status='failed' AND attempts<3))",[(int)$c['id']]);if((int)($pending['n']??0)===0)App::db()->execute("UPDATE newsletter_campaigns SET status='sent',sent_at=NOW(),updated_at=NOW() WHERE id=?",[(int)$c['id']]);else App::db()->execute("UPDATE newsletter_campaigns SET status='sending',updated_at=NOW() WHERE id=?",[(int)$c['id']]);}return compact('sent','failed','skipped');
    }
    private function unsubscribeUrl(int $id,string $email): string{$base=rtrim((string)App::config('app.url',''),'/');return $base.'/bulten/ayril?id='.$id.'&sig='.$this->signature($id,$email);}
    private function signature(int $id,string $email): string{$secret=(string)App::config('newsletter.secret','');if(strlen($secret)<32)throw new \RuntimeException('newsletter.secret en az 32 karakter olmalı.');return hash_hmac('sha256',$id.'|'.strtolower($email),$secret);}
}

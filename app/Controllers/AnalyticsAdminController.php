<?php
declare(strict_types=1);
namespace Arcates\Controllers;
use Arcates\Core\AdminView;use Arcates\Core\App;use Arcates\Core\Security;

final class AnalyticsAdminController
{
    public function index(): void
    {
        AdminView::header('Ziyaretçi İstatistikleri');$days=(int)($_GET['days']??30);if(!in_array($days,[7,30,90],true))$days=30;$from=(new \DateTimeImmutable('today'))->modify('-'.($days-1).' days')->format('Y-m-d');$total=App::db()->fetch('SELECT COALESCE(SUM(pageviews),0) views FROM analytics_daily WHERE day>=?',[$from]);$daily=App::db()->fetchAll('SELECT day,SUM(pageviews) views FROM analytics_daily WHERE day>=? GROUP BY day ORDER BY day ASC',[$from]);$pages=App::db()->fetchAll('SELECT path,SUM(pageviews) views FROM analytics_daily WHERE day>=? GROUP BY path ORDER BY views DESC LIMIT 20',[$from]);$refs=App::db()->fetchAll("SELECT CASE WHEN referrer_host='' THEN 'Doğrudan / iç trafik' ELSE referrer_host END source,SUM(pageviews) views FROM analytics_daily WHERE day>=? GROUP BY referrer_host ORDER BY views DESC LIMIT 20",[$from]);echo '<section class="card"><p>Takip çerezi/IP saklanmaz. DNT ve bot trafiği sayılmaz.</p><p><a href="?days=7">7 gün</a> · <a href="?days=30">30 gün</a> · <a href="?days=90">90 gün</a></p><h2>'.number_format((int)($total['views']??0),0,',','.').' sayfa görüntüleme</h2></section><section class="card"><h2>Günlük</h2><table><tr><th>Gün</th><th>Görüntüleme</th></tr>';foreach($daily as $r)echo '<tr><td>'.Security::escape($r['day']).'</td><td>'.(int)$r['views'].'</td></tr>';echo '</table></section><section class="card"><h2>En çok görüntülenen sayfalar</h2><table><tr><th>Sayfa</th><th>Görüntüleme</th></tr>';foreach($pages as $r)echo '<tr><td>'.Security::escape($r['path']).'</td><td>'.(int)$r['views'].'</td></tr>';echo '</table></section><section class="card"><h2>Kaynaklar</h2><table><tr><th>Kaynak</th><th>Görüntüleme</th></tr>';foreach($refs as $r)echo '<tr><td>'.Security::escape($r['source']).'</td><td>'.(int)$r['views'].'</td></tr>';echo '</table></section>';AdminView::footer();
    }
}

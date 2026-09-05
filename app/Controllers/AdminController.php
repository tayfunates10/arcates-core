<?php
declare(strict_types=1);
namespace Arcates\Controllers;
use Arcates\Core\AdminView;use Arcates\Core\App;use Arcates\Core\Csrf;use Arcates\Core\Security;
final class AdminController
{
 public function index(): void{$user=AdminView::requireUser();AdminView::header('Yönetim');echo '<div class="grid two"><section class="card"><h2>İçerik</h2><p>Sayfalar, menüler ve medya içeriklerini yönetin.</p></section><section class="card"><h2>Oturum</h2><p>'.Security::escape($user['email']??'').'</p><form method="post" action="/'.Security::escape((string)App::config('app.admin_path','yonetim')).'/cikis">'.Csrf::field().'<button>Çıkış</button></form></section></div>';AdminView::footer();}
}

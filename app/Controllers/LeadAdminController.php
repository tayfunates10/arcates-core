<?php
declare(strict_types=1);
namespace Arcates\Controllers;
use Arcates\Core\AdminView;use Arcates\Core\App;use Arcates\Core\Security;
final class LeadAdminController
{
 public function index(): void{AdminView::header('Form Kayıtları');foreach(App::db()->fetchAll('SELECT * FROM contact_submissions ORDER BY created_at DESC LIMIT 200') as $r){echo '<article class="card"><strong>'.Security::escape($r['name']).'</strong><p>'.Security::escape($r['email']).' · '.Security::escape($r['phone']).'</p><p>'.nl2br(Security::escape($r['message'])).'</p><small>'.Security::escape($r['created_at']).'</small></article>';}AdminView::footer();}
}

<?php
declare(strict_types=1);
namespace Arcates\Controllers;
use Arcates\Core\AdminView;use Arcates\Core\App;use Arcates\Core\Csrf;use Arcates\Core\Security;use Arcates\Core\Upload;
final class MediaController
{
 public function index(): void{AdminView::header('Medya');echo '<form class="card" method="post" enctype="multipart/form-data">'.Csrf::field().'<label>Görsel<input type="file" name="image" accept="image/jpeg,image/png,image/webp" required></label><label>Alt metin<input name="alt" required></label><button>Yükle</button></form>';foreach(App::db()->fetchAll('SELECT * FROM media ORDER BY id DESC LIMIT 100') as $m){echo '<div class="card"><img loading="lazy" width="200" src="'.Security::escape($m['path']).'" alt="'.Security::escape($m['alt_text']).'"><code>'.Security::escape($m['path']).'</code></div>';}AdminView::footer();}
 public function upload(): void{AdminView::requireUser();Csrf::requireValid($_POST['_csrf']??null);$r=Upload::image($_FILES['image']??[]);App::db()->execute('INSERT INTO media (path,alt_text,width,height,mime,created_at) VALUES (?,?,?,?,?,NOW())',[$r['path'],trim((string)($_POST['alt']??'')),$r['width'],$r['height'],$r['mime']]);header('Location: /'.App::config('app.admin_path','yonetim').'/medya');}
}

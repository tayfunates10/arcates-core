<!doctype html>
<html lang="<?= \Arcates\Core\Security::escape($locale) ?>" dir="<?= $dir ?>">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= $title ?></title><meta name="description" content="<?= $description ?>"><link rel="canonical" href="<?= \Arcates\Core\Security::escape($canonical) ?>">
<meta property="og:title" content="<?= $title ?>"><meta property="og:description" content="<?= $description ?>"><?php if (!empty($page['og_image'])): ?><meta property="og:image" content="<?= \Arcates\Core\Security::escape((string)$page['og_image']) ?>"><?php endif; ?>
<link rel="stylesheet" href="/assets/css/theme.css"></head>
<body><main class="container"><article><h1><?= \Arcates\Core\Security::escape($page['title']) ?></h1><div class="content"><?= $page['body_html'] ?></div></article></main><?= \Arcates\Core\WhatsApp::button('Merhaba, '.(string)$page['title'].' sayfası hakkında bilgi almak istiyorum.') ?><?= \Arcates\Core\AssistantWidget::render($locale) ?><div class="cookie-notice" role="status">Bu site yalnız gerekli oturum çerezlerini kullanır. <button type="button" onclick="this.parentElement.remove()">Tamam</button></div></body></html>

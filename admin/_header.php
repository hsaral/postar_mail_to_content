<?php
$siteName = setting('site_name', 'POSTAR');
?>
<!doctype html>
<html lang="tr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($pageTitle ?? 'Yonetim') ?></title>
  <link rel="stylesheet" href="<?= e(url('assets/css/style.css')) ?>">
</head>
<body>
<main class="container">
  <h1><?= e($siteName) ?> Yönetim Paneli</h1>
  <?php if (current_admin()): ?>
    <div class="admin-menu">
      <a class="btn" href="<?= e(url('admin/index.php')) ?>">Panel</a>
      <a class="btn" href="<?= e(url('admin/settings.php')) ?>">Ayarlar</a>
      <a class="btn" href="<?= e(url('admin/allowed_senders.php')) ?>">İzinli Göndericiler</a>
      <a class="btn" href="<?= e(url('admin/posts.php')) ?>">Yazılar</a>
      <a class="btn" href="<?= e(url('admin/applications.php')) ?>">Başvurular</a>
      <a class="btn alt" href="<?= e(url('admin/logout.php')) ?>">Çıkış</a>
    </div>
  <?php endif; ?>

<?php
$siteName = setting('site_name', 'POSTAR');
?>
<!doctype html>
<html lang="tr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($pageTitle ?? $siteName) ?></title>
  <link rel="stylesheet" href="<?= e(url('assets/css/style.css')) ?>">
</head>
<body>
<header class="site-header">
  <div class="container nav-wrap">
    <a class="logo" href="<?= e(url('index.php')) ?>">
      <img src="<?= e(url('assets/logo.svg')) ?>" alt="POSTAR logo">
      <span><?= e($siteName) ?></span>
    </a>
    <nav>
      <a href="<?= e(url('index.php')) ?>">Anasayfa</a>
      <a href="<?= e(url('search.php')) ?>">Arama</a>
      <a href="<?= e(url('apply.php')) ?>">Yazar Başvuru</a>
      <a href="<?= e(url('admin/login.php')) ?>">Yönetim</a>
    </nav>
  </div>
</header>
<main class="container">

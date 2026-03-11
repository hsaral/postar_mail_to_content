<?php
require_once __DIR__ . '/_bootstrap.php';
require_admin();

$stats = [
    'pending_posts' => (int) db()->query('SELECT COUNT(*) FROM posts WHERE status = "pending"')->fetchColumn(),
    'published_posts' => (int) db()->query('SELECT COUNT(*) FROM posts WHERE status = "published"')->fetchColumn(),
    'applications' => (int) db()->query('SELECT COUNT(*) FROM sender_applications WHERE status = "pending"')->fetchColumn(),
    'allowed_senders' => (int) db()->query('SELECT COUNT(*) FROM allowed_senders WHERE active = 1')->fetchColumn(),
];

$lastLog = db()->query('SELECT * FROM email_fetch_logs ORDER BY id DESC LIMIT 1')->fetch();

$pageTitle = 'Yonetim Paneli';
require __DIR__ . '/_header.php';
?>
<div class="grid grid-3">
  <div class="card"><div class="card-body"><h3>Bekleyen Yazı</h3><p><?= $stats['pending_posts'] ?></p></div></div>
  <div class="card"><div class="card-body"><h3>Yayınlanan Yazı</h3><p><?= $stats['published_posts'] ?></p></div></div>
  <div class="card"><div class="card-body"><h3>Bekleyen Başvuru</h3><p><?= $stats['applications'] ?></p></div></div>
</div>

<section class="card" style="padding:1rem;margin-top:1rem;">
  <h3>Mail Tarama Durumu</h3>
  <p>Site Maili: <strong><?= e(setting('site_email', '-')) ?></strong></p>
  <p>Tarama Aralığı: <?= e(setting('poll_interval_minutes', '5')) ?> dakika</p>
  <?php if ($lastLog): ?>
    <p>Son Çalışma: <?= e($lastLog['started_at']) ?> | Durum: <?= e($lastLog['status']) ?> | İşlenen: <?= (int) $lastLog['processed_count'] ?></p>
    <?php if ($lastLog['status'] === 'error' && !empty($lastLog['error_message'])): ?>
      <div class="notice err"><strong>Son Hata:</strong> <?= e($lastLog['error_message']) ?></div>
    <?php endif; ?>
  <?php else: ?>
    <p>Henüz mail çekme logu yok.</p>
  <?php endif; ?>
  <p><code>*/5 * * * * php /var/www/html/postar/cron/fetch_emails.php</code> şeklinde cron tanımı kullanın.</p>
  <p>Manuel test için: <code>php /var/www/html/postar/cron/fetch_emails.php --force</code></p>
</section>
<?php require __DIR__ . '/_footer.php'; ?>

<?php
require_once __DIR__ . '/init.php';

$q = trim((string) ($_GET['q'] ?? ''));
$posts = [];
if ($q !== '') {
    $stmt = db()->prepare('SELECT p.id, p.title, p.excerpt, p.mail_date, c.name AS category_name,
        (SELECT stored_path FROM media_attachments m WHERE m.post_id = p.id AND m.is_featured = 1 LIMIT 1) AS featured
        FROM posts p
        JOIN categories c ON c.id = p.category_id
        WHERE p.status = "published" AND (p.title LIKE :q OR p.content LIKE :q OR p.excerpt LIKE :q)
        ORDER BY p.mail_date DESC
        LIMIT 50');
    $stmt->execute(['q' => '%' . $q . '%']);
    $posts = $stmt->fetchAll();
}

$pageTitle = 'Arama - POSTAR';
require __DIR__ . '/partials/header.php';
?>
<h1>Yazı Arama</h1>
<form method="get">
  <input type="text" name="q" value="<?= e($q) ?>" placeholder="Başlık, içerik, anahtar kelime...">
  <button type="submit">Ara</button>
</form>

<?php if ($q !== ''): ?>
  <p><strong><?= count($posts) ?></strong> sonuç bulundu.</p>
  <div class="grid grid-3">
    <?php foreach ($posts as $post): ?>
      <article class="card">
        <a href="<?= e(url('post.php')) ?>?id=<?= (int) $post['id'] ?>">
          <img src="<?= e($post['featured'] ? url($post['featured']) : url('assets/placeholder.svg')) ?>" alt="<?= e($post['title']) ?>">
        </a>
        <div class="card-body">
          <div class="meta"><?= e($post['category_name']) ?> | <?= e(date('d.m.Y H:i', strtotime($post['mail_date']))) ?></div>
          <h3><a href="<?= e(url('post.php')) ?>?id=<?= (int) $post['id'] ?>"><?= e($post['title']) ?></a></h3>
          <p><?= e($post['excerpt'] ?: '') ?></p>
        </div>
      </article>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
<?php require __DIR__ . '/partials/footer.php'; ?>

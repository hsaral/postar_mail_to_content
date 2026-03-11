<?php
require_once __DIR__ . '/init.php';

$slug = trim((string) ($_GET['slug'] ?? ''));
$stmt = db()->prepare('SELECT id, name FROM categories WHERE slug = :slug LIMIT 1');
$stmt->execute(['slug' => $slug]);
$category = $stmt->fetch();

if (!$category) {
    http_response_code(404);
    exit('Kategori bulunamadı');
}

$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 12;
$offset = ($page - 1) * $perPage;

$countStmt = db()->prepare('SELECT COUNT(*) FROM posts WHERE category_id = :cid AND status = "published"');
$countStmt->execute(['cid' => (int) $category['id']]);
$total = (int) $countStmt->fetchColumn();
$totalPages = max(1, (int) ceil($total / $perPage));

$listStmt = db()->prepare(
    'SELECT p.id, p.title, p.excerpt, p.mail_date,
        (SELECT stored_path FROM media_attachments m WHERE m.post_id = p.id AND m.is_featured = 1 LIMIT 1) AS featured
     FROM posts p
     WHERE p.category_id = :cid AND p.status = "published"
     ORDER BY p.mail_date DESC
     LIMIT :limit OFFSET :offset'
);
$listStmt->bindValue(':cid', (int) $category['id'], PDO::PARAM_INT);
$listStmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$listStmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$listStmt->execute();
$posts = $listStmt->fetchAll();

$pageTitle = $category['name'] . ' - POSTAR';
require __DIR__ . '/partials/header.php';
?>
<h1><?= e($category['name']) ?></h1>
<div class="grid grid-3">
  <?php foreach ($posts as $post): ?>
    <article class="card">
      <a href="<?= e(url('post.php')) ?>?id=<?= (int) $post['id'] ?>">
        <img src="<?= e($post['featured'] ? url($post['featured']) : url('assets/placeholder.svg')) ?>" alt="<?= e($post['title']) ?>">
      </a>
      <div class="card-body">
        <div class="meta"><?= e(date('d.m.Y H:i', strtotime($post['mail_date']))) ?></div>
        <h3><a href="<?= e(url('post.php')) ?>?id=<?= (int) $post['id'] ?>"><?= e($post['title']) ?></a></h3>
        <p><?= e($post['excerpt'] ?: '') ?></p>
      </div>
    </article>
  <?php endforeach; ?>
</div>

<div class="pager">
  <?php for ($i = 1; $i <= $totalPages; $i++): ?>
    <?php if ($i === $page): ?>
      <span class="active"><?= $i ?></span>
    <?php else: ?>
      <a href="<?= e(url('category.php')) ?>?slug=<?= e($slug) ?>&page=<?= $i ?>"><?= $i ?></a>
    <?php endif; ?>
  <?php endfor; ?>
</div>
<?php require __DIR__ . '/partials/footer.php'; ?>

<?php
require_once __DIR__ . '/_bootstrap.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int) ($_POST['id'] ?? 0);
    $action = $_POST['action'] ?? '';

    if ($id > 0) {
        if ($action === 'publish') {
            db()->prepare('UPDATE posts SET status="published", published_at=NOW() WHERE id=:id')->execute(['id' => $id]);
        } elseif ($action === 'archive') {
            db()->prepare('UPDATE posts SET status="archived" WHERE id=:id')->execute(['id' => $id]);
        } elseif ($action === 'pending') {
            db()->prepare('UPDATE posts SET status="pending" WHERE id=:id')->execute(['id' => $id]);
        }
    }
}

$filter = $_GET['status'] ?? 'pending';
if (!in_array($filter, ['pending', 'published', 'archived'], true)) {
    $filter = 'pending';
}

$stmt = db()->prepare('SELECT p.id, p.title, p.sender_name, p.sender_email, p.mail_date, p.status, c.name as category_name
    FROM posts p JOIN categories c ON c.id=p.category_id
    WHERE p.status=:status
    ORDER BY p.mail_date DESC LIMIT 200');
$stmt->execute(['status' => $filter]);
$posts = $stmt->fetchAll();

$pageTitle = 'Yazilar';
require __DIR__ . '/_header.php';
?>
<h2>Yazılar</h2>
<p>
  <a href="?status=pending">Bekleyen</a> |
  <a href="?status=published">Yayınlanan</a> |
  <a href="?status=archived">Arşiv</a>
</p>

<table>
  <tr><th>Başlık</th><th>Kategori</th><th>Gönderen</th><th>Mail Tarihi</th><th>İşlemler</th></tr>
  <?php foreach ($posts as $p): ?>
    <tr>
      <td><a href="<?= e(url('post.php')) ?>?id=<?= (int) $p['id'] ?>" target="_blank"><?= e($p['title']) ?></a></td>
      <td><?= e($p['category_name']) ?></td>
      <td><?= e(($p['sender_name'] ?: '-') . ' / ' . $p['sender_email']) ?></td>
      <td><?= e(date('d.m.Y H:i', strtotime($p['mail_date']))) ?></td>
      <td>
        <form method="post" style="display:flex;gap:.3rem;flex-wrap:wrap;align-items:center;">
          <input type="hidden" name="id" value="<?= (int) $p['id'] ?>">
          <a class="btn" href="<?= e(url('admin/post_edit.php')) ?>?id=<?= (int) $p['id'] ?>">Düzenle</a>
          <button name="action" value="publish" type="submit">Yayına Al</button>
          <button class="btn alt" name="action" value="archive" type="submit">Arşivle</button>
          <button name="action" value="pending" type="submit">Beklemeye Al</button>
        </form>
      </td>
    </tr>
  <?php endforeach; ?>
</table>
<?php require __DIR__ . '/_footer.php'; ?>

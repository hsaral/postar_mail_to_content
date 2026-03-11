<?php
require_once __DIR__ . '/_bootstrap.php';
require_admin();

$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
if ($id <= 0) {
    http_response_code(400);
    exit('Geçersiz yazı ID');
}

$stmt = db()->prepare('SELECT * FROM posts WHERE id = :id LIMIT 1');
$stmt->execute(['id' => $id]);
$post = $stmt->fetch();

if (!$post) {
    http_response_code(404);
    exit('Yazı bulunamadı');
}

$ok = null;
$err = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim((string) ($_POST['title'] ?? ''));
    $excerpt = trim((string) ($_POST['excerpt'] ?? ''));
    $content = trim((string) ($_POST['content'] ?? ''));
    $categoryId = (int) ($_POST['category_id'] ?? 0);
    $status = (string) ($_POST['status'] ?? 'pending');

    if ($title === '' || $content === '' || $categoryId <= 0) {
        $err = 'Başlık, içerik ve kategori zorunlu.';
    } elseif (!in_array($status, ['pending', 'published', 'archived'], true)) {
        $err = 'Geçersiz durum.';
    } else {
        $pubAt = null;
        if ($status === 'published') {
            $pubAt = $post['published_at'] ?: date('Y-m-d H:i:s');
        }

        $upd = db()->prepare('UPDATE posts
            SET title = :title,
                excerpt = :excerpt,
                content = :content,
                category_id = :category_id,
                status = :status,
                published_at = :published_at
            WHERE id = :id');
        $upd->execute([
            'title' => $title,
            'excerpt' => $excerpt,
            'content' => $content,
            'category_id' => $categoryId,
            'status' => $status,
            'published_at' => $pubAt,
            'id' => $id,
        ]);

        $stmt->execute(['id' => $id]);
        $post = $stmt->fetch();
        $ok = 'Yazı güncellendi.';
    }
}

$categories = db()->query('SELECT id, name FROM categories ORDER BY name ASC')->fetchAll();

$pageTitle = 'Yazı Düzenle';
require __DIR__ . '/_header.php';
?>
<h2>Yazı Düzenle</h2>
<?php if ($ok): ?><div class="notice ok"><?= e($ok) ?></div><?php endif; ?>
<?php if ($err): ?><div class="notice err"><?= e($err) ?></div><?php endif; ?>

<div class="card" style="padding:1rem;margin-bottom:1rem;">
  <p><strong>Gönderen:</strong> <?= e(($post['sender_name'] ?: '-') . ' / ' . $post['sender_email']) ?></p>
  <p><strong>Mail Tarihi:</strong> <?= e(date('d.m.Y H:i', strtotime($post['mail_date']))) ?></p>
  <p><strong>Mevcut Durum:</strong> <?= e($post['status']) ?></p>
</div>

<form method="post" style="max-width:980px;">
  <input type="hidden" name="id" value="<?= (int) $post['id'] ?>">

  <label>Başlık
    <input type="text" name="title" required value="<?= e($post['title']) ?>">
  </label>

  <label>Kısa Özet
    <textarea name="excerpt" rows="3" placeholder="Listeleme kısa metni"><?= e($post['excerpt'] ?: '') ?></textarea>
  </label>

  <label>İçerik
    <textarea name="content" rows="16" required><?= e($post['content']) ?></textarea>
  </label>

  <label>Kategori
    <select name="category_id" required>
      <option value="">Kategori seçin</option>
      <?php foreach ($categories as $cat): ?>
        <option value="<?= (int) $cat['id'] ?>" <?= (int) $post['category_id'] === (int) $cat['id'] ? 'selected' : '' ?>>
          <?= e($cat['name']) ?>
        </option>
      <?php endforeach; ?>
    </select>
  </label>

  <label>Durum
    <select name="status">
      <option value="pending" <?= $post['status'] === 'pending' ? 'selected' : '' ?>>Beklemede</option>
      <option value="published" <?= $post['status'] === 'published' ? 'selected' : '' ?>>Yayında</option>
      <option value="archived" <?= $post['status'] === 'archived' ? 'selected' : '' ?>>Arşiv</option>
    </select>
  </label>

  <div style="display:flex;gap:.6rem;flex-wrap:wrap;">
    <button type="submit">Kaydet</button>
    <a class="btn alt" href="<?= e(url('admin/posts.php')) ?>?status=<?= e($post['status']) ?>">Listeye Dön</a>
    <a class="btn" href="<?= e(url('post.php')) ?>?id=<?= (int) $post['id'] ?>" target="_blank">Önizleme</a>
  </div>
</form>

<?php require __DIR__ . '/_footer.php'; ?>

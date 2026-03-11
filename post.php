<?php
require_once __DIR__ . '/init.php';

$id = (int) ($_GET['id'] ?? 0);
$isAdmin = current_admin() !== null;
$sql = 'SELECT p.*, c.name AS category_name, c.slug FROM posts p JOIN categories c ON c.id = p.category_id WHERE p.id = :id';
if (!$isAdmin) {
    $sql .= ' AND p.status = "published"';
}
$sql .= ' LIMIT 1';
$stmt = db()->prepare($sql);
$stmt->execute(['id' => $id]);
$post = $stmt->fetch();

if (!$post) {
    http_response_code(404);
    exit('Yazı bulunamadı');
}

$mStmt = db()->prepare('SELECT * FROM media_attachments WHERE post_id = :pid ORDER BY is_featured DESC, sort_order ASC, id ASC');
$mStmt->execute(['pid' => $id]);
$media = $mStmt->fetchAll();

$images = array_values(array_filter($media, fn($m) => $m['media_type'] === 'image'));
$videos = array_values(array_filter($media, fn($m) => $m['media_type'] === 'video'));
$audios = array_values(array_filter($media, fn($m) => $m['media_type'] === 'audio'));
$pdfs = array_values(array_filter($media, fn($m) => $m['media_type'] === 'pdf'));
$files = array_values(array_filter($media, fn($m) => $m['media_type'] === 'file'));
$featuredImage = url('assets/placeholder.svg');
if (!empty($images)) {
    $featuredImage = url($images[0]['stored_path']);
}

$pageTitle = $post['title'] . ' - POSTAR';
require __DIR__ . '/partials/header.php';
?>
<article class="card" style="padding:1rem;">
  <div class="meta">
    <a href="<?= e(url('category.php')) ?>?slug=<?= e($post['slug']) ?>"><?= e($post['category_name']) ?></a> |
    <?= e(date('d.m.Y H:i', strtotime($post['mail_date']))) ?> |
    <?= e($post['sender_name'] ?: $post['sender_email']) ?>
  </div>
  <h1><?= e($post['title']) ?></h1>
  <p><img src="<?= e($featuredImage) ?>" alt="<?= e($post['title']) ?>"></p>
  <div class="post-content"><?= nl2br(e($post['content'])) ?></div>

  <?php if ($images): ?>
    <h3>Galeri</h3>
    <div class="gallery">
      <?php foreach ($images as $image): ?>
        <img src="<?= e(url($image['stored_path'])) ?>" data-full="<?= e(url($image['stored_path'])) ?>" alt="<?= e($image['original_name']) ?>">
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <?php if ($videos): ?>
    <h3>Videolar</h3>
    <?php foreach ($videos as $video): ?>
      <video controls style="width:100%;max-width:760px;display:block;margin-bottom:.7rem;">
        <source src="<?= e(url($video['stored_path'])) ?>" type="<?= e($video['mime_type'] ?: 'video/mp4') ?>">
      </video>
      <p><a href="<?= e(url($video['stored_path'])) ?>" download>Videoyu indir (<?= e($video['original_name']) ?>)</a></p>
    <?php endforeach; ?>
  <?php endif; ?>

  <?php if ($audios): ?>
    <h3>Ses Dosyaları</h3>
    <?php foreach ($audios as $audio): ?>
      <audio controls style="width:100%;max-width:760px;display:block;margin-bottom:.7rem;">
        <source src="<?= e(url($audio['stored_path'])) ?>" type="<?= e($audio['mime_type'] ?: 'audio/mpeg') ?>">
      </audio>
      <p><a href="<?= e(url($audio['stored_path'])) ?>" download>Sesi indir (<?= e($audio['original_name']) ?>)</a></p>
    <?php endforeach; ?>
  <?php endif; ?>

  <?php if ($pdfs): ?>
    <h3>PDF Dosyaları</h3>
    <?php foreach ($pdfs as $pdf): ?>
      <iframe src="<?= e(url($pdf['stored_path'])) ?>" style="width:100%;height:560px;border:1px solid #ddd;margin-bottom:.8rem"></iframe>
    <?php endforeach; ?>
  <?php endif; ?>

  <?php if ($files): ?>
    <h3>Ek Dosyalar</h3>
    <ul class="attachments">
      <?php foreach ($files as $f): ?>
        <li><a href="<?= e(url($f['stored_path'])) ?>" download><?= e($f['original_name']) ?></a></li>
      <?php endforeach; ?>
    </ul>
  <?php endif; ?>
</article>
<?php require __DIR__ . '/partials/footer.php'; ?>

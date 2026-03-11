<?php
require_once __DIR__ . '/init.php';

$pageTitle = 'POSTAR - Mailden Bloga';

$latestPosts = db()->query(
    'SELECT p.id, p.title, p.excerpt, p.mail_date, c.name AS category_name,
            (SELECT stored_path FROM media_attachments m WHERE m.post_id = p.id AND m.is_featured = 1 LIMIT 1) AS featured
     FROM posts p
     JOIN categories c ON c.id = p.category_id
     WHERE p.status = "published"
     ORDER BY p.mail_date DESC
     LIMIT 12'
)->fetchAll();

$latestImages = db()->query(
    'SELECT m.stored_path, p.id AS post_id, p.title
     FROM media_attachments m
     JOIN posts p ON p.id = m.post_id
     WHERE m.media_type = "image" AND p.status = "published"
     ORDER BY p.mail_date DESC, m.id DESC
     LIMIT 10'
)->fetchAll();

$cloud = fetch_category_cloud();

require __DIR__ . '/partials/header.php';
?>
<section class="hero">
  <h1>POSTAR</h1>
  <p>Mail ile gelen içerikleri otomatik ayrıştırır, kategoriler, medya dosyalarıyla birlikte yayına hazırlar.</p>
  <p><strong>Sistem Nasıl Çalışır?</strong> Yetkili mail adreslerinden gelen iletiler çekilir, başlık/yazı/görseller/ekler ayrıştırılır; yönetim panelinde onaya düşer veya otomatik yayına açılır.</p>
</section>

<section class="how-wrap">
  <article class="how-card">
    <h2>Sistem Nasıl Çalışır?</h2>
    <p>POSTAR, e-posta ile gelen içerikleri blog yazısına dönüştüren ve editöryel kontrol sunan bir yayın altyapısıdır.</p>
    <ol class="how-steps">
      <li>Yönetim panelinden tanımlı site mail adresi belirlenen aralıkla IMAP üzerinden kontrol edilir.</li>
      <li>Gelen mailin göndericisi, izinli gönderici listesi ile karşılaştırılır.</li>
      <li>Mail başlığı yazı başlığına, metin içeriği yazı gövdesine dönüştürülür.</li>
      <li>Ekler türüne göre ayrıştırılır: görsel, video, ses, PDF ve dosya.</li>
      <li>İlk görsel öne çıkarılan görsel olarak atanır; görsel yoksa varsayılan görsel kullanılır.</li>
      <li>Gönderici adı veya e-posta adresine göre kategori otomatik oluşturulur/atanır.</li>
      <li>Gönderici için otomatik yayın açıksa yazı yayınlanır; değilse yönetim onayına düşer.</li>
      <li>Yönetici yazıyı “Yayına Al” veya “Arşivle” olarak kararlandırır.</li>
    </ol>
  </article>
  <figure class="how-figure">
    <img src="<?= e(url('assets/workflow.svg')) ?>" alt="POSTAR sistem akış şeması">
  </figure>
</section>

<section class="card" style="padding:1rem;margin-bottom:1rem;">
  <h2>Kategori Bulutu</h2>
  <div class="cloud">
    <?php foreach ($cloud as $cat): ?>
      <a href="<?= e(url('category.php')) ?>?slug=<?= e($cat['slug']) ?>"><?= e($cat['name']) ?> (<?= (int) $cat['post_count'] ?>)</a>
    <?php endforeach; ?>
  </div>
</section>

<section>
  <h2>Son 10 Görsel</h2>
  <div class="grid grid-3">
    <?php foreach ($latestImages as $img): ?>
      <a class="card" href="<?= e(url('post.php')) ?>?id=<?= (int) $img['post_id'] ?>">
        <img src="<?= e(url($img['stored_path'])) ?>" alt="<?= e($img['title']) ?>">
      </a>
    <?php endforeach; ?>
  </div>
</section>

<section style="margin-top:1rem;">
  <h2>Son Yazılar</h2>
  <div class="grid grid-3">
    <?php foreach ($latestPosts as $post): ?>
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
</section>
<?php require __DIR__ . '/partials/footer.php'; ?>

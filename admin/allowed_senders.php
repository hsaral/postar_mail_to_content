<?php
require_once __DIR__ . '/_bootstrap.php';
require_admin();

$ok = null;
$err = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $name = trim((string) ($_POST['name'] ?? ''));
        $email = trim((string) ($_POST['email'] ?? ''));
        $autoPublish = isset($_POST['auto_publish']) ? 1 : 0;
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $err = 'Geçerli bir e-posta girin.';
        } else {
            $stmt = db()->prepare('INSERT INTO allowed_senders(name, email, auto_publish, active) VALUES(:n,:e,:a,1)
                ON DUPLICATE KEY UPDATE name=VALUES(name), auto_publish=VALUES(auto_publish), active=1');
            $stmt->execute(['n' => $name, 'e' => strtolower($email), 'a' => $autoPublish]);
            $ok = 'Gönderici eklendi/güncellendi.';
        }
    }

    if ($action === 'toggle') {
        $id = (int) ($_POST['id'] ?? 0);
        db()->prepare('UPDATE allowed_senders SET active = IF(active=1,0,1) WHERE id=:id')->execute(['id' => $id]);
        $ok = 'Durum güncellendi.';
    }
}

$senders = db()->query('SELECT * FROM allowed_senders ORDER BY created_at DESC')->fetchAll();

$pageTitle = 'Izinli Gondericiler';
require __DIR__ . '/_header.php';
?>
<h2>İzinli Mail Adresleri</h2>
<?php if ($ok): ?><div class="notice ok"><?= e($ok) ?></div><?php endif; ?>
<?php if ($err): ?><div class="notice err"><?= e($err) ?></div><?php endif; ?>

<form method="post" class="card" style="padding:1rem;max-width:760px;margin-bottom:1rem;">
  <input type="hidden" name="action" value="add">
  <label>Ad
    <input type="text" name="name" placeholder="Gönderici adı">
  </label>
  <label>E-posta
    <input type="email" name="email" required placeholder="ornek@alanadi.com">
  </label>
  <label><input type="checkbox" name="auto_publish" value="1"> Bu adresin yazıları otomatik yayına açılsın</label>
  <button type="submit">Ekle / Güncelle</button>
</form>

<table>
  <tr><th>Ad</th><th>E-posta</th><th>Oto Yayın</th><th>Durum</th><th>İşlem</th></tr>
  <?php foreach ($senders as $s): ?>
    <tr>
      <td><?= e($s['name'] ?: '-') ?></td>
      <td><?= e($s['email']) ?></td>
      <td><?= (int) $s['auto_publish'] === 1 ? 'Evet' : 'Hayır' ?></td>
      <td><?= (int) $s['active'] === 1 ? 'Aktif' : 'Pasif' ?></td>
      <td>
        <form method="post">
          <input type="hidden" name="action" value="toggle">
          <input type="hidden" name="id" value="<?= (int) $s['id'] ?>">
          <button type="submit"><?= (int) $s['active'] === 1 ? 'Pasifleştir' : 'Aktifleştir' ?></button>
        </form>
      </td>
    </tr>
  <?php endforeach; ?>
</table>
<?php require __DIR__ . '/_footer.php'; ?>

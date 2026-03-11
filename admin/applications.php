<?php
require_once __DIR__ . '/_bootstrap.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int) ($_POST['id'] ?? 0);
    $action = $_POST['action'] ?? '';

    if ($id > 0 && in_array($action, ['approve', 'reject'], true)) {
        $status = $action === 'approve' ? 'approved' : 'rejected';

        db()->prepare('UPDATE sender_applications SET status=:s, processed_at=NOW(), processed_by=:aid WHERE id=:id')
            ->execute(['s' => $status, 'aid' => current_admin()['id'], 'id' => $id]);

        if ($action === 'approve') {
            $s = db()->prepare('SELECT name, email FROM sender_applications WHERE id=:id');
            $s->execute(['id' => $id]);
            $app = $s->fetch();
            if ($app) {
                db()->prepare('INSERT INTO allowed_senders(name, email, active, auto_publish) VALUES(:n,:e,1,0)
                    ON DUPLICATE KEY UPDATE name=VALUES(name), active=1')
                    ->execute(['n' => $app['name'], 'e' => strtolower($app['email'])]);
            }
        }
    }
}

$apps = db()->query('SELECT * FROM sender_applications ORDER BY created_at DESC LIMIT 300')->fetchAll();

$pageTitle = 'Basvurular';
require __DIR__ . '/_header.php';
?>
<h2>Başvurular</h2>
<table>
  <tr><th>Ad</th><th>E-posta</th><th>Mesaj</th><th>Durum</th><th>Tarih</th><th>İşlem</th></tr>
  <?php foreach ($apps as $a): ?>
    <tr>
      <td><?= e($a['name']) ?></td>
      <td><?= e($a['email']) ?></td>
      <td><?= e($a['message'] ?: '-') ?></td>
      <td><?= e($a['status']) ?></td>
      <td><?= e(date('d.m.Y H:i', strtotime($a['created_at']))) ?></td>
      <td>
        <?php if ($a['status'] === 'pending'): ?>
          <form method="post" style="display:flex;gap:.3rem;">
            <input type="hidden" name="id" value="<?= (int) $a['id'] ?>">
            <button type="submit" name="action" value="approve">Onayla</button>
            <button class="btn alt" type="submit" name="action" value="reject">Reddet</button>
          </form>
        <?php else: ?>-
        <?php endif; ?>
      </td>
    </tr>
  <?php endforeach; ?>
</table>
<?php require __DIR__ . '/_footer.php'; ?>

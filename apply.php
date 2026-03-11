<?php
require_once __DIR__ . '/init.php';

$msg = null;
$err = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim((string) ($_POST['name'] ?? ''));
    $email = trim((string) ($_POST['email'] ?? ''));
    $message = trim((string) ($_POST['message'] ?? ''));

    if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $err = 'Ad soyad ve geçerli e-posta zorunlu.';
    } else {
        $stmt = db()->prepare('INSERT INTO sender_applications(name, email, message) VALUES(:n, :e, :m)');
        $stmt->execute(['n' => $name, 'e' => $email, 'm' => $message]);
        $msg = 'Başvurunuz alındı. Yönetim onayı sonrası mail ile yazı gönderebilirsiniz.';
    }
}

$pageTitle = 'Yazar Başvurusu - POSTAR';
require __DIR__ . '/partials/header.php';
?>
<h1>Yazar Başvuru Formu</h1>
<p>Kendi e-posta adresinizden POSTAR sistemine yazı gönderebilmek için başvuru yapın.</p>
<?php if ($msg): ?><div class="notice ok"><?= e($msg) ?></div><?php endif; ?>
<?php if ($err): ?><div class="notice err"><?= e($err) ?></div><?php endif; ?>
<form method="post">
  <label>Ad Soyad
    <input type="text" name="name" required>
  </label>
  <label>E-posta
    <input type="email" name="email" required>
  </label>
  <label>Not
    <textarea name="message" rows="6" placeholder="Kısa açıklama"></textarea>
  </label>
  <button type="submit">Başvuruyu Gönder</button>
</form>
<?php require __DIR__ . '/partials/footer.php'; ?>

<?php
require_once __DIR__ . '/_bootstrap.php';

if (current_admin()) {
    redirect_to('admin/index.php');
}

$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim((string) ($_POST['email'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    $stmt = db()->prepare('SELECT id, password_hash FROM admins WHERE email = :email LIMIT 1');
    $stmt->execute(['email' => $email]);
    $admin = $stmt->fetch();

    if ($admin && password_verify($password, $admin['password_hash'])) {
        $_SESSION['admin_id'] = (int) $admin['id'];
        redirect_to('admin/index.php');
    }

    $error = 'Giriş bilgileri hatalı.';
}

$pageTitle = 'Yonetici Girisi';
require __DIR__ . '/_header.php';
?>
<h2>Giriş</h2>
<?php if ($error): ?><div class="notice err"><?= e($error) ?></div><?php endif; ?>
<form method="post" style="max-width:420px;">
  <label>E-posta
    <input type="email" name="email" required>
  </label>
  <label>Şifre
    <input type="password" name="password" required>
  </label>
  <button type="submit">Giriş Yap</button>
</form>
<?php require __DIR__ . '/_footer.php'; ?>

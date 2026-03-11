<?php
require_once __DIR__ . '/_bootstrap.php';
require_admin();

$keys = ['site_name','site_email','imap_host','imap_port','imap_encryption','imap_username','imap_password','imap_mailbox','poll_interval_minutes'];

$ok = null;
$err = null;
$testResult = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'save';

    if ($action === 'gmail_preset') {
        save_setting('imap_host', 'imap.gmail.com');
        save_setting('imap_port', '993');
        save_setting('imap_encryption', 'ssl');
        save_setting('imap_mailbox', 'INBOX');
        $ok = 'Gmail hazır ayarları dolduruldu. Kullanıcı ve App Password alanlarını girip kaydedin.';
    } elseif ($action === 'test_imap') {
        $host = trim((string) ($_POST['imap_host'] ?? ''));
        $port = trim((string) ($_POST['imap_port'] ?? '993'));
        $enc = trim((string) ($_POST['imap_encryption'] ?? 'ssl'));
        $user = trim((string) ($_POST['imap_username'] ?? ''));
        $pass = trim((string) ($_POST['imap_password'] ?? ''));
        $box = trim((string) ($_POST['imap_mailbox'] ?? 'INBOX'));

        if (!function_exists('imap_open')) {
            $err = 'PHP IMAP eklentisi sunucuda yüklü değil.';
        } elseif ($host === '' || $user === '' || $pass === '') {
            $err = 'Test için host, kullanıcı ve şifre alanları dolu olmalı.';
        } else {
            $flags = '/imap';
            if ($enc === 'ssl') {
                $flags .= '/ssl';
            } elseif ($enc === 'tls') {
                $flags .= '/tls';
            } else {
                $flags .= '/notls';
            }
            $mailbox = '{' . $host . ':' . $port . $flags . '}' . ($box !== '' ? $box : 'INBOX');
            $inbox = @imap_open($mailbox, $user, $pass);
            if ($inbox) {
                $count = imap_num_msg($inbox);
                imap_close($inbox);
                $testResult = 'Bağlantı başarılı. Kutu erişimi var, toplam mesaj: ' . (int) $count;
            } else {
                $imapErr = imap_last_error() ?: 'Bilinmeyen IMAP hatası';
                $err = 'Bağlantı başarısız: ' . $imapErr;
            }
        }
    } else {
        foreach ($keys as $key) {
            save_setting($key, trim((string) ($_POST[$key] ?? '')));
        }
        $ok = 'Ayarlar kaydedildi.';
    }
}

$vals = [];
foreach ($keys as $k) {
    $vals[$k] = setting($k, '');
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'test_imap') {
    foreach ($keys as $k) {
        if (isset($_POST[$k])) {
            $vals[$k] = trim((string) $_POST[$k]);
        }
    }
}

$pageTitle = 'Ayarlar';
require __DIR__ . '/_header.php';
?>
<h2>Site ve Mail Ayarları</h2>
<?php if ($ok): ?><div class="notice ok"><?= e($ok) ?></div><?php endif; ?>
<?php if ($testResult): ?><div class="notice ok"><?= e($testResult) ?></div><?php endif; ?>
<?php if ($err): ?><div class="notice err"><?= e($err) ?></div><?php endif; ?>

<section class="card" style="padding:1rem;max-width:760px;margin-bottom:1rem;">
  <h3>Gmail Hızlı Kurulum</h3>
  <p>Gmail çoğu durumda normal hesap şifresi ile IMAP erişimi vermez. 2 adımlı doğrulama açıp <strong>App Password</strong> üretin ve IMAP şifresi olarak onu kullanın.</p>
  <form method="post" style="margin-bottom:.6rem;">
    <input type="hidden" name="action" value="gmail_preset">
    <button type="submit">Gmail Ayarlarını Otomatik Doldur</button>
  </form>
  <p style="margin:.2rem 0 0 0;color:#5f6c78;">Host: imap.gmail.com | Port: 993 | Şifreleme: SSL | Klasör: INBOX</p>
</section>

<form method="post" style="max-width:760px;">
  <label>Site Adı
    <input type="text" name="site_name" value="<?= e($vals['site_name']) ?>">
  </label>
  <label>Site Özel Mail Adresi
    <input type="email" name="site_email" value="<?= e($vals['site_email']) ?>" placeholder="yazi@alanadi.com">
  </label>
  <label>IMAP Host
    <input type="text" name="imap_host" value="<?= e($vals['imap_host']) ?>" placeholder="imap.ornek.com">
  </label>
  <label>IMAP Port
    <input type="number" name="imap_port" value="<?= e($vals['imap_port']) ?>">
  </label>
  <label>Şifreleme
    <select name="imap_encryption">
      <option value="ssl" <?= $vals['imap_encryption'] === 'ssl' ? 'selected' : '' ?>>SSL</option>
      <option value="tls" <?= $vals['imap_encryption'] === 'tls' ? 'selected' : '' ?>>TLS</option>
      <option value="none" <?= $vals['imap_encryption'] === 'none' ? 'selected' : '' ?>>Yok</option>
    </select>
  </label>
  <label>IMAP Kullanıcı
    <input type="text" name="imap_username" value="<?= e($vals['imap_username']) ?>">
  </label>
  <label>IMAP Şifre
    <input type="password" name="imap_password" value="<?= e($vals['imap_password']) ?>">
  </label>
  <label>Klasör
    <input type="text" name="imap_mailbox" value="<?= e($vals['imap_mailbox']) ?>" placeholder="INBOX">
  </label>
  <label>Tarama Aralığı (dakika)
    <input type="number" min="1" name="poll_interval_minutes" value="<?= e($vals['poll_interval_minutes']) ?>">
  </label>
  <div style="display:flex;gap:.6rem;flex-wrap:wrap;">
    <button type="submit" name="action" value="save">Kaydet</button>
    <button type="submit" name="action" value="test_imap" class="btn alt">IMAP Bağlantısını Test Et</button>
  </div>
</form>
<?php require __DIR__ . '/_footer.php'; ?>

<?php

require_once __DIR__ . '/../lib/mail_ingest.php';

$force = false;
if (php_sapi_name() === 'cli') {
    $argvCopy = $argv ?? [];
    $force = in_array('--force', $argvCopy, true) || in_array('-f', $argvCopy, true);
} else {
    $force = isset($_GET['force']) && $_GET['force'] === '1';
}

$intervalMinutes = max(1, (int) setting('poll_interval_minutes', '5'));
$lastPollRun = (int) setting('last_poll_run', '0');
$nowTs = time();
if (!$force && $lastPollRun > 0 && ($nowTs - $lastPollRun) < ($intervalMinutes * 60)) {
    echo "ATLANDI - interval dolmadi\n";
    exit(0);
}
save_setting('last_poll_run', (string) $nowTs);

$start = date('Y-m-d H:i:s');
$logIns = db()->prepare('INSERT INTO email_fetch_logs(started_at, status, processed_count) VALUES(:s, "ok", 0)');
$logIns->execute(['s' => $start]);
$logId = (int) db()->lastInsertId();

try {
    $result = ingest_new_emails();

    $upd = db()->prepare('UPDATE email_fetch_logs SET finished_at=:f, status="ok", processed_count=:c WHERE id=:id');
    $upd->execute([
        'f' => date('Y-m-d H:i:s'),
        'c' => (int) $result['processed'],
        'id' => $logId,
    ]);

    echo sprintf("OK - islenen: %d, son_uid: %d\n", (int) $result['processed'], (int) $result['max_uid']);
} catch (Throwable $e) {
    $upd = db()->prepare('UPDATE email_fetch_logs SET finished_at=:f, status="error", error_message=:m WHERE id=:id');
    $upd->execute([
        'f' => date('Y-m-d H:i:s'),
        'm' => $e->getMessage(),
        'id' => $logId,
    ]);

    fwrite(STDERR, 'HATA: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}

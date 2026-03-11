<?php
require_once __DIR__ . '/lib/db.php';

if (php_sapi_name() !== 'cli' && ($_GET['token'] ?? '') !== 'postar-install') {
    http_response_code(403);
    exit('Kurulum yetkisi yok. CLI kullanın veya ?token=postar-install ekleyin.');
}

$sql = file_get_contents(__DIR__ . '/db/schema.sql');
if ($sql === false) {
    exit('schema.sql okunamadi');
}

try {
    $pdo = db();
    foreach (array_filter(array_map('trim', explode(";\n", $sql))) as $statement) {
        if ($statement !== '') {
            $pdo->exec($statement);
        }
    }
    echo "Kurulum tamamlandi. Varsayilan admin: admin@postar.local / Admin123!\n";
} catch (Throwable $e) {
    echo 'Kurulum hatasi: ' . $e->getMessage() . "\n";
    exit(1);
}

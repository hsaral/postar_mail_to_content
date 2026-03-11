<?php

$config = [
    'db' => [
        'host' => 'XXXXXX',
        'name' => 'XXXXXX',
        'user' => 'XXXXXX',
        'pass' => 'XXXXXX',
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_turkish_ci',
    ],
    'app' => [
        'base_url' => '',
        'uploads_dir' => __DIR__ . '/uploads',
    ],
];

$localConfigPath = __DIR__ . '/config.local.php';
if (is_file($localConfigPath)) {
    $local = require $localConfigPath;
    if (is_array($local)) {
        $config = array_replace_recursive($config, $local);
    }
}

return $config;

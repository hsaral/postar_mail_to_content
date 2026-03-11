<?php

require_once __DIR__ . '/db.php';

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function setting(string $key, ?string $default = null): ?string
{
    $stmt = db()->prepare('SELECT value FROM site_settings WHERE `key` = :k LIMIT 1');
    $stmt->execute(['k' => $key]);
    $val = $stmt->fetchColumn();

    if ($val === false || $val === null) {
        return $default;
    }

    return (string) $val;
}

function save_setting(string $key, string $value): void
{
    $stmt = db()->prepare('INSERT INTO site_settings(`key`, `value`) VALUES(:k, :v)
        ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)');
    $stmt->execute(['k' => $key, 'v' => $value]);
}

function slugify(string $text): string
{
    $map = ['ç' => 'c', 'Ç' => 'c', 'ğ' => 'g', 'Ğ' => 'g', 'ı' => 'i', 'İ' => 'i', 'ö' => 'o', 'Ö' => 'o', 'ş' => 's', 'Ş' => 's', 'ü' => 'u', 'Ü' => 'u'];
    $text = strtr($text, $map);
    $text = mb_strtolower($text, 'UTF-8');
    $text = preg_replace('/[^a-z0-9]+/u', '-', $text);
    $text = trim((string) $text, '-');
    return $text !== '' ? $text : 'kategori';
}

function excerpt(string $text, int $len = 180): string
{
    $plain = trim(strip_tags($text));
    if (mb_strlen($plain, 'UTF-8') <= $len) {
        return $plain;
    }
    return rtrim(mb_substr($plain, 0, $len, 'UTF-8')) . '...';
}

function current_admin(): ?array
{
    if (empty($_SESSION['admin_id'])) {
        return null;
    }

    static $admin = false;
    if ($admin !== false) {
        return $admin ?: null;
    }

    $stmt = db()->prepare('SELECT id, name, email FROM admins WHERE id = :id LIMIT 1');
    $stmt->execute(['id' => (int) $_SESSION['admin_id']]);
    $admin = $stmt->fetch() ?: null;

    return $admin;
}

function require_admin(): void
{
    if (!current_admin()) {
        redirect_to('admin/login.php');
    }
}

function app_base_path(): string
{
    static $basePath = null;
    if ($basePath !== null) {
        return $basePath;
    }

    $config = require __DIR__ . '/../config.php';
    $manual = trim((string) ($config['app']['base_url'] ?? ''));
    if ($manual !== '') {
        $manual = '/' . trim($manual, '/');
        $basePath = $manual === '/' ? '' : $manual;
        return $basePath;
    }

    $docRoot = isset($_SERVER['DOCUMENT_ROOT']) ? realpath((string) $_SERVER['DOCUMENT_ROOT']) : false;
    $appRoot = realpath(__DIR__ . '/..');
    if ($docRoot && $appRoot && str_starts_with($appRoot, $docRoot)) {
        $rel = str_replace('\\', '/', substr($appRoot, strlen($docRoot)));
        $rel = trim($rel, '/');
        $basePath = $rel === '' ? '' : '/' . $rel;
        return $basePath;
    }

    $basePath = '';
    return $basePath;
}

function url(string $path = ''): string
{
    $base = app_base_path();
    $path = ltrim($path, '/');

    if ($path === '') {
        return $base === '' ? '/' : $base . '/';
    }

    return ($base === '' ? '' : $base) . '/' . $path;
}

function redirect_to(string $path): void
{
    header('Location: ' . url($path));
    exit;
}

function media_type_from_mime(?string $mime, string $filename): string
{
    $mime = strtolower((string) $mime);
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

    if (str_starts_with($mime, 'image/') || in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp'], true)) {
        return 'image';
    }
    if (str_starts_with($mime, 'video/') || in_array($ext, ['mp4', 'webm', 'mov', 'avi', 'mkv'], true)) {
        return 'video';
    }
    if (str_starts_with($mime, 'audio/') || in_array($ext, ['mp3', 'wav', 'ogg', 'm4a', 'aac', 'flac'], true)) {
        return 'audio';
    }
    if ($mime === 'application/pdf' || $ext === 'pdf') {
        return 'pdf';
    }

    return 'file';
}

function fetch_category_cloud(): array
{
    $sql = 'SELECT c.id, c.name, c.slug, COUNT(p.id) AS post_count
            FROM categories c
            LEFT JOIN posts p ON p.category_id = c.id AND p.status = "published"
            GROUP BY c.id
            HAVING post_count > 0
            ORDER BY post_count DESC, c.name ASC';
    return db()->query($sql)->fetchAll();
}

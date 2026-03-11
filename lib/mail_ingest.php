<?php

require_once __DIR__ . '/../init.php';

function decode_mime_header_value(string $value): string
{
    $elements = imap_mime_header_decode($value);
    $out = '';
    foreach ($elements as $el) {
        $charset = strtoupper($el->charset ?? 'UTF-8');
        $text = $el->text ?? '';
        if ($charset !== 'DEFAULT' && $charset !== 'UTF-8') {
            $converted = @iconv($charset, 'UTF-8//IGNORE', $text);
            $out .= $converted !== false ? $converted : $text;
        } else {
            $out .= $text;
        }
    }
    return trim($out);
}

function decode_part_content(string $data, int $encoding): string
{
    return match ($encoding) {
        3 => base64_decode($data, true) ?: '',
        4 => quoted_printable_decode($data),
        default => $data,
    };
}

function decode_rfc2231_value(string $value): string
{
    $value = trim($value, "\"' ");
    if (preg_match("/^[^']*'[^']*'(.*)$/", $value, $m)) {
        $value = $m[1];
    }
    $decoded = rawurldecode($value);
    return decode_mime_header_value($decoded);
}

function extract_filename_from_params(array $params): string
{
    $direct = '';
    $chunks = [];

    foreach ($params as $param) {
        $attr = strtolower((string) ($param->attribute ?? ''));
        $val = (string) ($param->value ?? '');

        if (in_array($attr, ['filename', 'name'], true)) {
            $direct = decode_mime_header_value($val);
            continue;
        }

        if (preg_match('/^(filename|name)\\*(\\d+)\\*?$/', $attr, $m)) {
            $chunks[(int) $m[2]] = decode_rfc2231_value($val);
            continue;
        }

        if (in_array($attr, ['filename*', 'name*'], true)) {
            $direct = decode_rfc2231_value($val);
        }
    }

    if ($direct !== '') {
        return trim($direct);
    }

    if (!empty($chunks)) {
        ksort($chunks, SORT_NUMERIC);
        return trim(implode('', $chunks));
    }

    return '';
}

function mime_main_type_from_int(int $type): string
{
    return match ($type) {
        0 => 'text',
        1 => 'multipart',
        2 => 'message',
        3 => 'application',
        4 => 'audio',
        5 => 'image',
        6 => 'video',
        7 => 'other',
        default => 'application',
    };
}

function generated_filename_for_part(int $type, string $subtype, string $partNo): string
{
    $extMap = [
        'jpeg' => 'jpg',
        'pjpeg' => 'jpg',
        'png' => 'png',
        'gif' => 'gif',
        'webp' => 'webp',
        'bmp' => 'bmp',
        'heic' => 'heic',
        'heif' => 'heif',
        'mp4' => 'mp4',
        'quicktime' => 'mov',
        'mpeg' => 'mp3',
        'mpga' => 'mp3',
        'x-wav' => 'wav',
        'wav' => 'wav',
        'ogg' => 'ogg',
        'pdf' => 'pdf',
    ];
    $ext = $extMap[$subtype] ?? ($subtype !== '' ? $subtype : 'bin');
    $partToken = str_replace('.', '_', $partNo !== '' ? $partNo : 'body');
    return 'attachment_' . $partToken . '.' . $ext;
}

function extract_mail_parts($inbox, int $msgNo, object $structure, string $partNo = ''): array
{
    $texts = [];
    $attachments = [];

    $isMultipart = isset($structure->parts) && is_array($structure->parts) && count($structure->parts) > 0;
    if ($isMultipart) {
        foreach ($structure->parts as $idx => $subPart) {
            $newPartNo = $partNo === '' ? (string) ($idx + 1) : $partNo . '.' . ($idx + 1);
            $result = extract_mail_parts($inbox, $msgNo, $subPart, $newPartNo);
            $texts = array_merge($texts, $result['texts']);
            $attachments = array_merge($attachments, $result['attachments']);
        }
        return ['texts' => $texts, 'attachments' => $attachments];
    }

    $data = $partNo === ''
        ? imap_body($inbox, $msgNo)
        : imap_fetchbody($inbox, $msgNo, $partNo);

    $content = decode_part_content((string) $data, (int) ($structure->encoding ?? 0));
    $subtype = strtolower((string) ($structure->subtype ?? ''));
    $type = (int) ($structure->type ?? 0);
    $mainType = mime_main_type_from_int($type);

    $filename = '';
    if (!empty($structure->dparameters)) {
        $filename = extract_filename_from_params((array) $structure->dparameters);
    }
    if ($filename === '' && !empty($structure->parameters)) {
        $filename = extract_filename_from_params((array) $structure->parameters);
    }

    $disposition = strtolower((string) ($structure->disposition ?? ''));
    $hasDispositionAttachment = !empty($structure->ifdisposition) && in_array($disposition, ['attachment', 'inline'], true);
    $hasContentId = !empty($structure->ifid);
    $hasFilename = $filename !== '';
    $isBinaryPart = in_array($type, [3, 4, 5, 6, 7], true);
    $isAttachment = $hasFilename || $hasDispositionAttachment || $hasContentId || $isBinaryPart;

    if ($isAttachment) {
        if ($filename === '') {
            $filename = generated_filename_for_part($type, $subtype, $partNo);
        }

        $attachments[] = [
            'filename' => $filename,
            'mime' => $mainType . '/' . ($subtype !== '' ? $subtype : 'octet-stream'),
            'content' => $content,
        ];
    } else {
        if ($type === 0 && $subtype === 'plain') {
            $texts[] = ['type' => 'plain', 'content' => trim($content)];
        }
        if ($type === 0 && $subtype === 'html') {
            $texts[] = ['type' => 'html', 'content' => trim($content)];
        }
    }

    return ['texts' => $texts, 'attachments' => $attachments];
}

function ensure_category_for_sender(string $senderName, string $senderEmail): int
{
    $categoryName = trim($senderName) !== '' ? $senderName : $senderEmail;
    $slugBase = slugify($categoryName);

    $stmt = db()->prepare('SELECT id FROM categories WHERE sender_email = :email LIMIT 1');
    $stmt->execute(['email' => $senderEmail]);
    $existing = $stmt->fetchColumn();
    if ($existing) {
        return (int) $existing;
    }

    $slug = $slugBase;
    $counter = 1;
    while (true) {
        $check = db()->prepare('SELECT id FROM categories WHERE slug = :slug LIMIT 1');
        $check->execute(['slug' => $slug]);
        if (!$check->fetchColumn()) {
            break;
        }
        $counter++;
        $slug = $slugBase . '-' . $counter;
    }

    $ins = db()->prepare('INSERT INTO categories(name, slug, sender_email) VALUES(:n,:s,:e)');
    $ins->execute(['n' => $categoryName, 's' => $slug, 'e' => $senderEmail]);

    return (int) db()->lastInsertId();
}

function persist_attachments(int $postId, array $attachments): void
{
    $uploadRoot = rtrim((string) setting('uploads_root', ''), '/');
    if ($uploadRoot === '') {
        $cfg = require __DIR__ . '/../config.php';
        $uploadRoot = $cfg['app']['uploads_dir'];
    }

    $subDir = date('Y/m');
    $fullDir = rtrim($uploadRoot, '/') . '/' . $subDir;
    if (!is_dir($fullDir)) {
        mkdir($fullDir, 0775, true);
    }

    $featuredSet = false;
    $sort = 0;

    foreach ($attachments as $att) {
        $origName = $att['filename'] !== '' ? $att['filename'] : ('file-' . uniqid());
        $safeName = preg_replace('/[^A-Za-z0-9._-]/', '_', $origName);
        $safeName = ltrim((string) $safeName, '.');
        if ($safeName === '') {
            $safeName = 'file-' . uniqid();
        }

        $storedName = uniqid('m_', true) . '_' . $safeName;
        $fullPath = $fullDir . '/' . $storedName;

        $writeResult = file_put_contents($fullPath, $att['content']);
        if ($writeResult === false) {
            throw new RuntimeException('Ek dosya diske yazılamadı: ' . $origName);
        }

        $relative = 'uploads/' . $subDir . '/' . $storedName;
        $mediaType = media_type_from_mime($att['mime'], $origName);
        $isFeatured = 0;

        if ($mediaType === 'image' && !$featuredSet) {
            $isFeatured = 1;
            $featuredSet = true;
        }

        $stmt = db()->prepare('INSERT INTO media_attachments
            (post_id, original_name, stored_path, mime_type, file_size, media_type, sort_order, is_featured)
            VALUES(:pid,:on,:sp,:mt,:fs,:type,:sort,:feat)');
        $stmt->execute([
            'pid' => $postId,
            'on' => $origName,
            'sp' => $relative,
            'mt' => $att['mime'],
            'fs' => filesize($fullPath) ?: null,
            'type' => $mediaType,
            'sort' => $sort++,
            'feat' => $isFeatured,
        ]);
    }
}

function ingest_new_emails(): array
{
    if (!function_exists('imap_open')) {
        throw new RuntimeException('PHP IMAP eklentisi yüklü değil.');
    }

    $imapHost = (string) setting('imap_host', '');
    $imapPort = (string) setting('imap_port', '993');
    $imapEncryption = (string) setting('imap_encryption', 'ssl');
    $imapUsername = (string) setting('imap_username', '');
    $imapPassword = (string) setting('imap_password', '');
    $mailboxFolder = (string) setting('imap_mailbox', 'INBOX');
    $lastUid = (int) setting('last_imap_uid', '0');

    if ($imapHost === '' || $imapUsername === '' || $imapPassword === '') {
        throw new RuntimeException('IMAP ayarları eksik.');
    }

    $flags = '/imap';
    if ($imapEncryption === 'ssl') {
        $flags .= '/ssl';
    } elseif ($imapEncryption === 'tls') {
        $flags .= '/tls';
    } else {
        $flags .= '/notls';
    }

    $mailbox = '{' . $imapHost . ':' . $imapPort . $flags . '}' . $mailboxFolder;

    $inbox = @imap_open($mailbox, $imapUsername, $imapPassword);
    if (!$inbox) {
        throw new RuntimeException('IMAP bağlantısı başarısız: ' . imap_last_error());
    }

    $messages = imap_search($inbox, 'ALL') ?: [];
    sort($messages, SORT_NUMERIC);

    $processed = 0;
    $maxUid = $lastUid;

    foreach ($messages as $msgNo) {
        $uid = (int) imap_uid($inbox, (int) $msgNo);
        if ($uid <= $lastUid) {
            continue;
        }
        if ($uid > $maxUid) {
            $maxUid = $uid;
        }

        $header = imap_headerinfo($inbox, (int) $msgNo);
        if (!$header || empty($header->from) || !isset($header->from[0])) {
            continue;
        }

        $fromObj = $header->from[0];
        $senderEmail = strtolower(trim(($fromObj->mailbox ?? '') . '@' . ($fromObj->host ?? '')));
        $senderName = isset($fromObj->personal) ? decode_mime_header_value((string) $fromObj->personal) : '';

        if (!filter_var($senderEmail, FILTER_VALIDATE_EMAIL)) {
            continue;
        }

        $allowStmt = db()->prepare('SELECT id, auto_publish, category_id FROM allowed_senders WHERE email = :e AND active = 1 LIMIT 1');
        $allowStmt->execute(['e' => $senderEmail]);
        $allowed = $allowStmt->fetch();
        if (!$allowed) {
            continue;
        }

        $subjectRaw = $header->subject ?? '';
        $subject = $subjectRaw !== '' ? decode_mime_header_value((string) $subjectRaw) : '(Konu yok)';

        $structure = imap_fetchstructure($inbox, (int) $msgNo);
        if (!$structure) {
            continue;
        }

        $parts = extract_mail_parts($inbox, (int) $msgNo, $structure);
        $plainParts = array_values(array_filter($parts['texts'], fn($t) => $t['type'] === 'plain' && $t['content'] !== ''));
        $htmlParts = array_values(array_filter($parts['texts'], fn($t) => $t['type'] === 'html' && $t['content'] !== ''));

        if ($plainParts) {
            $content = implode("\n\n", array_column($plainParts, 'content'));
        } elseif ($htmlParts) {
            $content = trim(strip_tags(implode("\n\n", array_column($htmlParts, 'content'))));
        } else {
            $content = '';
        }

        $overview = imap_fetch_overview($inbox, (int) $msgNo, 0);
        $messageId = !empty($overview[0]->message_id) ? trim((string) $overview[0]->message_id) : null;
        $mailDate = !empty($overview[0]->date) ? date('Y-m-d H:i:s', strtotime((string) $overview[0]->date)) : date('Y-m-d H:i:s');

        if ($messageId) {
            $dup = db()->prepare('SELECT id FROM posts WHERE mail_message_id = :m LIMIT 1');
            $dup->execute(['m' => $messageId]);
            if ($dup->fetchColumn()) {
                continue;
            }
        }

        $categoryId = (int) ($allowed['category_id'] ?? 0);
        if ($categoryId <= 0) {
            $categoryId = ensure_category_for_sender($senderName, $senderEmail);
            db()->prepare('UPDATE allowed_senders SET category_id = :cid WHERE id = :id')
                ->execute(['cid' => $categoryId, 'id' => (int) $allowed['id']]);
        }

        $status = (int) $allowed['auto_publish'] === 1 ? 'published' : 'pending';
        $pdo = db();
        $pdo->beginTransaction();
        try {
            $postIns = $pdo->prepare('INSERT INTO posts
                (category_id, sender_name, sender_email, title, excerpt, content, mail_message_id, mail_date, status, published_at)
                VALUES(:cid,:sn,:se,:t,:ex,:c,:mid,:md,:st,:pub)');
            $postIns->execute([
                'cid' => $categoryId,
                'sn' => $senderName,
                'se' => $senderEmail,
                't' => $subject,
                'ex' => excerpt($content, 240),
                'c' => $content,
                'mid' => $messageId,
                'md' => $mailDate,
                'st' => $status,
                'pub' => $status === 'published' ? date('Y-m-d H:i:s') : null,
            ]);

            $postId = (int) $pdo->lastInsertId();
            persist_attachments($postId, $parts['attachments']);
            $pdo->commit();
            $processed++;
        } catch (Throwable $inner) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $inner;
        }
    }

    imap_close($inbox);
    save_setting('last_imap_uid', (string) $maxUid);

    return ['processed' => $processed, 'max_uid' => $maxUid];
}

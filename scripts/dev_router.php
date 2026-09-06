<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$public = $root . '/public';
$uri = (string) (parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH) ?: '/');
$decoded = rawurldecode($uri);

if (preg_match('/[[:cntrl:]]/u', $decoded) || str_contains($decoded, '..')) {
    http_response_code(400);
    echo 'Bad request';
    return true;
}

$publicFile = realpath($public . $decoded);
$publicRoot = realpath($public);
if ($decoded !== '/'
    && $publicFile !== false
    && $publicRoot !== false
    && str_starts_with($publicFile, $publicRoot . DIRECTORY_SEPARATOR)
    && is_file($publicFile)
) {
    return false;
}

if (str_starts_with($decoded, '/uploads/')) {
    $uploadsRoot = realpath($root . '/uploads');
    $candidate = $uploadsRoot !== false
        ? realpath($uploadsRoot . '/' . ltrim(substr($decoded, strlen('/uploads/')), '/'))
        : false;

    if ($candidate === false
        || $uploadsRoot === false
        || !str_starts_with($candidate, $uploadsRoot . DIRECTORY_SEPARATOR)
        || !is_file($candidate)
    ) {
        http_response_code(404);
        echo 'Not found';
        return true;
    }

    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($candidate) ?: 'application/octet-stream';
    if (!str_starts_with($mime, 'image/')) {
        http_response_code(403);
        echo 'Forbidden';
        return true;
    }

    header('Content-Type: ' . $mime);
    header('Content-Length: ' . (string) filesize($candidate));
    readfile($candidate);
    return true;
}

require $public . '/index.php';
return true;

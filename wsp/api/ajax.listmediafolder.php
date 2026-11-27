<?php

session_start(['read_and_close' => true]);
header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store');

if (empty($_SESSION['wspvars']['usevar']) || empty($_SESSION['wspvars']['userid'])) {
    http_response_code(401);
    echo '{"error":"not_authenticated"}';
    exit;
}

$folder = $_GET['folder'] ?? null;
if (!is_string($folder) || !preg_match('~^/media/([^/]+)(?:/|$)~', $folder, $parts)
    || strpbrk($folder, "\\:\0") !== false
    || array_intersect(['.', '..'], explode('/', $folder))) {
    http_response_code(400);
    echo '{"error":"invalid_media_folder"}';
    exit;
}

$mediaType = $parts[1];
$mediaBase = '/media/' . $mediaType;
$folder = rtrim($folder, '/');
$allowedRoot = $mediaBase;
$right = (string) ($_SESSION['wspvars']['rights'][$mediaType . 'folder'] ?? '');
if ((int) ($_SESSION['wspvars']['usertype'] ?? 0) !== 1 && !in_array($right, ['1', '/'], true)) {
    $allowedRoot = '/' . trim($right, '/');
    if ($allowedRoot !== $mediaBase && !str_starts_with($allowedRoot, $mediaBase . '/')) {
        $allowedRoot = $mediaBase . $allowedRoot;
    }
    if ($right === '' || $right === '0'
        || ($folder !== $allowedRoot && !str_starts_with($folder, $allowedRoot . '/'))) {
        http_response_code(403);
        echo '{"error":"media_folder_forbidden"}';
        exit;
    }
}

// Require the requested path to stay in its declared media and rights subtree.
$projectRoot = dirname(__DIR__, 2);
$mediaRoot = realpath($projectRoot . '/media');
$resolvedFolder = realpath($projectRoot . $folder);
if ($mediaRoot !== false && $resolvedFolder !== false
    && str_replace('\\', '/', $resolvedFolder)
        !== str_replace('\\', '/', $mediaRoot) . substr($folder, strlen('/media'))) {
    http_response_code(403);
    echo '{"error":"media_folder_forbidden"}';
    exit;
}

require_once __DIR__ . '/../data/classes/files.class.php';
$excludedFolders = $_GET['excludedFolders'] ?? wspFiles::DEFAULT_EXCLUDED_FOLDERS;
if ($excludedFolders === '') {
    $excludedFolders = [];
}
if (!is_array($excludedFolders) || array_filter($excludedFolders, static fn($name) => !is_string($name) || $name === '')) {
    http_response_code(400);
    echo '{"error":"invalid_excluded_folders"}';
    exit;
}

$listing = json_decode(
    (new wspFiles())->listFolder(
        $folder,
        allowedExtensions: $allowed ?? [],
        excludedExtensions: $excluded ?? ['php'],
        excludedFolders: $excludedFolders
    ),
    true,
    512,
    JSON_THROW_ON_ERROR
);
if (isset($listing['folder'], $listing['files'])) {
    $listing['parent'] = $folder === $allowedRoot ? null : substr($folder, 0, strrpos($folder, '/'));
    $siteBase = trim($_SESSION['wspvars']['wspbasediradd'] ?? '', '/');
    $mediaUrlPath = static fn(string $path): string =>
        '/' . implode('/', array_map('rawurlencode', explode('/', ltrim($siteBase . $path, '/'))));
    $host = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? 'localhost';
    if (!preg_match('/\A(?:[a-z0-9.-]+|\[[a-f0-9:.]+\])(?::[0-9]+)?\z/i', $host)) {
        http_response_code(400);
        echo '{"error":"invalid_host"}';
        exit;
    }
    $https = !in_array(strtolower((string) ($_SERVER['HTTPS'] ?? '')), ['', 'off', '0'], true)
        || ($_SERVER['REQUEST_SCHEME'] ?? '') === 'https';
    $origin = ($https ? 'https://' : 'http://') . $host;
    $folderUri = $origin . $mediaUrlPath($folder);
    foreach (['folder', 'files'] as $branch) {
        foreach ($listing[$branch] as $index => $entry) {
            $listing[$branch][$index]['uri'] = $folderUri . '/' . rawurlencode($entry['name']);
        }
    }

    $thumbFolder = $mediaBase . '/thumbs' . substr($folder, strlen($mediaBase));
    $expectedThumbFolder = str_replace('\\', '/', $mediaRoot) . substr($thumbFolder, strlen('/media'));
    $resolvedThumbFolder = realpath($projectRoot . $thumbFolder);
    if ($resolvedThumbFolder !== false && str_replace('\\', '/', $resolvedThumbFolder) === $expectedThumbFolder) {
        $thumbUrl = $mediaUrlPath($thumbFolder);
        foreach ($listing['files'] as &$entry) {
            $thumbnail = $resolvedThumbFolder . DIRECTORY_SEPARATOR . $entry['name'];
            if (is_file($thumbnail) && !is_link($thumbnail)) {
                $entry['preview'] = $thumbUrl . '/' . rawurlencode($entry['name']);
            }
        }
        unset($entry);
    }
}
echo json_encode($listing, JSON_INVALID_UTF8_SUBSTITUTE | JSON_THROW_ON_ERROR);

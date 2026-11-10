<?php
/**
 * Copy deployed module files back into the module checkout.
 * Database access is intentionally not loaded or used by this endpoint.
 */
session_start();
header('Content-Type: text/plain; charset=UTF-8');

$isAdmin = isset($_SESSION['wspvars']['usertype']) && (int)$_SESSION['wspvars']['usertype'] === 1;
$hasModuleRights = isset($_SESSION['wspvars']['rights']['modules']) && (int)$_SESSION['wspvars']['rights']['modules'] > 0;
if (!$isAdmin && !$hasModuleRights) {
    http_response_code(403);
    exit("Zugriff verweigert. Bitte mit einem WSP-Konto mit Modulrechten anmelden.\n");
}

$moduleName = isset($_GET['modul']) ? (string)$_GET['modul'] : '';
if (!preg_match('/^[A-Za-z0-9_-]+$/', $moduleName)) {
    http_response_code(400);
    exit("Ungültiger oder fehlender Parameter 'modul'.\n");
}

$wspRoot = dirname(__DIR__);
$moduleRoot = __DIR__ . DIRECTORY_SEPARATOR . $moduleName;
$dataModuleRoot = $wspRoot . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'modules' . DIRECTORY_SEPARATOR . $moduleName;
if (!is_dir($moduleRoot) || !is_dir($dataModuleRoot)) {
    http_response_code(404);
    exit("Modulverzeichnis oder data/modules/{$moduleName} wurde nicht gefunden.\n");
}

$copyTree = function (string $source, string $target) use (&$copyTree): int {
    if (!is_dir($target) && !mkdir($target, 0775, true) && !is_dir($target)) {
        throw new RuntimeException("Zielverzeichnis kann nicht angelegt werden: {$target}");
    }
    $entries = scandir($source);
    if ($entries === false) throw new RuntimeException("Quellverzeichnis kann nicht gelesen werden: {$source}");
    $count = 0;
    foreach ($entries as $entry) {
        if ($entry === '.' || $entry === '..') continue;
        $sourcePath = $source . DIRECTORY_SEPARATOR . $entry;
        $targetPath = $target . DIRECTORY_SEPARATOR . $entry;
        if (is_dir($sourcePath)) {
            $count += $copyTree($sourcePath, $targetPath);
        } elseif (is_file($sourcePath)) {
            if (!copy($sourcePath, $targetPath)) throw new RuntimeException("Kopieren fehlgeschlagen: {$sourcePath} -> {$targetPath}");
            $count++;
        }
    }
    return $count;
};

try {
    // Copy without comparing timestamps or contents; existing files are overwritten.
    $moduleCount = $copyTree($dataModuleRoot, $moduleRoot . DIRECTORY_SEPARATOR . 'modules');
    $publicCount = $copyTree($dataModuleRoot, $moduleRoot . DIRECTORY_SEPARATOR . 'public');

    $interpreterTarget = $moduleRoot . DIRECTORY_SEPARATOR . 'interpreter';
    if (!is_dir($interpreterTarget)) throw new RuntimeException("Modul-Interpreterverzeichnis fehlt: {$interpreterTarget}");
    $interpreterRoot = realpath($interpreterTarget);
    $dataInterpreterRoot = $wspRoot . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'interpreter';
    if ($interpreterRoot === false || !is_dir($dataInterpreterRoot)) {
        throw new RuntimeException("WSP-Interpreterverzeichnis wurde nicht gefunden.");
    }
    $interpreterCount = 0;
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($interpreterRoot, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::LEAVES_ONLY
    );
    foreach ($iterator as $file) {
        if (!$file->isFile()) continue;
        $relative = substr($file->getPathname(), strlen($interpreterRoot) + 1);
        $source = $dataInterpreterRoot . DIRECTORY_SEPARATOR . $relative;
        if (!is_file($source)) throw new RuntimeException("Passende Datei in data/interpreter fehlt: {$relative}");
        if (!copy($source, $file->getPathname())) throw new RuntimeException("Kopieren fehlgeschlagen: {$source} -> {$file->getPathname()}");
        $interpreterCount++;
    }

    echo "Modul {$moduleName} synchronisiert.\n";
    echo "modules: {$moduleCount} Datei(en), public: {$publicCount} Datei(en), interpreter: {$interpreterCount} Datei(en).\n";
} catch (Throwable $error) {
    http_response_code(500);
    echo "Fehler: " . $error->getMessage() . "\n";
}

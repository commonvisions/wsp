<?php



function copyRecursive(string $Source, string $Target): void
{
    if (is_dir($Source)) {
        if (!is_dir($Target)) {
            mkdir($Target, 0777, true);
        }
        foreach (scandir($Source) as $File) {
            if ($File === '.' || $File === '..') {
                continue;
            }
            copyRecursive(
                $Source . DIRECTORY_SEPARATOR . $File,
                $Target . DIRECTORY_SEPARATOR . $File
            );
        }
    } else {
        copy($Source, $Target);
    }
}
function checkDB() {
    $xml = simplexml_load_file(__DIR__ . DIRECTORY_SEPARATOR . 'props' .
        DIRECTORY_SEPARATOR . 'database.xml');
    echo "Database checks done.\n";
}

$Assets = [
    'vendor/twbs/bootstrap/dist/css/bootstrap.min.css' => 'assets/css/bootstrap.min.css',
    'vendor/twbs/bootstrap/dist/css/bootstrap.min.css.map' => 'assets/css/bootstrap.min.css.map',
    'vendor/twbs/bootstrap/dist/js/bootstrap.bundle.js' => 'assets/js/bootstrap.bundle.js',
    'vendor/datatables.net/datatables.net/js/dataTables.min.js' => './assets/js/dataTables.min.js',
    'vendor/datatables.net/datatables.net-dt/js/dataTables.dataTables.min.js' => './assets/js/dataTables.dataTables.min.js',
    'vendor/datatables.net/datatables.net-dt/css/dataTables.dataTables.min.css' => './assets/css/dataTables.dataTables.min.css',
    'vendor/components/jquery/jquery.min.js' => './assets/js/jquery.min.js',
    'vendor/components/jquery-migrate/jquery-migrate.min.js' => './assets/js/jquery-migrate.min.js',
    'vendor/components/jqueryui/jquery-ui.min.js' => './assets/js/jquery-ui.min.js',
    'vendor/select2/select2/dist/js/select2.min.js' => './assets/js/select2.min.js',
    'vendor/select2/select2/dist/css/select2.min.css' => './assets/css/select2.min.css',
    'vendor/fortawesome/font-awesome/css/all.min.css' => './assets/css/fontawesome.min.css',
    'vendor/fortawesome/font-awesome/webfonts' => './assets/webfonts/',
];
$Uniques = [
    'forward.php' => '../index.php'
];

foreach ($Assets as $Source => $Target) {
    if (!is_string($Source) || !is_string($Target)) {
        throw new RuntimeException("Error in configuration");
    }
    $Source = str_replace('/', DIRECTORY_SEPARATOR, __DIR__ . '/' . $Source);
    $Target = str_replace('/', DIRECTORY_SEPARATOR, __DIR__ . '/' . $Target);
    copyRecursive(
        $Source,
        $Target
    );
}
foreach ($Uniques as $Source => $Target) {
    if (!is_string($Source) || !is_string($Target)) {
        throw new RuntimeException("Error in configuration");
    }
    $Source = str_replace('/', DIRECTORY_SEPARATOR, __DIR__ . '/' . $Source);
    $Target = str_replace('/', DIRECTORY_SEPARATOR, __DIR__ . '/' . $Target);
    if (!file_exists($Source)) {
        throw new RuntimeException("File " . $Source . " does not exist");
    } elseif (!file_exists($Target)) {
        if (!is_dir(dirname($Target))) mkdir(dirname($Target), 0777, true);
        if (!copy($Source, $Target)) throw new RuntimeException("Fehler beim Kopieren: $Source");
    }
}

echo "Copying assets done\n";

if (file_exists(__DIR__ . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR .
    'include' . DIRECTORY_SEPARATOR . 'dbaccess.inc.php')) {
    checkDB();
}

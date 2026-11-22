<?php
/** Fetch/update a configured module repository and run its Composer install. */
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

$configFile = __DIR__ . DIRECTORY_SEPARATOR . 'modules.json';
if (!is_file($configFile)) {
    http_response_code(500);
    exit("Modulkonfiguration fehlt: modules.json\n");
}
$config = json_decode((string)file_get_contents($configFile), true);
$moduleConfig = is_array($config) ? ($config[$moduleName] ?? null) : null;
$gitUrl = is_array($moduleConfig) ? ($moduleConfig['git_url'] ?? null) : null;
if (!is_string($gitUrl) || !preg_match('~^(https://|ssh://|git@)[^\s]+$~', $gitUrl)) {
    http_response_code(404);
    exit("Für das Modul {$moduleName} ist keine gültige Git-URL konfiguriert.\n");
}
$gitToken = null;
if (is_array($moduleConfig)) {
    foreach (['git_token', 'token', 'read_token'] as $tokenKey) {
        if (isset($moduleConfig[$tokenKey]) && is_string($moduleConfig[$tokenKey]) && $moduleConfig[$tokenKey] !== '') {
            $gitToken = $moduleConfig[$tokenKey];
            break;
        }
    }
}
$tokenEnvName = is_array($moduleConfig) ? ($moduleConfig['git_token_env'] ?? null) : null;
$gitEnvironment = null;
$askpassFile = null;
if ($gitToken === null && $tokenEnvName !== null) {
    if (!is_string($tokenEnvName) || !preg_match('/^[A-Z][A-Z0-9_]*$/', $tokenEnvName)) {
        http_response_code(500);
        exit("Ungültige Umgebungsvariablen-Angabe für Git-Token.\n");
    }
    $gitToken = getenv($tokenEnvName);
    if (!is_string($gitToken) || $gitToken === '') {
        http_response_code(500);
        exit("Der konfigurierte Git-Token ist in der Server-Umgebung nicht gesetzt ({$tokenEnvName}).\n");
    }
}
if ($gitToken !== null) {
    if (strpos($gitUrl, 'https://') !== 0) {
        http_response_code(500);
        exit("Git-Token-Authentifizierung ist nur für HTTPS-URLs konfiguriert.\n");
    }
    $askpassFile = tempnam(sys_get_temp_dir(), 'wsp-git-askpass-');
    $askpassScript = "#!/bin/sh\ncase \"$1\" in\n  *Username*) printf '%s\\n' 'x-access-token' ;;\n  *Password*) printf '%s\\n' \"\$WSP_GITHUB_TOKEN\" ;;\n  *) exit 1 ;;\nesac\n";
    if ($askpassFile === false || file_put_contents($askpassFile, $askpassScript) === false || !chmod($askpassFile, 0700)) {
        if (is_string($askpassFile)) @unlink($askpassFile);
        http_response_code(500);
        exit('Git-Authentifizierungshilfe konnte nicht eingerichtet werden.');
    }
    $gitEnvironment = getenv();
    if (!is_array($gitEnvironment)) $gitEnvironment = [];
    $gitEnvironment['GIT_ASKPASS'] = $askpassFile;
    $gitEnvironment['GIT_TERMINAL_PROMPT'] = '0';
    $gitEnvironment['WSP_GITHUB_TOKEN'] = $gitToken;
}

/** Run a fixed executable with argument-array semantics (no shell interpolation). */
$runCommand = static function (array $command, string $cwd, ?array $environment = null): array {
    $output = tmpfile();
    if ($output === false) throw new RuntimeException('Temporäre Ausgabedatei konnte nicht angelegt werden.');
    $process = proc_open($command, [0 => ['pipe', 'r'], 1 => $output, 2 => ['redirect', 1]], $pipes, $cwd, $environment);
    if (!is_resource($process)) {
        fclose($output);
        throw new RuntimeException('Prozess konnte nicht gestartet werden: ' . $command[0]);
    }
    fclose($pipes[0]);
    $exitCode = proc_close($process);
    rewind($output);
    $text = stream_get_contents($output);
    fclose($output);
    return [$exitCode, $text === false ? '' : $text];
};

$moduleDirectory = __DIR__ . DIRECTORY_SEPARATOR . $moduleName;
$modulesParent = realpath(__DIR__);
try {
    if ($modulesParent === false) throw new RuntimeException('Modulverzeichnis konnte nicht aufgelöst werden.');

    if (!is_dir($moduleDirectory)) {
        [$exitCode, $output] = $runCommand(['git', 'clone', '--', $gitUrl, $moduleDirectory], $modulesParent, $gitEnvironment);
        echo $output;
        if ($exitCode !== 0) throw new RuntimeException("Git clone ist mit Status {$exitCode} fehlgeschlagen.");
    } else {
        $realModuleDirectory = realpath($moduleDirectory);
        if ($realModuleDirectory === false || dirname($realModuleDirectory) !== $modulesParent) {
            throw new RuntimeException('Das Modulverzeichnis liegt außerhalb von modules/.');
        }
        if (!is_dir($moduleDirectory . DIRECTORY_SEPARATOR . '.git')) {
            throw new RuntimeException("{$moduleName} existiert bereits, ist aber kein Git-Checkout.");
        }
        [$remoteStatus, $remoteOutput] = $runCommand(['git', 'remote', 'get-url', 'origin'], $moduleDirectory);
        if ($remoteStatus !== 0) {
            [$setStatus, $setOutput] = $runCommand(['git', 'remote', 'add', 'origin', $gitUrl], $moduleDirectory);
            echo $setOutput;
            if ($setStatus !== 0) throw new RuntimeException('Git-Remote konnte nicht gesetzt werden.');
        } elseif (trim($remoteOutput) !== $gitUrl) {
            throw new RuntimeException('Die konfigurierte Git-URL stimmt nicht mit origin des vorhandenen Checkouts überein.');
        }
        [$exitCode, $output] = $runCommand(['git', 'pull', '--ff-only'], $moduleDirectory, $gitEnvironment);
        echo $output;
        if ($exitCode !== 0) throw new RuntimeException("Git pull ist mit Status {$exitCode} fehlgeschlagen.");
    }

    $composerFile = $moduleDirectory . DIRECTORY_SEPARATOR . 'composer.json';
    if (!is_file($composerFile)) throw new RuntimeException("composer.json wurde für {$moduleName} nicht gefunden.");
    [$exitCode, $output] = $runCommand(['composer', 'install', '--no-interaction', '--prefer-dist'], $moduleDirectory);
    echo $output;
    if ($exitCode !== 0) throw new RuntimeException("Composer install ist mit Status {$exitCode} fehlgeschlagen.");

    echo "Modul {$moduleName} wurde aktualisiert und installiert.\n";
} catch (Throwable $error) {
    http_response_code(500);
    echo "Fehler: " . $error->getMessage() . "\n";
} finally {
    if (is_string($askpassFile)) @unlink($askpassFile);
}

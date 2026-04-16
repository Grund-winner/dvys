<?php
// Diagnostic rapide
echo '<h1>DVYS AI - Diagnostic</h1>';

echo '<h2>PHP Version</h2>';
echo phpversion();

echo '<h2>Extensions</h2>';
echo 'PDO PostgreSQL: ' . (in_array('pgsql', PDO::getAvailableDrivers()) ? 'OK' : 'MANQUANT') . '<br>';
echo 'cURL: ' . (function_exists('curl_init') ? 'OK' : 'MANQUANT') . '<br>';
echo 'mbstring: ' . (function_exists('mb_strlen') ? 'OK' : 'MANQUANT') . '<br>';

echo '<h2>Repertoire</h2>';
echo 'CWD: ' . getcwd() . '<br>';
echo '__DIR__: ' . __DIR__ . '<br>';
echo 'Fichiers: <pre>';
print_r(scandir(__DIR__));
echo '</pre>';

echo '<h2>Permissions data/</h2>';
$dataDir = __DIR__ . '/data';
if (!is_dir($dataDir)) {
    echo 'data/ n\'existe pas, creation... ';
    if (mkdir($dataDir, 0755, true)) {
        echo 'OK';
    } else {
        echo 'ECHEC - permission refusee';
    }
} else {
    echo 'data/ existe, writable: ' . (is_writable($dataDir) ? 'OUI' : 'NON') . '<br>';
    echo 'owner: ' . posix_getpwuid(fileowner($dataDir))['name'] . '<br>';
}

echo '<h2>Test PostgreSQL (Neon)</h2>';
try {
    require_once __DIR__ . '/includes/config.php';
    $db = Database::getInstance();
    $result = $db->query("SELECT version()")->fetchColumn();
    echo 'PostgreSQL: OK - ' . $result;
} catch (Exception $e) {
    echo 'PostgreSQL ERREUR: ' . $e->getMessage();
}

echo '<h2>Session</h2>';
echo 'session_status: ' . session_status() . '<br>';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
    echo 'Session demarree OK';
}

echo '<h2>Includes</h2>';
echo 'config.php: ' . (file_exists(__DIR__ . '/includes/config.php') ? 'OK' : 'MANQUANT') . '<br>';
echo 'database.php: ' . (file_exists(__DIR__ . '/includes/database.php') ? 'OK' : 'MANQUANT') . '<br>';
echo 'i18n.php: ' . (file_exists(__DIR__ . '/includes/i18n.php') ? 'OK' : 'MANQUANT') . '<br>';

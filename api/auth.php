<?php
/**
 * DVYS AI - API Auth (Login / Register / Lang)
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/i18n.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

$auth = new Auth();
$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'set_lang':
        $lang = $_POST['lang'] ?? '';
        if (in_array($lang, SUPPORTED_LANGUAGES)) {
            setLang($lang);
            echo json_encode(['success' => true]);
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid language']);
        }
        break;

    case 'me':
        if (!$auth->isLoggedIn()) {
            http_response_code(401);
            echo json_encode(['error' => 'Not authenticated']);
            break;
        }
        $user = $auth->currentUser();
        echo json_encode(['success' => true, 'user' => $user]);
        break;

    default:
        http_response_code(400);
        echo json_encode(['error' => 'Unknown action']);
}

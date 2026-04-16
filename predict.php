<?php
/**
 * DVYS AI - Endpoint de Prédiction
 * 
 * Reçoit ?b=<auth_token> (hash de l'iframe 1Win),
 * authentifie auprès de l'API 100hp.app, récupère l'historique
 * des crashes, et retourne une prédiction via notre algorithme.
 * 
 * Réponse JSON :
 * {
 *   "ai_prediction": 2.47,
 *   "confidence": 0.72,
 *   "signals": ["triple_low_rebound", "pattern_matched"],
 *   "last_rounds": [1.23, 3.56, 1.01, ...],
 *   "total_rounds": 50,
 *   "status": "ok"
 * }
 */

// ================================================================
//  SÉCURITÉ
// ================================================================

session_start();
header('Content-Type: application/json');

// Autoriser CORS pour les iframes 1Win
$allowedOrigins = [
    'https://dvys.onrender.com',
    'https://100hp.app',
    'https://1play.gamedev-tech.cc',
];

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (in_array($origin, $allowedOrigins)) {
    header("Access-Control-Allow-Origin: $origin");
    header('Access-Control-Allow-Methods: GET, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');
}

// Répondre aux requêtes preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// ================================================================
//  PROTECTION ANTI-ABUS
// ================================================================

// Anti-bot basique
$ua = strtolower($_SERVER['HTTP_USER_AGENT'] ?? '');
$blocked = ['curl', 'wget', 'python', 'postman', 'httpclient', 'java/', 'libwww'];
foreach ($blocked as $bot) {
    if (strpos($ua, $bot) !== false) {
        http_response_code(403);
        echo json_encode(['error' => 'Bot blocked', 'status' => 'blocked']);
        exit;
    }
}

// Rate limiting simple (fichier temporaire)
$rateLimitKey = sys_get_temp_dir() . '/dvys_rl_' . md5(session_id() . ($_SERVER['REMOTE_ADDR'] ?? ''));
$rateData = @json_decode(@file_get_contents($rateLimitKey), true) ?: ['c' => 0, 't' => time()];
$rateWindow = 60;
$rateMax = 30;

if (time() - $rateData['t'] > $rateWindow) {
    $rateData = ['c' => 1, 't' => time()];
} else {
    $rateData['c']++;
}
@file_put_contents($rateLimitKey, json_encode($rateData));

if ($rateData['c'] > $rateMax) {
    http_response_code(429);
    echo json_encode(['error' => 'Too many requests', 'status' => 'rate_limited']);
    exit;
}

// ================================================================
//  VALIDATION DU PARAMÈTRE
// ================================================================

$b = isset($_GET['b']) ? trim($_GET['b']) : null;
if (!$b || strlen($b) < 20) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing or invalid b parameter', 'status' => 'error']);
    exit;
}

// ================================================================
//  ÉTAPE 1 : AUTHENTIFICATION AUPRÈS DE 100hp.app
// ================================================================

$ch = curl_init('https://crash-gateway-grm-cr.100hp.app/user/auth');
curl_setopt_array($ch, [
    CURLOPT_POST           => true,
    CURLOPT_HTTPHEADER     => [
        'auth-token: ' . $b,
        'Content-Type: application/json',
    ],
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT        => 10,
    CURLOPT_CONNECTTIMEOUT => 5,
]);
$authResponse = curl_exec($ch);
$authHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$authError = curl_error($ch);
curl_close($ch);

if ($authError) {
    echo json_encode(['error' => 'Auth service unavailable', 'status' => 'error']);
    exit;
}

$authData = json_decode($authResponse, true);

if (!$authData || !isset($authData['sessionId'])) {
    echo json_encode(['error' => 'Invalid auth token', 'status' => 'error', 'raw' => $authResponse]);
    exit;
}

$sessionId  = trim($authData['sessionId'] ?? '');
$customerId = trim($authData['customerId'] ?? '');

if (!$sessionId || !$customerId) {
    echo json_encode(['error' => 'Missing session data', 'status' => 'error']);
    exit;
}

// ================================================================
//  ÉTAPE 2 : RÉCUPÉRER L'HISTORIQUE DES CRASHES
// ================================================================

function fetchCrashHistory(string $sessionId, string $customerId): ?array
{
    $url = 'https://crash-gateway-grm-cr.100hp.app/history';
    $headers = [
        'accept: application/json, text/plain, */*',
        'origin: https://1play.gamedev-tech.cc',
        'referer: https://1play.gamedev-tech.cc/',
        'customer-id: ' . $customerId,
        'session-id: ' . $sessionId,
        'user-agent: Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X)',
    ];

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_HTTPHEADER     => $headers,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 10,
        CURLOPT_CONNECTTIMEOUT => 5,
    ]);

    $resp = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200 || !$resp) {
        return null;
    }

    $data = json_decode($resp, true);
    if (!is_array($data)) {
        return null;
    }

    // Extraire les finalValues (multiplicateurs des rounds)
    $values = [];
    foreach ($data as $item) {
        if (isset($item['finalValues']) && is_array($item['finalValues'])) {
            foreach ($item['finalValues'] as $val) {
                if (is_numeric($val) && $val > 0) {
                    $values[] = (float) $val;
                }
            }
        }
    }

    return count($values) > 0 ? $values : null;
}

$history = fetchCrashHistory($sessionId, $customerId);

if (!$history) {
    echo json_encode(['error' => 'Failed to fetch crash history', 'status' => 'error']);
    exit;
}

// ================================================================
//  ÉTAPE 3 : ALGORITHME DE PRÉDICTION DVYS
// ================================================================

require_once __DIR__ . '/includes/Predictor.php';

$predictor = new CrashPredictor($history, 1.00, 25.00);
$result = $predictor->predict();

// ================================================================
//  ÉTAPE 4 : RÉPONSE JSON
// ================================================================

echo json_encode([
    'ai_prediction'  => $result['prediction'],
    'confidence'     => $result['confidence'],
    'signals'        => $result['signals'],
    'modules'        => $result['modules'],
    'last_rounds'    => array_slice(array_reverse($history), 0, 15),
    'total_rounds'   => $result['analysis']['rounds_analyzed'],
    'avg'            => $result['analysis']['avg'],
    'std_dev'        => $result['analysis']['std_dev'],
    'direction'      => $result['analysis']['direction'],
    'status'         => 'ok',
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

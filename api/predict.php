<?php
/**
 * DVYS AI - API de Prédiction pour Jeux Crash
 * 
 * GET /api/predict.php?b=<auth_token>&game=<game_name>
 * 
 * Paramètres :
 *   - b : Token d'authentification de l'iframe 1Win (obligatoire)
 *   - game : Nom du jeu (optionnel, pour le cache)
 * 
 * Utilise l'algorithme CrashPredictor (8 modules d'analyse)
 * avec cache par session pour éviter les appels API redondants.
 */

// ================================================================
//  CONFIGURATION
// ================================================================

session_start();

// CORS — Autoriser les iframes des domaines de jeux
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
$allowedOrigins = [
    parse_url(BASE_URL ?? '', PHP_URL_HOST) ?: '',
    '100hp.app',
    '1play.gamedev-tech.cc',
];

header('Content-Type: application/json');
if ($origin) {
    $originHost = parse_url($origin, PHP_URL_HOST) ?? '';
    foreach ($allowedOrigins as $allowed) {
        if ($allowed && str_contains($originHost, $allowed)) {
            header("Access-Control-Allow-Origin: $origin");
            break;
        }
    }
}
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// ================================================================
//  PROTECTION ANTI-ABUS
// ================================================================

// Anti-bot
$ua = strtolower($_SERVER['HTTP_USER_AGENT'] ?? '');
if (preg_match('/(curl|wget|python|postman|httpclient|java\/|libwww)/i', $ua)) {
    http_response_code(403);
    echo json_encode(['error' => 'Access denied']);
    exit;
}

// Rate limit (30 req/min par IP)
$rlKey = sys_get_temp_dir() . '/dvys_pred_rl_' . md5(session_id() . ($_SERVER['REMOTE_ADDR'] ?? ''));
$rl = @json_decode(@file_get_contents($rlKey), true) ?: ['c' => 0, 't' => time()];
if (time() - $rl['t'] > 60) $rl = ['c' => 1, 't' => time()];
else $rl['c']++;
@file_put_contents($rlKey, json_encode($rl));
if ($rl['c'] > 30) {
    http_response_code(429);
    echo json_encode(['error' => 'Rate limit exceeded']);
    exit;
}

// ================================================================
//  VALIDATION
// ================================================================

$b = $_GET['b'] ?? '';
if (strlen($b) < 20) {
    echo json_encode(['error' => 'Invalid token', 'status' => 'error']);
    exit;
}

$game = preg_replace('/[^a-zA-Z0-9_-]/', '', $_GET['game'] ?? 'default');

// ================================================================
//  CACHE — Ne pas re-prédire pour le même round
// ================================================================

$cacheDir = sys_get_temp_dir();
$cacheKey = md5($b . '_' . $game);
$cacheFile = $cacheDir . '/dvys_pred_' . $cacheKey;

// Vérifier le cache (validité : 15 secondes)
if (file_exists($cacheFile)) {
    $cached = @json_decode(@file_get_contents($cacheFile), true);
    if ($cached && isset($cached['timestamp']) && (time() - $cached['timestamp']) < 15) {
        // Retourner le cache
        echo json_encode([
            'ai_prediction'  => $cached['prediction'],
            'confidence'     => $cached['confidence'],
            'signals'        => $cached['signals'] ?? [],
            'last_rounds'    => $cached['last_rounds'] ?? [],
            'total_rounds'   => $cached['total_rounds'] ?? 0,
            'cached'         => true,
            'status'         => 'ok',
        ]);
        exit;
    }
}

// ================================================================
//  ÉTAPE 1 : AUTHENTIFICATION 100hp.app
// ================================================================

$ch = curl_init('https://crash-gateway-grm-cr.100hp.app/user/auth');
curl_setopt_array($ch, [
    CURLOPT_POST           => true,
    CURLOPT_HTTPHEADER     => ['auth-token: ' . $b, 'Content-Type: application/json'],
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT        => 8,
    CURLOPT_CONNECTTIMEOUT => 5,
]);
$authResp = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200 || !$authResp) {
    echo json_encode(['error' => 'Auth service unavailable', 'status' => 'error']);
    exit;
}

$authData = json_decode($authResp, true);
if (!$authData || !isset($authData['sessionId'])) {
    echo json_encode(['error' => 'Invalid auth token', 'status' => 'error']);
    exit;
}

$sessionId  = trim($authData['sessionId'] ?? '');
$customerId = trim($authData['customerId'] ?? '');

if (!$sessionId || !$customerId) {
    echo json_encode(['error' => 'Missing session credentials', 'status' => 'error']);
    exit;
}

// ================================================================
//  ÉTAPE 2 : RÉCUPÉRER L'HISTORIQUE
// ================================================================

function fetchCrashHistory(string $sid, string $cid): ?array
{
    $ch = curl_init('https://crash-gateway-grm-cr.100hp.app/history');
    curl_setopt_array($ch, [
        CURLOPT_HTTPHEADER     => [
            'accept: application/json, text/plain, */*',
            'origin: https://1play.gamedev-tech.cc',
            'referer: https://1play.gamedev-tech.cc/',
            'customer-id: ' . $cid,
            'session-id: ' . $sid,
            'user-agent: Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X)',
        ],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 8,
        CURLOPT_CONNECTTIMEOUT => 5,
    ]);
    $resp = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($code !== 200 || !$resp) return null;

    $data = json_decode($resp, true);
    if (!is_array($data)) return null;

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
    echo json_encode(['error' => 'History unavailable', 'status' => 'error']);
    exit;
}

// ================================================================
//  ÉTAPE 3 : PRÉDICTION DVYS AI
// ================================================================

require_once __DIR__ . '/includes/Predictor.php';

$predictor = new CrashPredictor($history, 1.00, 25.00);
$result = $predictor->predict();

// ================================================================
//  ÉTAPE 4 : METTRE EN CACHE
// ================================================================

$cacheData = [
    'timestamp'     => time(),
    'prediction'    => $result['prediction'],
    'confidence'    => $result['confidence'],
    'signals'       => $result['signals'],
    'last_rounds'   => array_slice(array_reverse($history), 0, 15),
    'total_rounds'  => $result['analysis']['rounds_analyzed'],
];
@file_put_contents($cacheFile, json_encode($cacheData));

// ================================================================
//  ÉTAPE 5 : RÉPONSE
// ================================================================

echo json_encode([
    'ai_prediction'  => $result['prediction'],
    'confidence'     => $result['confidence'],
    'signals'        => $result['signals'],
    'last_rounds'    => $cacheData['last_rounds'],
    'total_rounds'   => $result['analysis']['rounds_analyzed'],
    'avg'            => $result['analysis']['avg'],
    'direction'      => $result['analysis']['direction'],
    'status'         => 'ok',
], JSON_PRETTY_PRINT);

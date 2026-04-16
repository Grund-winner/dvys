<?php
/**
 * DVYS AI - API Predictions
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

$auth = new Auth();

if (!$auth->isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

$db = Database::getInstance();

// GET = récupérer les pronostics
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $date = $_GET['date'] ?? date('Y-m-d');
    
    // Pronostics programmés pour la date demandée (status = active)
    $stmt = $db->prepare("SELECT * FROM predictions WHERE scheduled_date = ? AND status = 'active' ORDER BY match_time ASC");
    $stmt->execute([$date]);
    $predictions = $stmt->fetchAll();
    
    // Si pas de pronostics pour cette date, retourner les pronostics d'aujourd'hui
    if (empty($predictions) && $date !== date('Y-m-d')) {
        $stmt = $db->prepare("SELECT * FROM predictions WHERE scheduled_date = ? AND status = 'active' ORDER BY match_time ASC");
        $stmt->execute([date('Y-m-d')]);
        $predictions = $stmt->fetchAll();
    }
    
    // Vérifier si l'utilisateur a accès VIP
    $hasVip = $auth->hasVipAccess();
    
    // Masquer les prédictions VIP si pas VIP
    foreach ($predictions as &$pred) {
        if ($pred['is_vip'] && !$hasVip) {
            $pred['prediction'] = null;
            $pred['odds'] = null;
        }
    }
    unset($pred);
    
    echo json_encode(['success' => true, 'predictions' => $predictions, 'date' => $date, 'has_vip' => $hasVip]);
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);

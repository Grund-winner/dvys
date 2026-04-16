<?php
/**
 * DVYS AI - Postback 1Win
 * Endpoint appelé par 1Win quand un utilisateur s'inscrit ou dépose
 * 
 * Configuration 1Win Partners :
 * Postback URL: https://votre-domaine.com/postback.php
 * Paramètres attendus: sub1, sub2, event, amount
 * 
 * sub1 = ID utilisateur DVYS AI ou code de parrainage
 */
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/database.php';
require_once __DIR__ . '/includes/functions.php';

// Log toutes les requêtes postback pour debug
$rawInput = file_get_contents('php://input');
$rawData = json_encode([
    'GET' => $_GET,
    'POST' => $_POST,
    'INPUT' => $rawInput,
    'SERVER' => [
        'REMOTE_ADDR' => $_SERVER['REMOTE_ADDR'] ?? '',
        'HTTP_USER_AGENT' => $_SERVER['HTTP_USER_AGENT'] ?? '',
        'REQUEST_URI' => $_SERVER['REQUEST_URI'] ?? '',
    ]
]);

// Récupérer les paramètres (support GET et POST)
$sub1 = $_GET['sub1'] ?? $_POST['sub1'] ?? '';
$sub2 = $_GET['sub2'] ?? $_POST['sub2'] ?? '';
$event = $_GET['event'] ?? $_POST['event'] ?? '';
$amount = floatval($_GET['amount'] ?? $_POST['amount'] ?? 0);

// Sécurité basique : vérifier l'IP de 1Win (optionnel)
// $allowedIps = ['']; // Ajouter les IPs de 1Win si disponibles

// Traiter le postback
if (!empty($event)) {
    logPostback($event, $amount, $sub1, $sub2, $rawData);
    
    // Répondre "OK" pour confirmer la réception
    header('Content-Type: text/plain');
    echo 'OK';
} else {
    // Pas d'événement, juste log
    $db = Database::getInstance();
    $stmt = $db->prepare("INSERT INTO postback_logs (event, raw_data, ip_address) VALUES (?, ?, ?)");
    $stmt->execute(['unknown', $rawData, $_SERVER['REMOTE_ADDR'] ?? '']);
    
    header('Content-Type: text/plain');
    echo 'OK';
}

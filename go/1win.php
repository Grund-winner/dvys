<?php
/**
 * DVYS AI - Redirect vers 1Win avec sub1/sub2 automatiques
 * Ce lien masqué redirige vers le lien d'affiliation personnalisé de l'utilisateur.
 * 
 * Utilisation : https://dvys.onrender.com/go/1win
 * Si l'utilisateur est connecté, sub1 = son ID DVYS, sub2 = son code de parrainage
 * Si non connecté, redirige vers le lien de base (sans tracking)
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';

// Vérifier si l'utilisateur est connecté
session_start();
$userId = $_SESSION['user_id'] ?? null;

if ($userId) {
    // Utilisateur connecté : lien personnalisé avec sub1
    $db = Database::getInstance();
    $stmt = $db->prepare("SELECT referral_code FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();

    if ($user) {
        $sep = (strpos(AFFILIATE_LINK, '?') !== false) ? '&' : '?';
        $link = AFFILIATE_LINK . $sep . 'sub1=' . $userId . '&sub2=' . urlencode($user['referral_code']);
    } else {
        $link = AFFILIATE_LINK;
    }
} else {
    // Non connecté : lien de base
    $link = AFFILIATE_LINK;
}

header('Location: ' . $link, true, 302);
exit;

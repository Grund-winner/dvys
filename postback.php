<?php
/**
 * DVYS AI - Postback 1Win (Ultra-léger)
 * Endpoint appelé par 1Win quand un utilisateur s'inscrit ou dépose
 * 
 * Configuration 1Win Partners :
 * Postback URL: https://votre-domaine.com/postback.php
 * Paramètres attendus: sub1, sub2, event, amount
 * 
 * sub1 = ID utilisateur DVYS AI ou code de parrainage
 * sub2 = Code de parrainage (backup)
 */

// === Réponse immédiate pour éviter le timeout 1Win ===
ignore_user_abort(true);
set_time_limit(5);

// === Connexion DB directe (pas de session, pas de config lourd) ===
$dbUrl = getenv('DATABASE_URL');
if (empty($dbUrl)) {
    header('Content-Type: text/plain');
    echo 'OK';
    exit;
}

$url = parse_url($dbUrl);
$host = $url['host'] ?? 'localhost';
$port = $url['port'] ?? '5432';
$dbname = ltrim($url['path'] ?? 'neondb', '/');
$user = $url['user'] ?? 'neondb_owner';
$pass = $url['pass'] ?? '';
$query = $url['query'] ?? '';
parse_str($query, $params);
unset($params['channel_binding']);
$sslmode = $params['sslmode'] ?? 'require';

try {
    $dsn = "pgsql:host={$host};port={$port};dbname={$dbname};sslmode={$sslmode}";
    $db = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
} catch (Exception $e) {
    header('Content-Type: text/plain');
    echo 'OK';
    exit;
}

// === Récupérer les paramètres ===
$sub1 = $_GET['sub1'] ?? $_POST['sub1'] ?? '';
$sub2 = $_GET['sub2'] ?? $_POST['sub2'] ?? '';
$event = $_GET['event'] ?? $_POST['event'] ?? '';
$amount = floatval($_GET['amount'] ?? $_POST['amount'] ?? 0);

$rawData = json_encode([
    'GET' => $_GET,
    'POST' => $_POST,
    'INPUT' => file_get_contents('php://input'),
    'SERVER' => [
        'REMOTE_ADDR' => $_SERVER['REMOTE_ADDR'] ?? '',
        'HTTP_USER_AGENT' => $_SERVER['HTTP_USER_AGENT'] ?? '',
        'REQUEST_URI' => $_SERVER['REQUEST_URI'] ?? '',
    ]
]);

$ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['HTTP_X_REAL_IP'] ?? $_SERVER['REMOTE_ADDR'] ?? '';

// === Traiter le postback ===
if (!empty($event)) {
    // Trouver l'utilisateur par ID ou code parrainage
    $userId = null;
    if (!empty($sub1)) {
        $stmt = $db->prepare("SELECT id FROM users WHERE id = ? OR referral_code = ?");
        $stmt->execute([$sub1, $sub1]);
        $result = $stmt->fetch();
        if ($result) $userId = $result['id'];
    }

    // Log du postback
    $stmt = $db->prepare("INSERT INTO postback_logs (user_id, event, amount, sub1, sub2, raw_data, ip_address) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$userId, $event, $amount, $sub1, $sub2, $rawData, $ip]);

    // Mettre à jour l'utilisateur selon l'événement
    if ($userId) {
        if ($event === 'registration' || $event === 'signup') {
            $db->prepare("UPDATE users SET is_1win_verified = 1 WHERE id = ?")->execute([$userId]);
            $db->prepare("UPDATE referrals SET status = 'active' WHERE referred_id = ?")->execute([$userId]);
        }

        if ($event === 'deposit' || $event === 'first_deposit') {
            $db->prepare("UPDATE users SET has_deposited = 1, deposit_amount = deposit_amount + ? WHERE id = ?")->execute([$amount, $userId]);
            $db->prepare("UPDATE referrals SET status = 'verified', deposit_amount = ?, deposit_confirmed_at = CURRENT_TIMESTAMP WHERE referred_id = ?")->execute([$amount, $userId]);

            // Activer VIP du parrain si dépôt
            $stmt = $db->prepare("SELECT referrer_id FROM referrals WHERE referred_id = ?");
            $stmt->execute([$userId]);
            $ref = $stmt->fetch();
            if ($ref) {
                updateVipDirect($db, $ref['referrer_id']);
            }
        }
    }
} else {
    // Pas d'événement, juste log
    $stmt = $db->prepare("INSERT INTO postback_logs (event, raw_data, ip_address) VALUES (?, ?, ?)");
    $stmt->execute(['unknown', $rawData, $ip]);
}

// === Fonction VIP directe (sans charger tout le framework) ===
function updateVipDirect(PDO $db, int $userId): void {
    // Nombre total de filleuls
    $stmt = $db->prepare("SELECT COUNT(*) FROM referrals WHERE referrer_id = ?");
    $stmt->execute([$userId]);
    $totalReferrals = (int) $stmt->fetchColumn();

    // Récupérer le statut VIP actuel
    $stmt = $db->prepare("SELECT vip_expires_at FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $ud = $stmt->fetch();
    $now = date('Y-m-d H:i:s');

    if ($totalReferrals >= 30) {
        // VIP illimité
        $db->prepare("UPDATE users SET vip_expires_at = '2099-12-31 23:59:59' WHERE id = ?")->execute([$userId]);
    } elseif ($totalReferrals >= 15 && ($ud['vip_expires_at'] === null || $ud['vip_expires_at'] < $now)) {
        // 30 jours VIP
        $expires = date('Y-m-d H:i:s', strtotime('+30 days'));
        $db->prepare("UPDATE users SET vip_expires_at = ? WHERE id = ?")->execute([$expires, $userId]);
    } elseif ($totalReferrals >= 3 && ($ud['vip_expires_at'] === null || $ud['vip_expires_at'] < $now)) {
        // 7 jours VIP
        $expires = date('Y-m-d H:i:s', strtotime('+7 days'));
        $db->prepare("UPDATE users SET vip_expires_at = ? WHERE id = ?")->execute([$expires, $userId]);
    }
}

// Répondre OK
header('Content-Type: text/plain');
echo 'OK';

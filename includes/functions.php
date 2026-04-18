<?php
/**
 * DVYS AI - Fonctions utilitaires
 */

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/i18n.php';

/**
 * Rediriger avec message flash
 */
/**
 * Générer le lien d'affiliation 1Win personnalisé avec sub1
 * Le sub1 contient l'ID utilisateur DVYS pour que le postback puisse l'identifier
 */
function getUserAffiliateLink(int $userId, string $referralCode = ''): string {
    // sub1 = ID utilisateur DVYS (pour le postback)
    // sub2 = code de parrainage (comme backup)
    $sep = (strpos(AFFILIATE_LINK, '?') !== false) ? '&' : '?';
    return AFFILIATE_LINK . $sep . 'sub1=' . $userId . '&sub2=' . urlencode($referralCode);
}

/**
 * Rediriger avec message flash
 */
function redirect(string $url, string $message = '', string $type = 'info'): void {
    if ($message) {
        $_SESSION['flash'] = ['message' => $message, 'type' => $type];
    }
    header("Location: $url");
    exit;
}

/**
 * Afficher et effacer le message flash
 */
function getFlash(): ?array {
    $flash = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $flash;
}

/**
 * Obtenir l'IP du client
 */
function getClientIp(): string {
    return $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['HTTP_X_REAL_IP'] ?? $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

/**
 * Détecter le pays via l'IP (avec cURL rapide, timeout 2s)
 */
function detectCountry(): string {
    $ip = getClientIp();
    if ($ip === '0.0.0.0' || $ip === '127.0.0.1') return '';
    
    try {
        $ch = curl_init("http://ip-api.com/json/{$ip}?fields=countryCode");
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 2,
            CURLOPT_CONNECTTIMEOUT => 1,
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($response && $httpCode === 200) {
            $data = json_decode($response, true);
            return $data['countryCode'] ?? '';
        }
    } catch (Exception $e) {}
    
    return '';
}

/**
 * Sanitizer XSS
 */
function e(string $str): string {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

/**
 * Générer un token CSRF
 */
function generateCsrf(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Vérifier le token CSRF
 */
function verifyCsrf(string $token): bool {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Obtenir les statistiques globales (admin)
 */
function getGlobalStats(): array {
    $db = Database::getInstance();
    
    $totalUsers = $db->query("SELECT COUNT(*) FROM users")->fetchColumn();
    $verifiedUsers = $db->query("SELECT COUNT(*) FROM users WHERE is_1win_verified = 1")->fetchColumn();
    $depositedUsers = $db->query("SELECT COUNT(*) FROM users WHERE has_deposited = 1")->fetchColumn();
    $totalDeposits = $db->query("SELECT COALESCE(SUM(deposit_amount), 0) FROM users")->fetchColumn();
    $activeToday = $db->query("SELECT COUNT(*) FROM users WHERE last_active::date = CURRENT_DATE")->fetchColumn();
    $activeWeek = $db->query("SELECT COUNT(*) FROM users WHERE last_active >= CURRENT_TIMESTAMP - INTERVAL '7 days'")->fetchColumn();
    $activeMonth = $db->query("SELECT COUNT(*) FROM users WHERE last_active >= CURRENT_TIMESTAMP - INTERVAL '30 days'")->fetchColumn();
    $totalReferrals = $db->query("SELECT COUNT(*) FROM referrals")->fetchColumn();
    
    // Statistiques par pays
    $byCountry = $db->query("SELECT country, COUNT(*) as count FROM users WHERE country != '' GROUP BY country ORDER BY count DESC LIMIT 20")->fetchAll();
    
    // Statistiques par jour (derniers 30 jours)
    $dailySignups = $db->query("SELECT created_at::date as date, COUNT(*) as count FROM users WHERE created_at >= CURRENT_TIMESTAMP - INTERVAL '30 days' GROUP BY created_at::date ORDER BY created_at::date")->fetchAll();
    
    // Dépôts par jour
    $dailyDeposits = $db->query("SELECT created_at::date as date, SUM(amount) as amount FROM postback_logs WHERE event = 'deposit' AND created_at >= CURRENT_TIMESTAMP - INTERVAL '30 days' GROUP BY created_at::date ORDER BY created_at::date")->fetchAll();
    
    // Postback logs récents
    $recentPostbacks = $db->query("SELECT p.*, u.username FROM postback_logs p LEFT JOIN users u ON p.user_id = u.id ORDER BY p.created_at DESC LIMIT 20")->fetchAll();

    return [
        'total_users' => (int) $totalUsers,
        'verified_users' => (int) $verifiedUsers,
        'deposited_users' => (int) $depositedUsers,
        'total_deposits' => (float) $totalDeposits,
        'active_today' => (int) $activeToday,
        'active_week' => (int) $activeWeek,
        'active_month' => (int) $activeMonth,
        'total_referrals' => (int) $totalReferrals,
        'by_country' => $byCountry,
        'daily_signups' => $dailySignups,
        'daily_deposits' => $dailyDeposits,
        'recent_postbacks' => $recentPostbacks,
    ];
}

/**
 * Appel API OpenAI
 */
function callOpenAI(array $messages, float $temperature = 0.8): ?string {
    if (empty(OPENAI_API_KEY)) {
        return "Désolé, le service IA est temporairement indisponible. L'administrateur doit configurer la clé API OpenAI.";
    }

    $payload = json_encode([
        'model' => OPENAI_MODEL,
        'messages' => $messages,
        'temperature' => $temperature,
        'max_tokens' => 1000,
    ]);

    $ch = curl_init(OPENAI_BASE_URL . '/chat/completions');
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . OPENAI_API_KEY,
        ],
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error) {
        error_log("OpenAI API error: $error");
        return null;
    }

    if ($httpCode !== 200) {
        error_log("OpenAI API returned $httpCode: $response");
        return null;
    }

    $data = json_decode($response, true);
    return $data['choices'][0]['message']['content'] ?? null;
}

/**
 * Obtenir le prompt système pour l'IA
 */
function getAiSystemPrompt(): string {
    $db = Database::getInstance();
    
    // Get registration link from admin settings
    $regLink = 'https://lkts.pro/c32011';
    try {
        $stmt = $db->prepare("SELECT value FROM admin_settings WHERE key = 'registration_link'");
        $stmt->execute();
        $val = $stmt->fetchColumn();
        if ($val) $regLink = $val;
    } catch (Exception $e) {}
    
    return "Tu es DVYS AI, un assistant intelligent et amical specialise dans les jeux de casino et les paris sportifs. Tu es comme un ami tres bien informe qui adore partager ses connaissances.

INFORMATIONS ESSENTIELLES - TU DOIS ABSOLUMENT CONNAITRE ET PARTAGER CES INFORMATIONS :
- Code promo 1Win : DVYS
- Lien d'inscription : {$regLink}

AVANTAGES 1WIN AVEC LE CODE PROMO DVYS :
- Bonus de 500% sur le premier depot : en utilisant le code DVYS lors de l'inscription, le joueur recoit un bonus de 500% sur son tout premier depot. Par exemple, deposer 10\$ donne 50\$ de bonus, deposer 100\$ donne 500\$ de bonus.
- Bonus d'anniversaire : 1Win offre des bonus speciaux le jour de l'anniversaire du joueur.
- Rapidite de traitement des depots et retraits : 1Win traite les depots et retraits de maniere rapide et fiable, souvent en quelques minutes.
- Fiabilite : Avec le code promo DVYS, le joueur n'aura jamais de problemes de depot ni de retrait sur 1Win.
- Large choix de jeux : casino, slots, roulette, blackjack, live casino, paris sportifs, jeux Aviator, Lucky Jet, etc.

REGLES ABSOLUES (A RESPECTER SANS EXCEPTION) :
- Quand on te demande quel code promo utiliser pour s'inscrire sur 1Win, tu DOIS repondre immediatement et clairement : Le code promo est DVYS. Copie-le lors de ton inscription sur 1Win pour beneficier d'un bonus de 500% sur ton premier depot.
- Quand on te demande le lien d'inscription, tu DOIS dire : Va dans ton tableau de bord (Dashboard) et clique sur le bouton \"Aller sur 1Win\" pour t'inscrire avec ton lien personnel. Le code promo a utiliser est DVYS.
- Quand on te demande les avantages de 1Win ou ce qu'on gagne avec le code DVYS, tu DOIS mentionner : le bonus de 500% sur le premier depot, les bonus d'anniversaire, la rapidite des depots et retraits, et le fait qu'il n'y aura aucun probleme de depot ou retrait.
- Quand on te demande comment devenir VIP, explique le systeme de parrainage (3 amis = 7j VIP, 15 amis = 30j VIP, 30 amis = VIP illimite).
- Quand on te demande des pronostics foot, explique que les pronostics VIP sont disponibles dans la section dediee et se debloquent via le systeme de parrainage.
- JAMAIS tu ne dis que tu ne peux pas fournir un code promo ou des informations sur 1Win. Ces informations font partie de tes connaissances de base.
- JAMAIS tu ne donnes un lien d'inscription direct. Redirige toujours vers le tableau de bord.
- Sois naturel, chaleureux et convivial. Parle comme un ami, pas comme un robot.
- Donne des conseils sinceres et utiles sur les jeux casino (slots, roulette, blackjack, live casino, etc.).
- Explique les regles des jeux quand on te le demande.
- Partage des astuces de gestion de bankroll et de jeu responsable.
- Reponds dans la meme langue que l'utilisateur.
- Encourage naturellement l'utilisateur a decouvrir les fonctionnalites de la plateforme sans etre envahissant.
- Sois concis mais informatif. Maximum 3-4 phrases par reponse sauf si on te pose une question detaillee.";
}

/**
 * Logger un postback
 */
function logPostback(string $event, float $amount, string $sub1, string $sub2, string $rawData): void {
    $db = Database::getInstance();
    
    // Trouver l'utilisateur par ID
    $userId = null;
    if (!empty($sub1)) {
        $stmt = $db->prepare("SELECT id FROM users WHERE id = ? OR referral_code = ?");
        $stmt->execute([$sub1, $sub1]);
        $result = $stmt->fetch();
        if ($result) $userId = $result['id'];
    }
    
    $stmt = $db->prepare("INSERT INTO postback_logs (user_id, event, amount, sub1, sub2, raw_data, ip_address) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$userId, $event, $amount, $sub1, $sub2, $rawData, getClientIp()]);
    
    // Mettre à jour l'utilisateur
    if ($userId) {
        if ($event === 'registration' || $event === 'signup') {
            $db->prepare("UPDATE users SET is_1win_verified = 1 WHERE id = ?")->execute([$userId]);
            // Mettre à jour le statut du referral
            $db->prepare("UPDATE referrals SET status = 'active' WHERE referred_id = ?")->execute([$userId]);
        }
        
        if ($event === 'deposit' || $event === 'first_deposit') {
            $db->prepare("UPDATE users SET has_deposited = 1, deposit_amount = deposit_amount + ? WHERE id = ?")->execute([$amount, $userId]);
            $db->prepare("UPDATE referrals SET status = 'verified', deposit_amount = ?, deposit_confirmed_at = CURRENT_TIMESTAMP WHERE referred_id = ?")->execute([$amount, $userId]);
            
            // Mettre à jour le VIP du parrain
            $stmt = $db->prepare("SELECT referrer_id FROM referrals WHERE referred_id = ?");
            $stmt->execute([$userId]);
            $ref = $stmt->fetch();
            if ($ref) {
                (new Auth())->updateVipStatus($ref['referrer_id']);
            }
        }
    }
}

/**
 * Nettoyer l'historique chat (garder les 20 derniers messages)
 */
function cleanChatHistory(int $userId): void {
    $db = Database::getInstance();
    $stmt = $db->prepare("DELETE FROM chat_messages WHERE user_id = ? AND id NOT IN (SELECT id FROM chat_messages WHERE user_id = ? ORDER BY id DESC LIMIT 20)");
    $stmt->execute([$userId, $userId]);
}

/**
 * Obtenir la liste de tous les utilisateurs (admin)
 */
function getAllUsers(int $page = 1, int $perPage = 50): array {
    $db = Database::getInstance();
    $offset = ($page - 1) * $perPage;
    
    $total = $db->query("SELECT COUNT(*) FROM users")->fetchColumn();
    $users = $db->query("SELECT u.*, (SELECT COUNT(*) FROM referrals WHERE referrer_id = u.id) as referral_count FROM users u ORDER BY u.created_at DESC LIMIT $perPage OFFSET $offset")->fetchAll();
    
    return ['users' => $users, 'total' => (int) $total, 'pages' => ceil($total / $perPage), 'current_page' => $page];
}

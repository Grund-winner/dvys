<?php
/**
 * DVYS AI - Authentification et sessions
 */

require_once __DIR__ . '/database.php';

class Auth {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * Créer un nouveau compte utilisateur
     */
    public function register(string $username, string $email, string $password, string $referralCode = ''): array {
        // Valider l'username
        if (strlen($username) < 3 || strlen($username) > 30) {
            return ['success' => false, 'error' => 'username_invalid'];
        }
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
            return ['success' => false, 'error' => 'username_chars'];
        }

        // Valider l'email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'error' => 'email_invalid'];
        }

        // Valider le mot de passe
        if (strlen($password) < 6) {
            return ['success' => false, 'error' => 'password_short'];
        }

        // Vérifier l'unicité
        $stmt = $this->db->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        if ($stmt->fetch()) {
            return ['success' => false, 'error' => 'user_exists'];
        }

        // Générer un code de parrainage unique
        $myReferralCode = $this->generateReferralCode($username);

        // Traiter le parrainage
        $referredBy = null;
        if (!empty($referralCode)) {
            $stmt = $this->db->prepare("SELECT id FROM users WHERE referral_code = ?");
            $stmt->execute([$referralCode]);
            $referrer = $stmt->fetch();
            if ($referrer) {
                $referredBy = $referrer['id'];
            }
        }

        // Hasher le mot de passe
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        // Insérer l'utilisateur
        $stmt = $this->db->prepare("INSERT INTO users (username, email, password, referral_code, referred_by, ip_address) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$username, $email, $hashedPassword, $myReferralCode, $referredBy, $_SERVER['REMOTE_ADDR'] ?? '']);

        $userId = $this->db->lastInsertId();

        // Créer l'entrée de parrainage si applicable
        if ($referredBy !== null) {
            $stmt = $this->db->prepare("INSERT INTO referrals (referrer_id, referred_id) VALUES (?, ?)");
            $stmt->execute([$referredBy, $userId]);
            
            // Vérifier et activer le VIP du parrain dès qu'un nouveau filleul s'inscrit
            $this->updateVipStatus($referredBy);
        }

        // Démarrer la session
        $this->startSession($userId);

        return ['success' => true, 'user_id' => $userId, 'referral_code' => $myReferralCode];
    }

    /**
     * Connexion utilisateur
     */
    public function login(string $login, string $password): array {
        $stmt = $this->db->prepare("SELECT id, username, email, password FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$login, $login]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password'])) {
            return ['success' => false, 'error' => 'invalid_credentials'];
        }

        // Mettre à jour la dernière activité
        $this->db->prepare("UPDATE users SET last_active = CURRENT_TIMESTAMP WHERE id = ?")->execute([$user['id']]);

        // Démarrer la session
        $this->startSession($user['id']);

        return ['success' => true, 'user_id' => $user['id']];
    }

    /**
     * Déconnexion
     */
    public function logout(): void {
        if (isset($_SESSION['session_id'])) {
            $this->db->prepare("DELETE FROM sessions WHERE id = ?")->execute([$_SESSION['session_id']]);
        }
        session_destroy();
    }

    /**
     * Vérifier si l'utilisateur est connecté
     */
    public function isLoggedIn(): bool {
        return isset($_SESSION['user_id']) && $_SESSION['user_id'] > 0;
    }

    /**
     * Obtenir l'utilisateur actuel
     */
    public function currentUser(): ?array {
        if (!$this->isLoggedIn()) return null;

        $stmt = $this->db->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Vérifier si l'utilisateur est admin
     */
    public function isAdmin(): bool {
        $user = $this->currentUser();
        return $user && $user['id'] === 1; // Le premier utilisateur est admin
    }

    /**
     * Démarrer une session sécurisée
     */
    private function startSession(int $userId): void {
        // Session already started by config.php

        $sessionId = bin2hex(random_bytes(32));
        $_SESSION['user_id'] = $userId;
        $_SESSION['session_id'] = $sessionId;

        // Stocker en base
        $expiresAt = date('Y-m-d H:i:s', time() + SESSION_LIFETIME);
        $this->db->prepare("INSERT INTO sessions (id, user_id, ip_address, user_agent, expires_at) VALUES (?, ?, ?, ?, ?) ON CONFLICT (id) DO UPDATE SET user_id = EXCLUDED.user_id, ip_address = EXCLUDED.ip_address, user_agent = EXCLUDED.user_agent, expires_at = EXCLUDED.expires_at")
            ->execute([$sessionId, $userId, $_SERVER['REMOTE_ADDR'] ?? '', $_SERVER['HTTP_USER_AGENT'] ?? '', $expiresAt]);
    }

    /**
     * Générer un code de parrainage unique
     */
    private function generateReferralCode(string $username): string {
        $base = strtoupper(substr($username, 0, 4)) . rand(100, 999);
        $code = $base;
        $i = 1;
        while (true) {
            $stmt = $this->db->prepare("SELECT id FROM users WHERE referral_code = ?");
            $stmt->execute([$code]);
            if (!$stmt->fetch()) break;
            $code = $base . $i++;
        }
        return $code;
    }

    /**
     * Obtenir les statistiques de parrainage d'un utilisateur
     */
    public function getReferralStats(int $userId): array {
        // Nombre total de filleuls
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM referrals WHERE referrer_id = ?");
        $stmt->execute([$userId]);
        $totalReferrals = (int) $stmt->fetchColumn();

        // Filleuls vérifiés (ont fait un dépôt)
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM referrals WHERE referrer_id = ? AND status = 'verified'");
        $stmt->execute([$userId]);
        $verifiedReferrals = (int) $stmt->fetchColumn();

        // Niveau VIP
        $vipLevel = 0;
        $vipExpiresAt = null;
        if ($totalReferrals >= REFERRAL_TIER_3) {
            $vipLevel = 3; // Illimité
            $vipExpiresAt = '2099-12-31 23:59:59';
        } elseif ($totalReferrals >= REFERRAL_TIER_2) {
            $vipLevel = 2;
            $user = $this->db->prepare("SELECT vip_expires_at FROM users WHERE id = ?");
            $user->execute([$userId]);
            $ud = $user->fetch();
            $vipExpiresAt = $ud['vip_expires_at'];
        } elseif ($totalReferrals >= REFERRAL_TIER_1) {
            $vipLevel = 1;
            $user = $this->db->prepare("SELECT vip_expires_at FROM users WHERE id = ?");
            $user->execute([$userId]);
            $ud = $user->fetch();
            $vipExpiresAt = $ud['vip_expires_at'];
        }

        // Progression vers le prochain palier
        $nextTier = 0;
        $nextTierName = '';
        $progress = 0;
        if ($totalReferrals < REFERRAL_TIER_1) {
            $nextTier = REFERRAL_TIER_1;
            $nextTierName = '7 jours VIP';
            $progress = ($totalReferrals / REFERRAL_TIER_1) * 100;
        } elseif ($totalReferrals < REFERRAL_TIER_2) {
            $nextTier = REFERRAL_TIER_2;
            $nextTierName = '30 jours VIP';
            $progress = (($totalReferrals - REFERRAL_TIER_1) / ($REFERRAL_TIER_2 - REFERRAL_TIER_1)) * 100;
        } elseif ($totalReferrals < REFERRAL_TIER_3) {
            $nextTier = REFERRAL_TIER_3;
            $nextTierName = 'VIP Illimité';
            $progress = (($totalReferrals - REFERRAL_TIER_2) / ($REFERRAL_TIER_3 - REFERRAL_TIER_2)) * 100;
        }

        // Liste des filleuls récents
        $stmt = $this->db->prepare("SELECT u.username, u.is_1win_verified, u.has_deposited, u.created_at, r.status FROM referrals r JOIN users u ON r.referred_id = u.id WHERE r.referrer_id = ? ORDER BY r.created_at DESC LIMIT 20");
        $stmt->execute([$userId]);
        $referralsList = $stmt->fetchAll();

        return [
            'total' => $totalReferrals,
            'verified' => $verifiedReferrals,
            'vip_level' => $vipLevel,
            'vip_expires_at' => $vipExpiresAt,
            'next_tier' => $nextTier,
            'next_tier_name' => $nextTierName,
            'progress' => min(100, $progress),
            'list' => $referralsList,
        ];
    }

    /**
     * Vérifier et mettre à jour le statut VIP
     */
    public function updateVipStatus(int $userId): void {
        $stats = $this->getReferralStats($userId);
        $user = $this->db->prepare("SELECT vip_expires_at FROM users WHERE id = ?");
        $user->execute([$userId]);
        $ud = $user->fetch();

        $now = date('Y-m-d H:i:s');

        if ($stats['vip_level'] === 3) {
            // VIP illimité
            $this->db->prepare("UPDATE users SET vip_expires_at = '2099-12-31 23:59:59' WHERE id = ?")->execute([$userId]);
        } elseif ($stats['vip_level'] === 2 && ($ud['vip_expires_at'] === null || $ud['vip_expires_at'] < $now)) {
            // Activer 30 jours VIP
            $expires = date('Y-m-d H:i:s', strtotime('+30 days'));
            $this->db->prepare("UPDATE users SET vip_expires_at = ? WHERE id = ?")->execute([$expires, $userId]);
        } elseif ($stats['vip_level'] === 1 && ($ud['vip_expires_at'] === null || $ud['vip_expires_at'] < $now)) {
            // Activer 7 jours VIP
            $expires = date('Y-m-d H:i:s', strtotime('+7 days'));
            $this->db->prepare("UPDATE users SET vip_expires_at = ? WHERE id = ?")->execute([$expires, $userId]);
        }
    }

    /**
     * Vérifier si l'utilisateur a accès VIP
     */
    public function hasVipAccess(?int $userId = null): bool {
        if ($userId === null) $userId = $_SESSION['user_id'] ?? 0;
        if (!$userId) return false;

        // Admin a toujours acces VIP
        if ($userId === 1) return true;

        $stmt = $this->db->prepare("SELECT vip_expires_at FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();

        if (!$user) return false;
        if ($user['vip_expires_at'] === null) return false;
        return $user['vip_expires_at'] > date('Y-m-d H:i:s');
    }
}

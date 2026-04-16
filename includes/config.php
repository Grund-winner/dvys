<?php
/**
 * DVYS AI - Configuration
 */

// === Output buffering (fix headers already sent) ===
ob_start();

// === Session ===
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// === Debug ===
$isProduction = getenv('RENDER') || getenv('RENDER_SERVICE_NAME');
if (!$isProduction) {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
    error_reporting(0);
}

// === Sécurité ===
define('APP_SECRET', getenv('APP_SECRET') ?: 'dvys-ai-secret-change-in-production-' . md5(__FILE__));
define('SESSION_LIFETIME', 86400 * 30); // 30 jours

// === Base de données PostgreSQL (Neon) ===
define('DATABASE_URL', getenv('DATABASE_URL') ?: '');

// === Sécurité cookie ===
define('COOKIE_SECURE', (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https'));

// === 1Win Affiliation ===
define('AFFILIATE_LINK', 'https://lkts.pro/c32011');
define('PROMO_CODE', 'DVYS');

// === OpenAI / IA (Groq - gratuit et rapide) ===
define('OPENAI_API_KEY', getenv('OPENAI_API_KEY') ?: '');
define('OPENAI_MODEL', getenv('OPENAI_MODEL') ?: 'llama-3.3-70b-versatile');
define('OPENAI_BASE_URL', getenv('OPENAI_BASE_URL') ?: 'https://api.groq.com/openai/v1');

// === Parrainage ===
define('REFERRAL_TIER_1', 3);   // 3 filleuls = 7 jours VIP
define('REFERRAL_TIER_2', 15);  // 15 filleuls = 30 jours VIP
define('REFERRAL_TIER_3', 30);  // 30 filleuls = VIP illimité

// === Pronostics ===
define('MAX_PREDICTIONS_PER_DAY', 3);

// === Admin ===
define('ADMIN_USERNAME', getenv('ADMIN_USERNAME') ?: 'admin');
define('ADMIN_PASSWORD_HASH', getenv('ADMIN_PASSWORD_HASH') ?: password_hash('dvys2025admin', PASSWORD_DEFAULT));

// === Langues supportées ===
define('SUPPORTED_LANGUAGES', ['fr', 'en', 'es', 'pt', 'ru', 'ar', 'tr', 'hi', 'uz', 'az']);
define('DEFAULT_LANGUAGE', 'fr');

// === URLs ===
define('BASE_URL', getenv('BASE_URL') ?: (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost'));
define('APP_NAME', 'DVYS AI');

// === Fuseau horaire ===
date_default_timezone_set('UTC');

// === Headers de sécurité ===
if (!headers_sent()) {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('X-XSS-Protection: 1; mode=block');
}

// === Run database migrations ===
if (class_exists('Database')) {
    Database::migrate();
}

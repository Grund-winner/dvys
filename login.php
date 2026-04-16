<?php
/**
 * DVYS AI - Page de connexion
 * ZERO promo code sur cette page
 */

// === TRAITEMENT AVANT TOUT OUTPUT ===
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/database.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/i18n.php';
require_once __DIR__ . '/includes/functions.php';

$lang = getCurrentLang();
$auth = new Auth();

// Rediriger si déjà connecté
if ($auth->isLoggedIn()) {
    header('Location: /dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = $_POST['csrf'] ?? '';
    $login = trim($_POST['login'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!verifyCsrf($csrf)) {
        $error = 'Invalid request';
    } else {
        $result = $auth->login($login, $password);
        if ($result['success']) {
            header('Location: /dashboard.php');
            exit;
        } else {
            $error = t('login_failed', $lang);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="<?= e($lang) ?>" dir="<?= isRTL($lang) ? 'rtl' : 'ltr' ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Connexion - DVYS AI</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
<div class="auth-page">
    <div class="auth-container">
        <div class="auth-logo">
            <div class="auth-logo-icon"></div>
            <h1>DVYS AI</h1>
            <p><?= e(t('login', $lang)) ?></p>
        </div>

        <div class="auth-card">
            <?php if ($error): ?>
                <div style="background: #FFEDED; color: #FF3B30; padding: 12px; border-radius: 12px; font-size: 14px; margin-bottom: 16px;">
                    <?= e($error) ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="/login.php">
                <input type="hidden" name="csrf" value="<?= e(generateCsrf()) ?>">
                
                <div class="form-group">
                    <label><?= e(t('username', $lang)) ?> / <?= e(t('email', $lang)) ?></label>
                    <input type="text" name="login" class="form-input" placeholder="pseudo ou email" required autocomplete="username">
                </div>

                <div class="form-group">
                    <label><?= e(t('password', $lang)) ?></label>
                    <input type="password" name="password" class="form-input" placeholder="••••••••" required minlength="6">
                </div>

                <button type="submit" class="form-submit"><?= e(t('submit', $lang)) ?></button>
            </form>
        </div>

        <div class="auth-footer">
            Pas encore de compte ? <a href="/register.php<?= !empty($_GET['ref']) ? '?ref=' . e($_GET['ref']) : '' ?>"><?= e(t('register', $lang)) ?></a>
        </div>
    </div>
</div>
</body>
</html>

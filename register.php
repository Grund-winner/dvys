<?php
/**
 * DVYS AI - Page d'inscription
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

$refCode = $_GET['ref'] ?? '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = $_POST['csrf'] ?? '';
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';
    $ref = $_POST['ref'] ?? '';

    if (!verifyCsrf($csrf)) {
        $error = 'Invalid request';
    } elseif ($password !== $password_confirm) {
        $error = 'Les mots de passe ne correspondent pas';
    } else {
        $result = $auth->register($username, $email, $password, $ref);
        if ($result['success']) {
            // Détecter le pays en arrière-plan (ne bloque pas la redirection)
            $userId = $result['user_id'];
            header('Location: /dashboard.php');
            fastcgi_finish_request();
            // Exécuté après que la réponse soit envoyée au navigateur
            $country = detectCountry();
            if ($country) {
                $db = Database::getInstance();
                $db->prepare("UPDATE users SET country = ? WHERE id = ?")->execute([$country, $userId]);
            }
            exit;
        } else {
            $errors = [
                'username_invalid' => "Nom d'utilisateur invalide (3-30 caracteres, lettres/chiffres uniquement)",
                'username_chars' => "Lettres, chiffres et _ uniquement",
                'email_invalid' => 'Email invalide',
                'password_short' => 'Mot de passe trop court (minimum 6 caracteres)',
                'user_exists' => 'Ce nom d\'utilisateur ou email est deja utilise',
            ];
            $error = $errors[$result['error']] ?? 'Echec de l\'inscription';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="<?= e($lang) ?>" dir="<?= isRTL($lang) ? 'rtl' : 'ltr' ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Inscription - DVYS AI</title>
    <link rel="stylesheet" href="/assets/css/style.css?v=1.7">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
<div class="auth-page">
    <div class="auth-container">
        <div class="auth-logo">
            <div class="auth-logo-icon"></div>
            <h1>DVYS AI</h1>
            <p><?= e(t('register', $lang)) ?></p>
        </div>

        <div class="auth-card">
            <?php if ($error): ?>
                <div style="background: #FFEDED; color: #FF3B30; padding: 12px; border-radius: 12px; font-size: 14px; margin-bottom: 16px;">
                    <?= e($error) ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="/register.php">
                <input type="hidden" name="csrf" value="<?= e(generateCsrf()) ?>">
                <?php if ($refCode): ?>
                <input type="hidden" name="ref" value="<?= e($refCode) ?>">
                <?php endif; ?>

                <div class="form-group">
                    <label><?= e(t('username', $lang)) ?></label>
                    <input type="text" name="username" class="form-input" placeholder="pseudo" required minlength="3" maxlength="30" pattern="[a-zA-Z0-9_]+" value="<?= e($_POST['username'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label><?= e(t('email', $lang)) ?></label>
                    <input type="email" name="email" class="form-input" placeholder="email@exemple.com" required value="<?= e($_POST['email'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label><?= e(t('password', $lang)) ?></label>
                    <input type="password" name="password" class="form-input" placeholder="••••••••" required minlength="6">
                </div>

                <div class="form-group">
                    <label><?= e(t('password', $lang)) ?> (confirmation)</label>
                    <input type="password" name="password_confirm" class="form-input" placeholder="••••••••" required minlength="6">
                </div>

                <button type="submit" class="form-submit"><?= e(t('cta_button', $lang)) ?></button>
            </form>
        </div>

        <div class="auth-footer">
            Deja un compte ? <a href="/login.php<?= $refCode ? '?ref=' . e($refCode) : '' ?>"><?= e(t('login', $lang)) ?></a>
        </div>
    </div>
</div>
</body>
</html>

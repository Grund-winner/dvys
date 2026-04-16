<?php
/**
 * DVYS AI - Admin Settings
 * Gestion des liens sociaux et parametres du site
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

$auth = new Auth();
if (!$auth->isLoggedIn() || !$auth->isAdmin()) {
    header('Location: /login.php');
    exit;
}

$db = Database::getInstance();
Database::migrate();
$message = '';
$messageType = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $telegram = trim($_POST['social_telegram'] ?? '');
    $whatsapp = trim($_POST['social_whatsapp'] ?? '');
    $tiktok = trim($_POST['social_tiktok'] ?? '');
    $registrationLink = trim($_POST['registration_link'] ?? '');

    $db->prepare("INSERT INTO admin_settings (key, value, updated_at) VALUES ('social_telegram', ?, CURRENT_TIMESTAMP) ON CONFLICT (key) DO UPDATE SET value = EXCLUDED.value, updated_at = EXCLUDED.updated_at")->execute([$telegram]);
    $db->prepare("INSERT INTO admin_settings (key, value, updated_at) VALUES ('social_whatsapp', ?, CURRENT_TIMESTAMP) ON CONFLICT (key) DO UPDATE SET value = EXCLUDED.value, updated_at = EXCLUDED.updated_at")->execute([$whatsapp]);
    $db->prepare("INSERT INTO admin_settings (key, value, updated_at) VALUES ('social_tiktok', ?, CURRENT_TIMESTAMP) ON CONFLICT (key) DO UPDATE SET value = EXCLUDED.value, updated_at = EXCLUDED.updated_at")->execute([$tiktok]);
    $db->prepare("INSERT INTO admin_settings (key, value, updated_at) VALUES ('registration_link', ?, CURRENT_TIMESTAMP) ON CONFLICT (key) DO UPDATE SET value = EXCLUDED.value, updated_at = EXCLUDED.updated_at")->execute([$registrationLink]);

    $message = 'Parametres enregistres';
    $messageType = 'success';
}

// Fetch current settings
function getSetting($db, $key, $default = '') {
    $stmt = $db->prepare("SELECT value FROM admin_settings WHERE key = ?");
    $stmt->execute([$key]);
    $val = $stmt->fetchColumn();
    return $val !== false ? $val : $default;
}

$socialTelegram = getSetting($db, 'social_telegram', 'https://t.me/dvys_ai');
$socialWhatsapp = getSetting($db, 'social_whatsapp', 'https://wa.me/dvys_ai');
$socialTiktok = getSetting($db, 'social_tiktok', 'https://tiktok.com/@dvys_ai');
$registrationLink = getSetting($db, 'registration_link', 'https://lkts.pro/c32011');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Parametres - DVYS Admin</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        .admin-mobile-nav { display: none; background: white; border-bottom: 1px solid var(--border); padding: 12px 16px; position: sticky; top: 0; z-index: 100; }
        .admin-mobile-nav a { display: inline-block; padding: 6px 12px; margin: 0 4px 4px 0; border-radius: var(--radius-full); font-size: 13px; font-weight: 500; color: var(--text-secondary); text-decoration: none; background: var(--bg); }
        @media (max-width: 768px) {
            .admin-sidebar { display: none !important; }
            .admin-content { margin-left: 0 !important; }
            .admin-mobile-nav { display: block; }
        }
        .settings-form { background: white; padding: 24px; border-radius: var(--radius-lg); margin-bottom: 24px; box-shadow: 0 1px 4px rgba(0,0,0,0.04); }
        .settings-form h3 { font-size: 18px; font-weight: 700; margin-bottom: 8px; }
        .settings-form .desc { font-size: 14px; color: var(--text-secondary); margin-bottom: 20px; line-height: 1.5; }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 12px; }
        .form-row.full { grid-template-columns: 1fr; }
        .form-group { margin-bottom: 16px; }
        .form-group label { display: flex; align-items: center; gap: 8px; font-size: 13px; font-weight: 600; margin-bottom: 4px; color: var(--text-secondary); }
        .form-group label svg { width: 16px; height: 16px; flex-shrink: 0; }
        .form-group input { width: 100%; padding: 10px 12px; border: 1px solid var(--border); border-radius: var(--radius-md); font-size: 14px; font-family: inherit; }
        .form-group input:focus { outline: none; border-color: var(--primary); }
        .form-group .hint { font-size: 12px; color: var(--text-tertiary); margin-top: 4px; }
        .social-preview { display: flex; gap: 12px; margin-top: 16px; padding: 16px; background: var(--bg); border-radius: var(--radius-md); }
        .social-preview a { width: 44px; height: 44px; border-radius: 50%; display: flex; align-items: center; justify-content: center; text-decoration: none; transition: transform 0.2s; }
        .social-preview a:hover { transform: translateY(-2px); }
        .social-preview a svg { width: 22px; height: 22px; }
        .social-preview a.telegram { background: #E8F4FD; color: #0088CC; }
        .social-preview a.whatsapp { background: #E8F8EE; color: #25D366; }
        .social-preview a.tiktok { background: #F2F2F7; color: #010101; }
        .msg { padding: 12px 16px; border-radius: 12px; margin-bottom: 20px; font-size: 14px; font-weight: 500; }
        .msg.success { background: #E8F8EE; color: #34C759; }
        .msg.error { background: #FFEDED; color: #FF3B30; }
    </style>
</head>
<body class="admin-page">
    <div class="admin-mobile-nav">
        <a href="/admin/">Dashboard</a>
        <a href="/admin/users.php">Utilisateurs</a>
        <a href="/admin/broadcast.php">Broadcast</a>
        <a href="/admin/export.php">Export</a>
        <a href="/admin/postbacks.php">Postbacks</a>
        <a href="/admin/predictions.php">Pronostics</a>
        <a href="/admin/games.php">Jeux</a>
        <a href="/admin/settings.php" style="color:var(--primary);background:var(--primary-light);">Parametres</a>
        <a href="/dashboard.php">Retour</a>
    </div>
    <div class="admin-layout">
        <aside class="admin-sidebar">
            <div class="admin-logo"><a href="/admin/"><span class="logo-icon" style="width:28px;height:28px;font-size:12px;">&#10022;</span> DVYS Admin</a></div>
            <nav class="admin-nav">
                <a href="/admin/" class="admin-nav-link">Dashboard</a>
                <a href="/admin/users.php" class="admin-nav-link">Utilisateurs</a>
                <a href="/admin/broadcast.php" class="admin-nav-link">Broadcast</a>
                <a href="/admin/export.php" class="admin-nav-link">Export CSV</a>
                <a href="/admin/postbacks.php" class="admin-nav-link">Postbacks</a>
                <a href="/admin/predictions.php" class="admin-nav-link">Pronostics</a>
                <a href="/admin/games.php" class="admin-nav-link">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="6" width="20" height="12" rx="2"/><path d="M6 12h4M8 10v4M15 11h.01M18 13h.01"/></svg>
                    Jeux
                </a>
                <a href="/admin/settings.php" class="admin-nav-link active" style="color:var(--primary);">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
                    Parametres
                </a>
                <div style="border-top:1px solid var(--border);margin:12px 0;"></div>
                <a href="/dashboard.php" class="admin-nav-link">Retour au site</a>
            </nav>
        </aside>
        <main class="admin-content">
            <div class="admin-header">
                <h1>Parametres du site</h1>
                <p>Configure les liens sociaux et autres parametres affiches sur la page d'accueil</p>
            </div>

            <?php if ($message): ?>
            <div class="msg <?= $messageType ?>"><?= e($message) ?></div>
            <?php endif; ?>

            <!-- Lien d'inscription -->
            <div class="settings-form">
                <h3>Lien d'inscription</h3>
                <p class="desc">Configuration du lien d'affiliation 1Win pour l'inscription des utilisateurs.</p>

                <form method="POST">
                    <div class="form-group">
                        <label>Lien d'inscription 1Win</label>
                        <input type="url" name="registration_link" value="<?= e($registrationLink) ?>" placeholder="https://lkts.pro/c32011">
                        <div class="hint">Le lien d'affiliation pour l'inscription des utilisateurs. Changez-le si le lien 1Win est mis a jour.</div>
                    </div>
                    <div class="form-actions" style="margin-top: 20px;">
                        <button type="submit" class="btn-primary">Enregistrer le lien d'inscription</button>
                    </div>
                </form>
            </div>

            <!-- Liens sociaux -->
            <div class="settings-form">
                <h3>Liens Sociaux</h3>
                <p class="desc">Ces liens sont affiches dans la section "Rejoins la communaute" sur la page d'accueil. Modifie-les avec tes propres URLs.</p>

                <form method="POST">
                    <div class="form-group">
                        <label>
                            <svg viewBox="0 0 24 24" fill="#0088CC"><path d="M11.944 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0a12 12 0 0 0-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 0 1 .171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.479.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z"/></svg>
                            Lien Telegram
                        </label>
                        <input type="url" name="social_telegram" value="<?= e($socialTelegram) ?>" placeholder="https://t.me/ton_canal">
                        <div class="hint">Le lien de ta chaine ou groupe Telegram</div>
                    </div>

                    <div class="form-group">
                        <label>
                            <svg viewBox="0 0 24 24" fill="#25D366"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                            Lien WhatsApp
                        </label>
                        <input type="url" name="social_whatsapp" value="<?= e($socialWhatsapp) ?>" placeholder="https://wa.me/ton_numero">
                        <div class="hint">Le lien de ta communaute ou canal WhatsApp</div>
                    </div>

                    <div class="form-group">
                        <label>
                            <svg viewBox="0 0 24 24" fill="#010101"><path d="M19.59 6.69a4.83 4.83 0 01-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 01-2.88 2.5 2.89 2.89 0 01-2.89-2.89 2.89 2.89 0 012.89-2.89c.28 0 .54.04.79.11V9.02a6.27 6.27 0 00-.79-.05 6.34 6.34 0 00-6.34 6.34 6.34 6.34 0 006.34 6.34 6.34 6.34 0 006.34-6.34V8.72a8.2 8.2 0 004.76 1.52V6.79a4.85 4.85 0 01-1-.1z"/></svg>
                            Lien TikTok
                        </label>
                        <input type="url" name="social_tiktok" value="<?= e($socialTiktok) ?>" placeholder="https://tiktok.com/@ton_compte">
                        <div class="hint">Le lien de ton profil TikTok</div>
                    </div>

                    <!-- Apercu -->
                    <h3 style="margin-top: 24px; margin-bottom: 8px;">Apercu</h3>
                    <div class="social-preview">
                        <a href="<?= e($socialTelegram) ?>" target="_blank" class="telegram" aria-label="Telegram">
                            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M11.944 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0a12 12 0 0 0-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 0 1 .171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.479.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z"/></svg>
                        </a>
                        <a href="<?= e($socialWhatsapp) ?>" target="_blank" class="whatsapp" aria-label="WhatsApp">
                            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                        </a>
                        <a href="<?= e($socialTiktok) ?>" target="_blank" class="tiktok" aria-label="TikTok">
                            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M19.59 6.69a4.83 4.83 0 01-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 01-2.88 2.5 2.89 2.89 0 01-2.89-2.89 2.89 2.89 0 012.89-2.89c.28 0 .54.04.79.11V9.02a6.27 6.27 0 00-.79-.05 6.34 6.34 0 00-6.34 6.34 6.34 6.34 0 006.34 6.34 6.34 6.34 0 006.34-6.34V8.72a8.2 8.2 0 004.76 1.52V6.79a4.85 4.85 0 01-1-.1z"/></svg>
                        </a>
                    </div>

                    <div class="form-actions" style="margin-top: 20px;">
                        <button type="submit" class="btn-primary">Enregistrer les modifications</button>
                    </div>
                </form>
            </div>
        </main>
    </div>
</body>
</html>

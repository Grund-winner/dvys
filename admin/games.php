<?php
/**
 * DVYS AI - Admin Games Management
 * Gestion des jeux populaires (images, previsions, seuils de depot)
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

// Handle actions
$action = $_GET['action'] ?? '';

if ($action === 'delete' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $db->prepare("DELETE FROM games WHERE id = ?")->execute([$id]);
    $message = 'Jeu supprime';
    $messageType = 'success';
}

if ($action === 'toggle' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $db->prepare("UPDATE games SET is_active = CASE WHEN is_active = 1 THEN 0 ELSE 1 END WHERE id = ?")->execute([$id]);
    $message = 'Statut mis a jour';
    $messageType = 'success';
}

if ($action === 'move_up' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $stmt = $db->prepare("SELECT * FROM games WHERE id = ?");
    $stmt->execute([$id]);
    $game = $stmt->fetch();
    if ($game) {
        $stmt2 = $db->prepare("SELECT * FROM games WHERE sort_order < ? ORDER BY sort_order DESC LIMIT 1");
        $stmt2->execute([$game['sort_order']]);
        $prev = $stmt2->fetch();
        if ($prev) {
            $db->prepare("UPDATE games SET sort_order = ? WHERE id = ?")->execute([$prev['sort_order'], $game['id']]);
            $db->prepare("UPDATE games SET sort_order = ? WHERE id = ?")->execute([$game['sort_order'], $prev['id']]);
        }
    }
    header('Location: /admin/games.php');
    exit;
}

if ($action === 'move_down' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $stmt = $db->prepare("SELECT * FROM games WHERE id = ?");
    $stmt->execute([$id]);
    $game = $stmt->fetch();
    if ($game) {
        $stmt2 = $db->prepare("SELECT * FROM games WHERE sort_order > ? ORDER BY sort_order ASC LIMIT 1");
        $stmt2->execute([$game['sort_order']]);
        $next = $stmt2->fetch();
        if ($next) {
            $db->prepare("UPDATE games SET sort_order = ? WHERE id = ?")->execute([$next['sort_order'], $game['id']]);
            $db->prepare("UPDATE games SET sort_order = ? WHERE id = ?")->execute([$game['sort_order'], $next['id']]);
        }
    }
    header('Location: /admin/games.php');
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $editId = intval($_POST['edit_id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $imageUrl = trim($_POST['image_url'] ?? '');
    $linkUrl = trim($_POST['link_url'] ?? '');
    $minDepositUsd = floatval($_POST['min_deposit_usd'] ?? 0);
    $predictionText = trim($_POST['prediction_text'] ?? '');
    $predictionLink = trim($_POST['prediction_link'] ?? '');

    if (empty($imageUrl)) {
        $message = "L'URL de l'image est obligatoire";
        $messageType = 'error';
    } else {
        if ($editId > 0) {
            $stmt = $db->prepare("UPDATE games SET name = ?, image_url = ?, link_url = ?, min_deposit_usd = ?, prediction_text = ?, prediction_link = ? WHERE id = ?");
            $stmt->execute([$name, $imageUrl, $linkUrl, $minDepositUsd, $predictionText, $predictionLink, $editId]);
            $message = 'Jeu mis a jour';
        } else {
            $maxSort = $db->query("SELECT COALESCE(MAX(sort_order), 0) FROM games")->fetchColumn();
            $stmt = $db->prepare("INSERT INTO games (name, image_url, link_url, sort_order, min_deposit_usd, prediction_text, prediction_link) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$name, $imageUrl, $linkUrl, $maxSort + 1, $minDepositUsd, $predictionText, $predictionLink]);
            $message = 'Jeu ajoute';
        }
        $messageType = 'success';
    }
}

// Edit mode
$editGame = null;
if ($action === 'edit' && isset($_GET['id'])) {
    $stmt = $db->prepare("SELECT * FROM games WHERE id = ?");
    $stmt->execute([intval($_GET['id'])]);
    $editGame = $stmt->fetch();
}

// Fetch all games
$games = $db->query("SELECT * FROM games ORDER BY sort_order ASC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Jeux - DVYS Admin</title>
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
        .game-form { background: white; padding: 24px; border-radius: var(--radius-lg); margin-bottom: 24px; box-shadow: 0 1px 4px rgba(0,0,0,0.04); }
        .game-form h3 { font-size: 18px; font-weight: 700; margin-bottom: 20px; }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 12px; }
        .form-row.full { grid-template-columns: 1fr; }
        .form-group label { display: block; font-size: 13px; font-weight: 600; margin-bottom: 4px; color: var(--text-secondary); }
        .form-group input, .form-group textarea { width: 100%; padding: 10px 12px; border: 1px solid var(--border); border-radius: var(--radius-md); font-size: 14px; font-family: inherit; }
        .form-group input:focus, .form-group textarea:focus { outline: none; border-color: var(--primary); }
        .form-actions { display: flex; gap: 8px; margin-top: 16px; }
        .img-preview { width: 100px; height: 100px; border-radius: 12px; object-fit: cover; border: 2px solid var(--border); margin-top: 8px; display: none; }
        .game-grid-admin { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 16px; margin-top: 16px; }
        .game-card-admin { background: white; border-radius: 14px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.06); position: relative; }
        .game-card-admin img { width: 100%; height: 140px; object-fit: cover; display: block; }
        .game-card-admin .card-body { padding: 10px; }
        .game-card-admin .card-name { font-size: 13px; font-weight: 600; margin-bottom: 4px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .game-card-admin .card-deposit { font-size: 11px; color: var(--warning); font-weight: 600; margin-bottom: 6px; }
        .game-card-admin .card-prediction { font-size: 11px; color: var(--text-tertiary); margin-bottom: 8px; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
        .game-card-admin .card-actions { display: flex; gap: 4px; flex-wrap: wrap; justify-content: center; }
        .game-card-admin.inactive { opacity: 0.5; }
        .game-card-admin.inactive img { filter: grayscale(1); }
        .action-btn { padding: 6px 12px; border-radius: 8px; font-size: 12px; font-weight: 600; text-decoration: none; border: none; cursor: pointer; transition: all 0.2s; }
        .action-btn.edit { background: #E8F4FD; color: #007AFF; }
        .action-btn.delete { background: #FFEDED; color: #FF3B30; }
        .action-btn.toggle { background: #F2F2F7; color: #8E8E93; }
        .action-btn.move { background: #FFF3E0; color: #FF9500; }
        .msg { padding: 12px 16px; border-radius: 12px; margin-bottom: 20px; font-size: 14px; font-weight: 500; }
        .msg.success { background: #E8F8EE; color: #34C759; }
        .msg.error { background: #FFEDED; color: #FF3B30; }
        .empty-state { text-align: center; padding: 48px 24px; color: var(--text-tertiary); }
        .empty-state svg { width: 48px; height: 48px; margin-bottom: 12px; opacity: 0.4; }
        .hint { font-size: 12px; color: var(--text-tertiary); margin-top: 4px; }
        .section-divider { border: none; border-top: 1px dashed var(--border); margin: 20px 0; }
        .section-label { font-size: 14px; font-weight: 700; color: var(--text-primary); margin-bottom: 12px; display: flex; align-items: center; gap: 6px; }
        .section-label svg { width: 16px; height: 16px; color: var(--primary); }
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
        <a href="/admin/games.php" style="color:var(--primary);background:var(--primary-light);">Jeux</a>
        <a href="/admin/settings.php">Parametres</a>
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
                <a href="/admin/games.php" class="admin-nav-link active" style="color:var(--primary);">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="6" width="20" height="12" rx="2"/><path d="M6 12h4M8 10v4M15 11h.01M18 13h.01"/></svg>
                    Jeux
                </a>
                <a href="/admin/settings.php" class="admin-nav-link">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
                    Parametres
                </a>
                <div style="border-top:1px solid var(--border);margin:12px 0;"></div>
                <a href="/dashboard.php" class="admin-nav-link">Retour au site</a>
            </nav>
        </aside>
        <main class="admin-content">
            <div class="admin-header">
                <h1>Jeux Populaires</h1>
                <p>Gere les jeux : images, previsions et conditions d'acces par depot minimum</p>
            </div>

            <?php if ($message): ?>
            <div class="msg <?= $messageType ?>"><?= e($message) ?></div>
            <?php endif; ?>

            <!-- Formulaire -->
            <div class="game-form">
                <h3><?= $editGame ? 'Modifier le jeu #' . $editGame['id'] : 'Ajouter un jeu' ?></h3>
                <form method="POST" id="gameForm">
                    <?php if ($editGame): ?>
                    <input type="hidden" name="edit_id" value="<?= $editGame['id'] ?>">
                    <?php endif; ?>

                    <div class="form-row full">
                        <div class="form-group">
                            <label>URL de l'image *</label>
                            <input type="url" name="image_url" id="imageUrl" placeholder="https://example.com/image.png" value="<?= e($editGame['image_url'] ?? '') ?>" required>
                            <div class="hint">Colle le lien direct de l'image du jeu (PNG, JPG, WebP)</div>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Nom du jeu</label>
                            <input type="text" name="name" placeholder="Ex: Sweet Bonanza" value="<?= e($editGame['name'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label>Depot minimum requis ($)</label>
                            <input type="number" step="0.01" min="0" name="min_deposit_usd" placeholder="0 = libre acces" value="<?= $editGame['min_deposit_usd'] ?? 0 ?>">
                            <div class="hint">Somme totale de depot cumulee requise pour debloquer ce jeu (en USD). 0 = acces libre.</div>
                        </div>
                    </div>

                    <div class="form-group" style="margin-top: 8px;">
                        <img id="imgPreview" class="img-preview" alt="Apercu">
                    </div>

                    <hr class="section-divider">

                    <div class="section-label">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                        Prediction et acces
                    </div>

                    <div class="form-row full">
                        <div class="form-group">
                            <label>Texte de la prediction</label>
                            <textarea name="prediction_text" rows="4" placeholder="Ecris ici la prediction ou le contenu affiche quand l'utilisateur debloque le jeu..."><?= e($editGame['prediction_text'] ?? '') ?></textarea>
                            <div class="hint">Ce texte sera affiche a l'utilisateur apres deblocage (quand le depot minimum est atteint)</div>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Lien de la prediction</label>
                            <input type="url" name="prediction_link" placeholder="https://1win.ci/..." value="<?= e($editGame['prediction_link'] ?? '') ?>">
                            <div class="hint">Lien d'affiliation vers lequel l'utilisateur sera redirige apres deblocage</div>
                        </div>
                        <div class="form-group">
                            <label>Lien du jeu sur la page d'accueil</label>
                            <input type="url" name="link_url" placeholder="(optionnel)" value="<?= e($editGame['link_url'] ?? '') ?>">
                            <div class="hint">Lien sur la page d'accueil (les images ne sont pas cliquables, ce lien n'est utilise que sur le dashboard)</div>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn-primary"><?= $editGame ? 'Mettre a jour' : 'Ajouter le jeu' ?></button>
                        <?php if ($editGame): ?>
                        <a href="/admin/games.php" class="btn-secondary" style="display:inline-flex;align-items:center;">Annuler</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <!-- Liste des jeux -->
            <div class="admin-table-wrap">
                <div class="admin-table-header">
                    <h2>Jeux configures (<?= count($games) ?>)</h2>
                </div>

                <?php if (empty($games)): ?>
                <div class="empty-state">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="2" y="6" width="20" height="12" rx="2"/><path d="M6 12h4M8 10v4M15 11h.01"/></svg>
                    <p>Aucun jeu configure. Ajoute ton premier jeu ci-dessus.</p>
                </div>
                <?php else: ?>
                <div class="game-grid-admin">
                    <?php foreach ($games as $game): ?>
                    <div class="game-card-admin <?= $game['is_active'] ? '' : 'inactive' ?>">
                        <img src="<?= e($game['image_url']) ?>" alt="<?= e($game['name']) ?>" loading="lazy" onerror="this.src='data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 width=%22200%22 height=%22140%22><rect fill=%22%23E5E5EA%22 width=%22200%22 height=%22140%22/><text x=%22100%22 y=%2278%22 text-anchor=%22middle%22 fill=%22%238E8E93%22 font-size=%2213%22>Image introuvable</text></svg>'">
                        <div class="card-body">
                            <div class="card-name"><?= e($game['name']) ?: 'Sans nom' ?></div>
                            <?php if ($game['min_deposit_usd'] > 0): ?>
                            <div class="card-deposit">$<?= number_format($game['min_deposit_usd'], 2) ?> depot requis</div>
                            <?php else: ?>
                            <div class="card-deposit" style="color: var(--success);">Acces libre</div>
                            <?php endif; ?>
                            <?php if ($game['prediction_text']): ?>
                            <div class="card-prediction"><?= e(mb_substr($game['prediction_text'], 0, 80)) ?>...</div>
                            <?php endif; ?>
                            <div class="card-actions">
                                <a href="?action=move_up&id=<?= $game['id'] ?>" class="action-btn move" title="Monter">&#8593;</a>
                                <a href="?action=move_down&id=<?= $game['id'] ?>" class="action-btn move" title="Descendre">&#8595;</a>
                                <a href="?action=edit&id=<?= $game['id'] ?>" class="action-btn edit">Edit</a>
                                <a href="?action=toggle&id=<?= $game['id'] ?>" class="action-btn toggle" title="Activer/Desactiver"><?= $game['is_active'] ? '&#128308;' : '&#9898;' ?></a>
                                <a href="?action=delete&id=<?= $game['id'] ?>" class="action-btn delete" onclick="return confirm('Supprimer ce jeu ?')">X</a>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
    <script>
        const urlInput = document.getElementById('imageUrl');
        const preview = document.getElementById('imgPreview');
        if (urlInput && preview) {
            function updatePreview() {
                const url = urlInput.value.trim();
                if (url) {
                    preview.src = url;
                    preview.style.display = 'block';
                    preview.onerror = function() { this.style.display = 'none'; };
                } else {
                    preview.style.display = 'none';
                }
            }
            urlInput.addEventListener('input', updatePreview);
            updatePreview();
        }
    </script>
</body>
</html>

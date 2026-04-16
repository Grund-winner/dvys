<?php
/**
 * DVYS AI - Admin Predictions Management
 * Gestion manuelle des pronostics avec date de programmation
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
$message = '';
$messageType = '';

// Handle actions
$action = $_GET['action'] ?? '';

if ($action === 'delete' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $db->prepare("DELETE FROM predictions WHERE id = ?")->execute([$id]);
    $message = 'Pronostic supprime';
    $messageType = 'success';
}

if ($action === 'toggle_result' && isset($_GET['id']) && isset($_GET['result'])) {
    $id = intval($_GET['id']);
    $result = $_GET['result'];
    if (in_array($result, ['won', 'lost', 'pending', 'cancelled'])) {
        $db->prepare("UPDATE predictions SET result = ? WHERE id = ?")->execute([$result, $id]);
        $message = 'Resultat mis a jour';
        $messageType = 'success';
    }
}

// Handle form submission (add/edit prediction)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $editId = intval($_POST['edit_id'] ?? 0);
    $league = trim($_POST['league'] ?? '');
    $home = trim($_POST['home'] ?? '');
    $away = trim($_POST['away'] ?? '');
    $time = trim($_POST['time'] ?? '');
    $prediction = trim($_POST['prediction'] ?? '');
    $odds = floatval($_POST['odds'] ?? 0);
    $isVip = intval($_POST['is_vip'] ?? 0);
    $scheduledDate = trim($_POST['scheduled_date'] ?? date('Y-m-d'));
    $status = trim($_POST['status'] ?? 'active');
    $homeLogoUrl = trim($_POST['home_logo_url'] ?? '');
    $awayLogoUrl = trim($_POST['away_logo_url'] ?? '');
    $report = trim($_POST['report'] ?? '');

    if (empty($league) || empty($home) || empty($away) || empty($prediction)) {
        $message = 'Remplissez tous les champs obligatoires';
        $messageType = 'error';
    } else {
        if ($editId > 0) {
            $stmt = $db->prepare("UPDATE predictions SET match_league = ?, match_home = ?, match_away = ?, match_time = ?, prediction = ?, odds = ?, is_vip = ?, scheduled_date = ?, status = ?, home_logo_url = ?, away_logo_url = ?, report = ? WHERE id = ?");
            $stmt->execute([$league, $home, $away, $time, $prediction, $odds, $isVip, $scheduledDate, $status, $homeLogoUrl, $awayLogoUrl, $report, $editId]);
            $message = 'Pronostic mis a jour';
        } else {
            $stmt = $db->prepare("INSERT INTO predictions (match_league, match_home, match_away, match_time, prediction, odds, is_vip, scheduled_date, status, home_logo_url, away_logo_url, report) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$league, $home, $away, $time, $prediction, $odds, $isVip, $scheduledDate, $status, $homeLogoUrl, $awayLogoUrl, $report]);
            $message = 'Pronostic ajoute';
        }
        $messageType = 'success';
    }
}

// Edit mode
$editPrediction = null;
if ($action === 'edit' && isset($_GET['id'])) {
    $stmt = $db->prepare("SELECT * FROM predictions WHERE id = ?");
    $stmt->execute([intval($_GET['id'])]);
    $editPrediction = $stmt->fetch();
}

// Fetch all predictions
$predictions = $db->query("SELECT * FROM predictions ORDER BY scheduled_date DESC, match_time DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Pronostics - DVYS Admin</title>
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
        .pred-form { background: white; padding: 24px; border-radius: var(--radius-lg); margin-bottom: 24px; box-shadow: 0 1px 4px rgba(0,0,0,0.04); }
        .pred-form h3 { font-size: 18px; font-weight: 700; margin-bottom: 20px; }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 12px; }
        .form-row.full { grid-template-columns: 1fr; }
        .form-group label { display: block; font-size: 13px; font-weight: 600; margin-bottom: 4px; color: var(--text-secondary); }
        .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 10px 12px; border: 1px solid var(--border); border-radius: var(--radius-md); font-size: 14px; font-family: inherit; }
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus { outline: none; border-color: var(--primary); }
        .checkbox-group { display: flex; align-items: center; gap: 8px; margin-top: 8px; }
        .checkbox-group input { width: 18px; height: 18px; }
        .form-actions { display: flex; gap: 8px; margin-top: 16px; }
        .result-badge { display: inline-block; padding: 3px 10px; border-radius: 12px; font-size: 12px; font-weight: 600; }
        .result-won { background: #E8F8EE; color: #34C759; }
        .result-lost { background: #FFEDED; color: #FF3B30; }
        .result-pending { background: #FFF3E0; color: #FF9500; }
        .result-cancelled { background: #F2F2F7; color: #8E8E93; }
        .action-btn { padding: 6px 12px; border-radius: 8px; font-size: 12px; font-weight: 600; text-decoration: none; border: none; cursor: pointer; transition: all 0.2s; }
        .action-btn.edit { background: #E8F4FD; color: #007AFF; }
        .action-btn.delete { background: #FFEDED; color: #FF3B30; }
        .action-btn.won { background: #E8F8EE; color: #34C759; }
        .action-btn.lost { background: #FFEDED; color: #FF3B30; }
        .status-active { color: var(--success); font-weight: 600; }
        .status-draft { color: var(--text-tertiary); }
        .scheduled-highlight { background: #FFF3E0 !important; }
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
        <a href="/admin/predictions.php" style="color:var(--primary);background:var(--primary-light);">Pronostics</a>
        <a href="/admin/games.php">Jeux</a>
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
                <a href="/admin/predictions.php" class="admin-nav-link active" style="color:var(--primary);">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
                    Pronostics
                </a>
                <a href="/admin/games.php" class="admin-nav-link">
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
                <h1>Gestion des Pronostics</h1>
                <p>Ajouter, modifier et programmer les pronostics foot</p>
            </div>

            <?php if ($message): ?>
            <div class="msg <?= $messageType ?>"><?= e($message) ?></div>
            <?php endif; ?>

            <!-- Formulaire -->
            <div class="pred-form">
                <h3><?= $editPrediction ? 'Modifier le pronostic #' . $editPrediction['id'] : 'Nouveau pronostic' ?></h3>
                <form method="POST">
                    <?php if ($editPrediction): ?>
                    <input type="hidden" name="edit_id" value="<?= $editPrediction['id'] ?>">
                    <?php endif; ?>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Ligue *</label>
                            <input type="text" name="league" placeholder="Ex: Ligue 1, Champions League..." value="<?= e($editPrediction['match_league'] ?? '') ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Status</label>
                            <select name="status">
                                <option value="active" <?= ($editPrediction['status'] ?? 'active') === 'active' ? 'selected' : '' ?>>Actif</option>
                                <option value="draft" <?= ($editPrediction['status'] ?? '') === 'draft' ? 'selected' : '' ?>>Brouillon</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Equipe domicile *</label>
                            <input type="text" name="home" placeholder="Ex: PSG" value="<?= e($editPrediction['match_home'] ?? '') ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Equipe exterieur *</label>
                            <input type="text" name="away" placeholder="Ex: OM" value="<?= e($editPrediction['match_away'] ?? '') ?>" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Logo equipe domicile</label>
                            <input type="url" name="home_logo_url" placeholder="https://example.com/logo-psg.png" value="<?= e($editPrediction['home_logo_url'] ?? '') ?>">
                            <div class="hint">Colle le lien de l'image du logo (optionnel)</div>
                        </div>
                        <div class="form-group">
                            <label>Logo equipe exterieur</label>
                            <input type="url" name="away_logo_url" placeholder="https://example.com/logo-om.png" value="<?= e($editPrediction['away_logo_url'] ?? '') ?>">
                            <div class="hint">Colle le lien de l'image du logo (optionnel)</div>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Heure du match</label>
                            <input type="time" name="time" value="<?= e($editPrediction['match_time'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label>Cote</label>
                            <input type="number" step="0.01" min="1" name="odds" placeholder="Ex: 1.85" value="<?= $editPrediction['odds'] ?? '' ?>">
                        </div>
                    </div>

                    <div class="form-row full">
                        <div class="form-group">
                            <label>Prediction *</label>
                            <textarea name="prediction" rows="2" placeholder="Ex: Victoire PSG, Score exact 2-1, Under 2.5 buts..." required><?= e($editPrediction['prediction'] ?? '') ?></textarea>
                        </div>
                    </div>

                    <div class="form-row full">
                        <div class="form-group">
                            <label>Analyse / Report</label>
                            <textarea name="report" rows="4" placeholder="Ex: PSG en grande forme avec 5 victoires consecutives. OM sans victoire a l'exterieur depuis 3 matchs..."><?= e($editPrediction['report'] ?? '') ?></textarea>
                            <div class="hint">Texte d'analyse affiche sur la carte de pronostic (optionnel)</div>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Date de programmation</label>
                            <input type="date" name="scheduled_date" value="<?= e($editPrediction['scheduled_date'] ?? date('Y-m-d')) ?>">
                        </div>
                        <div class="form-group">
                            <label>&nbsp;</label>
                            <div class="checkbox-group">
                                <input type="checkbox" name="is_vip" value="1" <?= ($editPrediction['is_vip'] ?? 0) ? 'checked' : '' ?>>
                                <label style="margin:0;">Pronostic VIP</label>
                            </div>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn-primary"><?= $editPrediction ? 'Mettre a jour' : 'Ajouter le pronostic' ?></button>
                        <?php if ($editPrediction): ?>
                        <a href="/admin/predictions.php" class="btn-secondary" style="display:inline-flex;align-items:center;">Annuler</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <!-- Liste des pronostics -->
            <div class="admin-table-wrap">
                <div class="admin-table-header">
                    <h2>Tous les pronostics (<?= count($predictions) ?>)</h2>
                </div>
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Date prog.</th>
                            <th>Ligue</th>
                            <th>Match</th>
                            <th>Heure</th>
                            <th>Prediction</th>
                            <th>Cote</th>
                            <th>VIP</th>
                            <th>Resultat</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($predictions as $pred):
                            $isToday = $pred['scheduled_date'] === date('Y-m-d');
                            $isFuture = $pred['scheduled_date'] > date('Y-m-d');
                        ?>
                        <tr class="<?= $isToday ? 'scheduled-highlight' : '' ?>">
                            <td style="white-space:nowrap;"><?= $pred['scheduled_date'] ? date('d/m/Y', strtotime($pred['scheduled_date'])) : '-' ?></td>
                            <td><?= e($pred['match_league']) ?></td>
                            <td><strong><?= e($pred['match_home']) ?></strong> vs <strong><?= e($pred['match_away']) ?></strong></td>
                            <td><?= $pred['match_time'] ? date('H:i', strtotime($pred['match_time'])) : '-' ?></td>
                            <td><?= e($pred['prediction']) ?></td>
                            <td><?= $pred['odds'] > 0 ? number_format($pred['odds'], 2) : '-' ?></td>
                            <td><?= $pred['is_vip'] ? '<span class="vip-badge">VIP</span>' : '-' ?></td>
                            <td>
                                <?php
                                $resultClass = 'result-' . ($pred['result'] ?: 'pending');
                                $resultLabel = ['won' => 'Gagne', 'lost' => 'Perdu', 'pending' => 'En attente', 'cancelled' => 'Annule'];
                                ?>
                                <span class="result-badge <?= $resultClass ?>"><?= $resultLabel[$pred['result']] ?? $pred['result'] ?></span>
                            </td>
                            <td>
                                <?php if ($pred['status'] === 'active'): ?>
                                    <span class="status-active">Actif</span>
                                <?php else: ?>
                                    <span class="status-draft">Brouillon</span>
                                <?php endif; ?>
                            </td>
                            <td style="white-space:nowrap;">
                                <a href="?action=edit&id=<?= $pred['id'] ?>" class="action-btn edit">Edit</a>
                                <a href="?action=toggle_result&id=<?= $pred['id'] ?>&result=won" class="action-btn won" title="Marquer gagne">W</a>
                                <a href="?action=toggle_result&id=<?= $pred['id'] ?>&result=lost" class="action-btn lost" title="Marquer perdu">L</a>
                                <a href="?action=delete&id=<?= $pred['id'] ?>" class="action-btn delete" onclick="return confirm('Supprimer ce pronostic ?')">X</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($predictions)): ?>
                        <tr><td colspan="10" style="text-align:center;color:var(--text-tertiary);padding:24px;">Aucun pronostic</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
</body>
</html>

<?php
/**
 * DVYS AI - Admin Postbacks Log
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

// Statistiques postbacks
$totalPostbacks = $db->query("SELECT COUNT(*) FROM postback_logs")->fetchColumn();
$totalDeposits = $db->query("SELECT COUNT(*) FROM postback_logs WHERE event = 'deposit'")->fetchColumn();
$totalRegistrations = $db->query("SELECT COUNT(*) FROM postback_logs WHERE event IN ('registration', 'signup')")->fetchColumn();
$totalAmount = $db->query("SELECT COALESCE(SUM(amount), 0) FROM postback_logs WHERE event = 'deposit'")->fetchColumn();

// Détails
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 30;
$offset = ($page - 1) * $perPage;

$postbacks = $db->query("SELECT p.*, u.username FROM postback_logs p LEFT JOIN users u ON p.user_id = u.id ORDER BY p.created_at DESC LIMIT $perPage OFFSET $offset")->fetchAll();
$total = $db->query("SELECT COUNT(*) FROM postback_logs")->fetchColumn();
$pages = ceil($total / $perPage);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Postbacks - DVYS Admin</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        .admin-mobile-nav { display: none; background: white; border-bottom: 1px solid var(--border); padding: 12px 16px; position: sticky; top: 0; z-index: 100; }
        .admin-mobile-nav a { display: inline-block; padding: 6px 12px; margin: 0 4px 4px 0; border-radius: var(--radius-full); font-size: 13px; font-weight: 500; color: var(--text-secondary); text-decoration: none; background: var(--bg); }
        .raw-data { font-size: 11px; color: var(--text-tertiary); max-width: 300px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        @media (max-width: 768px) {
            .admin-sidebar { display: none !important; }
            .admin-content { margin-left: 0 !important; }
            .admin-mobile-nav { display: block; }
            .admin-table { min-width: 800px; }
        }
    </style>
</head>
<body class="admin-page">
    <div class="admin-mobile-nav">
        <a href="/admin/">Dashboard</a>
        <a href="/admin/users.php">Utilisateurs</a>
        <a href="/admin/broadcast.php">Broadcast</a>
        <a href="/admin/export.php">Export</a>
        <a href="/admin/postbacks.php" style="color:var(--primary);background:var(--primary-light);">Postbacks</a>
        <a href="/admin/predictions.php">Pronostics</a>
        <a href="/admin/games.php">Jeux</a>
        <a href="/admin/settings.php">Parametres</a>
        <a href="/dashboard.php">← Retour</a>
    </div>
    <div class="admin-layout">
        <aside class="admin-sidebar">
            <div class="admin-logo"><a href="/admin/"><span class="logo-icon" style="width:28px;height:28px;font-size:12px;">✦</span> DVYS Admin</a></div>
            <nav class="admin-nav">
                <a href="/admin/" class="admin-nav-link">Dashboard</a>
                <a href="/admin/users.php" class="admin-nav-link">👥 Utilisateurs</a>
                <a href="/admin/broadcast.php" class="admin-nav-link">📢 Broadcast</a>
                <a href="/admin/export.php" class="admin-nav-link">📥 Export CSV</a>
                <a href="/admin/postbacks.php" class="admin-nav-link active">Postbacks</a>
                <a href="/admin/predictions.php" class="admin-nav-link">Pronostics</a>
                <a href="/admin/games.php" class="admin-nav-link">Jeux</a>
                <a href="/admin/settings.php" class="admin-nav-link">Parametres</a>
                <div style="border-top:1px solid var(--border);margin:12px 0;"></div>
                <a href="/dashboard.php" class="admin-nav-link">← Retour au site</a>
            </nav>
        </aside>
        <main class="admin-content">
            <div class="admin-header">
                <h1>Postbacks</h1>
                <p>Historique des notifications reçues de 1Win</p>
            </div>

            <div class="admin-stats" style="grid-template-columns: repeat(4, 1fr);">
                <div class="admin-stat-card">
                    <div class="stat-value"><?= number_format($totalPostbacks) ?></div>
                    <div class="stat-label">Total postbacks</div>
                </div>
                <div class="admin-stat-card">
                    <div class="stat-value" style="color:var(--primary);"><?= number_format($totalRegistrations) ?></div>
                    <div class="stat-label">Inscriptions</div>
                </div>
                <div class="admin-stat-card">
                    <div class="stat-value" style="color:var(--success);"><?= number_format($totalDeposits) ?></div>
                    <div class="stat-label">Dépôts</div>
                </div>
                <div class="admin-stat-card">
                    <div class="stat-value" style="color:var(--warning);">$<?= number_format($totalAmount, 2) ?></div>
                    <div class="stat-label">Montant total</div>
                </div>
            </div>

            <!-- Postback URL Info -->
            <div class="section-card" style="margin-bottom: 24px;">
                <h3 style="font-size: 16px; font-weight: 700; margin-bottom: 8px;">URL Postback pour 1Win Partners</h3>
                <div style="background: var(--bg); padding: 12px; border-radius: var(--radius-md); font-family: monospace; font-size: 13px; word-break: break-all;">
                    <?= BASE_URL ?>/postback.php?event={event}&sub1={sub1}&amount={amount}
                </div>
                <p style="font-size: 12px; color: var(--text-secondary); margin-top: 8px;">
                    sub1 = ID utilisateur DVYS AI (ou code parrainage) • event = registration | deposit • amount = montant du dépôt
                </p>
            </div>

            <!-- Postback Logs -->
            <div class="admin-table-wrap">
                <div class="admin-table-header">
                    <h2>Logs (<?= number_format($total) ?>)</h2>
                </div>
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Utilisateur</th>
                            <th>Événement</th>
                            <th>Montant</th>
                            <th>sub1</th>
                            <th>IP</th>
                            <th>Raw Data</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($postbacks as $pb): ?>
                        <tr>
                            <td><?= date('d/m H:i:s', strtotime($pb['created_at'])) ?></td>
                            <td><?= e($pb['username'] ?? 'ID: ' . $pb['user_id']) ?></td>
                            <td>
                                <?php if ($pb['event'] === 'deposit'): ?>
                                    <span style="color:var(--success);font-weight:600;">💰 Dépôt</span>
                                <?php elseif (in_array($pb['event'], ['registration', 'signup'])): ?>
                                    <span style="color:var(--primary);font-weight:600;">📝 Inscription</span>
                                <?php else: ?>
                                    <span style="color:var(--text-secondary);"><?= e($pb['event']) ?></span>
                                <?php endif; ?>
                            </td>
                            <td><?= $pb['amount'] > 0 ? '$' . number_format($pb['amount'], 2) : '-' ?></td>
                            <td><?= e($pb['sub1']) ?></td>
                            <td><?= e($pb['ip_address']) ?></td>
                            <td class="raw-data" title="<?= e($pb['raw_data']) ?>"><?= e(mb_substr($pb['raw_data'], 0, 80)) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($pages > 1): ?>
            <div style="display:flex;gap:8px;margin-top:16px;">
                <?php for ($p = 1; $p <= min($pages, 20); $p++): ?>
                <a href="/admin/postbacks.php?page=<?= $p ?>" class="btn-secondary" style="<?= $p === $page ? 'background:var(--primary);color:white;' : '' ?>">
                    <?= $p ?>
                </a>
                <?php endfor; ?>
            </div>
            <?php endif; ?>
        </main>
    </div>
</body>
</html>

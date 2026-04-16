<?php
/**
 * DVYS AI - Admin Users
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

$page = max(1, intval($_GET['page'] ?? 1));
$search = trim($_GET['search'] ?? '');
$usersData = getAllUsers($page);
$db = Database::getInstance();

if ($search) {
    $stmt = $db->prepare("SELECT * FROM users WHERE username LIKE ? OR email LIKE ? ORDER BY created_at DESC LIMIT 50");
    $like = "%$search%";
    $stmt->execute([$like, $like]);
    $usersData = ['users' => $stmt->fetchAll(), 'total' => count($stmt->fetchAll()), 'pages' => 1, 'current_page' => 1];
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Utilisateurs - DVYS Admin</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        .admin-mobile-nav { display: none; background: white; border-bottom: 1px solid var(--border); padding: 12px 16px; position: sticky; top: 0; z-index: 100; }
        .admin-mobile-nav a { display: inline-block; padding: 6px 12px; margin: 0 4px 4px 0; border-radius: var(--radius-full); font-size: 13px; font-weight: 500; color: var(--text-secondary); text-decoration: none; background: var(--bg); }
        @media (max-width: 768px) {
            .admin-sidebar { display: none !important; }
            .admin-content { margin-left: 0 !important; }
            .admin-mobile-nav { display: block; }
            .admin-table { min-width: 700px; }
        }
    </style>
</head>
<body class="admin-page">
    <div class="admin-mobile-nav">
        <a href="/admin/">Dashboard</a>
        <a href="/admin/users.php" style="color:var(--primary);background:var(--primary-light);">Utilisateurs</a>
        <a href="/admin/broadcast.php">Broadcast</a>
        <a href="/admin/export.php">Export</a>
        <a href="/admin/postbacks.php">Postbacks</a>
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
                <a href="/admin/users.php" class="admin-nav-link active">👥 Utilisateurs</a>
                <a href="/admin/broadcast.php" class="admin-nav-link">📢 Broadcast</a>
                <a href="/admin/export.php" class="admin-nav-link">📥 Export CSV</a>
                <a href="/admin/postbacks.php" class="admin-nav-link">Postbacks</a>
                <a href="/admin/predictions.php" class="admin-nav-link">Pronostics</a>
                <a href="/admin/games.php" class="admin-nav-link">Jeux</a>
                <a href="/admin/settings.php" class="admin-nav-link">Parametres</a>
                <div style="border-top:1px solid var(--border);margin:12px 0;"></div>
                <a href="/dashboard.php" class="admin-nav-link">← Retour au site</a>
            </nav>
        </aside>
        <main class="admin-content">
            <div class="admin-header">
                <h1>Utilisateurs (<?= number_format($usersData['total']) ?>)</h1>
                <p>Gestion des comptes utilisateurs</p>
            </div>

            <!-- Search -->
            <form method="GET" style="margin-bottom: 20px;">
                <div style="display:flex;gap:8px;">
                    <input type="text" name="search" class="form-input" placeholder="Rechercher par nom ou email..." value="<?= e($search) ?>" style="max-width:400px;">
                    <button type="submit" class="btn-primary">Rechercher</button>
                    <?php if ($search): ?>
                    <a href="/admin/users.php" class="btn-secondary" style="display:inline-flex;align-items:center;">Effacer</a>
                    <?php endif; ?>
                </div>
            </form>

            <!-- Users Table -->
            <div class="admin-table-wrap">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Utilisateur</th>
                            <th>Email</th>
                            <th>Pays</th>
                            <th>Vérifié</th>
                            <th>Dépôt</th>
                            <th>Filleuls</th>
                            <th>VIP</th>
                            <th>Inscription</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($usersData['users'] as $u): ?>
                        <tr>
                            <td><?= $u['id'] ?></td>
                            <td><strong><?= e($u['username']) ?></strong></td>
                            <td><?= e($u['email']) ?></td>
                            <td><?= e($u['country'] ?: '-') ?></td>
                            <td><?= $u['is_1win_verified'] ? '✅' : '❌' ?></td>
                            <td><?= $u['has_deposited'] ? '$' . number_format($u['deposit_amount'], 2) : '-' ?></td>
                            <td><?= $u['referral_count'] ?? 0 ?></td>
                            <td>
                                <?php if ($u['vip_expires_at'] && $u['vip_expires_at'] > date('Y-m-d H:i:s')): ?>
                                    <?php if ($u['vip_expires_at'] > '2099-01-01'): ?>
                                        <span class="vip-badge">Illimité</span>
                                    <?php else: ?>
                                        <span style="color:var(--success);font-size:12px;font-weight:600;">✅ <?= date('d/m/Y', strtotime($u['vip_expires_at'])) ?></span>
                                    <?php endif; ?>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                            <td><?= date('d/m/Y', strtotime($u['created_at'])) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($usersData['pages'] > 1): ?>
            <div style="display:flex;gap:8px;margin-top:16px;">
                <?php for ($p = 1; $p <= $usersData['pages']; $p++): ?>
                <a href="/admin/users.php?page=<?= $p ?>" class="btn-secondary" style="<?= $p === $page ? 'background:var(--primary);color:white;' : '' ?>">
                    <?= $p ?>
                </a>
                <?php endfor; ?>
            </div>
            <?php endif; ?>
        </main>
    </div>
</body>
</html>

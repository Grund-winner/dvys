<?php
/**
 * DVYS AI - Admin Dashboard
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/i18n.php';

$auth = new Auth();

// Vérifier admin
if (!$auth->isLoggedIn() || !$auth->isAdmin()) {
    header('Location: /login.php');
    exit;
}

$lang = getCurrentLang();
$stats = getGlobalStats();
$db = Database::getInstance();
?>
<!DOCTYPE html>
<html lang="<?= e($lang) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Admin - DVYS AI</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        .admin-page { background: var(--bg); min-height: 100vh; }
        .admin-mobile-nav {
            display: none;
            background: white;
            border-bottom: 1px solid var(--border);
            padding: 12px 16px;
            position: sticky;
            top: 0;
            z-index: 100;
        }
        .admin-mobile-nav a {
            display: inline-block;
            padding: 6px 12px;
            margin: 0 4px 4px 0;
            border-radius: var(--radius-full);
            font-size: 13px;
            font-weight: 500;
            color: var(--text-secondary);
            text-decoration: none;
            background: var(--bg);
        }
        .admin-mobile-nav a.active { color: var(--primary); background: var(--primary-light); }
        @media (max-width: 768px) {
            .admin-sidebar { display: none !important; }
            .admin-content { margin-left: 0 !important; }
            .admin-mobile-nav { display: block; }
            .admin-stats { grid-template-columns: repeat(2, 1fr) !important; }
        }
    </style>
</head>
<body class="admin-page">
    <!-- Mobile Nav -->
    <div class="admin-mobile-nav">
        <a href="/admin/" class="active">Dashboard</a>
        <a href="/admin/users.php">Utilisateurs</a>
        <a href="/admin/broadcast.php">Broadcast</a>
        <a href="/admin/export.php">Export</a>
        <a href="/admin/postbacks.php">Postbacks</a>
        <a href="/admin/predictions.php">Pronostics</a>
        <a href="/admin/games.php">Jeux</a>
        <a href="/admin/settings.php">Parametres</a>
        <a href="/dashboard.php">← Retour</a>
    </div>

    <div class="admin-layout">
        <!-- Sidebar -->
        <aside class="admin-sidebar">
            <div class="admin-logo">
                <a href="/admin/">
                    <span class="logo-icon" style="width:28px;height:28px;font-size:12px;">✦</span>
                    DVYS Admin
                </a>
            </div>
            <nav class="admin-nav">
                <a href="/admin/" class="admin-nav-link active">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                    Dashboard
                </a>
                <a href="/admin/users.php" class="admin-nav-link">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                    Utilisateurs
                </a>
                <a href="/admin/broadcast.php" class="admin-nav-link">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 2L11 13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
                    Broadcast
                </a>
                <a href="/admin/export.php" class="admin-nav-link">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                    Export CSV
                </a>
                <a href="/admin/postbacks.php" class="admin-nav-link">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
                    Postbacks
                </a>
                <a href="/admin/predictions.php" class="admin-nav-link">
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
                <div style="border-top: 1px solid var(--border); margin: 12px 0;"></div>
                <a href="/dashboard.php" class="admin-nav-link">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/></svg>
                    Retour au site
                </a>
            </nav>
        </aside>

        <!-- Content -->
        <main class="admin-content">
            <div class="admin-header">
                <h1>Dashboard</h1>
                <p>Vue d'ensemble de la plateforme DVYS AI</p>
            </div>

            <!-- Stats -->
            <div class="admin-stats">
                <div class="admin-stat-card">
                    <div class="stat-icon" style="background: var(--primary-light); color: var(--primary);">👥</div>
                    <div class="stat-value"><?= number_format($stats['total_users']) ?></div>
                    <div class="stat-label"><?= e(t('total_users', $lang)) ?></div>
                </div>
                <div class="admin-stat-card">
                    <div class="stat-icon" style="background: var(--success-light); color: var(--success);">✅</div>
                    <div class="stat-value"><?= number_format($stats['verified_users']) ?></div>
                    <div class="stat-label"><?= e(t('verified_users', $lang)) ?></div>
                </div>
                <div class="admin-stat-card">
                    <div class="stat-icon" style="background: var(--warning-light); color: var(--warning);">💰</div>
                    <div class="stat-value">$<?= number_format($stats['total_deposits'], 2) ?></div>
                    <div class="stat-label"><?= e(t('total_deposits', $lang)) ?></div>
                </div>
                <div class="admin-stat-card">
                    <div class="stat-icon" style="background: #F3E8FF; color: #8B5CF6;">📊</div>
                    <div class="stat-value"><?= number_format($stats['active_today']) ?></div>
                    <div class="stat-label"><?= e(t('active_today', $lang)) ?></div>
                </div>
            </div>

            <!-- Graphique inscriptions -->
            <div class="admin-chart-card">
                <h3>Inscriptions (30 derniers jours)</h3>
                <div class="chart-container">
                    <div class="bar-chart">
                        <?php foreach ($stats['daily_signups'] as $day): ?>
                        <div class="bar-item">
                            <div class="bar" style="height: <?= max(4, ($day['count'] / max(1, max(array_column($stats['daily_signups'], 'count')))) * 100) ?>%"></div>
                            <span class="bar-label"><?= date('d/m', strtotime($day['date'])) ?></span>
                        </div>
                        <?php endforeach; ?>
                        <?php if (empty($stats['daily_signups'])): ?>
                        <div style="text-align: center; color: var(--text-tertiary); width: 100%; display: flex; align-items: center; justify-content: center;">Aucune donnée</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Dépôts par jour -->
            <div class="admin-chart-card">
                <h3>Dépôts (30 derniers jours)</h3>
                <div class="chart-container">
                    <div class="bar-chart">
                        <?php foreach ($stats['daily_deposits'] as $day): ?>
                        <div class="bar-item">
                            <div class="bar" style="height: <?= max(4, ($day['amount'] / max(1, max(array_column($stats['daily_deposits'], 'amount')))) * 100) ?>%; background: linear-gradient(180deg, var(--success), #30D158);"></div>
                            <span class="bar-label"><?= date('d/m', strtotime($day['date'])) ?></span>
                        </div>
                        <?php endforeach; ?>
                        <?php if (empty($stats['daily_deposits'])): ?>
                        <div style="text-align: center; color: var(--text-tertiary); width: 100%; display: flex; align-items: center; justify-content: center;">Aucune donnée</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Par pays -->
            <?php if (!empty($stats['by_country'])): ?>
            <div class="admin-table-wrap">
                <div class="admin-table-header">
                    <h2>Inscrits par pays</h2>
                </div>
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Pays</th>
                            <th>Utilisateurs</th>
                            <th>%</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($stats['by_country'] as $row): ?>
                        <tr>
                            <td><strong><?= e($row['country']) ?></strong></td>
                            <td><?= number_format($row['count']) ?></td>
                            <td><?= round($row['count'] / $stats['total_users'] * 100, 1) ?>%</td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>

            <!-- Postbacks récents -->
            <div class="admin-table-wrap">
                <div class="admin-table-header">
                    <h2>Postbacks récents</h2>
                    <a href="/admin/postbacks.php" class="section-link">Voir tout →</a>
                </div>
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Utilisateur</th>
                            <th>Événement</th>
                            <th>Montant</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (array_slice($stats['recent_postbacks'], 0, 10) as $pb): ?>
                        <tr>
                            <td><?= date('d/m H:i', strtotime($pb['created_at'])) ?></td>
                            <td><?= e($pb['username'] ?? 'N/A (' . e($pb['sub1']) . ')') ?></td>
                            <td>
                                <?php if ($pb['event'] === 'deposit'): ?>
                                    <span style="color: var(--success); font-weight: 600;">💰 Dépôt</span>
                                <?php elseif ($pb['event'] === 'registration'): ?>
                                    <span style="color: var(--primary); font-weight: 600;">📝 Inscription</span>
                                <?php else: ?>
                                    <?= e($pb['event']) ?>
                                <?php endif; ?>
                            </td>
                            <td><?= $pb['amount'] > 0 ? '$' . number_format($pb['amount'], 2) : '-' ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($stats['recent_postbacks'])): ?>
                        <tr><td colspan="4" style="text-align: center; color: var(--text-tertiary); padding: 24px;">Aucun postback reçu</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
</body>
</html>

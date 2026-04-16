<?php
/**
 * DVYS AI - Admin Export CSV
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

// Traitement export
if (isset($_GET['export'])) {
    $type = $_GET['export'];
    
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="dvys_' . $type . '_' . date('Y-m-d') . '.csv"');
    
    $output = fopen('php://output', 'w');
    // BOM for Excel UTF-8
    fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));
    
    if ($type === 'users') {
        fputcsv($output, ['ID', 'Username', 'Email', 'Pays', 'Inscrit le', 'Vérifié 1Win', 'A déposé', 'Montant dépôt', 'Filleuls', 'Code parrainage', 'VIP expire le', 'Dernière activité']);
        
        $users = $db->query("SELECT u.*, (SELECT COUNT(*) FROM referrals WHERE referrer_id = u.id) as referral_count FROM users u ORDER BY u.created_at DESC")->fetchAll();
        foreach ($users as $u) {
            fputcsv($output, [
                $u['id'], $u['username'], $u['email'], $u['country'],
                $u['created_at'], $u['is_1win_verified'], $u['has_deposited'],
                $u['deposit_amount'], $u['referral_count'], $u['referral_code'],
                $u['vip_expires_at'], $u['last_active']
            ]);
        }
    } elseif ($type === 'referrals') {
        fputcsv($output, ['ID', 'Parrain', 'Filleul', 'Statut', 'Montant dépôt', 'Date confirmation', 'Date création']);
        
        $refs = $db->query("SELECT r.*, p.username as parent_name, c.username as child_name FROM referrals r LEFT JOIN users p ON r.referrer_id = p.id LEFT JOIN users c ON r.referred_id = c.id ORDER BY r.created_at DESC")->fetchAll();
        foreach ($refs as $r) {
            fputcsv($output, [
                $r['id'], $r['parent_name'], $r['child_name'],
                $r['status'], $r['deposit_amount'], $r['deposit_confirmed_at'], $r['created_at']
            ]);
        }
    } elseif ($type === 'postbacks') {
        fputcsv($output, ['ID', 'Utilisateur ID', 'Événement', 'Montant', 'sub1', 'sub2', 'IP', 'Date']);
        
        $pbs = $db->query("SELECT * FROM postback_logs ORDER BY created_at DESC")->fetchAll();
        foreach ($pbs as $p) {
            fputcsv($output, [
                $p['id'], $p['user_id'], $p['event'], $p['amount'],
                $p['sub1'], $p['sub2'], $p['ip_address'], $p['created_at']
            ]);
        }
    }
    
    fclose($output);
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Export - DVYS Admin</title>
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
    </style>
</head>
<body class="admin-page">
    <div class="admin-mobile-nav">
        <a href="/admin/">Dashboard</a>
        <a href="/admin/users.php">Utilisateurs</a>
        <a href="/admin/broadcast.php">Broadcast</a>
        <a href="/admin/export.php" style="color:var(--primary);background:var(--primary-light);">Export</a>
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
                <a href="/admin/users.php" class="admin-nav-link">👥 Utilisateurs</a>
                <a href="/admin/broadcast.php" class="admin-nav-link">📢 Broadcast</a>
                <a href="/admin/export.php" class="admin-nav-link active">📥 Export CSV</a>
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
                <h1>Export CSV</h1>
                <p>Télécharger les données en format CSV</p>
            </div>

            <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:16px;max-width:700px;">
                <a href="/admin/export.php?export=users" class="admin-stat-card" style="text-decoration:none;transition:all var(--transition);">
                    <div class="stat-icon" style="background:var(--primary-light);color:var(--primary);">👥</div>
                    <div class="stat-label" style="margin-bottom:8px;">Utilisateurs</div>
                    <div style="color:var(--primary);font-size:14px;font-weight:600;">Télécharger CSV →</div>
                </a>
                <a href="/admin/export.php?export=referrals" class="admin-stat-card" style="text-decoration:none;transition:all var(--transition);">
                    <div class="stat-icon" style="background:var(--success-light);color:var(--success);">🤝</div>
                    <div class="stat-label" style="margin-bottom:8px;">Parrainages</div>
                    <div style="color:var(--success);font-size:14px;font-weight:600;">Télécharger CSV →</div>
                </a>
                <a href="/admin/export.php?export=postbacks" class="admin-stat-card" style="text-decoration:none;transition:all var(--transition);">
                    <div class="stat-icon" style="background:var(--warning-light);color:var(--warning);">📡</div>
                    <div class="stat-label" style="margin-bottom:8px;">Postbacks</div>
                    <div style="color:var(--warning);font-size:14px;font-weight:600;">Télécharger CSV →</div>
                </a>
            </div>
        </main>
    </div>
</body>
</html>

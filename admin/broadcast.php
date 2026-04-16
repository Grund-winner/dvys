<?php
/**
 * DVYS AI - Admin Broadcast
 * Envoyer des messages auto-traduits aux utilisateurs avec cible et image
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

$sent = false;
$edited = false;
$deleted = false;
$error = '';

// Handle edit form pre-fill
$editBroadcast = null;
$editId = isset($_GET['edit']) ? (int) $_GET['edit'] : 0;
if ($editId > 0) {
    $stmt = $db->prepare("SELECT * FROM broadcasts WHERE id = ?");
    $stmt->execute([$editId]);
    $editBroadcast = $stmt->fetch();
    if (!$editBroadcast) {
        $error = 'Broadcast introuvable';
    }
}

// Handle delete
if (isset($_GET['delete']) && isset($_GET['confirm']) && $_GET['confirm'] === '1') {
    $deleteId = (int) $_GET['delete'];
    $stmt = $db->prepare("DELETE FROM broadcasts WHERE id = ?");
    $stmt->execute([$deleteId]);
    $stmt = $db->prepare("DELETE FROM user_notifications WHERE broadcast_id = ?");
    $stmt->execute([$deleteId]);
    $deleted = true;
}

// Handle POST: create or update broadcast
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['form_action'] ?? 'create';
    $title = trim($_POST['title'] ?? '');
    $messageFr = trim($_POST['message_fr'] ?? '');
    $targetType = $_POST['target_type'] ?? 'all';
    $imageUrl = trim($_POST['image_url'] ?? '');

    // Validate target_type
    if (!in_array($targetType, ['all', 'visitors', 'no_deposit'])) {
        $targetType = 'all';
    }

    if (empty($messageFr)) {
        $error = 'Le message en francais est obligatoire';
    } else {
        // Messages par defaut (francais)
        $messages = ['fr' => $messageFr];

        // Traduction basique pour les autres langues
        $translations = [
            'en' => $messageFr,
            'es' => $messageFr,
            'pt' => $messageFr,
            'ru' => $messageFr,
            'ar' => $messageFr,
            'tr' => $messageFr,
            'hi' => $messageFr,
            'uz' => $messageFr,
            'az' => $messageFr,
        ];

        // Tenter de traduire avec l'IA si disponible
        if (!empty(OPENAI_API_KEY)) {
            foreach (SUPPORTED_LANGUAGES as $targetLang) {
                if ($targetLang === 'fr') continue;

                $langNames = ['en' => 'English', 'es' => 'Spanish', 'pt' => 'Portuguese', 'ru' => 'Russian', 'ar' => 'Arabic', 'tr' => 'Turkish', 'hi' => 'Hindi', 'uz' => 'Uzbek', 'az' => 'Azerbaijani'];

                $translatePrompt = [
                    ['role' => 'system', 'content' => "You are a translator. Translate the following text to {$langNames[$targetLang]}. Return ONLY the translation, nothing else."],
                    ['role' => 'user', 'content' => $messageFr]
                ];

                $translated = callOpenAI($translatePrompt, 0.3);
                if ($translated) {
                    $translations[$targetLang] = trim($translated);
                }
            }
        }

        $messagesJson = json_encode($translations, JSON_UNESCAPED_UNICODE);

        if ($action === 'edit' && !empty($_POST['broadcast_id'])) {
            // Update existing broadcast
            $broadcastId = (int) $_POST['broadcast_id'];
            $stmt = $db->prepare("UPDATE broadcasts SET title = ?, messages = ?, target_type = ?, image_url = ? WHERE id = ?");
            $stmt->execute([$title, $messagesJson, $targetType, $imageUrl, $broadcastId]);
            $edited = true;
        } else {
            // Create new broadcast
            $stmt = $db->prepare("INSERT INTO broadcasts (admin_id, title, messages, target_type, image_url) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$_SESSION['user_id'], $title, $messagesJson, $targetType, $imageUrl]);
            $broadcastId = (int) $db->lastInsertId();

            // Insert notification rows for targeted users
            if ($targetType === 'all') {
                $users = $db->query("SELECT id FROM users")->fetchAll();
                $insertStmt = $db->prepare("INSERT INTO user_notifications (user_id, broadcast_id) VALUES (?, ?) ON CONFLICT DO NOTHING");
                foreach ($users as $u) {
                    $insertStmt->execute([$u['id'], $broadcastId]);
                }
            } elseif ($targetType === 'no_deposit') {
                $users = $db->query("SELECT id FROM users WHERE has_deposited = 0")->fetchAll();
                $insertStmt = $db->prepare("INSERT INTO user_notifications (user_id, broadcast_id) VALUES (?, ?) ON CONFLICT DO NOTHING");
                foreach ($users as $u) {
                    $insertStmt->execute([$u['id'], $broadcastId]);
                }
            }
            // 'visitors' target: stored but only shown to non-logged-in users via JS/API

            $sent = true;
        }
    }
}

// Target type labels
$targetLabels = [
    'all' => 'Tous les utilisateurs',
    'visitors' => 'Visiteurs du site',
    'no_deposit' => 'Inscrits sans depot',
];

// Historique des broadcasts
$broadcasts = $db->query("SELECT * FROM broadcasts ORDER BY sent_at DESC LIMIT 50")->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Broadcast - DVYS Admin</title>
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
        .broadcast-form { background: white; padding: 24px; border-radius: var(--radius-lg); margin-bottom: 24px; box-shadow: 0 1px 4px rgba(0,0,0,0.04); }
        .broadcast-form h3 { font-size: 18px; font-weight: 700; margin-bottom: 16px; }
        .broadcast-form textarea { width: 100%; padding: 12px 14px; border: 1.5px solid var(--border); border-radius: var(--radius-md); font-size: 14px; font-family: inherit; min-height: 100px; resize: vertical; }
        .broadcast-form textarea:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 3px var(--primary-light); }
        .target-selector { display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 16px; }
        .target-option { display: flex; align-items: center; gap: 8px; padding: 10px 16px; border: 1.5px solid var(--border); border-radius: var(--radius-md); cursor: pointer; font-size: 14px; font-weight: 500; transition: all 0.2s; }
        .target-option:has(input:checked) { border-color: var(--primary); background: var(--primary-light); color: var(--primary); }
        .target-option input { accent-color: var(--primary); width: 16px; height: 16px; cursor: pointer; }
        .img-preview { margin-top: 8px; max-width: 200px; border-radius: var(--radius-sm); display: none; }
        .img-preview img { max-width: 100%; border-radius: var(--radius-sm); }
        .broadcast-actions { margin-top: 16px; display: flex; gap: 10px; }
        .btn-primary { display: inline-flex; align-items: center; gap: 6px; padding: 10px 24px; background: var(--primary); color: white; border: none; border-radius: var(--radius-md); font-size: 14px; font-weight: 600; cursor: pointer; transition: all 0.2s; text-decoration: none; }
        .btn-primary:hover { background: var(--primary-dark); }
        .btn-secondary { display: inline-flex; align-items: center; gap: 6px; padding: 10px 24px; background: var(--bg); color: var(--text-secondary); border: 1px solid var(--border); border-radius: var(--radius-md); font-size: 14px; font-weight: 500; cursor: pointer; transition: all 0.2s; text-decoration: none; }
        .btn-secondary:hover { background: var(--border); }
        .btn-danger { display: inline-flex; align-items: center; gap: 4px; padding: 6px 12px; background: var(--danger-light); color: var(--danger); border: none; border-radius: var(--radius-sm); font-size: 12px; font-weight: 600; cursor: pointer; transition: all 0.2s; text-decoration: none; }
        .btn-danger:hover { background: var(--danger); color: white; }
        .btn-edit { display: inline-flex; align-items: center; gap: 4px; padding: 6px 12px; background: var(--primary-light); color: var(--primary); border: none; border-radius: var(--radius-sm); font-size: 12px; font-weight: 600; cursor: pointer; transition: all 0.2s; text-decoration: none; }
        .btn-edit:hover { background: var(--primary); color: white; }
        .thumb-img { width: 40px; height: 40px; border-radius: var(--radius-sm); object-fit: cover; }
        .msg { padding: 12px 16px; border-radius: 12px; margin-bottom: 20px; font-size: 14px; font-weight: 500; }
        .msg.success { background: #E8F8EE; color: #34C759; }
        .msg.error { background: #FFEDED; color: #FF3B30; }
    </style>
</head>
<body class="admin-page">
    <div class="admin-mobile-nav">
        <a href="/admin/">Dashboard</a>
        <a href="/admin/users.php">Utilisateurs</a>
        <a href="/admin/broadcast.php" style="color:var(--primary);background:var(--primary-light);">Broadcast</a>
        <a href="/admin/export.php">Export</a>
        <a href="/admin/postbacks.php">Postbacks</a>
        <a href="/admin/predictions.php">Pronostics</a>
        <a href="/admin/games.php">Jeux</a>
        <a href="/admin/settings.php">Parametres</a>
        <a href="/dashboard.php">&larr; Retour</a>
    </div>
    <div class="admin-layout">
        <aside class="admin-sidebar">
            <div class="admin-logo"><a href="/admin/"><span class="logo-icon" style="width:28px;height:28px;font-size:12px;">&#10022;</span> DVYS Admin</a></div>
            <nav class="admin-nav">
                <a href="/admin/" class="admin-nav-link">Dashboard</a>
                <a href="/admin/users.php" class="admin-nav-link">Utilisateurs</a>
                <a href="/admin/broadcast.php" class="admin-nav-link active">Broadcast</a>
                <a href="/admin/export.php" class="admin-nav-link">Export CSV</a>
                <a href="/admin/postbacks.php" class="admin-nav-link">Postbacks</a>
                <a href="/admin/predictions.php" class="admin-nav-link">Pronostics</a>
                <a href="/admin/games.php" class="admin-nav-link">Jeux</a>
                <a href="/admin/settings.php" class="admin-nav-link">Parametres</a>
                <div style="border-top:1px solid var(--border);margin:12px 0;"></div>
                <a href="/dashboard.php" class="admin-nav-link">&larr; Retour au site</a>
            </nav>
        </aside>
        <main class="admin-content">
            <div class="admin-header">
                <h1>Broadcast</h1>
                <p>Envoyer des messages auto-traduits avec ciblage avance</p>
            </div>

            <?php if ($error): ?>
            <div class="msg error"><?= e($error) ?></div>
            <?php endif; ?>

            <?php if ($sent): ?>
            <div class="msg success">Message envoye avec succes ! Les notifications ont ete creees pour les utilisateurs cibles.</div>
            <?php endif; ?>

            <?php if ($edited): ?>
            <div class="msg success">Broadcast mis a jour avec succes !</div>
            <?php endif; ?>

            <?php if ($deleted): ?>
            <div class="msg success">Broadcast supprime avec succes.</div>
            <?php endif; ?>

            <!-- Formulaire creation / edition -->
            <div class="broadcast-form">
                <h3><?= $editBroadcast ? 'Modifier le broadcast' : 'Nouveau broadcast' ?></h3>
                <form method="POST" id="broadcastForm">
                    <?php if ($editBroadcast): ?>
                    <input type="hidden" name="form_action" value="edit">
                    <input type="hidden" name="broadcast_id" value="<?= e($editBroadcast['id']) ?>">
                    <?php else: ?>
                    <input type="hidden" name="form_action" value="create">
                    <?php endif; ?>

                    <div class="form-group" style="margin-bottom:16px;">
                        <label style="display:block;font-size:13px;font-weight:600;color:var(--text-secondary);margin-bottom:6px;">Titre (optionnel)</label>
                        <input type="text" name="title" class="form-input" placeholder="Ex: Nouvelle mise a jour" value="<?= e($editBroadcast['title'] ?? '') ?>">
                    </div>

                    <!-- Cible -->
                    <div class="form-group" style="margin-bottom:16px;">
                        <label style="display:block;font-size:13px;font-weight:600;color:var(--text-secondary);margin-bottom:8px;">Public cible</label>
                        <div class="target-selector">
                            <label class="target-option">
                                <input type="radio" name="target_type" value="all" <?= ($editBroadcast['target_type'] ?? 'all') === 'all' ? 'checked' : '' ?>>
                                Tous les utilisateurs
                            </label>
                            <label class="target-option">
                                <input type="radio" name="target_type" value="visitors" <?= ($editBroadcast['target_type'] ?? '') === 'visitors' ? 'checked' : '' ?>>
                                Visiteurs du site uniquement
                            </label>
                            <label class="target-option">
                                <input type="radio" name="target_type" value="no_deposit" <?= ($editBroadcast['target_type'] ?? '') === 'no_deposit' ? 'checked' : '' ?>>
                                Inscrits sans depot
                            </label>
                        </div>
                    </div>

                    <!-- Image URL -->
                    <div class="form-group" style="margin-bottom:16px;">
                        <label style="display:block;font-size:13px;font-weight:600;color:var(--text-secondary);margin-bottom:6px;">Image (URL optionnelle)</label>
                        <input type="url" name="image_url" id="imageUrlInput" class="form-input" placeholder="https://example.com/image.jpg" value="<?= e($editBroadcast['image_url'] ?? '') ?>" oninput="updateImagePreview()">
                        <div class="img-preview" id="imgPreview">
                            <img id="imgPreviewImg" src="" alt="Apercu">
                        </div>
                    </div>

                    <!-- Message -->
                    <div class="form-group" style="margin-bottom:16px;">
                        <label style="display:block;font-size:13px;font-weight:600;color:var(--text-secondary);margin-bottom:6px;">Message en francais *</label>
                        <textarea name="message_fr" placeholder="Ecrivez votre message ici... Il sera automatiquement traduit dans les 10 langues."><?= e(json_decode($editBroadcast['messages'] ?? '{}', true)['fr'] ?? '') ?></textarea>
                        <p style="font-size: 12px; color: var(--text-secondary); margin-top: 6px;">
                            Le message sera traduit automatiquement en EN, ES, PT, RU, AR, TR, HI, UZ, AZ via l'IA (si la cle API OpenAI est configuree).
                        </p>
                    </div>

                    <div class="broadcast-actions">
                        <button type="submit" class="btn-primary"><?= $editBroadcast ? 'Mettre a jour' : 'Envoyer' ?></button>
                        <?php if ($editBroadcast): ?>
                        <a href="/admin/broadcast.php" class="btn-secondary">Annuler</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <!-- Historique des broadcasts -->
            <div class="admin-table-wrap" style="margin-top: 24px;">
                <div class="admin-table-header">
                    <h2>Historique des broadcasts (<?= count($broadcasts) ?>)</h2>
                </div>
                <?php if (empty($broadcasts)): ?>
                <div style="padding: 32px; text-align: center; color: var(--text-tertiary);">Aucun broadcast envoye</div>
                <?php else: ?>
                <div style="overflow-x:auto;">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Titre</th>
                            <th>Cible</th>
                            <th>Message (FR)</th>
                            <th>Image</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($broadcasts as $b):
                            $msgs = json_decode($b['messages'], true) ?: [];
                            $targetLabel = $targetLabels[$b['target_type'] ?? 'all'] ?? 'Tous';
                            $hasImage = !empty($b['image_url']);
                        ?>
                        <tr>
                            <td style="white-space:nowrap;"><?= date('d/m/Y H:i', strtotime($b['sent_at'])) ?></td>
                            <td><?= e($b['title']) ?: '<em style="color:var(--text-tertiary)">-</em>' ?></td>
                            <td><span style="font-size:12px;padding:3px 8px;border-radius:var(--radius-full);background:var(--primary-light);color:var(--primary);font-weight:600;"><?= e($targetLabel) ?></span></td>
                            <td style="max-width:280px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="<?= e($msgs['fr'] ?? '') ?>"><?= e($msgs['fr'] ?? '-') ?></td>
                            <td>
                                <?php if ($hasImage): ?>
                                <img src="<?= e($b['image_url']) ?>" alt="" class="thumb-img" onerror="this.outerHTML='Non'">
                                <?php else: ?>
                                <span style="font-size:12px;color:var(--text-tertiary);">Non</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div style="display:flex;gap:6px;align-items:center;">
                                    <a href="/admin/broadcast.php?edit=<?= $b['id'] ?>" class="btn-edit">Modifier</a>
                                    <a href="/admin/broadcast.php?delete=<?= $b['id'] ?>&confirm=1" class="btn-danger" onclick="return confirm('Supprimer ce broadcast ? Cette action est irreversible.')">Supprimer</a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <script>
    // Live image preview for broadcast form
    function updateImagePreview() {
        const input = document.getElementById('imageUrlInput');
        const preview = document.getElementById('imgPreview');
        const img = document.getElementById('imgPreviewImg');
        const url = input.value.trim();
        if (url) {
            img.src = url;
            img.onload = function() { preview.style.display = 'block'; };
            img.onerror = function() { preview.style.display = 'none'; };
        } else {
            preview.style.display = 'none';
        }
    }

    // Initialize preview if editing with existing image
    document.addEventListener('DOMContentLoaded', function() {
        const input = document.getElementById('imageUrlInput');
        if (input && input.value.trim()) {
            updateImagePreview();
        }
    });
    </script>
</body>
</html>

<?php
/**
 * DVYS AI - Game Prediction Page
 * Affiche la prediction d'un jeu avec verification du depot minimum
 */
require_once __DIR__ . '/includes/header.php';

$lang = getCurrentLang();
$userId = $user['id'];
$db = Database::getInstance();
Database::migrate();

$gameId = intval($_GET['id'] ?? 0);

if ($gameId <= 0) {
    redirect('/dashboard.php');
    exit;
}

$stmt = $db->prepare("SELECT * FROM games WHERE id = ? AND is_active = 1");
$stmt->execute([$gameId]);
$game = $stmt->fetch();

if (!$game) {
    redirect('/dashboard.php');
    exit;
}

// Verifier le depot cumule de l'utilisateur (admin a tout debloque)
$userDeposit = $user['deposit_amount'] ?? 0;
$minDeposit = $game['min_deposit_usd'] ?? 0;
$isAdmin = $auth->isAdmin();
$isUnlocked = $isAdmin || ($minDeposit <= 0) || ($userDeposit >= $minDeposit);
$progressPercent = $minDeposit > 0 ? min(100, round(($userDeposit / $minDeposit) * 100)) : 100;
?>

<div class="container">
    <!-- Retour -->
    <a href="/dashboard.php" style="display: inline-flex; align-items: center; gap: 6px; font-size: 14px; color: var(--primary); text-decoration: none; margin-bottom: 16px; font-weight: 600;">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
        Retour
    </a>

    <!-- Image du jeu -->
    <div style="width: 100%; height: 220px; border-radius: 16px; overflow: hidden; margin-bottom: 20px; box-shadow: 0 4px 20px rgba(0,0,0,0.1);">
        <img src="<?= e($game['image_url']) ?>" alt="<?= e($game['name']) ?>" style="width: 100%; height: 100%; object-fit: cover; display: block;">
    </div>

    <!-- Nom du jeu -->
    <?php if ($game['name']): ?>
    <h1 style="font-size: 24px; font-weight: 800; margin-bottom: 8px;"><?= e($game['name']) ?></h1>
    <?php endif; ?>

    <?php if ($isUnlocked): ?>
    <!-- DEBLOQUE : Afficher la prediction -->
    <div class="section-card" style="border-left: 4px solid var(--success);">
        <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 12px;">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="var(--success)" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
            <span style="font-size: 14px; font-weight: 700; color: var(--success);">Prediction debloquee</span>
        </div>

        <?php if ($game['prediction_text']): ?>
        <div style="font-size: 15px; line-height: 1.7; color: var(--text-primary); white-space: pre-wrap;"><?= e($game['prediction_text']) ?></div>
        <?php else: ?>
        <p style="font-size: 14px; color: var(--text-secondary);">Aucune prediction disponible pour le moment.</p>
        <?php endif; ?>

        <?php if ($game['prediction_link']): ?>
        <a href="<?= e($game['prediction_link']) ?>" target="_blank" rel="noopener noreferrer" style="display: block; margin-top: 20px; padding: 14px 24px; background: linear-gradient(135deg, #007AFF, #5AC8FA); color: white; text-align: center; text-decoration: none; font-weight: 700; font-size: 16px; border-radius: 14px; box-shadow: 0 4px 16px rgba(0,122,255,0.3); transition: all 0.2s;">
            Jouer maintenant sur 1Win
        </a>
        <?php endif; ?>
    </div>

    <?php else: ?>
    <!-- VERROUILLE : Afficher la condition -->
    <div class="section-card" style="border-left: 4px solid var(--warning);">
        <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 16px;">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="var(--warning)" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
            <span style="font-size: 14px; font-weight: 700; color: var(--warning);">Prediction verrouillee</span>
        </div>

        <p style="font-size: 15px; line-height: 1.6; color: var(--text-primary); margin-bottom: 16px;">
            Pour debloquer la prediction de <strong><?= e($game['name']) ?></strong>, vous devez cumuler un total de <strong style="color: var(--warning);">$<?= number_format($minDeposit, 2) ?></strong> de depot.
        </p>

        <!-- Barre de progression -->
        <div style="margin-bottom: 12px;">
            <div style="display: flex; justify-content: space-between; font-size: 13px; color: var(--text-secondary); margin-bottom: 6px;">
                <span>Depot cumule</span>
                <span><strong>$<?= number_format($userDeposit, 2) ?></strong> / $<?= number_format($minDeposit, 2) ?></span>
            </div>
            <div style="width: 100%; height: 10px; background: var(--bg); border-radius: 10px; overflow: hidden;">
                <div style="height: 100%; width: <?= $progressPercent ?>%; background: linear-gradient(90deg, var(--warning), #FF6B00); border-radius: 10px; transition: width 0.5s ease;"></div>
            </div>
            <div style="text-align: right; font-size: 12px; color: var(--text-tertiary); margin-top: 4px;"><?= $progressPercent ?>% atteint</div>
        </div>

        <?php if ($minDeposit - $userDeposit > 0): ?>
        <p style="font-size: 14px; color: var(--text-secondary); margin-bottom: 16px;">
            Il vous reste <strong>$<?= number_format($minDeposit - $userDeposit, 2) ?></strong> a deposer pour debloquer cette prediction.
        </p>
        <?php endif; ?>

        <a href="/go/1win" target="_blank" rel="noopener noreferrer" style="display: block; padding: 14px 24px; background: linear-gradient(135deg, #FF9500, #FF6B00); color: white; text-align: center; text-decoration: none; font-weight: 700; font-size: 16px; border-radius: 14px; box-shadow: 0 4px 16px rgba(255,149,0,0.3); transition: all 0.2s;">
            Depoter sur 1Win
        </a>
    </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

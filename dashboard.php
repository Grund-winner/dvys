<?php
/**
 * DVYS AI - Dashboard principal
 * Le code promo DVYS apparaît UNIQUEMENT ici (carte d'activation)
 */
require_once __DIR__ . '/includes/header.php';

$lang = getCurrentLang();
$userId = $user['id'];

// Statistiques
$db = Database::getInstance();

// Nombre de filleuls
$stmt = $db->prepare("SELECT COUNT(*) FROM referrals WHERE referrer_id = ?");
$stmt->execute([$userId]);
$referralCount = (int) $stmt->fetchColumn();

// VIP status
$hasVip = $auth->hasVipAccess($userId);
$vipExpires = $user['vip_expires_at'];

// Dernière activité
$lastChat = $db->prepare("SELECT content, created_at FROM chat_messages WHERE user_id = ? AND role = 'assistant' ORDER BY id DESC LIMIT 1");
$lastChat->execute([$userId]);
$lastChatMsg = $lastChat->fetch();

// Nombre de pronostics aujourd'hui
$stmt = $db->prepare("SELECT COUNT(*) FROM predictions WHERE created_at >= CURRENT_DATE");
$stmt->execute();
$todayPredictions = (int) $stmt->fetchColumn();

// Jeux populaires depuis la DB
$games = $db->query("SELECT * FROM games WHERE is_active = 1 ORDER BY sort_order ASC")->fetchAll();

// Referral code
$myRefCode = $user['referral_code'];
$referralLink = BASE_URL . '/register.php?ref=' . $myRefCode;
?>

<div class="container">
    <!-- Header -->
    <div class="dashboard-header">
        <div class="dashboard-greeting">
            <?= e(t('welcome', $lang)) ?>, <?= e($user['username']) ?>
        </div>
        <div class="dashboard-sub">
            <?= $hasVip ? '<span class="vip-badge">' . e(t('vip_active', $lang)) . '</span>' : '' ?>
        </div>
    </div>

    <!-- Carte d'activation - SEUL endroit où apparaît le code promo -->
    <?php if (!$auth->isAdmin() && (!$user['is_1win_verified'] || !$user['has_deposited'])): ?>
    <div class="activation-card">
        <h2><?= e(t('activate_title', $lang)) ?></h2>
        <p><?= e(t('activate_desc', $lang)) ?></p>
        
        <div class="promo-code-box">
            <div>
                <div class="promo-code-label"><?= e(t('promo_code_label', $lang)) ?></div>
                <div class="promo-code-value"><?= e(PROMO_CODE) ?></div>
            </div>
            <button class="promo-code-copy" onclick="copyPromoCode(this)">Copier</button>
        </div>

        <a href="<?= getUserAffiliateLink($userId, $myRefCode) ?>" target="_blank" rel="noopener noreferrer" class="activation-btn">
            <?= e(t('go_to_partner', $lang)) ?> →
        </a>

        <?php if ($user['is_1win_verified'] && !$user['has_deposited']): ?>
        <p style="margin-top: 12px; font-size: 13px; opacity: 0.9; position: relative;">
            ✅ <?= e(t('deposit_pending', $lang)) ?>
        </p>
        <?php endif; ?>
    </div>
    <?php else: ?>
    <div class="activation-card activated">
        <h2><?= e(t('activated', $lang)) ?></h2>
        <p>Tu as accès à toutes les fonctionnalités de la plateforme.</p>
    </div>
    <?php endif; ?>

    <!-- Stats rapides -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-card-label"><?= e(t('referrals', $lang)) ?></div>
            <div class="stat-card-value"><?= $referralCount ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-card-label">VIP</div>
            <div class="stat-card-value">
                <?= $hasVip ? '✅' : '🔒' ?>
                <?php if ($hasVip && $vipExpires && $vipExpires < '2099-01-01'): ?>
                    <div style="font-size: 12px; font-weight: 400; color: var(--text-secondary);">
                        <?= e(t('vip_expires', $lang)) ?> <?= date('d/m/Y', strtotime($vipExpires)) ?>
                    </div>
                <?php elseif ($hasVip): ?>
                    <div style="font-size: 12px; font-weight: 400; color: var(--text-secondary);">Illimité</div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Actions rapides -->
    <div class="quick-actions">
        <a href="/chat.php" class="quick-action">
            <div class="quick-action-icon" style="background: var(--primary-light);">💬</div>
            <div class="quick-action-text"><?= e(t('chat', $lang)) ?></div>
        </a>
        <a href="/predictions.php" class="quick-action">
            <div class="quick-action-icon" style="background: var(--success-light);">⚽</div>
            <div class="quick-action-text"><?= e(t('predictions', $lang)) ?></div>
        </a>
        <a href="/referrals.php" class="quick-action">
            <div class="quick-action-icon" style="background: var(--warning-light);">👥</div>
            <div class="quick-action-text"><?= e(t('referrals', $lang)) ?></div>
        </a>
        <?php if ($auth->isAdmin()): ?>
        <a href="/admin/" class="quick-action">
            <div class="quick-action-icon" style="background: #F3E8FF;">⚙️</div>
            <div class="quick-action-text">Admin</div>
        </a>
        <?php endif; ?>
    </div>

    <!-- Dernière conversation IA -->
    <?php if ($lastChatMsg): ?>
    <div class="section-card">
        <div class="section-card-header">
            <h2>Dernière conversation IA</h2>
            <a href="/chat.php" class="section-link">
                Ouvrir →
            </a>
        </div>
        <p style="font-size: 14px; color: var(--text-secondary); line-height: 1.5;">
            <?= e(mb_substr($lastChatMsg['content'], 0, 150)) ?><?= mb_strlen($lastChatMsg['content']) > 150 ? '...' : '' ?>
        </p>
        <p style="font-size: 12px; color: var(--text-tertiary); margin-top: 8px;">
            <?= date('d/m/Y H:i', strtotime($lastChatMsg['created_at'])) ?>
        </p>
    </div>
    <?php endif; ?>

    <!-- Jeux 1Win populaires -->
    <?php if (!empty($games)): ?>
    <div class="section-card">
        <div class="section-card-header">
            <h2>Jeux populaires</h2>
        </div>
        <div style="display: flex; gap: 12px; overflow-x: auto; padding-bottom: 8px; -webkit-overflow-scrolling: touch; margin: 0 -16px; padding-left: 16px; padding-right: 16px;">
            <?php foreach ($games as $game): ?>
            <a href="/game.php?id=<?= $game['id'] ?>" style="flex-shrink: 0; width: 140px; height: 170px; border-radius: 14px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.08); transition: transform 0.2s; text-decoration: none; display: block; position: relative;" onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform='none'">
                <img src="<?= e($game['image_url']) ?>" alt="<?= e($game['name']) ?>" loading="lazy" style="width: 100%; height: 100%; object-fit: cover; display: block;">
                <?php if ($game['min_deposit_usd'] > 0): ?>
                <div style="position: absolute; top: 6px; right: 6px; background: rgba(0,0,0,0.6); color: white; font-size: 10px; font-weight: 600; padding: 3px 7px; border-radius: 8px; backdrop-filter: blur(4px);">$<?= number_format($game['min_deposit_usd'], 0) ?></div>
                <?php endif; ?>
                <?php if ($user['deposit_amount'] < $game['min_deposit_usd']): ?>
                <div style="position: absolute; bottom: 0; left: 0; right: 0; background: linear-gradient(transparent, rgba(0,0,0,0.7)); padding: 20px 8px 8px; text-align: center;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" style="vertical-align: middle;"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                </div>
                <?php endif; ?>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
function copyPromoCode(btn) {
    navigator.clipboard.writeText('<?= e(PROMO_CODE) ?>').then(() => {
        btn.textContent = '✓ Copié !';
        setTimeout(() => btn.textContent = 'Copier', 2000);
    });
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

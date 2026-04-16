<?php
/**
 * DVYS AI - Page de parrainage
 * Système viral de recrutement
 */
require_once __DIR__ . '/includes/header.php';

$lang = getCurrentLang();
$userId = $user['id'];
$db = Database::getInstance();

$referralStats = $auth->getReferralStats($userId);
$myRefCode = $user['referral_code'];
$referralLink = BASE_URL . '/register.php?ref=' . $myRefCode;
$hasVip = $auth->hasVipAccess($userId);
?>

<div class="container">
    <!-- Hero Parrainage -->
    <div class="referral-hero">
        <h1>Invite tes amis, gagne des avantages</h1>
        <p>Plus tu parraines, plus tu débloques de fonctionnalités premium</p>
    </div>

    <!-- Lien de parrainage -->
    <div class="section-card" style="margin-bottom: 16px;">
        <label style="font-size: 13px; font-weight: 600; color: var(--text-secondary); display: block; margin-bottom: 8px;">
            <?= e(t('your_referral_link', $lang)) ?>
        </label>
        <div class="referral-link-box">
            <input type="text" class="referral-link-input" id="refLink" value="<?= e($referralLink) ?>" readonly onclick="this.select()">
            <button class="copy-btn" id="copyLinkBtn" onclick="copyRefLink()">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
                <?= e(t('copy_link', $lang)) ?>
            </button>
        </div>

        <label style="font-size: 13px; font-weight: 600; color: var(--text-secondary); display: block; margin-bottom: 8px; margin-top: 16px;">
            <?= e(t('referral_code', $lang)) ?>
        </label>
        <div class="referral-link-box">
            <input type="text" class="referral-link-input" id="refCode" value="<?= e($myRefCode) ?>" readonly onclick="this.select()" style="font-weight: 800; letter-spacing: 2px; text-transform: uppercase;">
            <button class="copy-btn" onclick="copyRefCode()">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
                Copier
            </button>
        </div>
    </div>

    <!-- Boutons de partage -->
    <div class="share-buttons">
        <a href="https://t.me/share/url?url=<?= urlencode($referralLink) ?>&text=<?= urlencode('Rejoins DVYS AI - L\'assistant casino IA intelligent !') ?>" target="_blank" class="share-btn telegram">
            ✈️ Telegram
        </a>
        <a href="https://wa.me/?text=<?= urlencode('Rejoins DVYS AI - L\'assistant casino IA intelligent ! ' . $referralLink) ?>" target="_blank" class="share-btn whatsapp">
            💬 WhatsApp
        </a>
        <a href="https://twitter.com/intent/tweet?url=<?= urlencode($referralLink) ?>&text=<?= urlencode('Rejoins DVYS AI - L\'assistant casino IA intelligent !') ?>" target="_blank" class="share-btn twitter">
            🐦 Twitter
        </a>
    </div>

    <!-- Statistiques -->
    <div class="stats-grid" style="margin-bottom: 24px;">
        <div class="stat-card">
            <div class="stat-card-label"><?= e(t('referrals_count', $lang)) ?></div>
            <div class="stat-card-value"><?= $referralStats['total'] ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-card-label">Vérifiés (dépôt)</div>
            <div class="stat-card-value"><?= $referralStats['verified'] ?></div>
        </div>
    </div>

    <!-- Paliers de parrainage -->
    <div class="referral-progress">
        <!-- Prochain palier -->
        <?php if ($referralStats['total'] < REFERRAL_TIER_3): ?>
        <div class="progress-section">
            <div class="progress-header">
                <span class="progress-label"><?= e(t('next_reward', $lang)) ?></span>
                <span class="progress-value"><?= $referralStats['total'] ?> / <?= $referralStats['next_tier'] ?></span>
            </div>
            <div class="progress-bar">
                <div class="progress-fill" style="width: <?= $referralStats['progress'] ?>%"></div>
            </div>
            <p class="progress-hint"><?= e($referralStats['next_tier_name'], $lang) ?></p>
        </div>
        <?php endif; ?>

        <!-- Tous les paliers -->
        <div class="tiers-grid">
            <div class="tier-card <?= $referralStats['total'] >= REFERRAL_TIER_1 ? 'completed' : ($referralStats['total'] >= 1 ? 'active' : '') ?>">
                <div class="tier-icon">🥉</div>
                <div class="tier-info">
                    <h3><?= e(t('tier_1', $lang)) ?></h3>
                    <p><?= $referralStats['total'] >= REFERRAL_TIER_1 ? '✅ Débloqué !' : 'Encore ' . (REFERRAL_TIER_1 - $referralStats['total']) . ' filleul(s)' ?></p>
                </div>
            </div>
            <div class="tier-card <?= $referralStats['total'] >= REFERRAL_TIER_2 ? 'completed' : ($referralStats['total'] >= REFERRAL_TIER_1 ? 'active' : '') ?>">
                <div class="tier-icon">🥈</div>
                <div class="tier-info">
                    <h3><?= e(t('tier_2', $lang)) ?></h3>
                    <p><?= $referralStats['total'] >= REFERRAL_TIER_2 ? '✅ Débloqué !' : ($referralStats['total'] >= REFERRAL_TIER_1 ? 'Encore ' . (REFERRAL_TIER_2 - $referralStats['total']) . ' filleul(s)' : '🔒') ?></p>
                </div>
            </div>
            <div class="tier-card <?= $referralStats['total'] >= REFERRAL_TIER_3 ? 'completed' : ($referralStats['total'] >= REFERRAL_TIER_2 ? 'active' : '') ?>">
                <div class="tier-icon">🥇</div>
                <div class="tier-info">
                    <h3><?= e(t('tier_3', $lang)) ?></h3>
                    <p><?= $referralStats['total'] >= REFERRAL_TIER_3 ? '✅ Débloqué !' : ($referralStats['total'] >= REFERRAL_TIER_2 ? 'Encore ' . (REFERRAL_TIER_3 - $referralStats['total']) . ' filleul(s)' : '🔒') ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Liste des filleuls -->
    <?php if (!empty($referralStats['list'])): ?>
    <div class="referral-list-header">
        <h2>Mes filleuls</h2>
        <span style="font-size: 13px; color: var(--text-secondary);"><?= $referralStats['total'] ?></span>
    </div>
    <?php foreach ($referralStats['list'] as $ref): ?>
    <div class="referral-item">
        <div class="referral-item-avatar"><?= e(mb_strtoupper(mb_substr($ref['username'], 0, 1))) ?></div>
        <div class="referral-item-info">
            <div class="referral-item-name"><?= e($ref['username']) ?></div>
            <div class="referral-item-date"><?= date('d/m/Y', strtotime($ref['created_at'])) ?></div>
        </div>
        <span class="referral-item-status status-<?= e($ref['status']) ?>">
            <?= $ref['status'] === 'verified' ? '✅' : ($ref['status'] === 'active' ? '⏳' : '⏸') ?>
        </span>
    </div>
    <?php endforeach; ?>
    <?php else: ?>
    <div class="section-card text-center" style="padding: 32px 20px;">
        <div style="font-size: 40px; margin-bottom: 12px;">🤝</div>
        <p style="font-size: 15px; color: var(--text-secondary);">Tu n'as pas encore de filleuls.</p>
        <p style="font-size: 13px; color: var(--text-tertiary); margin-top: 6px;">Partage ton lien de parrainage pour commencer !</p>
    </div>
    <?php endif; ?>
</div>

<script>
function copyRefLink() {
    const link = document.getElementById('refLink').value;
    navigator.clipboard.writeText(link).then(() => {
        const btn = document.getElementById('copyLinkBtn');
        btn.classList.add('copied');
        btn.innerHTML = '✓ Copié !';
        setTimeout(() => {
            btn.classList.remove('copied');
            btn.innerHTML = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg> <?= '<?= e(t("copy_link", $lang)) ?>' ?>';
        }, 2000);
    });
}

function copyRefCode() {
    const code = document.getElementById('refCode').value;
    navigator.clipboard.writeText(code).then(() => {
        event.target.closest('.copy-btn').textContent = '✓ Copié !';
        setTimeout(() => event.target.closest('.copy-btn').textContent = 'Copier', 2000);
    });
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

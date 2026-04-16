<?php
/**
 * DVYS AI - Pronostics foot
 * Contenu VIP debloque par parrainage
 * Pronostics programmes par l'admin avec date de publication
 * V6: Clean modern match card
 */
require_once __DIR__ . '/includes/header.php';

$lang = getCurrentLang();
$userId = $user['id'];
$db = Database::getInstance();

$hasVip = $auth->hasVipAccess($userId);
$referralStats = $auth->getReferralStats($userId);

// Pronostics du jour (programmes pour aujourd'hui, status = active)
$today = date('Y-m-d');
$stmt = $db->prepare("SELECT * FROM predictions WHERE scheduled_date = ? AND status = 'active' ORDER BY match_time ASC");
$stmt->execute([$today]);
$predictions = $stmt->fetchAll();

// Pronostics a venir (programmes pour les jours suivants)
$stmt = $db->prepare("SELECT * FROM predictions WHERE scheduled_date > ? AND status = 'active' ORDER BY scheduled_date ASC, match_time ASC LIMIT 10");
$stmt->execute([$today]);
$upcomingPredictions = $stmt->fetchAll();

// Pronostics passes (resultats)
$stmt = $db->prepare("SELECT * FROM predictions WHERE scheduled_date < ? AND status = 'active' ORDER BY scheduled_date DESC, match_time DESC LIMIT 15");
$stmt->execute([$today]);
$pastPredictions = $stmt->fetchAll();

/**
 * Format date label
 */
function formatDateLabel($scheduledDate) {
    $today = date('Y-m-d');
    $tomorrow = date('Y-m-d', strtotime('+1 day'));
    $yesterday = date('Y-m-d', strtotime('-1 day'));
    if ($scheduledDate === $today) return "Aujourd'hui";
    if ($scheduledDate === $tomorrow) return "Demain";
    if ($scheduledDate === $yesterday) return "Hier";
    $d = strtotime($scheduledDate);
    $dayNames = ['Dim', 'Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam'];
    return $dayNames[date('w', $d)] . ' ' . date('d', $d) . '/' . date('m', $d);
}

/**
 * Render a match card V6 - Clean Modern
 * 
 * Layout:
 *   Top: Competition name + date
 *   Middle: Home logo | VS/time center | Away logo  
 *   Teams: Name below each logo
 *   Report: Analysis text
 *   Bottom: Prediction text + Odds badge (green)
 */
function renderMatchCard($pred, $lang, $hasVip, $showVipLock = true) {
    $isLocked = !$hasVip && $pred['is_vip'];
    $matchTime = !empty($pred['match_time']) ? date('H:i', strtotime($pred['match_time'])) : '';
    $matchDate = !empty($pred['scheduled_date']) ? formatDateLabel($pred['scheduled_date']) : '';
    $report = trim($pred['report'] ?? '');

    $html = '<div class="mc' . ($isLocked ? ' mc-locked' : '') . '">';

    // === TOP: Competition + Date ===
    $html .= '<div class="mc-header">';
    $html .= '<div class="mc-league">';
    $html .= '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 9H4.5a2.5 2.5 0 0 1 0-5H6"/><path d="M18 9h1.5a2.5 2.5 0 0 0 0-5H18"/><path d="M4 22h16"/><path d="M10 14.66V17c0 .55-.47.98-.97 1.21C7.85 18.75 7 20 7 22"/><path d="M14 14.66V17c0 .55.47.98.97 1.21C16.15 18.75 17 20 17 22"/><path d="M18 2H6v7a6 6 0 0 0 12 0V2Z"/></svg>';
    $html .= '<span>' . e($pred['match_league']) . '</span>';
    $html .= '</div>';
    if ($matchDate) {
        $html .= '<div class="mc-date">' . e($matchDate) . '</div>';
    }
    $html .= '</div>';

    // === TEAMS: Logos + Names + Center ===
    $html .= '<div class="mc-matchup">';

    // Home team
    $html .= '<div class="mc-team mc-team-home">';
    if (!empty($pred['home_logo_url'])) {
        $html .= '<div class="mc-logo"><img src="' . e($pred['home_logo_url']) . '" alt="" onerror="this.parentElement.innerHTML=\'<span class=mc-logo-letter>' . mb_substr(e($pred['match_home']), 0, 1) . '</span>\'"></div>';
    } else {
        $html .= '<div class="mc-logo"><span class="mc-logo-letter">' . mb_substr(e($pred['match_home']), 0, 1) . '</span></div>';
    }
    $html .= '<span class="mc-team-name">' . e($pred['match_home']) . '</span>';
    $html .= '</div>';

    // Center
    $html .= '<div class="mc-center">';
    if ($matchTime) {
        $html .= '<span class="mc-time">' . $matchTime . '</span>';
    } else {
        $html .= '<span class="mc-vs">VS</span>';
    }
    $html .= '</div>';

    // Away team
    $html .= '<div class="mc-team mc-team-away">';
    if (!empty($pred['away_logo_url'])) {
        $html .= '<div class="mc-logo"><img src="' . e($pred['away_logo_url']) . '" alt="" onerror="this.parentElement.innerHTML=\'<span class=mc-logo-letter>' . mb_substr(e($pred['match_away']), 0, 1) . '</span>\'"></div>';
    } else {
        $html .= '<div class="mc-logo"><span class="mc-logo-letter">' . mb_substr(e($pred['match_away']), 0, 1) . '</span></div>';
    }
    $html .= '<span class="mc-team-name">' . e($pred['match_away']) . '</span>';
    $html .= '</div>';

    $html .= '</div>'; // end .mc-matchup

    // === REPORT SECTION ===
    if ($report) {
        $html .= '<div class="mc-report' . ($isLocked ? ' mc-report-blur' : '') . '">';
        $html .= '<p>' . e($report) . '</p>';
        $html .= '</div>';
    }

    // === BOTTOM: Prediction + Odds ===
    if ($isLocked && $showVipLock) {
        $html .= '<div class="mc-bottom mc-bottom-locked">';
        $html .= '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>';
        $html .= '<a href="/referrals.php" class="mc-lock-link">' . e(t('unlock_with_referrals', $lang)) . '</a>';
        $html .= '</div>';
    } else {
        $html .= '<div class="mc-bottom">';
        $html .= '<div class="mc-prediction">';
        $html .= '<svg width="14" height="14" viewBox="0 0 24 24" fill="var(--primary)" stroke="none"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>';
        $html .= '<span>' . e($pred['prediction']) . '</span>';
        $html .= '</div>';
        if ($pred['odds'] > 0) {
            $html .= '<div class="mc-odds">' . number_format($pred['odds'], 2) . '</div>';
        }
        $html .= '</div>';
    }

    $html .= '</div>'; // end .mc
    return $html;
}
?>

<div class="container">
    <div class="predictions-header">
        <h1><?= e(t('todays_predictions', $lang)) ?></h1>
        <?php if ($hasVip): ?>
            <span class="vip-badge"><?= e(t('vip_badge', $lang)) ?></span>
        <?php endif; ?>
    </div>

    <!-- Pronostics du jour -->
    <?php if (empty($predictions)): ?>
    <div class="section-card text-center" style="padding: 40px 20px;">
        <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="var(--text-tertiary)" stroke-width="1.5" style="margin: 0 auto 12px;"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
        <p style="color: var(--text-secondary); font-size: 15px;"><?= e(t('no_predictions', $lang)) ?></p>
        <p style="color: var(--text-tertiary); font-size: 13px; margin-top: 8px;">Les pronostics apparaitront ici quand ils seront programmés.</p>
    </div>
    <?php else: ?>
        <?php foreach ($predictions as $pred): ?>
        <?= renderMatchCard($pred, $lang, $hasVip, true) ?>
        <?php endforeach; ?>
    <?php endif; ?>

    <!-- Pronostics a venir -->
    <?php if (!empty($upcomingPredictions)): ?>
    <div style="margin-top: 32px;">
        <h2 style="font-size: 18px; font-weight: 700; margin-bottom: 16px;">A venir</h2>
        <?php foreach ($upcomingPredictions as $pred): ?>
        <div style="opacity: 0.85;">
            <?= renderMatchCard($pred, $lang, $hasVip, false) ?>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Encouragement parrainage si pas VIP -->
    <?php if (!$hasVip && $referralStats['total'] < REFERRAL_TIER_1): ?>
    <div class="section-card" style="margin-top: 24px; text-align: center;">
        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="var(--primary)" stroke-width="1.5" style="margin-bottom: 8px;"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/></svg>
        <h3 style="font-size: 16px; font-weight: 700; margin-bottom: 8px;">Debloque les pronostics VIP</h3>
        <p style="font-size: 14px; color: var(--text-secondary); margin-bottom: 16px;">
            Invite <strong><?= REFERRAL_TIER_1 - $referralStats['total'] ?></strong> ami(s) supplementaire(s) pour acceder a <strong>7 jours VIP</strong>.
        </p>
        <a href="/referrals.php" class="btn-primary" style="display: inline-block; text-decoration: none; color: white;">
            Inviter des amis
        </a>
    </div>
    <?php endif; ?>

    <!-- Resultats passes -->
    <?php if (!empty($pastPredictions)): ?>
    <div style="margin-top: 32px;">
        <h2 style="font-size: 18px; font-weight: 700; margin-bottom: 16px;">Resultats recents</h2>
        <?php foreach ($pastPredictions as $pred): ?>
        <div class="prediction-card">
            <div style="display: flex; align-items: center; justify-content: space-between;">
                <div>
                    <div style="font-size: 13px; color: var(--text-secondary);"><?= e($pred['match_league']) ?> - <?= date('d/m', strtotime($pred['scheduled_date'])) ?></div>
                    <div style="font-size: 14px; font-weight: 600; margin-top: 4px;">
                        <?php if (!empty($pred['home_logo_url'])): ?><img src="<?= e($pred['home_logo_url']) ?>" alt="" style="width:20px;height:20px;object-fit:contain;vertical-align:middle;margin-right:3px;" onerror="this.style.display='none'"><?php endif; ?>
                        <?= e($pred['match_home']) ?> vs <?= e($pred['match_away']) ?>
                        <?php if (!empty($pred['away_logo_url'])): ?><img src="<?= e($pred['away_logo_url']) ?>" alt="" style="width:20px;height:20px;object-fit:contain;vertical-align:middle;margin-left:3px;" onerror="this.style.display='none'"><?php endif; ?>
                    </div>
                    <div style="font-size: 13px; color: var(--text-secondary); margin-top: 4px;"><?= e($pred['prediction']) ?></div>
                </div>
                <div style="text-align: right;">
                    <?php if ($pred['odds'] > 0): ?>
                    <div class="prediction-odds"><?= number_format($pred['odds'], 2) ?></div>
                    <?php endif; ?>
                    <div style="font-size: 12px; font-weight: 600; margin-top: 4px; color: <?= $pred['result'] === 'won' ? 'var(--success)' : ($pred['result'] === 'lost' ? 'var(--danger)' : 'var(--warning)') ?>;">
                        <?php
                        if ($pred['result'] === 'won') echo 'Gagne';
                        elseif ($pred['result'] === 'lost') echo 'Perdu';
                        elseif ($pred['result'] === 'cancelled') echo 'Annule';
                        else echo 'En attente';
                        ?>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

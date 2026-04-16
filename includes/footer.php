<?php
/**
 * DVYS AI - Footer commun
 */
?>
</main>

<!-- Navigation mobile bottom -->
<?php if (isset($user) && $user): ?>
<nav class="mobile-nav">
    <a href="/dashboard.php" class="mobile-nav-item <?= basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'active' : '' ?>">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
        <span><?= e(t('dashboard', $lang ?? 'fr')) ?></span>
    </a>
    <a href="/chat.php" class="mobile-nav-item <?= basename($_SERVER['PHP_SELF']) === 'chat.php' ? 'active' : '' ?>">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
        <span><?= e(t('chat', $lang ?? 'fr')) ?></span>
    </a>
    <a href="/predictions.php" class="mobile-nav-item <?= basename($_SERVER['PHP_SELF']) === 'predictions.php' ? 'active' : '' ?>">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
        <span><?= e(t('predictions', $lang ?? 'fr')) ?></span>
    </a>
    <a href="/referrals.php" class="mobile-nav-item <?= basename($_SERVER['PHP_SELF']) === 'referrals.php' ? 'active' : '' ?>">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
        <span><?= e(t('referrals', $lang ?? 'fr')) ?></span>
    </a>
</nav>
<?php endif; ?>

<script src="/assets/js/app.js"></script>
</body>
</html>

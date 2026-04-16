<?php
/**
 * DVYS AI - Header commun
 * Design iOS light épuré
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/i18n.php';
require_once __DIR__ . '/functions.php';

$lang = getCurrentLang();
$auth = new Auth();
$user = $auth->currentUser();
$flash = getFlash();
$isRtl = isRTL($lang);

// Auto-detect language from browser if not set
if (!isset($_SESSION['lang']) && !isset($_COOKIE['dvys_lang'])) {
    $browserLang = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? 'fr', 0, 2);
    if (in_array($browserLang, SUPPORTED_LANGUAGES)) {
        setLang($browserLang);
        $lang = $browserLang;
    }
}
?>
<!DOCTYPE html>
<html lang="<?= e($lang) ?>" dir="<?= $isRtl ? 'rtl' : 'ltr' ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="description" content="DVYS AI - Votre assistant intelligent pour les jeux 1Win et paris sportifs">
    <meta name="theme-color" content="#007AFF">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <title><?= e(t('app_name', $lang)) ?></title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <?php if ($isRtl): ?>
    <link rel="stylesheet" href="/assets/css/rtl.css">
    <?php endif; ?>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
<?php if ($flash): ?>
<div class="flash flash-<?= e($flash['type']) ?>" id="flash">
    <span><?= e($flash['message']) ?></span>
    <button onclick="this.parentElement.remove()" class="flash-close">&times;</button>
</div>
<?php endif; ?>

<?php if ($user): ?>
<!-- Navigation connecté -->
<nav class="nav-bar">
    <a href="/dashboard.php" class="nav-logo">
        <span class="logo-icon">✦</span>
        <span class="logo-text">DVYS</span>
    </a>
    <div class="nav-links">
        <a href="/dashboard.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'active' : '' ?>">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
            <span><?= e(t('dashboard', $lang)) ?></span>
        </a>
        <a href="/chat.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'chat.php' ? 'active' : '' ?>">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
            <span><?= e(t('chat', $lang)) ?></span>
        </a>
        <a href="/predictions.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'predictions.php' ? 'active' : '' ?>">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
            <span><?= e(t('predictions', $lang)) ?></span>
        </a>
        <a href="/referrals.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'referrals.php' ? 'active' : '' ?>">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
            <span><?= e(t('referrals', $lang)) ?></span>
        </a>
    </div>
    <div class="nav-actions">
        <!-- Notification bell -->
        <button type="button" class="notif-bell" id="notifBellBtn" onclick="toggleNotifPopup()" aria-label="Notifications" style="position:relative;display:flex;align-items:center;justify-content:center;width:36px;height:36px;border-radius:9999px;color:var(--text-secondary);transition:all 0.25s;cursor:pointer;background:none;border:none;text-decoration:none;">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path></svg>
            <span id="notifBadge" style="display:none;position:absolute;top:2px;right:2px;min-width:16px;height:16px;background:var(--danger);color:white;font-size:10px;font-weight:700;border-radius:10px;align-items:center;justify-content:center;padding:0 4px;line-height:1;"></span>
        </button>
        <div id="notifPopup" style="display:none;position:absolute;top:calc(100% + 8px);right:0;width:320px;max-height:400px;background:var(--bg-card);border-radius:16px;box-shadow:var(--shadow-lg);border:1px solid var(--border);z-index:2000;overflow:hidden;">
            <div style="display:flex;align-items:center;justify-content:space-between;padding:14px 16px;border-bottom:1px solid var(--border);">
                <h3 style="font-size:15px;font-weight:700;margin:0;">Notifications</h3>
                <a href="javascript:void(0)" onclick="markAllRead()" style="font-size:12px;color:var(--primary);font-weight:600;text-decoration:none;">Tout marquer comme lu</a>
            </div>
            <div id="notifList" style="overflow-y:auto;max-height:300px;"></div>
        </div>
        <!-- Sélecteur de langue -->
        <select class="lang-select" onchange="changeLang(this.value)" aria-label="Language">
            <?php foreach (['fr' => 'FR', 'en' => 'EN', 'es' => 'ES', 'pt' => 'PT', 'ru' => 'RU', 'ar' => 'AR', 'tr' => 'TR', 'hi' => 'HI', 'uz' => 'UZ', 'az' => 'AZ'] as $code => $label): ?>
                <option value="<?= $code ?>" <?= $lang === $code ? 'selected' : '' ?>><?= $label ?></option>
            <?php endforeach; ?>
        </select>
        <a href="/logout.php" class="nav-logout" title="<?= e(t('logout', $lang)) ?>">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
        </a>
    </div>
</nav>
<main class="main-content logged-in">
<?php elseif (!in_array(basename($_SERVER['PHP_SELF']), ['index.php', 'login.php', 'register.php']) && !str_contains($_SERVER['PHP_SELF'], 'admin/')): ?>
<!-- Rediriger si pas connecté -->
<?php redirect('/login.php'); ?>
<?php endif; ?>

<script>
function changeLang(lang) {
    fetch('/api/auth.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({action: 'set_lang', lang: lang, csrf: '<?= generateCsrf() ?>'})
    }).then(() => location.reload());
}

// === Notification Bell ===
(function() {
    var popupOpen = false;

    window.toggleNotifPopup = function() {
        var popup = document.getElementById('notifPopup');
        if (!popup) return;
        popupOpen = !popupOpen;
        popup.style.display = popupOpen ? 'block' : 'none';
        if (popupOpen) {
            loadNotifications();
        }
    };

    window.toggleMobileNotif = function() {
        var panel = document.getElementById('mobileNotifPanel');
        if (!panel) return;
        var isOpen = panel.classList.contains('mobile-notif-open');
        if (isOpen) {
            panel.classList.remove('mobile-notif-open');
            setTimeout(function() { panel.style.display = 'none'; }, 300);
            document.body.style.overflow = '';
        } else {
            panel.style.display = 'block';
            // Force reflow before adding class for transition
            void panel.offsetHeight;
            panel.classList.add('mobile-notif-open');
            document.body.style.overflow = 'hidden';
            loadMobileNotifications();
        }
    };

    // Close mobile panel on overlay backdrop click
    document.addEventListener('click', function(e) {
        var panel = document.getElementById('mobileNotifPanel');
        if (!panel || !panel.classList.contains('mobile-notif-open')) return;
        // Close if clicking outside the panel header area
        var header = panel.querySelector('div[style*="sticky"]');
        if (header && header.contains(e.target)) return;
        // Close if clicking the list area (acts as backdrop)
        var list = document.getElementById('mobileNotifList');
        if (list && list.contains(e.target)) return;
        toggleMobileNotif();
    });

    // Close popup when clicking outside
    document.addEventListener('click', function(e) {
        var btn = document.getElementById('notifBellBtn');
        var popup = document.getElementById('notifPopup');
        if (!btn || !popup) return;
        if (popupOpen && !btn.contains(e.target) && !popup.contains(e.target)) {
            popupOpen = false;
            popup.style.display = 'none';
        }
    });

    function updateBadge(count) {
        var badge = document.getElementById('notifBadge');
        if (badge) {
            if (count > 0) {
                badge.textContent = count > 99 ? '99+' : count;
                badge.style.display = 'flex';
            } else {
                badge.style.display = 'none';
            }
        }
        // Mobile bell
        var mobileBell = document.getElementById('mobileNotifBell');
        var mobileBadge = document.getElementById('mobileNotifBadge');
        if (mobileBell && mobileBadge) {
            if (count > 0) {
                mobileBadge.textContent = count > 99 ? '99+' : count;
                mobileBell.style.display = 'flex';
            } else {
                mobileBell.style.display = 'none';
            }
        }
    }

    function loadNotifications() {
        var listEl = document.getElementById('notifList');
        if (!listEl) return;
        listEl.innerHTML = '<div style="padding:24px 16px;text-align:center;color:var(--text-tertiary);font-size:13px;">Chargement...</div>';

        fetch('/api/notifications.php')
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (!data.success || !data.notifications || data.notifications.length === 0) {
                    listEl.innerHTML = '<div style="padding:32px 16px;text-align:center;color:var(--text-tertiary);font-size:13px;">Aucune notification</div>';
                    updateBadge(data.unread_count || 0);
                    return;
                }
                updateBadge(data.unread_count || 0);
                renderNotifList(listEl, data.notifications, false);
            })
            .catch(function() {
                listEl.innerHTML = '<div style="padding:32px 16px;text-align:center;color:var(--text-tertiary);font-size:13px;">Erreur de chargement</div>';
            });
    }

    function loadMobileNotifications() {
        var listEl = document.getElementById('mobileNotifList');
        if (!listEl) return;
        listEl.innerHTML = '<div style="padding:40px 20px;text-align:center;color:var(--text-tertiary);font-size:14px;">Chargement...</div>';

        fetch('/api/notifications.php')
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (!data.success || !data.notifications || data.notifications.length === 0) {
                    listEl.innerHTML = '<div style="padding:60px 20px;text-align:center;color:var(--text-tertiary);font-size:14px;">Aucune notification</div>';
                    return;
                }
                renderNotifList(listEl, data.notifications, true);
            })
            .catch(function() {
                listEl.innerHTML = '<div style="padding:60px 20px;text-align:center;color:var(--text-tertiary);font-size:14px;">Erreur de chargement</div>';
            });
    }

    function renderNotifList(container, notifications, isMobile) {
        var html = '';
        notifications.forEach(function(n) {
            var isUnread = !n.is_read;
            var bgStyle = isUnread ? 'background:var(--primary-light);' : '';
            var imgHtml = '';
            if (n.image_url) {
                imgHtml = '<img src="' + n.image_url + '" alt="" style="width:48px;height:48px;border-radius:8px;object-fit:cover;flex-shrink:0;" onerror="this.style.display=\'none\'">';
            }
            var timeStr = n.created_at ? n.created_at.replace('T', ' ').substring(0, 16) : '';

            html += '<div style="display:flex;gap:12px;padding:' + (isMobile ? '14px' : '12px') + ' 16px;border-bottom:1px solid var(--border-light);' + bgStyle + '" id="notif-item-' + n.id + '">';
            html += imgHtml;
            html += '<div style="flex:1;min-width:0;">';
            if (n.title) html += '<div style="font-size:13px;font-weight:700;color:var(--text-primary);margin-bottom:2px;">' + n.title + '</div>';
            html += '<div style="font-size:12px;color:var(--text-secondary);line-height:1.4;word-wrap:break-word;">' + n.message + '</div>';
            html += '<div style="font-size:11px;color:var(--text-tertiary);margin-top:4px;">' + timeStr + '</div>';
            html += '<div style="display:flex;gap:8px;margin-top:8px;">';
            if (isUnread) {
                html += '<button onclick="markRead(' + n.id + ')" style="padding:5px 12px;background:none;border:1px solid var(--border);border-radius:6px;font-size:11px;color:var(--primary);cursor:pointer;font-weight:600;">Marquer comme lu</button>';
            }
            html += '<button onclick="deleteNotif(' + n.id + ')" style="padding:5px 12px;background:none;border:1px solid var(--border);border-radius:6px;font-size:11px;color:var(--danger);cursor:pointer;font-weight:600;">Supprimer</button>';
            html += '</div>';
            html += '</div></div>';
        });
        container.innerHTML = html;
    }

    window.markRead = function(id) {
        fetch('/api/notifications.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({action: 'mark_read', id: id})
        }).then(function(r) { return r.json(); }).then(function(data) {
            loadNotifications();
            var mobilePanel = document.getElementById('mobileNotifPanel');
            if (mobilePanel && mobilePanel.classList.contains('mobile-notif-open')) {
                loadMobileNotifications();
            }
            refreshBadge();
        });
    };

    window.deleteNotif = function(id) {
        fetch('/api/notifications.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({action: 'delete', id: id})
        }).then(function(r) { return r.json(); }).then(function(data) {
            loadNotifications();
            var mobilePanel = document.getElementById('mobileNotifPanel');
            if (mobilePanel && mobilePanel.classList.contains('mobile-notif-open')) {
                loadMobileNotifications();
            }
            refreshBadge();
        });
    };

    window.markAllRead = function() {
        fetch('/api/notifications.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({action: 'mark_all_read'})
        }).then(function(r) { return r.json(); }).then(function(data) {
            loadNotifications();
            var mobilePanel = document.getElementById('mobileNotifPanel');
            if (mobilePanel && mobilePanel.classList.contains('mobile-notif-open')) {
                loadMobileNotifications();
            }
            refreshBadge();
        });
    };

    function refreshBadge() {
        fetch('/api/notifications.php')
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (data.success) updateBadge(data.unread_count || 0);
            })
            .catch(function() {});
    }

    // Load badge count on page load
    fetch('/api/notifications.php')
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.success) {
                updateBadge(data.unread_count || 0);
            }
        })
        .catch(function() {});
})();

function shareMatch(btn) {
    if (navigator.share) {
        var card = btn.closest('.mc');
        var text = card ? card.textContent.trim().substring(0, 200) : 'Regarde ce pronostic !';
        navigator.share({ title: 'DVYS AI - Pronostic', text: text, url: window.location.href }).catch(function(){});
    }
}
</script>

<?php if (isset($user) && $user): ?>
<!-- Mobile Floating Notification Bell -->
<button type="button" id="mobileNotifBell" onclick="toggleMobileNotif()" aria-label="Notifications" style="display:none;position:fixed;top:16px;right:16px;z-index:9999;width:48px;height:48px;border-radius:50%;background:rgba(255,255,255,0.95);box-shadow:0 4px 16px rgba(0,0,0,0.15);border:none;align-items:center;justify-content:center;cursor:pointer;backdrop-filter:blur(12px);-webkit-backdrop-filter:blur(12px);">
    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="var(--primary)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path></svg>
    <span id="mobileNotifBadge" style="display:none;position:absolute;top:2px;right:2px;min-width:18px;height:18px;background:var(--danger);color:white;font-size:10px;font-weight:700;border-radius:10px;align-items:center;justify-content:center;padding:0 4px;line-height:1;"></span>
</button>

<!-- Mobile Notification Panel (full-screen overlay) -->
<div id="mobileNotifPanel" style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;z-index:10001;background:var(--bg);opacity:0;transform:translateY(100%);transition:opacity 0.3s,transform 0.3s;">
    <div style="position:sticky;top:0;background:var(--bg-card);border-bottom:1px solid var(--border);padding:16px 20px;display:flex;align-items:center;justify-content:space-between;z-index:1;">
        <div style="display:flex;align-items:center;gap:12px;">
            <button onclick="toggleMobileNotif()" style="background:none;border:none;cursor:pointer;padding:4px;">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="var(--text-primary)" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
            </button>
            <h3 style="font-size:17px;font-weight:700;margin:0;">Notifications</h3>
        </div>
        <a href="javascript:void(0)" onclick="markAllRead()" style="font-size:13px;color:var(--primary);font-weight:600;text-decoration:none;">Tout lire</a>
    </div>
    <div id="mobileNotifList" style="overflow-y:auto;height:calc(100% - 60px);-webkit-overflow-scrolling:touch;"></div>
</div>

<style>
    #mobileNotifPanel.mobile-notif-open {
        opacity: 1 !important;
        transform: translateY(0) !important;
    }
    @media (min-width: 769px) {
        #mobileNotifBell { display: none !important; }
        #mobileNotifPanel { display: none !important; }
    }
    @media (max-width: 768px) {
        #mobileNotifPanel { display: block !important; }
    }
</style>
<?php endif; ?>

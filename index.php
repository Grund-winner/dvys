<?php
/**
 * DVYS AI - Landing Page
 * Page d'accueil avec jeux 1Win dynamiques, icons SVG, sans section parrainage
 */
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/database.php';
require_once __DIR__ . '/includes/functions.php';

$db = Database::getInstance();
Database::migrate();

// Fetch active games from DB
$games = $db->query("SELECT * FROM games WHERE is_active = 1 ORDER BY sort_order ASC")->fetchAll();

// Fetch social links from settings
function getSetting($db, $key, $default = '') {
    $stmt = $db->prepare("SELECT value FROM admin_settings WHERE key = ?");
    $stmt->execute([$key]);
    $val = $stmt->fetchColumn();
    return $val !== false ? $val : $default;
}

$socialTelegram = getSetting($db, 'social_telegram', 'https://t.me/dvys_ai');
$socialWhatsapp = getSetting($db, 'social_whatsapp', 'https://wa.me/dvys_ai');
$socialTiktok = getSetting($db, 'social_tiktok', 'https://tiktok.com/@dvys_ai');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>DVYS AI - Assistant IA pour les jeux 1Win</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif; background: #F2F2F7; color: #1C1C1E; }

        /* Top bar */
        .top-bar { display: flex; align-items: center; justify-content: space-between; padding: 16px 24px; background: white; position: sticky; top: 0; z-index: 50; border-bottom: 1px solid #E5E5EA; }
        .top-bar-logo { display: flex; align-items: center; gap: 10px; text-decoration: none; }
        .top-bar-logo span { font-size: 20px; font-weight: 700; color: #1C1C1E; }
        .top-bar-actions { display: flex; align-items: center; gap: 12px; }
        .top-bar-actions a { text-decoration: none; color: #007AFF; font-size: 14px; font-weight: 600; }
        .btn-cta { display: inline-block; padding: 10px 24px; background: #007AFF; color: white !important; border: none; border-radius: 50px; font-size: 14px; font-weight: 600; text-decoration: none !important; transition: all 0.2s; }
        .btn-cta:hover { background: #0056CC; }

        /* Hero */
        .hero { min-height: 90vh; display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center; padding: 60px 24px 40px; background: linear-gradient(180deg, #FFFFFF 0%, #F2F2F7 100%); }
        .badge { display: inline-flex; align-items: center; gap: 6px; padding: 8px 16px; background: #E8F4FD; color: #007AFF; border-radius: 20px; font-size: 13px; font-weight: 600; margin-bottom: 24px; }
        .hero-icon { width: 88px; height: 88px; background: linear-gradient(135deg, #007AFF, #5AC8FA); border-radius: 22px; display: flex; align-items: center; justify-content: center; margin-bottom: 28px; box-shadow: 0 12px 40px rgba(0,122,255,0.25); }
        .hero-icon svg { width: 44px; height: 44px; color: white; }
        h1 { font-size: 34px; font-weight: 800; margin-bottom: 16px; line-height: 1.15; letter-spacing: -0.5px; max-width: 500px; }
        .hero p { font-size: 17px; color: #8E8E93; max-width: 440px; line-height: 1.65; margin-bottom: 36px; }
        .hero-1win-logo { display: flex; align-items: center; gap: 8px; margin-bottom: 32px; opacity: 0.7; }
        .hero-1win-logo img { height: 24px; }
        .hero-1win-logo span { font-size: 13px; color: #8E8E93; font-weight: 500; }
        .btn-hero { display: inline-block; padding: 16px 40px; background: #007AFF; color: white; border: none; border-radius: 50px; font-size: 17px; font-weight: 600; text-decoration: none; box-shadow: 0 6px 20px rgba(0,122,255,0.35); transition: all 0.2s; }
        .btn-hero:hover { background: #0056CC; transform: translateY(-1px); box-shadow: 0 8px 28px rgba(0,122,255,0.4); }

        /* Features */
        .features { padding: 60px 24px; background: white; }
        .features h2 { text-align: center; font-size: 28px; font-weight: 700; margin-bottom: 40px; }
        .features-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px; max-width: 900px; margin: 0 auto; }
        .feature-card { background: #F2F2F7; border-radius: 16px; padding: 28px 24px; transition: transform 0.2s; }
        .feature-card:hover { transform: translateY(-2px); }
        .feature-icon { width: 48px; height: 48px; border-radius: 14px; display: flex; align-items: center; justify-content: center; margin-bottom: 16px; }
        .feature-icon svg { width: 24px; height: 24px; }
        .feature-icon.blue { background: #E8F4FD; color: #007AFF; }
        .feature-icon.green { background: #E8F8EE; color: #34C759; }
        .feature-icon.orange { background: #FFF3E0; color: #FF9500; }
        .feature-card h3 { font-size: 16px; font-weight: 700; margin-bottom: 8px; }
        .feature-card p { font-size: 14px; color: #8E8E93; line-height: 1.6; }

        /* Games section */
        .games { padding: 60px 24px; background: #F2F2F7; }
        .games-header { display: flex; align-items: center; justify-content: space-between; max-width: 700px; margin: 0 auto 24px; }
        .games-header h2 { font-size: 24px; font-weight: 700; }
        .games-header .partner-badge { display: flex; align-items: center; gap: 6px; padding: 6px 14px; background: white; border-radius: 20px; font-size: 12px; font-weight: 600; color: #8E8E93; box-shadow: 0 1px 4px rgba(0,0,0,0.06); }
        .games-header .partner-badge img { height: 16px; }
        .game-grid { display: flex; gap: 14px; overflow-x: auto; max-width: 700px; margin: 0 auto; padding-bottom: 16px; scroll-snap-type: x mandatory; -webkit-overflow-scrolling: touch; }
        .game-grid::-webkit-scrollbar { display: none; }
        .game-card { flex-shrink: 0; width: 160px; height: 200px; border-radius: 14px; overflow: hidden; background: white; box-shadow: 0 2px 8px rgba(0,0,0,0.06); scroll-snap-align: start; transition: transform 0.2s; position: relative; }
        .game-card:hover { transform: translateY(-2px); box-shadow: 0 4px 16px rgba(0,0,0,0.1); }
        .game-card a { display: block; width: 100%; height: 100%; }
        .game-card img { width: 100%; height: 100%; object-fit: cover; display: block; }

        /* Social / Contact */
        .social { padding: 48px 24px; background: white; text-align: center; }
        .social h2 { font-size: 22px; font-weight: 700; margin-bottom: 8px; }
        .social p { font-size: 14px; color: #8E8E93; margin-bottom: 24px; }
        .social-links { display: flex; justify-content: center; gap: 16px; }
        .social-link { width: 52px; height: 52px; border-radius: 50%; display: flex; align-items: center; justify-content: center; transition: transform 0.2s, box-shadow 0.2s; }
        .social-link:hover { transform: translateY(-2px); box-shadow: 0 4px 16px rgba(0,0,0,0.12); }
        .social-link svg { width: 24px; height: 24px; }
        .social-link.telegram { background: #E8F4FD; color: #0088CC; }
        .social-link.whatsapp { background: #E8F8EE; color: #25D366; }
        .social-link.tiktok { background: #F2F2F7; color: #010101; }

        /* CTA bottom */
        .cta-bottom { padding: 48px 24px 60px; text-align: center; background: linear-gradient(180deg, #F2F2F7 0%, #FFFFFF 100%); }
        .cta-bottom h2 { font-size: 24px; font-weight: 700; margin-bottom: 8px; }
        .cta-bottom p { color: #8E8E93; margin-bottom: 28px; font-size: 15px; }

        /* Footer */
        .footer { text-align: center; padding: 24px; font-size: 12px; color: #AEAEB2; background: white; border-top: 1px solid #E5E5EA; }
        .footer a { color: #007AFF; text-decoration: none; }

        @media (max-width: 600px) {
            h1 { font-size: 26px; }
            .hero { min-height: 80vh; padding: 40px 20px 32px; }
            .game-card { width: 130px; height: 165px; }
            .features-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <!-- Top bar -->
    <nav class="top-bar">
        <a href="/" class="top-bar-logo">
            <svg width="28" height="28" viewBox="0 0 28 28" fill="none">
                <rect width="28" height="28" rx="7" fill="url(#logo-grad)"/>
                <path d="M14 7L8 17h12L14 7z" fill="white" opacity="0.9"/>
                <defs><linearGradient id="logo-grad" x1="0" y1="0" x2="28" y2="28"><stop stop-color="#007AFF"/><stop offset="1" stop-color="#5AC8FA"/></linearGradient></defs>
            </svg>
            <span>DVYS AI</span>
        </a>
        <div class="top-bar-actions">
            <a href="/login.php">Connexion</a>
            <a href="/register.php" class="btn-cta">Commencer</a>
        </div>
    </nav>

    <!-- Hero -->
    <section class="hero">
        <div class="badge">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>
            AI Powered
        </div>
        <div class="hero-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2z"/>
                <path d="M8 14s1.5 2 4 2 4-2 4-2"/>
                <line x1="9" y1="9" x2="9.01" y2="9"/>
                <line x1="15" y1="9" x2="15.01" y2="9"/>
            </svg>
        </div>
        <h1>Ton assistant IA pour les jeux 1Win</h1>
        <p>Recois des conseils personnalises, des pronostics exclusifs et accede a des bonus grace a la puissance de l'intelligence artificielle.</p>

        <div class="hero-1win-logo">
            <span>Partenaire officiel</span>
            <svg width="48" height="20" viewBox="0 0 48 20" fill="none">
                <rect width="48" height="20" rx="4" fill="#1A1A2E"/>
                <text x="5" y="14" font-family="Inter,sans-serif" font-weight="700" font-size="11" fill="white">1Win</text>
            </svg>
        </div>

        <a href="/register.php" class="btn-hero">Commencer gratuitement</a>
    </section>

    <!-- Features -->
    <section class="features">
        <h2>Pourquoi DVYS AI ?</h2>
        <div class="features-grid">
            <div class="feature-card">
                <div class="feature-icon blue">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                </div>
                <h3>Chat IA intelligent</h3>
                <p>Pose tes questions sur les jeux, les strategies et les bonus. Notre IA te repond instantanement.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon green">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
                </div>
                <h3>Pronostics foot VIP</h3>
                <p>Accede a des pronostics foot exclusifs chaque jour. Plus tu parraines, plus tu debloques.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon orange">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                </div>
                <h3>Securise et fiable</h3>
                <p>Plateforme securisee avec protection des donnees. Acces VIP par verification de depot.</p>
            </div>
        </div>
    </section>

    <!-- Games -->
    <?php if (!empty($games)): ?>
    <section class="games">
        <div class="games-header">
            <h2>Jeux 1Win populaires</h2>
            <div class="partner-badge">
                <svg width="16" height="10" viewBox="0 0 48 20" fill="none"><rect width="48" height="20" rx="4" fill="#1A1A2E"/><text x="5" y="14" font-family="Inter,sans-serif" font-weight="700" font-size="11" fill="white">1Win</text></svg>
                Casino
            </div>
        </div>
        <div class="game-grid">
            <?php foreach ($games as $game): ?>
            <div class="game-card">
                <?php if ($game['link_url']): ?>
                <a href="<?= e($game['link_url']) ?>" target="_blank" rel="noopener noreferrer" title="<?= e($game['name']) ?>">
                    <img src="<?= e($game['image_url']) ?>" alt="<?= e($game['name']) ?>" loading="lazy">
                </a>
                <?php else: ?>
                <img src="<?= e($game['image_url']) ?>" alt="<?= e($game['name']) ?>" loading="lazy">
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

    <!-- Social -->
    <section class="social">
        <h2>Rejoins la communaute</h2>
        <p>Suis-nous sur nos reseaux pour les dernieres astuces et pronostics</p>
        <div class="social-links">
            <!-- Telegram -->
            <a href="<?= e($socialTelegram) ?>" target="_blank" rel="noopener noreferrer" class="social-link telegram" aria-label="Telegram">
                <svg viewBox="0 0 24 24" fill="currentColor"><path d="M11.944 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0a12 12 0 0 0-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 0 1 .171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.479.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z"/></svg>
            </a>
            <!-- WhatsApp -->
            <a href="<?= e($socialWhatsapp) ?>" target="_blank" rel="noopener noreferrer" class="social-link whatsapp" aria-label="WhatsApp">
                <svg viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
            </a>
            <!-- TikTok -->
            <a href="<?= e($socialTiktok) ?>" target="_blank" rel="noopener noreferrer" class="social-link tiktok" aria-label="TikTok">
                <svg viewBox="0 0 24 24" fill="currentColor"><path d="M19.59 6.69a4.83 4.83 0 01-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 01-2.88 2.5 2.89 2.89 0 01-2.89-2.89 2.89 2.89 0 012.89-2.89c.28 0 .54.04.79.11V9.02a6.27 6.27 0 00-.79-.05 6.34 6.34 0 00-6.34 6.34 6.34 6.34 0 006.34 6.34 6.34 6.34 0 006.34-6.34V8.72a8.2 8.2 0 004.76 1.52V6.79a4.85 4.85 0 01-1-.1z"/></svg>
            </a>
        </div>
    </section>

    <!-- CTA bottom -->
    <section class="cta-bottom">
        <h2>Pret a commencer ?</h2>
        <p>Rejoins des milliers d'utilisateurs et booste tes gains.</p>
        <a href="/register.php" class="btn-hero">Commencer gratuitement</a>
    </section>

    <div class="footer">&copy; 2026 DVYS AI. All rights reserved. | <a href="https://1win.ci" target="_blank" rel="noopener noreferrer">1Win</a></div>
</body>
</html>

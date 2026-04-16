<?php
/**
 * DVYS AI - Base de donnees PostgreSQL (Neon) avec auto-initialisation
 */

require_once __DIR__ . '/config.php';

class Database {
    private static ?PDO $instance = null;

    public static function getInstance(): PDO {
        if (self::$instance === null) {
            // Parser DATABASE_URL
            $url = parse_url(DATABASE_URL);
            $host = $url['host'] ?? 'localhost';
            $port = $url['port'] ?? '5432';
            $dbname = ltrim($url['path'] ?? 'neondb', '/');
            $user = $url['user'] ?? 'neondb_owner';
            $pass = $url['pass'] ?? '';
            // Retirer channel_binding=require qui peut poser probleme avec PHP
            $query = $url['query'] ?? '';
            parse_str($query, $params);
            unset($params['channel_binding']);
            $sslmode = $params['sslmode'] ?? 'require';

            $dsn = "pgsql:host={$host};port={$port};dbname={$dbname};sslmode={$sslmode}";

            self::$instance = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);

            // Auto-initialisation des tables
            self::initSchema();
        }
        return self::$instance;
    }

    private static function initSchema(): void {
        $db = self::$instance;

        // --- USERS ---
        $db->exec("CREATE TABLE IF NOT EXISTS users (
            id SERIAL PRIMARY KEY,
            username TEXT NOT NULL UNIQUE,
            email TEXT NOT NULL UNIQUE,
            password TEXT NOT NULL,
            referral_code TEXT NOT NULL UNIQUE,
            referred_by INTEGER DEFAULT NULL REFERENCES users(id),
            is_1win_verified INTEGER DEFAULT 0,
            has_deposited INTEGER DEFAULT 0,
            deposit_amount REAL DEFAULT 0,
            vip_expires_at TIMESTAMP NULL,
            language TEXT DEFAULT 'fr',
            country TEXT DEFAULT '',
            ip_address TEXT DEFAULT '',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            last_active TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");

        // --- REFERRALS ---
        $db->exec("CREATE TABLE IF NOT EXISTS referrals (
            id SERIAL PRIMARY KEY,
            referrer_id INTEGER NOT NULL REFERENCES users(id),
            referred_id INTEGER NOT NULL UNIQUE REFERENCES users(id),
            status TEXT DEFAULT 'pending',
            deposit_amount REAL DEFAULT 0,
            deposit_confirmed_at TIMESTAMP NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");

        // --- PREDICTIONS ---
        $db->exec("CREATE TABLE IF NOT EXISTS predictions (
            id SERIAL PRIMARY KEY,
            match_league TEXT NOT NULL,
            match_home TEXT NOT NULL,
            match_away TEXT NOT NULL,
            match_time TEXT NOT NULL,
            prediction TEXT NOT NULL,
            odds REAL DEFAULT 0,
            result TEXT DEFAULT 'pending',
            is_vip INTEGER DEFAULT 0,
            scheduled_date TEXT DEFAULT '',
            status TEXT DEFAULT 'active',
            home_logo_url TEXT DEFAULT '',
            away_logo_url TEXT DEFAULT '',
            report TEXT DEFAULT '',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");

        // --- CHAT MESSAGES ---
        $db->exec("CREATE TABLE IF NOT EXISTS chat_messages (
            id SERIAL PRIMARY KEY,
            user_id INTEGER NOT NULL REFERENCES users(id),
            role TEXT NOT NULL CHECK(role IN ('user','assistant','system')),
            content TEXT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");

        // --- POSTBACK LOGS ---
        $db->exec("CREATE TABLE IF NOT EXISTS postback_logs (
            id SERIAL PRIMARY KEY,
            user_id INTEGER DEFAULT NULL REFERENCES users(id),
            event TEXT NOT NULL,
            amount REAL DEFAULT 0,
            sub1 TEXT DEFAULT '',
            sub2 TEXT DEFAULT '',
            raw_data TEXT DEFAULT '',
            ip_address TEXT DEFAULT '',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");

        // --- BROADCASTS ---
        $db->exec("CREATE TABLE IF NOT EXISTS broadcasts (
            id SERIAL PRIMARY KEY,
            admin_id INTEGER NOT NULL REFERENCES users(id),
            title TEXT DEFAULT '',
            messages JSONB DEFAULT '{}',
            sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            delivered_count INTEGER DEFAULT 0,
            target_type TEXT DEFAULT 'all',
            image_url TEXT DEFAULT ''
        )");

        // --- SESSIONS ---
        $db->exec("CREATE TABLE IF NOT EXISTS sessions (
            id TEXT PRIMARY KEY,
            user_id INTEGER NOT NULL REFERENCES users(id),
            ip_address TEXT DEFAULT '',
            user_agent TEXT DEFAULT '',
            expires_at TIMESTAMP NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");

        // --- ADMIN SETTINGS ---
        $db->exec("CREATE TABLE IF NOT EXISTS admin_settings (
            key TEXT PRIMARY KEY,
            value TEXT DEFAULT '',
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");

        // --- GAMES ---
        $db->exec("CREATE TABLE IF NOT EXISTS games (
            id SERIAL PRIMARY KEY,
            name TEXT NOT NULL,
            image_url TEXT NOT NULL,
            link_url TEXT DEFAULT '',
            sort_order INTEGER DEFAULT 0,
            is_active INTEGER DEFAULT 1,
            min_deposit_usd REAL DEFAULT 0,
            prediction_text TEXT DEFAULT '',
            prediction_link TEXT DEFAULT '',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");

        // --- USER NOTIFICATIONS ---
        $db->exec("CREATE TABLE IF NOT EXISTS user_notifications (
            id SERIAL PRIMARY KEY,
            user_id INTEGER NOT NULL REFERENCES users(id),
            broadcast_id INTEGER NOT NULL REFERENCES broadcasts(id),
            is_read INTEGER DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");

        // --- UNIQUE constraint on user_notifications ---
        try {
            $db->exec("ALTER TABLE user_notifications ADD CONSTRAINT unq_user_broadcast UNIQUE (user_id, broadcast_id)");
        } catch (Exception $e) {
            // Constraint may already exist, ignore
        }

        // --- INDEX ---
        $db->exec("CREATE INDEX IF NOT EXISTS idx_users_referral_code ON users(referral_code)");
        $db->exec("CREATE INDEX IF NOT EXISTS idx_users_referred_by ON users(referred_by)");
        $db->exec("CREATE INDEX IF NOT EXISTS idx_referrals_referrer ON referrals(referrer_id)");
        $db->exec("CREATE INDEX IF NOT EXISTS idx_chat_messages_user ON chat_messages(user_id)");
        $db->exec("CREATE INDEX IF NOT EXISTS idx_postback_logs_event ON postback_logs(event)");
        $db->exec("CREATE INDEX IF NOT EXISTS idx_games_sort ON games(sort_order)");
        $db->exec("CREATE INDEX IF NOT EXISTS idx_user_notifications ON user_notifications(user_id, is_read)");
    }

    public static function migrate(): void {
        $db = self::getInstance();

        // s'assurer que la cle db_version existe
        try {
            $version = $db->query("SELECT value FROM admin_settings WHERE key = 'db_version'")->fetchColumn() ?: '0';
        } catch (Exception $e) {
            $version = '0';
        }

        if (version_compare($version, '1.0', '<')) {
            $db->prepare("INSERT INTO admin_settings (key, value) VALUES ('db_version', '1.0') ON CONFLICT (key) DO NOTHING")->execute();
        }

        if (version_compare($version, '1.1', '<')) {
            // scheduled_date, status, logos, report deja inclus dans le schema
            $db->prepare("INSERT INTO admin_settings (key, value) VALUES ('db_version', '1.1') ON CONFLICT (key) DO UPDATE SET value = '1.1'")->execute();
        }

        if (version_compare($version, '1.2', '<')) {
            $db->prepare("INSERT INTO admin_settings (key, value) VALUES ('social_telegram', 'https://t.me/dvys_ai') ON CONFLICT (key) DO NOTHING")->execute();
            $db->prepare("INSERT INTO admin_settings (key, value) VALUES ('social_whatsapp', 'https://wa.me/dvys_ai') ON CONFLICT (key) DO NOTHING")->execute();
            $db->prepare("INSERT INTO admin_settings (key, value) VALUES ('social_tiktok', 'https://tiktok.com/@dvys_ai') ON CONFLICT (key) DO NOTHING")->execute();
            $db->prepare("INSERT INTO admin_settings (key, value) VALUES ('db_version', '1.2') ON CONFLICT (key) DO UPDATE SET value = '1.2'")->execute();
        }

        if (version_compare($version, '1.3', '<')) {
            $db->prepare("INSERT INTO admin_settings (key, value) VALUES ('db_version', '1.3') ON CONFLICT (key) DO UPDATE SET value = '1.3'")->execute();
        }

        if (version_compare($version, '1.4', '<')) {
            // user_notifications, target_type, image_url deja dans le schema
            $db->prepare("INSERT INTO admin_settings (key, value) VALUES ('registration_link', 'https://lkts.pro/c32011') ON CONFLICT (key) DO NOTHING")->execute();
            $db->prepare("INSERT INTO admin_settings (key, value) VALUES ('db_version', '1.4') ON CONFLICT (key) DO UPDATE SET value = '1.4'")->execute();
        }

        if (version_compare($version, '1.5', '<')) {
            $db->prepare("INSERT INTO admin_settings (key, value) VALUES ('db_version', '1.5') ON CONFLICT (key) DO UPDATE SET value = '1.5'")->execute();
        }
    }
}

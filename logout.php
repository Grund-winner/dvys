<?php
/**
 * DVYS AI - Déconnexion
 */
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/database.php';
require_once __DIR__ . '/includes/auth.php';

if (session_status() === PHP_SESSION_NONE) session_start();

$auth = new Auth();
$auth->logout();

header('Location: /');
exit;

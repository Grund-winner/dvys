<?php
/**
 * DVYS AI - API Chat IA
 * Endpoint pour la conversation avec l'IA
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

if (session_status() === PHP_SESSION_NONE) session_start();

$auth = new Auth();

if (!$auth->isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

$userId = $_SESSION['user_id'];
$db = Database::getInstance();

// POST = envoyer un message
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $userMessage = trim($input['message'] ?? '');

    if (empty($userMessage) || mb_strlen($userMessage) > 500) {
        http_response_code(400);
        echo json_encode(['error' => 'Message invalide']);
        exit;
    }

    // Rate limiting : max 30 messages par heure
    $stmt = $db->prepare("SELECT COUNT(*) FROM chat_messages WHERE user_id = ? AND role = 'user' AND created_at >= CURRENT_TIMESTAMP - INTERVAL '1 hour'");
    $stmt->execute([$userId]);
    $hourlyCount = (int) $stmt->fetchColumn();
    
    if ($hourlyCount >= 30) {
        http_response_code(429);
        echo json_encode(['error' => 'Trop de messages. Attends un moment.']);
        exit;
    }

    // Sauvegarder le message utilisateur
    $stmt = $db->prepare("INSERT INTO chat_messages (user_id, role, content) VALUES (?, 'user', ?)");
    $stmt->execute([$userId, $userMessage]);

    // Construire le contexte pour l'IA
    $messages = [['role' => 'system', 'content' => getAiSystemPrompt()]];

    // Charger les 10 derniers messages de contexte
    $stmt = $db->prepare("SELECT role, content FROM chat_messages WHERE user_id = ? ORDER BY id DESC LIMIT 10");
    $stmt->execute([$userId]);
    $recent = array_reverse($stmt->fetchAll());
    foreach ($recent as $msg) {
        $messages[] = ['role' => $msg['role'], 'content' => $msg['content']];
    }

    // Appeler l'IA
    $aiResponse = callOpenAI($messages);

    if ($aiResponse === null) {
        $aiResponse = "Désolé, je suis temporairement indisponible. Réessaie dans quelques instants.";
    }

    // Sauvegarder la réponse IA
    $stmt = $db->prepare("INSERT INTO chat_messages (user_id, role, content) VALUES (?, 'assistant', ?)");
    $stmt->execute([$userId, $aiResponse]);

    // Nettoyer l'historique
    cleanChatHistory($userId);

    echo json_encode(['success' => true, 'response' => $aiResponse]);
    exit;
}

// GET = charger l'historique
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $stmt = $db->prepare("SELECT role, content, created_at FROM chat_messages WHERE user_id = ? ORDER BY id DESC LIMIT 20");
    $stmt->execute([$userId]);
    $messages = array_reverse($stmt->fetchAll());

    echo json_encode(['success' => true, 'messages' => $messages]);
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);

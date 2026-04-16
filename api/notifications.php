<?php
/**
 * DVYS AI - API Notifications
 * JSON endpoints for user notification bell
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

$auth = new Auth();
$db = Database::getInstance();

// Authentication required for all endpoints
if (!$auth->isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

$user = $auth->currentUser();
$userId = $user['id'];
$lang = getCurrentLang();

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? '';

    switch ($action) {
        case 'mark_read':
            $notifId = (int) ($input['id'] ?? 0);
            if ($notifId > 0) {
                $stmt = $db->prepare("UPDATE user_notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
                $stmt->execute([$notifId, $userId]);
                echo json_encode(['success' => true]);
            } else {
                http_response_code(400);
                echo json_encode(['error' => 'Invalid notification id']);
            }
            break;

        case 'mark_all_read':
            $stmt = $db->prepare("UPDATE user_notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0");
            $stmt->execute([$userId]);
            echo json_encode(['success' => true]);
            break;

        case 'delete':
            $notifId = (int) ($input['id'] ?? 0);
            if ($notifId > 0) {
                $stmt = $db->prepare("DELETE FROM user_notifications WHERE id = ? AND user_id = ?");
                $stmt->execute([$notifId, $userId]);
                echo json_encode(['success' => true]);
            } else {
                http_response_code(400);
                echo json_encode(['error' => 'Invalid notification id']);
            }
            break;

        default:
            http_response_code(400);
            echo json_encode(['error' => 'Unknown action']);
    }
    exit;
}

// Handle GET: list notifications for current user
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Get unread count
    $unreadCount = $db->prepare("SELECT COUNT(*) FROM user_notifications WHERE user_id = ? AND is_read = 0");
    $unreadCount->execute([$userId]);
    $unreadCountVal = (int) $unreadCount->fetchColumn();

    // Get notifications with broadcast details, unread first, limit 20
    $stmt = $db->prepare("
        SELECT 
            un.id,
            un.is_read,
            un.created_at,
            b.title,
            b.messages,
            b.image_url,
            b.target_type
        FROM user_notifications un
        JOIN broadcasts b ON b.id = un.broadcast_id
        WHERE un.user_id = ?
        ORDER BY un.is_read ASC, un.created_at DESC
        LIMIT 20
    ");
    $stmt->execute([$userId]);
    $rows = $stmt->fetchAll();

    $notifications = [];
    foreach ($rows as $row) {
        $messages = json_decode($row['messages'], true) ?: [];
        // Get message in user's language, fallback to French
        $message = $messages[$lang] ?? $messages['fr'] ?? '';

        $notifications[] = [
            'id' => (int) $row['id'],
            'title' => $row['title'] ?? '',
            'message' => $message,
            'image_url' => $row['image_url'] ?? '',
            'created_at' => $row['created_at'] ?? '',
            'is_read' => (bool) $row['is_read'],
        ];
    }

    echo json_encode([
        'success' => true,
        'unread_count' => $unreadCountVal,
        'notifications' => $notifications,
    ]);
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);

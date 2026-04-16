<?php
/**
 * DVYS AI - Chat IA
 * Interface de conversation avec l'agent IA
 */
require_once __DIR__ . '/includes/header.php';

$lang = getCurrentLang();
$userId = $user['id'];
$db = Database::getInstance();

// Charger l'historique des 20 derniers messages
$stmt = $db->prepare("SELECT role, content, created_at FROM chat_messages WHERE user_id = ? ORDER BY id DESC LIMIT 20");
$stmt->execute([$userId]);
$historyRows = $stmt->fetchAll();
$history = array_reverse($historyRows);
?>

<div class="chat-container">
    <div class="chat-messages" id="chatMessages">
        <?php if (empty($history)): ?>
        <div class="chat-msg assistant">
            <div class="chat-avatar">✦</div>
            <div class="chat-bubble"><?= e(t('chat_welcome', $lang)) ?></div>
        </div>
        <?php else: ?>
            <?php foreach ($history as $msg): ?>
            <div class="chat-msg <?= e($msg['role']) ?>">
                <div class="chat-avatar"><?= $msg['role'] === 'assistant' ? '✦' : e(mb_substr($user['username'], 0, 1)) ?></div>
                <div class="chat-bubble"><?= nl2br(e($msg['content'])) ?></div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <div class="chat-input-area">
        <div class="chat-input-wrapper">
            <textarea class="chat-input" id="chatInput" placeholder="<?= e(t('chat_placeholder', $lang)) ?>" rows="1" maxlength="500"></textarea>
            <button class="chat-send" id="chatSend" aria-label="Envoyer">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
            </button>
        </div>
    </div>
</div>

<script src="/assets/js/chat.js"></script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>

<?php
/* POST CX/backend/user/message_send.php   body: { chat_id, message }
 * Sent messages can never be edited or deleted by users (evidence rule). */
require_once __DIR__ . '/_user_common.php';
cx_require_method('POST');

$data = cx_read_json();
$chatId = cx_int($data['chat_id'] ?? 0);
$text = trim((string)($data['message'] ?? ''));

if ($chatId <= 0) {
    cx_json_response(false, 'Invalid chat.', [], 422);
}
if ($text === '') {
    cx_json_response(false, 'Type a message first.', [], 422);
}
if (mb_strlen($text) > 2000) {
    cx_json_response(false, 'Message is too long (max 2000 characters).', [], 422);
}

$chat = cx_fetch_one(
    $conn,
    "SELECT c.chat_id, c.buyer_id, c.seller_id, p.title
     FROM Chats c INNER JOIN Products p ON p.product_id = c.product_id
     WHERE c.chat_id = ? AND (c.buyer_id = ? OR c.seller_id = ?) AND c.deleted_at IS NULL
     LIMIT 1",
    'iii',
    [$chatId, $userId, $userId]
);
if (!$chat) {
    cx_json_response(false, 'Conversation not found.', [], 404);
}

cx_exec(
    $conn,
    "INSERT INTO Messages (chat_id, sender_id, message_text, message_type)
     VALUES (?, ?, ?, 'Text')",
    'iis',
    [$chatId, $userId, $text]
);
$messageId = (int)$conn->insert_id;
cx_exec($conn, "UPDATE Chats SET updated_at = NOW() WHERE chat_id = ?", 'i', [$chatId]);

/* One unread "message" notification per chat is enough (no spam). */
$otherId = ((int)$chat['buyer_id'] === $userId) ? (int)$chat['seller_id'] : (int)$chat['buyer_id'];
$existing = cx_fetch_one(
    $conn,
    "SELECT notification_id FROM Notifications
     WHERE user_id = ? AND notification_type = 'Message'
       AND reference_type = 'Chat' AND reference_id = ?
       AND is_read = 0 AND deleted_at IS NULL LIMIT 1",
    'ii',
    [$otherId, $chatId]
);
if (!$existing) {
    cx_notify(
        $conn,
        $otherId,
        'Message',
        'New message',
        ($_SESSION['user_name'] ?? 'Someone') . ' sent you a message about "' . $chat['title'] . '".',
        'Chat',
        $chatId
    );
}

cx_json_response(true, 'Sent.', [
    'message' => [
        'id' => $messageId,
        'sender' => 'me',
        'text' => $text,
        'type' => 'Text',
        'attachment' => null,
        'sent_at' => date('Y-m-d H:i:s'),
        'status' => 'sent',      // server ACK: stored, not yet delivered
        'read' => false,
    ],
]);

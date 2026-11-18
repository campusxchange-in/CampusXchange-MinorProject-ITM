<?php
/* GET CX/backend/user/chat_messages.php?chat_id=5&after_id=0&status_from=0&mark_read=1
 * Messages of one chat (only the two participants may read it).
 *
 * Message life cycle (SYN / ACK):
 *   sent       - stored by message_send.php (server ACK to the sender)
 *   delivered  - the receiver's app fetched it (delivered_at)  -> this endpoint,
 *                chats_list.php, navbar.php or presence_ping.php
 *   read       - the receiver has the chat open and visible (read_at)
 *                -> only when mark_read=1; the app sends 0 while the tab is hidden
 *
 * status_from: lowest id of MY messages the app still shows as not "read".
 *   The reply then carries `statuses` for my messages from that id on, so the
 *   sender sees old messages turn delivered / read.
 */
require_once __DIR__ . '/_user_common.php';
require_once __DIR__ . '/_presence_helpers.php';
cx_require_method('GET');

$chatId = cx_int($_GET['chat_id'] ?? 0);
$afterId = max(0, cx_int($_GET['after_id'] ?? 0));
$statusFrom = max(0, cx_int($_GET['status_from'] ?? 0));
$markRead = ($_GET['mark_read'] ?? '1') === '1';
if ($chatId <= 0) {
    cx_json_response(false, 'Invalid chat.', [], 422);
}

$chat = cx_fetch_one(
    $conn,
    "SELECT chat_id FROM Chats
     WHERE chat_id = ? AND (buyer_id = ? OR seller_id = ?) AND deleted_at IS NULL LIMIT 1",
    'iii',
    [$chatId, $userId, $userId]
);
if (!$chat) {
    cx_json_response(false, 'Conversation not found.', [], 404);
}

$rows = cx_fetch_all(
    $conn,
    "SELECT message_id, sender_id, message_text, message_type, attachment_path, sent_at, delivered_at, read_at
     FROM Messages
     WHERE chat_id = ? AND message_id > ? AND deleted_at IS NULL
     ORDER BY message_id ASC
     LIMIT 500",
    'ii',
    [$chatId, $afterId]
);

/* delivery ack: this device has the messages */
cx_exec(
    $conn,
    "UPDATE Messages SET delivered_at = NOW()
     WHERE chat_id = ? AND sender_id <> ? AND delivered_at IS NULL AND deleted_at IS NULL",
    'ii',
    [$chatId, $userId]
);

/* read ack: only while the chat is open AND visible on screen */
if ($markRead) {
    cx_exec(
        $conn,
        "UPDATE Messages SET read_at = NOW()
         WHERE chat_id = ? AND sender_id <> ? AND read_at IS NULL AND deleted_at IS NULL",
        'ii',
        [$chatId, $userId]
    );
}

/* current delivered / read state of MY messages the app still shows as pending */
$statuses = [];
if ($statusFrom > 0) {
    $mine = cx_fetch_all(
        $conn,
        "SELECT message_id, delivered_at, read_at FROM Messages
         WHERE chat_id = ? AND sender_id = ? AND message_id >= ? AND deleted_at IS NULL
         ORDER BY message_id ASC LIMIT 500",
        'iii',
        [$chatId, $userId, $statusFrom]
    );
    foreach ($mine as $m) {
        $statuses[] = [
            'id' => (int)$m['message_id'],
            'status' => cx_message_status($m['delivered_at'], $m['read_at']),
        ];
    }
}

$messages = array_map(static fn(array $m): array => [
    'id' => (int)$m['message_id'],
    'sender' => ((int)$m['sender_id'] === $userId) ? 'me' : 'them',
    'text' => $m['message_text'],
    'type' => $m['message_type'],
    'attachment' => $m['attachment_path'] ? cx_upload_url($m['attachment_path'], 'messages') : null,
    'sent_at' => $m['sent_at'],
    'status' => cx_message_status($m['delivered_at'], $m['read_at']),
    'read' => $m['read_at'] !== null,
], $rows);

cx_json_response(true, 'OK', [
    'messages' => $messages,
    'statuses' => $statuses,
    'last_id' => $messages ? end($messages)['id'] : $afterId,
]);

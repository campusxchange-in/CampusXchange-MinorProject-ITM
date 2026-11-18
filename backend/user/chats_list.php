<?php
/* GET CX/backend/user/chats_list.php
 * Inbox: every product chat the user takes part in, newest activity first.
 * "online" comes from User_Presence (see _presence_helpers.php). This user's own
 * presence is refreshed by user_auth_check.php on every request. */
require_once __DIR__ . '/_user_common.php';
require_once __DIR__ . '/_presence_helpers.php';
cx_require_method('GET');

/* inbox is open on this device -> everything sent to the user is delivered */
cx_mark_all_delivered($conn, $userId);

$rows = cx_fetch_all(
    $conn,
    "SELECT c.chat_id, c.buyer_id, c.seller_id, c.updated_at,
            p.product_id, p.title, p.price, p.product_condition, p.status AS product_status,
            o.user_id AS other_id, o.full_name AS other_name,
            op.profile_picture AS other_picture,
            TIMESTAMPDIFF(SECOND, pr.last_seen_at, NOW()) AS seen_ago,
            (SELECT COUNT(*) FROM Messages m
              WHERE m.chat_id = c.chat_id AND m.sender_id <> ?
                AND m.read_at IS NULL AND m.deleted_at IS NULL) AS unread,
            (SELECT m2.message_text FROM Messages m2
              WHERE m2.chat_id = c.chat_id AND m2.deleted_at IS NULL
              ORDER BY m2.message_id DESC LIMIT 1) AS last_text,
            (SELECT m3.sent_at FROM Messages m3
              WHERE m3.chat_id = c.chat_id AND m3.deleted_at IS NULL
              ORDER BY m3.message_id DESC LIMIT 1) AS last_at
     FROM Chats c
     INNER JOIN Products p ON p.product_id = c.product_id
     INNER JOIN Users o ON o.user_id = IF(c.buyer_id = ?, c.seller_id, c.buyer_id)
     LEFT JOIN User_Profiles op ON op.user_id = o.user_id
     LEFT JOIN User_Presence pr ON pr.user_id = o.user_id
     WHERE (c.buyer_id = ? OR c.seller_id = ?) AND c.deleted_at IS NULL
     ORDER BY COALESCE(
        (SELECT MAX(m4.sent_at) FROM Messages m4 WHERE m4.chat_id = c.chat_id), c.created_at
     ) DESC",
    'iiii',
    [$userId, $userId, $userId, $userId]
);

$chats = array_map(static function (array $r) use ($userId): array {
    $seenAgo = $r['seen_ago'] !== null ? max(0, (int)$r['seen_ago']) : null;
    return [
        'id' => (int)$r['chat_id'],
        'type' => ((int)$r['buyer_id'] === $userId) ? 'buying' : 'selling',
        'other_id' => (int)$r['other_id'],
        'name' => $r['other_name'],
        'profile_picture' => cx_upload_url($r['other_picture'], 'user_profiles'),
        'online' => $seenAgo !== null && $seenAgo <= CX_ONLINE_WINDOW_SECONDS,
        'last_seen_ago' => $seenAgo,   // seconds, null = never seen
        'unread' => (int)$r['unread'],
        'product_id' => (int)$r['product_id'],
        'product' => $r['title'],
        'product_status' => $r['product_status'],
        'description' => $r['product_condition'],
        'price' => (float)$r['price'],
        'last_text' => $r['last_text'],
        'last_at' => $r['last_at'],
    ];
}, $rows);

cx_json_response(true, 'OK', ['chats' => $chats]);

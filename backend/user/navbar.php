<?php
/* GET  CX/backend/user/navbar.php
 * Data for the top navbar: name, avatar, unread notification + message counts. */
require_once __DIR__ . '/_user_common.php';
require_once __DIR__ . '/_presence_helpers.php';
cx_require_method('GET');

/* app is open on this device -> messages sent to the user are delivered */
cx_mark_all_delivered($conn, $userId);

$user = cx_fetch_one(
    $conn,
    "SELECT u.user_id, u.full_name, u.email, p.profile_picture, p.live_photo
     FROM Users u
     LEFT JOIN User_Profiles p ON p.user_id = u.user_id
     WHERE u.user_id = ? LIMIT 1",
    'i',
    [$userId]
);

$notif = cx_fetch_one(
    $conn,
    "SELECT COUNT(*) AS c FROM Notifications
     WHERE user_id = ? AND is_read = 0 AND deleted_at IS NULL",
    'i',
    [$userId]
);

$msgs = cx_fetch_one(
    $conn,
    "SELECT COUNT(*) AS c
     FROM Messages m
     INNER JOIN Chats c ON c.chat_id = m.chat_id
     WHERE (c.buyer_id = ? OR c.seller_id = ?)
       AND m.sender_id <> ?
       AND m.read_at IS NULL
       AND m.deleted_at IS NULL
       AND c.deleted_at IS NULL",
    'iii',
    [$userId, $userId, $userId]
);

cx_json_response(true, 'OK', [
    'user' => [
        'user_id' => (int)$user['user_id'],
        'full_name' => $user['full_name'],
        'email' => $user['email'],
        'profile_picture' => !empty($user['profile_picture'])
            ? cx_upload_url($user['profile_picture'], 'user_profiles')
            : (!empty($user['live_photo']) ? cx_upload_url($user['live_photo'], 'verification') : null),
    ],
    'unread_notifications' => (int)($notif['c'] ?? 0),
    'unread_messages' => (int)($msgs['c'] ?? 0),
]);

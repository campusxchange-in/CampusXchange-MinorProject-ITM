<?php
/* GET CX/backend/user/notifications_list.php
 * Latest 20 notifications + unread count for the navbar bell. */
require_once __DIR__ . '/_user_common.php';
cx_require_method('GET');

$rows = cx_fetch_all(
    $conn,
    "SELECT notification_id, notification_type, title, message,
            reference_type, reference_id, is_read, created_at
     FROM Notifications
     WHERE user_id = ? AND deleted_at IS NULL
     ORDER BY created_at DESC, notification_id DESC
     LIMIT 20",
    'i',
    [$userId]
);

$unread = cx_fetch_one($conn,
    "SELECT COUNT(*) AS c FROM Notifications WHERE user_id = ? AND is_read = 0 AND deleted_at IS NULL",
    'i', [$userId]);

/* Where the app should send the user when the notification is clicked */
$route = static function (?string $type, ?int $id): ?string {
    switch ($type) {
        case 'Chat':        return $id ? '/messages/' . $id : '/messages';
        case 'Product':     return $id ? '/buy/' . $id : '/browseproducts';
        case 'Transaction': return '/myorders';
        case 'Review':      return '/myorders';
        case 'User':        return '/profile';
        case 'Security':    return '/accountsecurity';
        default:            return null;
    }
};

cx_json_response(true, 'OK', [
    'unread' => (int)$unread['c'],
    'notifications' => array_map(static fn(array $r): array => [
        'id' => (int)$r['notification_id'],
        'type' => $r['notification_type'],
        'title' => $r['title'],
        'message' => $r['message'],
        'is_read' => (bool)$r['is_read'],
        'created_at' => $r['created_at'],
        'link' => $route($r['reference_type'], $r['reference_id'] !== null ? (int)$r['reference_id'] : null),
    ], $rows),
]);

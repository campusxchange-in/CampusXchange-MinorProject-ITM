<?php
/* POST CX/backend/user/notification_read.php   body: { id } or { all: true } */
require_once __DIR__ . '/_user_common.php';
cx_require_method('POST');

$d = cx_read_json();
if (!empty($d['all'])) {
    cx_exec($conn, "UPDATE Notifications SET is_read = 1, read_at = NOW() WHERE user_id = ? AND is_read = 0", 'i', [$userId]);
} else {
    $id = cx_int($d['id'] ?? 0);
    if ($id <= 0) cx_json_response(false, 'Invalid notification.', [], 422);
    cx_exec($conn, "UPDATE Notifications SET is_read = 1, read_at = NOW() WHERE notification_id = ? AND user_id = ? AND is_read = 0", 'ii', [$id, $userId]);
}
cx_json_response(true, 'OK');

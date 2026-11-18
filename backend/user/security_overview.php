<?php
/* GET CX/backend/user/security_overview.php
 * Recent login / security activity of the logged-in user. */
require_once __DIR__ . '/_user_common.php';
cx_require_method('GET');

$rows = cx_fetch_all(
    $conn,
    "SELECT activity_id, activity_type, title, description, created_at
     FROM Login_Security_Activity
     WHERE user_id = ?
     ORDER BY created_at DESC, activity_id DESC
     LIMIT 20",
    'i',
    [$userId]
);

cx_json_response(true, 'OK', ['activities' => array_map(static fn(array $r): array => [
    'id' => (int)$r['activity_id'],
    'type' => $r['activity_type'],
    'title' => $r['title'],
    'description' => $r['description'],
    'created_at' => $r['created_at'],
], $rows)]);

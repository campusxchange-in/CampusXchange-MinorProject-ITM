<?php
/*
 * Include after the user database connection is available.
 * This middleware returns JSON and stops execution for invalid sessions.
 */
require_once __DIR__ . '/../auth/_auth_common.php';

cx_start_user_session();
$conn = cx_db();

$userId = (int)($_SESSION['user_id'] ?? 0);
$sessionVersion = (int)($_SESSION['session_version'] ?? 0);

if ($userId <= 0 || empty($_SESSION['user_logged_in'])) {
    http_response_code(401);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => false, 'message' => 'Please log in.']);
    exit;
}

$stmt = $conn->prepare(
    "SELECT user_id, full_name, email, status, email_verified_at, session_version
     FROM Users WHERE user_id = ? AND deleted_at IS NULL LIMIT 1"
);
$stmt->bind_param('i', $userId);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (
    !$user ||
    $user['status'] !== 'Active' ||
    empty($user['email_verified_at']) ||
    (int)$user['session_version'] !== $sessionVersion
) {
    $_SESSION = [];
    session_destroy();
    http_response_code(401);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => false, 'message' => 'Your session is no longer valid. Please log in again.']);
    exit;
}

$_SESSION['user_name'] = $user['full_name'];
$_SESSION['user_email'] = $user['email'];

/* every authenticated request proves the user is active right now */
require_once __DIR__ . '/../user/_presence_helpers.php';
cx_presence_touch_throttled($conn, $userId);

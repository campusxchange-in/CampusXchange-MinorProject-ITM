<?php
require_once __DIR__ . '/_auth_common.php';
cx_require_method('GET');

cx_start_user_session();
if (!empty($_SESSION['user_logged_in']) && !empty($_SESSION['user_id'])) {
    cx_json_response(true, 'Session is active.', [
        'user' => [
            'user_id' => (int)$_SESSION['user_id'],
            'full_name' => $_SESSION['user_name'] ?? '',
            'email' => $_SESSION['user_email'] ?? ''
        ]
    ]);
}

$rawToken = $_COOKIE[cx_user_remember_cookie_name()] ?? '';
if (!preg_match('/^[a-f0-9]{64}$/i', $rawToken)) {
    cx_json_response(false, 'No active session.', [], 401);
}

$conn = cx_db();
$tokenHash = hash('sha256', $rawToken);
$stmt = $conn->prepare(
    "SELECT rt.remember_token_id, rt.user_id, u.full_name, u.email, u.status,
            u.email_verified_at, u.session_version
     FROM Remember_Tokens rt
     INNER JOIN Users u ON u.user_id = rt.user_id
     WHERE rt.token_hash = ?
       AND rt.revoked_at IS NULL
       AND rt.expires_at > NOW()
       AND u.deleted_at IS NULL
     LIMIT 1"
);
$stmt->bind_param('s', $tokenHash);
$stmt->execute();
$record = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$record || $record['status'] !== 'Active' || empty($record['email_verified_at'])) {
    cx_clear_remember_cookie();
    cx_json_response(false, 'Remembered session is invalid. Please log in again.', [], 401);
}

session_regenerate_id(true);
$_SESSION['user_id'] = (int)$record['user_id'];
$_SESSION['user_name'] = $record['full_name'];
$_SESSION['user_email'] = $record['email'];
$_SESSION['session_version'] = (int)$record['session_version'];
$_SESSION['user_logged_in'] = true;

$touch = $conn->prepare("UPDATE Remember_Tokens SET last_used_at = NOW() WHERE remember_token_id = ?");
$touch->bind_param('i', $record['remember_token_id']);
$touch->execute();
$touch->close();

cx_json_response(true, 'Session restored.', [
    'user' => [
        'user_id' => (int)$record['user_id'],
        'full_name' => $record['full_name'],
        'email' => $record['email']
    ]
]);

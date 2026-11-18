<?php
require_once __DIR__ . '/_auth_common.php';
cx_require_method('POST');

cx_start_user_session();
$conn = cx_db();

$userId = (int)($_SESSION['user_id'] ?? 0);
$rawToken = $_COOKIE[cx_user_remember_cookie_name()] ?? '';

if ($rawToken !== '' && preg_match('/^[a-f0-9]{64}$/i', $rawToken)) {
    $hash = hash('sha256', $rawToken);
    $revoke = $conn->prepare("UPDATE Remember_Tokens SET revoked_at = NOW() WHERE token_hash = ? AND revoked_at IS NULL");
    $revoke->bind_param('s', $hash);
    $revoke->execute();
    $revoke->close();
}
if ($userId > 0) {
    require_once __DIR__ . '/../user/_presence_helpers.php';
    cx_presence_set_offline($conn, $userId);   // show as offline immediately
    cx_log_security_activity(
        $conn,
        $userId,
        'Token Revoked',
        'User logged out',
        'User logged out and the current remember token was revoked.'
    );
}

$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', [
        'expires' => time() - 42000,
        'path' => $params['path'] ?: '/',
        'domain' => $params['domain'] ?? '',
        'secure' => (bool)$params['secure'],
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
}
session_destroy();
cx_clear_remember_cookie();

cx_json_response(true, 'Logged out successfully.');

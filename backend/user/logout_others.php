<?php
/* POST CX/backend/user/logout_others.php
 * Signs out every other device: bumps session_version and revokes remember tokens.
 * The current browser keeps its session. */
require_once __DIR__ . '/_user_common.php';
cx_require_method('POST');

$u = cx_fetch_one($conn, "SELECT session_version FROM Users WHERE user_id = ? LIMIT 1", 'i', [$userId]);
$newVersion = (int)($u['session_version'] ?? 1) + 1;

cx_exec($conn, "UPDATE Users SET session_version = ? WHERE user_id = ?", 'ii', [$newVersion, $userId]);
cx_exec($conn, "UPDATE Remember_Tokens SET revoked_at = NOW() WHERE user_id = ? AND revoked_at IS NULL", 'i', [$userId]);
$_SESSION['session_version'] = $newVersion;
cx_clear_remember_cookie();

cx_log_security_activity($conn, $userId, 'Logout All Devices', 'Logged out other devices', 'All other devices were signed out.');

cx_json_response(true, 'All other devices have been logged out.');

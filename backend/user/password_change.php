<?php
/* POST CX/backend/user/password_change.php
 * body: { current_password, new_password, confirm_password }
 * Changing the password signs out every OTHER device (session_version bump). */
require_once __DIR__ . '/_user_common.php';
cx_require_method('POST');

$d = cx_read_json();
$current = (string)($d['current_password'] ?? '');
$new = (string)($d['new_password'] ?? '');
$confirm = (string)($d['confirm_password'] ?? '');

if ($current === '') cx_json_response(false, 'Please enter your current password.', [], 422);
if (strlen($new) < 8) cx_json_response(false, 'New password must contain at least 8 characters.', [], 422);
if ($new !== $confirm) cx_json_response(false, 'New password and confirm password do not match.', [], 422);
if ($new === $current) cx_json_response(false, 'New password must be different from your current password.', [], 422);

$u = cx_fetch_one($conn, "SELECT password, session_version FROM Users WHERE user_id = ? LIMIT 1", 'i', [$userId]);
if (!$u || !password_verify($current, $u['password'])) {
    cx_json_response(false, 'Your current password is incorrect.', [], 403);
}

$hash = password_hash($new, PASSWORD_DEFAULT);
$newVersion = (int)$u['session_version'] + 1;

cx_exec($conn, "UPDATE Users SET password = ?, session_version = ? WHERE user_id = ?", 'sii', [$hash, $newVersion, $userId]);
cx_exec($conn, "UPDATE Remember_Tokens SET revoked_at = NOW() WHERE user_id = ? AND revoked_at IS NULL", 'i', [$userId]);
cx_clear_remember_cookie();
$_SESSION['session_version'] = $newVersion;   // this device stays signed in

cx_log_security_activity($conn, $userId, 'Password Changed', 'Password changed', 'Your password was changed from this account.');
cx_notify($conn, $userId, 'Security', 'Password changed', 'Your password was changed. Other devices were signed out.', 'Security', null);

cx_json_response(true, 'Password updated successfully.');

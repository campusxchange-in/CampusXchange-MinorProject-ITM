<?php
require_once __DIR__ . '/_auth_common.php';
cx_require_method('POST');

$conn = cx_db();
$data = cx_read_json();
$token = trim((string)($data['token'] ?? ''));
$password = (string)($data['password'] ?? '');
$confirmPassword = (string)($data['confirm_password'] ?? '');

if (!preg_match('/^[a-f0-9]{64}$/i', $token)) {
    cx_json_response(false, 'Invalid or expired password reset link.', [], 400);
}
if (strlen($password) < 8) {
    cx_json_response(false, 'Password must be at least 8 characters long.', [], 422);
}
if ($password !== $confirmPassword) {
    cx_json_response(false, 'Passwords do not match.', [], 422);
}

$conn->begin_transaction();
try {
    $stmt = $conn->prepare(
        "SELECT token_id, user_id, expires_at, used_at
         FROM Password_Reset_Tokens WHERE token = ? LIMIT 1 FOR UPDATE"
    );
    $stmt->bind_param('s', $token);
    $stmt->execute();
    $record = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$record || !empty($record['used_at']) || strtotime($record['expires_at']) < time()) {
        $conn->rollback();
        cx_json_response(false, 'This password reset link is invalid or has expired.', [], 400);
    }

    $userId = (int)$record['user_id'];
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $updateUser = $conn->prepare(
        "UPDATE Users SET password = ?, session_version = session_version + 1
         WHERE user_id = ? AND deleted_at IS NULL AND status = 'Active'"
    );
    $updateUser->bind_param('si', $hash, $userId);
    $updateUser->execute();
    $changed = $updateUser->affected_rows;
    $updateUser->close();

    if ($changed !== 1) {
        $conn->rollback();
        cx_json_response(false, 'This account cannot reset its password.', [], 403);
    }

    $tokenId = (int)$record['token_id'];
    $mark = $conn->prepare("UPDATE Password_Reset_Tokens SET used_at = NOW() WHERE token_id = ?");
    $mark->bind_param('i', $tokenId);
    $mark->execute();
    $mark->close();

    $invalidateOthers = $conn->prepare(
        "UPDATE Password_Reset_Tokens SET used_at = NOW()
         WHERE user_id = ? AND used_at IS NULL"
    );
    $invalidateOthers->bind_param('i', $userId);
    $invalidateOthers->execute();
    $invalidateOthers->close();

    $revoke = $conn->prepare(
        "UPDATE Remember_Tokens SET revoked_at = NOW()
         WHERE user_id = ? AND revoked_at IS NULL"
    );
    $revoke->bind_param('i', $userId);
    $revoke->execute();
    $revoke->close();

    cx_log_security_activity($conn, $userId, 'Password Changed', 'Password reset', 'Password changed through account recovery.');
    $conn->commit();

    cx_json_response(true, 'Password reset successfully. You can now log in with your new password.');
} catch (Throwable $e) {
    $conn->rollback();
    error_log('CX reset password error: ' . $e->getMessage());
    cx_json_response(false, 'Unable to reset your password right now.', [], 500);
}

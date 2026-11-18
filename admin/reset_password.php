<?php

session_start();
require_once __DIR__ . '/../config/app_config.php';
require_once __DIR__ . '/../server/database/db_connect.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: ' . FRONTEND_URL);
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Methods: POST, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

function admin_reset_response(int $status, array $payload): void
{
    http_response_code($status);
    echo json_encode($payload);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    admin_reset_response(405, ['success' => false, 'message' => 'Invalid request method.']);
}

$data = json_decode(file_get_contents('php://input'), true);
$token = trim($data['token'] ?? '');
$password = $data['password'] ?? '';
$confirmPassword = $data['confirm_password'] ?? '';

if (!preg_match('/^[a-f0-9]{64}$/', $token)) {
    admin_reset_response(400, ['success' => false, 'message' => 'This password reset link is invalid or has expired.']);
}

if (!is_string($password) || strlen($password) < 8 || strlen($password) > 20 || strlen($password) > 72) {
    admin_reset_response(422, ['success' => false, 'message' => 'Password must contain 8–20 characters.']);
}

if (!is_string($confirmPassword) || !hash_equals($password, $confirmPassword)) {
    admin_reset_response(422, ['success' => false, 'message' => 'The passwords do not match.']);
}

$tokenHash = hash('sha256', $token);

try {
    $conn->begin_transaction();

    $stmt = $conn->prepare("\n        SELECT t.token_id, t.admin_id\n        FROM Admin_Password_Reset_Tokens t\n        INNER JOIN Admins a ON a.admin_id = t.admin_id\n        WHERE t.token = ?\n          AND t.used_at IS NULL\n          AND t.expires_at > CURRENT_TIMESTAMP\n          AND a.status = 'Active'\n          AND a.deleted_at IS NULL\n        LIMIT 1\n        FOR UPDATE\n    ");

    if (!$stmt) {
        throw new RuntimeException('Could not validate reset token.');
    }

    $stmt->bind_param('s', $tokenHash);
    $stmt->execute();
    $reset = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$reset) {
        $conn->rollback();
        admin_reset_response(400, ['success' => false, 'message' => 'This password reset link is invalid, expired, or has already been used. Please request a new link.']);
    }

    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
    if (!$passwordHash) {
        throw new RuntimeException('Password hashing failed.');
    }

    $update = $conn->prepare("UPDATE Admins SET password = ?, updated_at = CURRENT_TIMESTAMP WHERE admin_id = ? AND status = 'Active' AND deleted_at IS NULL");
    if (!$update) {
        throw new RuntimeException('Could not update administrator password.');
    }
    $update->bind_param('si', $passwordHash, $reset['admin_id']);
    $update->execute();
    if ($update->affected_rows !== 1) {
        $update->close();
        throw new RuntimeException('Administrator password was not updated.');
    }
    $update->close();

    $mark = $conn->prepare("UPDATE Admin_Password_Reset_Tokens SET used_at = CURRENT_TIMESTAMP WHERE token_id = ? AND used_at IS NULL");
    if (!$mark) {
        throw new RuntimeException('Could not mark reset token as used.');
    }
    $mark->bind_param('i', $reset['token_id']);
    $mark->execute();
    $mark->close();

    // Revoke any other outstanding reset links for this admin.
    $revoke = $conn->prepare("DELETE FROM Admin_Password_Reset_Tokens WHERE admin_id = ? AND token_id <> ? AND used_at IS NULL");
    if ($revoke) {
        $revoke->bind_param('ii', $reset['admin_id'], $reset['token_id']);
        $revoke->execute();
        $revoke->close();
    }

    // Record the password reset without logging the token or password.
    $description = 'Administrator password was reset using the email recovery process.';
    $ip = $_SERVER['REMOTE_ADDR'] ?? null;
    $agent = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500);
    $log = $conn->prepare("\n        INSERT INTO Admin_Logs\n            (admin_id, action_type, action_description, target_type, target_id, ip_address, user_agent, created_at)\n        VALUES (?, 'Update', ?, 'Admin', ?, ?, ?, CURRENT_TIMESTAMP)\n    ");
    if ($log) {
        $log->bind_param('isiss', $reset['admin_id'], $description, $reset['admin_id'], $ip, $agent);
        $log->execute();
        $log->close();
    }

    $conn->commit();
    admin_reset_response(200, ['success' => true, 'message' => 'Your password has been reset successfully. You can now sign in with your new password.']);

} catch (Throwable $e) {
    try { $conn->rollback(); } catch (Throwable $ignored) {}
    error_log('Admin reset password error: ' . $e->getMessage());
    admin_reset_response(500, ['success' => false, 'message' => 'Unable to reset your password right now. Please request a new reset link and try again.']);
}

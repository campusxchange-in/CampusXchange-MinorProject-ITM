<?php

session_start();
require_once __DIR__ . '/../config/app_config.php';
require_once __DIR__ . '/../server/database/db_connect.php';
require_once __DIR__ . '/../server/helpers/email_helper.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: ' . FRONTEND_URL);
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Methods: POST, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

function admin_forgot_response(int $status, array $payload): void
{
    http_response_code($status);
    echo json_encode($payload);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    admin_forgot_response(405, ['success' => false, 'message' => 'Invalid request method.']);
}

$data = json_decode(file_get_contents('php://input'), true);
$email = strtolower(trim($data['email'] ?? ''));

if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 150) {
    admin_forgot_response(422, ['success' => false, 'message' => 'Please enter a valid email address.']);
}

// Always return the same public response to avoid revealing registered admin emails.
$genericResponse = [
    'success' => true,
    'message' => 'If an active administrator account exists for that email, a password reset link will be sent shortly.'
];

try {
    $stmt = $conn->prepare("\n        SELECT admin_id, full_name, email\n        FROM Admins\n        WHERE email = ? AND status = 'Active' AND deleted_at IS NULL\n        LIMIT 1\n    ");

    if (!$stmt) {
        throw new RuntimeException('Could not prepare administrator lookup.');
    }

    $stmt->bind_param('s', $email);
    $stmt->execute();
    $admin = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$admin) {
        admin_forgot_response(200, $genericResponse);
    }

    $rawToken = bin2hex(random_bytes(32));
    $tokenHash = hash('sha256', $rawToken);
    $expiresAt = date('Y-m-d H:i:s', time() + 3600);

    $conn->begin_transaction();

    // Only the newest unconsumed reset request remains valid.
    $delete = $conn->prepare("DELETE FROM Admin_Password_Reset_Tokens WHERE admin_id = ? AND used_at IS NULL");
    if (!$delete) {
        throw new RuntimeException('Could not invalidate previous reset tokens.');
    }
    $delete->bind_param('i', $admin['admin_id']);
    $delete->execute();
    $delete->close();

    $insert = $conn->prepare("\n        INSERT INTO Admin_Password_Reset_Tokens (admin_id, token, expires_at, created_at)\n        VALUES (?, ?, ?, CURRENT_TIMESTAMP)\n    ");
    if (!$insert) {
        throw new RuntimeException('Could not create password reset token.');
    }
    $insert->bind_param('iss', $admin['admin_id'], $tokenHash, $expiresAt);
    if (!$insert->execute()) {
        throw new RuntimeException('Could not save password reset token.');
    }
    $insert->close();
    $conn->commit();

    $resetLink = FRONTEND_URL . '/admin-reset-password?token=' . urlencode($rawToken);
    $sent = cx_send_password_reset($admin['email'], $admin['full_name'], $resetLink);

    if (!$sent) {
        error_log('Admin password reset email failed for admin_id=' . $admin['admin_id'] . ': ' . cx_mailer_last_error());
        $cleanup = $conn->prepare("DELETE FROM Admin_Password_Reset_Tokens WHERE admin_id = ? AND token = ? AND used_at IS NULL");
        if ($cleanup) {
            $cleanup->bind_param('is', $admin['admin_id'], $tokenHash);
            $cleanup->execute();
            $cleanup->close();
        }
    }

    admin_forgot_response(200, $genericResponse);

} catch (Throwable $e) {
    try { $conn->rollback(); } catch (Throwable $ignored) {}
    error_log('Admin forgot password error: ' . $e->getMessage());
    // Keep account-existence information private even on internal failures.
    admin_forgot_response(200, $genericResponse);
}

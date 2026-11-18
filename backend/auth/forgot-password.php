<?php
require_once __DIR__ . '/_auth_common.php';
cx_require_method('POST');

$conn = cx_db();
$data = cx_read_json();
$email = strtolower(trim((string)($data['email'] ?? '')));

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    cx_json_response(false, 'Enter a valid email address.', [], 422);
}

$stmt = $conn->prepare(
    "SELECT user_id, full_name, email FROM Users
     WHERE email = ? AND email_verified_at IS NOT NULL
       AND status = 'Active' AND deleted_at IS NULL
     LIMIT 1"
);
$stmt->bind_param('s', $email);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

/* Same response whether or not the account exists. */
if ($user) {
    $token = bin2hex(random_bytes(32));
    $expiresAt = date('Y-m-d H:i:s', time() + 3600);
    $userId = (int)$user['user_id'];

    $conn->begin_transaction();
    try {
        $invalidate = $conn->prepare(
            "UPDATE Password_Reset_Tokens SET used_at = NOW()
             WHERE user_id = ? AND used_at IS NULL"
        );
        $invalidate->bind_param('i', $userId);
        $invalidate->execute();
        $invalidate->close();

        $insert = $conn->prepare(
            "INSERT INTO Password_Reset_Tokens (user_id, token, expires_at)
             VALUES (?, ?, ?)"
        );
        $insert->bind_param('iss', $userId, $token, $expiresAt);
        $insert->execute();
        $insert->close();
        $conn->commit();
    } catch (Throwable $e) {
        $conn->rollback();
        error_log('CX forgot password DB error: ' . $e->getMessage());
        cx_json_response(false, 'Unable to process your request right now.', [], 500);
    }

    require_once __DIR__ . '/../../server/helpers/email_helper.php';
    $resetLink = rtrim(FRONTEND_URL, '/') . '/reset-password?token=' . urlencode($token);
    if (!cx_send_password_reset($user['email'], $user['full_name'], $resetLink)) {
        error_log('CX password reset email failed: ' . cx_mailer_last_error());
    }
}

cx_json_response(true, 'If an active verified account exists for that email, password reset instructions have been sent.');

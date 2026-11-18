<?php

require_once __DIR__ . '/../config/app_config.php';
require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/../server/database/db_connect.php';
require_once __DIR__ . '/../server/helpers/email_helper.php';

function account_email_redirect(string $type, string $message): void
{
    $_SESSION['account_alert'] = [
        'type' => $type,
        'message' => $message
    ];
    header('Location: account_security', true, 303);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: account_security', true, 303);
    exit;
}

$admin_id = (int)($_SESSION['admin_id'] ?? 0);
$csrf = $_POST['csrf_token'] ?? '';
$new_email = strtolower(trim($_POST['new_email'] ?? ''));
$current_password = $_POST['current_password'] ?? '';

if (!$admin_id) {
    header('Location: ' . FRONTEND_URL . '/admin-login');
    exit;
}

if (
    empty($_SESSION['csrf_token']) ||
    !is_string($csrf) ||
    !hash_equals($_SESSION['csrf_token'], $csrf)
) {
    account_email_redirect('error', 'Your session security check failed. Please try again.');
}

if (!filter_var($new_email, FILTER_VALIDATE_EMAIL) || strlen($new_email) > 150) {
    account_email_redirect('error', 'Please enter a valid email address.');
}

if ($current_password === '') {
    account_email_redirect('error', 'Please enter your current password.');
}

try {
    $conn->begin_transaction();

    $stmt = $conn->prepare("
        SELECT admin_id, full_name, email, password, status
        FROM Admins
        WHERE admin_id = ? AND deleted_at IS NULL
        LIMIT 1
        FOR UPDATE
    ");
    if (!$stmt) {
        throw new RuntimeException('Unable to load your account.');
    }

    $stmt->bind_param('i', $admin_id);
    $stmt->execute();
    $admin = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$admin || $admin['status'] !== 'Active') {
        throw new RuntimeException('Your administrator account is not active.');
    }

    if (!password_verify($current_password, $admin['password'])) {
        throw new RuntimeException('Your current password is incorrect.');
    }

    if (strcasecmp($new_email, $admin['email']) === 0) {
        throw new RuntimeException('The new email address is already your current email.');
    }

    $check = $conn->prepare("
        SELECT admin_id
        FROM Admins
        WHERE (LOWER(email) = LOWER(?) OR LOWER(pending_email) = LOWER(?))
          AND admin_id <> ?
          AND deleted_at IS NULL
        LIMIT 1
    ");
    if (!$check) {
        throw new RuntimeException('Unable to check email availability.');
    }

    $check->bind_param('ssi', $new_email, $new_email, $admin_id);
    $check->execute();
    $duplicate = $check->get_result()->fetch_assoc();
    $check->close();

    if ($duplicate) {
        throw new RuntimeException('This email address is already in use or pending verification by another admin.');
    }

    $token = bin2hex(random_bytes(32));
    $expires_at = date('Y-m-d H:i:s', time() + 86400);

    $update = $conn->prepare("
        UPDATE Admins
        SET pending_email = ?, updated_at = CURRENT_TIMESTAMP
        WHERE admin_id = ?
    ");
    if (!$update) {
        throw new RuntimeException('Unable to save the pending email address.');
    }

    $update->bind_param('si', $new_email, $admin_id);
    if (!$update->execute()) {
        throw new RuntimeException('Unable to save the pending email address.');
    }
    $update->close();

    // Invalidate previous unverified email-verification links for this admin.
    $invalidate = $conn->prepare("
        DELETE FROM Admin_Email_Verification_Tokens
        WHERE admin_id = ? AND verified_at IS NULL
    ");
    if (!$invalidate) {
        throw new RuntimeException('Unable to refresh verification tokens.');
    }
    $invalidate->bind_param('i', $admin_id);
    $invalidate->execute();
    $invalidate->close();

    $insert = $conn->prepare("
        INSERT INTO Admin_Email_Verification_Tokens
            (admin_id, email, token, expires_at, created_at)
        VALUES (?, ?, ?, ?, CURRENT_TIMESTAMP)
    ");
    if (!$insert) {
        throw new RuntimeException('Unable to create the verification request.');
    }
    $insert->bind_param('isss', $admin_id, $new_email, $token, $expires_at);
    if (!$insert->execute()) {
        throw new RuntimeException('Unable to create the verification request.');
    }
    $insert->close();

    $conn->commit();

    $verification_link = PHP_BASE_URL . '/admin/verify-admin-email-change?token=' . urlencode($token);
    $mail_sent = cx_send_verification_email($new_email, $admin['full_name'] ?? 'Administrator', $verification_link);

    if (!$mail_sent) {
        // Keep pending email and token so the request can be retried after diagnosing mail delivery.
        error_log('Admin email-change verification email failed for admin_id=' . $admin_id . ': ' . cx_mailer_last_error());
        account_email_redirect('error', 'Your email change request was saved, but the verification email could not be sent. Please try again or contact the system administrator.');
    }

    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    account_email_redirect('success', 'A verification link has been sent to your new email address. Your current email remains unchanged until verification.');
} catch (Throwable $e) {
    try {
        $conn->rollback();
    } catch (Throwable $ignored) {
    }

    error_log('Admin email update error: ' . $e->getMessage());
    $safe_message = $e instanceof RuntimeException
        ? $e->getMessage()
        : 'Something went wrong while requesting your email change. Please try again.';

    account_email_redirect('error', $safe_message);
}

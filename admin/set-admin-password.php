<?php
require_once __DIR__ . '/../config/app_config.php';
require_once __DIR__ . '/../server/database/db_connect.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Referrer-Policy: no-referrer');

function render_setup_error(string $message, string $token = ''): void
{
    $safeMessage = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');
    $safeToken = htmlspecialchars($token, ENT_QUOTES, 'UTF-8');
    $safeCsrf = htmlspecialchars($_SESSION['admin_verify_csrf'] ?? '', ENT_QUOTES, 'UTF-8');
    $base = htmlspecialchars(PHP_BASE_URL, ENT_QUOTES, 'UTF-8');

    echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8">';
    echo '<meta name="viewport" content="width=device-width, initial-scale=1.0">';
    echo '<title>Complete Admin Setup | CampusXchange</title>';
    echo '<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">';
    echo '<link rel="stylesheet" href="' . $base . '/admin/assets/css/admin-verification.css"></head><body>';
    echo '<main class="verification-shell"><section class="verification-card">';
    echo '<a class="verification-brand" href="' . htmlspecialchars(FRONTEND_URL, ENT_QUOTES, 'UTF-8') . '"><span class="verification-brand-mark"><i class="fa-solid fa-graduation-cap"></i></span><span>CampusXchange</span></a>';
    echo '<div class="verification-state verification-state-error"><div class="verification-state-icon"><i class="fa-solid fa-circle-exclamation"></i></div>';
    echo '<h1>We couldn’t complete setup</h1><p>' . $safeMessage . '</p>';
    echo '<a class="verification-primary-link" href="' . $base . '/admin/verify-admin-email?token=' . $safeToken . '">Return to password setup <i class="fa-solid fa-arrow-right"></i></a>';
    echo '</div><div class="verification-footer">© ' . date('Y') . ' CampusXchange. All rights reserved.</div></section></main></body></html>';
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . FRONTEND_URL . '/admin-login', true, 303);
    exit;
}

$token = trim($_POST['token'] ?? '');
$csrf = $_POST['csrf_token'] ?? '';
$password = $_POST['new_password'] ?? '';
$confirm = $_POST['confirm_password'] ?? '';

if (
    !is_string($csrf) ||
    empty($_SESSION['admin_verify_csrf']) ||
    !hash_equals($_SESSION['admin_verify_csrf'], $csrf)
) {
    render_setup_error('Your security session expired. Open the invitation link again and retry.', $token);
}

if (!preg_match('/\A[a-f0-9]{64}\z/', $token)) {
    render_setup_error('This invitation link is invalid. Ask the Main Admin to send a new invitation.');
}

if (!is_string($password) || strlen($password) < 8 || strlen($password) > 72) {
    render_setup_error('Your password must be at least 8 characters and no more than 72 bytes.', $token);
}

if (!is_string($confirm) || !hash_equals($password, $confirm)) {
    render_setup_error('The passwords do not match. Return to the form and enter matching passwords.', $token);
}

$password_hash = password_hash($password, PASSWORD_DEFAULT);
if ($password_hash === false) {
    render_setup_error('We could not secure your password. Please try again.', $token);
}

try {
    $conn->begin_transaction();

    $stmt = $conn->prepare("
        SELECT
            t.verification_token_id,
            t.admin_id,
            t.email,
            t.expires_at,
            t.verified_at,
            a.email_verified_at,
            a.status,
            a.deleted_at
        FROM Admin_Email_Verification_Tokens t
        INNER JOIN Admins a ON a.admin_id = t.admin_id
        WHERE t.token = ?
        LIMIT 1
        FOR UPDATE
    ");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $record = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (
        !$record ||
        !empty($record['verified_at']) ||
        !empty($record['email_verified_at']) ||
        strtotime($record['expires_at']) < time() ||
        $record['status'] !== 'Active' ||
        !empty($record['deleted_at'])
    ) {
        $conn->rollback();
        render_setup_error('This invitation is invalid, expired, already used, or no longer active. Ask the Main Admin for a new invitation.', $token);
    }

    $admin_id = (int) $record['admin_id'];
    $token_id = (int) $record['verification_token_id'];

    $update_admin = $conn->prepare("
        UPDATE Admins
        SET password = ?, email_verified_at = NOW(), updated_at = NOW()
        WHERE admin_id = ? AND email_verified_at IS NULL
    ");
    $update_admin->bind_param("si", $password_hash, $admin_id);
    $update_admin->execute();

    if ($update_admin->affected_rows !== 1) {
        $update_admin->close();
        $conn->rollback();
        render_setup_error('This administrator account has already been activated or is unavailable.', $token);
    }
    $update_admin->close();

    $update_token = $conn->prepare("
        UPDATE Admin_Email_Verification_Tokens
        SET verified_at = NOW()
        WHERE verification_token_id = ? AND verified_at IS NULL
    ");
    $update_token->bind_param("i", $token_id);
    $update_token->execute();
    $update_token->close();

    $conn->commit();

    unset($_SESSION['admin_verify_csrf']);

    header('Location: ' . FRONTEND_URL . '/admin-login?verified=1', true, 303);
    exit;

} catch (Throwable $e) {
    try {
        $conn->rollback();
    } catch (Throwable $rollback_error) {
        error_log('Admin Password Setup Rollback Error: ' . $rollback_error->getMessage());
    }

    error_log('Admin Password Setup Error: ' . $e->getMessage());
    render_setup_error('A server error prevented account setup. Please try again or ask the Main Admin for help.', $token);
}

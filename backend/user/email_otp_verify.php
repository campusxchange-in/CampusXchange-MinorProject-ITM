<?php
/* POST CX/backend/user/email_otp_verify.php   body: { otp }
 * Checks the 6-digit code; on success the pending email becomes the verified
 * account email. Max 5 wrong attempts per OTP. */
require_once __DIR__ . '/_user_common.php';
cx_require_method('POST');

$d = cx_read_json();
$otp = preg_replace('/\D/', '', (string)($d['otp'] ?? ''));
if (strlen($otp) !== 6) cx_json_response(false, 'Please enter a valid 6-digit OTP.', [], 422);

$u = cx_fetch_one($conn, "SELECT user_id, email, pending_email FROM Users WHERE user_id = ? LIMIT 1", 'i', [$userId]);
if (!$u || empty($u['pending_email'])) {
    cx_json_response(false, 'No email change is waiting for verification. Send a new OTP.', [], 409);
}
$newEmail = strtolower($u['pending_email']);

$row = cx_fetch_one($conn,
    "SELECT verification_token_id, token, expires_at FROM Email_Verification_Tokens
     WHERE user_id = ? AND email = ? AND token LIKE 'otp-%' AND verified_at IS NULL
     ORDER BY verification_token_id DESC LIMIT 1",
    'is', [$userId, $newEmail]);

if (!$row || strtotime($row['expires_at']) < time()) {
    cx_json_response(false, 'This OTP has expired. Please request a new one.', [], 410);
}

$attemptKey = 'otp_attempts_' . $row['verification_token_id'];
$attempts = (int)($_SESSION[$attemptKey] ?? 0);
if ($attempts >= 5) {
    cx_exec($conn, "UPDATE Email_Verification_Tokens SET expires_at = NOW() WHERE verification_token_id = ?", 'i', [(int)$row['verification_token_id']]);
    cx_json_response(false, 'Too many wrong attempts. Please request a new OTP.', [], 429);
}

$expected = 'otp-' . hash('sha256', $userId . '|' . $newEmail . '|' . $otp);
if (!hash_equals($row['token'], $expected)) {
    $_SESSION[$attemptKey] = $attempts + 1;
    cx_json_response(false, 'Invalid OTP. Please try again.', [], 422);
}

$conn->begin_transaction();
try {
    $taken = cx_fetch_one($conn, "SELECT user_id FROM Users WHERE email = ? AND user_id <> ? LIMIT 1", 'si', [$newEmail, $userId]);
    if ($taken) {
        $conn->rollback();
        cx_json_response(false, 'This email address is already used by another account.', [], 409);
    }
    cx_exec($conn,
        "UPDATE Users SET email = ?, pending_email = NULL, email_verified_at = NOW() WHERE user_id = ?",
        'si', [$newEmail, $userId]);
    cx_exec($conn, "UPDATE Email_Verification_Tokens SET verified_at = NOW() WHERE verification_token_id = ?",
        'i', [(int)$row['verification_token_id']]);
    $conn->commit();
} catch (Throwable $e) {
    $conn->rollback();
    error_log('email_otp_verify failed: ' . $e->getMessage());
    cx_json_response(false, 'Could not verify your email. Please try again.', [], 500);
}

unset($_SESSION[$attemptKey]);
cx_log_security_activity($conn, $userId, 'Email Changed', 'Email address changed', 'Your account email was changed to ' . $newEmail . '.');
cx_notify($conn, $userId, 'Security', 'Email verified', 'Your email address was verified and updated.', 'Security', null);

cx_json_response(true, 'Email verified successfully.', ['email' => $newEmail]);

<?php
/* POST CX/backend/user/email_otp_send.php   body: { new_email, password }
 * Sends a 6-digit OTP (PrivateMailer) to the NEW address. The address only becomes
 * the account's verified email after email_otp_verify.php succeeds.
 * OTP storage: Email_Verification_Tokens (token = "otp-" + sha256 hash, never the code). */
require_once __DIR__ . '/_user_common.php';
require_once __DIR__ . '/../../server/helpers/email_helper.php';
cx_require_method('POST');

$d = cx_read_json();
$newEmail = strtolower(trim((string)($d['new_email'] ?? '')));
$password = (string)($d['password'] ?? '');

if (!filter_var($newEmail, FILTER_VALIDATE_EMAIL) || strlen($newEmail) > 150) {
    cx_json_response(false, 'Please enter a valid email address.', [], 422);
}

$u = cx_fetch_one($conn,
    "SELECT user_id, full_name, email, password, email_verified_at FROM Users WHERE user_id = ? LIMIT 1",
    'i', [$userId]);
if (!$u) cx_json_response(false, 'Account not found.', [], 404);

if ($newEmail === strtolower($u['email']) && !empty($u['email_verified_at'])) {
    cx_json_response(false, 'This email address is already verified on your account.', [], 409);
}
if ($password === '' || !password_verify($password, $u['password'])) {
    cx_json_response(false, 'Your password is incorrect.', [], 403);
}

$taken = cx_fetch_one($conn,
    "SELECT user_id FROM Users WHERE (email = ? OR pending_email = ?) AND user_id <> ? LIMIT 1",
    'ssi', [$newEmail, $newEmail, $userId]);
if ($taken) cx_json_response(false, 'This email address is already used by another account.', [], 409);

/* at most 3 OTPs per 10 minutes */
$recent = cx_fetch_one($conn,
    "SELECT COUNT(*) AS c FROM Email_Verification_Tokens
     WHERE user_id = ? AND token LIKE 'otp-%' AND created_at > (NOW() - INTERVAL 10 MINUTE)",
    'i', [$userId]);
if ((int)$recent['c'] >= 3) {
    cx_json_response(false, 'Too many OTP requests. Please wait a few minutes and try again.', [], 429);
}

$otp = (string)random_int(100000, 999999);
$token = 'otp-' . hash('sha256', $userId . '|' . $newEmail . '|' . $otp);
$expires = date('Y-m-d H:i:s', time() + 600);

$conn->begin_transaction();
try {
    /* older unused OTPs stop working */
    cx_exec($conn, "UPDATE Email_Verification_Tokens SET expires_at = NOW()
                    WHERE user_id = ? AND token LIKE 'otp-%' AND verified_at IS NULL", 'i', [$userId]);
    cx_exec($conn, "INSERT INTO Email_Verification_Tokens (user_id, email, token, expires_at) VALUES (?, ?, ?, ?)",
        'isss', [$userId, $newEmail, $token, $expires]);
    $tokenId = (int)$conn->insert_id;
    cx_exec($conn, "UPDATE Users SET pending_email = ? WHERE user_id = ?", 'si', [$newEmail, $userId]);
    $conn->commit();
} catch (Throwable $e) {
    $conn->rollback();
    error_log('email_otp_send failed: ' . $e->getMessage());
    cx_json_response(false, 'Could not start email verification. Please try again.', [], 500);
}

if (!cx_send_otp($newEmail, $u['full_name'], $otp)) {
    error_log('CX OTP email failed: ' . cx_mailer_last_error());
    cx_exec($conn, "UPDATE Email_Verification_Tokens SET expires_at = NOW() WHERE verification_token_id = ?", 'i', [$tokenId]);
    cx_exec($conn, "UPDATE Users SET pending_email = NULL WHERE user_id = ?", 'i', [$userId]);
    cx_json_response(false, 'We could not send the OTP email right now. Please try again later.', [], 502);
}

$_SESSION['otp_attempts'] = 0;
cx_json_response(true, 'OTP has been sent to ' . $newEmail . '. It is valid for 10 minutes.', ['email' => $newEmail]);

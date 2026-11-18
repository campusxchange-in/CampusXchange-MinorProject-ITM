<?php
require_once __DIR__ . '/_auth_common.php';
cx_require_method('POST');

$conn = cx_db();
$data = cx_read_json();

$name = trim((string)($data['full_name'] ?? ''));
$email = strtolower(trim((string)($data['email'] ?? '')));
$phone = trim((string)($data['phone'] ?? ''));
$role = trim((string)($data['role'] ?? ''));
$password = (string)($data['password'] ?? '');
$confirmPassword = (string)($data['confirm_password'] ?? '');

if ($name === '' || $email === '' || $password === '' || $confirmPassword === '') {
    cx_json_response(false, 'Please complete all required fields.', [], 422);
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    cx_json_response(false, 'Enter a valid email address.', [], 422);
}
if (!in_array($role, ['Student', 'Faculty'], true)) {
    cx_json_response(false, 'Choose a valid account role.', [], 422);
}
if ($password !== $confirmPassword) {
    cx_json_response(false, 'Passwords do not match.', [], 422);
}
if (strlen($password) < 8) {
    cx_json_response(false, 'Password must be at least 8 characters long.', [], 422);
}
if ($phone !== '' && !preg_match('/^[0-9+\-\s()]{7,15}$/', $phone)) {
    cx_json_response(false, 'Enter a valid phone number.', [], 422);
}

$check = $conn->prepare("SELECT user_id FROM Users WHERE email = ? OR (? <> '' AND phone = ?) LIMIT 1");
$check->bind_param('sss', $email, $phone, $phone);
$check->execute();
$exists = $check->get_result()->fetch_assoc();
$check->close();

if ($exists) {
    cx_json_response(false, 'An account already exists with that email or phone number.', [], 409);
}

$passwordHash = password_hash($password, PASSWORD_DEFAULT);
$token = bin2hex(random_bytes(32));
$expiresAt = date('Y-m-d H:i:s', time() + 86400);

$conn->begin_transaction();

try {
    $stmt = $conn->prepare(
        "INSERT INTO Users (full_name, email, phone, role, password, status)
         VALUES (?, ?, NULLIF(?, ''), ?, ?, 'Pending')"
    );
    $stmt->bind_param('sssss', $name, $email, $phone, $role, $passwordHash);
    $stmt->execute();
    $userId = (int)$conn->insert_id;
    $stmt->close();

    $profile = $conn->prepare("INSERT INTO User_Profiles (user_id) VALUES (?)");
    $profile->bind_param('i', $userId);
    $profile->execute();
    $profile->close();

    $verification = $conn->prepare(
        "INSERT INTO Email_Verification_Tokens (user_id, email, token, expires_at)
         VALUES (?, ?, ?, ?)"
    );
    $verification->bind_param('isss', $userId, $email, $token, $expiresAt);
    $verification->execute();
    $verification->close();

    $conn->commit();
} catch (Throwable $e) {
    $conn->rollback();
    error_log('CX signup error: ' . $e->getMessage());
    cx_json_response(false, 'Unable to create your account right now.', [], 500);
}

require_once __DIR__ . '/../../server/helpers/email_helper.php';
$verificationLink = rtrim(FRONTEND_URL, '/') . '/verify-email?token=' . urlencode($token);

if (!cx_send_verification_email($email, $name, $verificationLink)) {
    error_log('CX verification email failed: ' . cx_mailer_last_error());
    cx_json_response(
        true,
        'Your account was created, but the verification email could not be sent. Please contact support or request a new verification email.',
        ['email_verification_sent' => false],
        201
    );
}

cx_json_response(
    true,
    'Account created. Please check your email and verify your email address before logging in.',
    ['email_verification_sent' => true],
    201
);

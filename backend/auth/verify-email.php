<?php
require_once __DIR__ . '/_auth_common.php';

if (!in_array($_SERVER['REQUEST_METHOD'] ?? '', ['GET', 'POST'], true)) {
    cx_json_response(false, 'Method not allowed.', [], 405);
}

$conn = cx_db();
$data = $_SERVER['REQUEST_METHOD'] === 'POST' ? cx_read_json() : [];
$token = trim((string)($data['token'] ?? $_GET['token'] ?? ''));

if (!preg_match('/^[a-f0-9]{64}$/i', $token)) {
    cx_json_response(false, 'Invalid verification link.', [], 400);
}

$conn->begin_transaction();

try {
    $stmt = $conn->prepare(
        "SELECT verification_token_id, user_id, email, expires_at, verified_at
         FROM Email_Verification_Tokens
         WHERE token = ? LIMIT 1 FOR UPDATE"
    );
    $stmt->bind_param('s', $token);
    $stmt->execute();
    $record = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$record) {
        $conn->rollback();
        cx_json_response(false, 'This verification link is invalid or has expired.', [], 400);
    }
    if (!empty($record['verified_at'])) {
        $conn->commit();
        cx_json_response(true, 'Your email address has already been verified.');
    }
    if (strtotime($record['expires_at']) < time()) {
        $conn->rollback();
        cx_json_response(false, 'This verification link has expired. Please request a new one.', [], 410);
    }

    $userId = (int)$record['user_id'];
    $userStmt = $conn->prepare(
        "SELECT email, status, deleted_at FROM Users WHERE user_id = ? LIMIT 1 FOR UPDATE"
    );
    $userStmt->bind_param('i', $userId);
    $userStmt->execute();
    $user = $userStmt->get_result()->fetch_assoc();
    $userStmt->close();

    if (!$user || !empty($user['deleted_at']) || $user['email'] !== $record['email']) {
        $conn->rollback();
        cx_json_response(false, 'This verification link is no longer valid.', [], 400);
    }
    if (in_array($user['status'], ['Suspended', 'Banned'], true)) {
        $conn->rollback();
        cx_json_response(false, 'This account cannot be activated. Please contact support.', [], 403);
    }

    $updateUser = $conn->prepare(
        "UPDATE Users SET email_verified_at = COALESCE(email_verified_at, NOW()), status = 'Active'
         WHERE user_id = ? AND status = 'Pending'"
    );
    $updateUser->bind_param('i', $userId);
    $updateUser->execute();
    $updateUser->close();

    $updateToken = $conn->prepare(
        "UPDATE Email_Verification_Tokens SET verified_at = NOW() WHERE verification_token_id = ?"
    );
    $tokenId = (int)$record['verification_token_id'];
    $updateToken->bind_param('i', $tokenId);
    $updateToken->execute();
    $updateToken->close();

    $conn->commit();
    cx_json_response(true, 'Email verified successfully. You can now log in.');
} catch (Throwable $e) {
    $conn->rollback();
    error_log('CX email verification error: ' . $e->getMessage());
    cx_json_response(false, 'Unable to verify your email right now.', [], 500);
}

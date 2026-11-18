
<?php
require_once __DIR__ . '/_auth_common.php';
cx_require_method('POST');

$conn = cx_db();
$data = cx_read_json();

$email = strtolower(trim((string)($data['email'] ?? '')));
$password = (string)($data['password'] ?? '');

$rememberMe = filter_var(
    $data['remember_me'] ?? false,
    FILTER_VALIDATE_BOOLEAN
);

if ($email === '' || $password === '') {
    cx_json_response(false, 'Enter your email and password.', [], 422);
}

$stmt = $conn->prepare(
    "SELECT user_id, full_name, email, password,
            email_verified_at, status, session_version
     FROM Users
     WHERE email = ? AND deleted_at IS NULL
     LIMIT 1"
);

$stmt->bind_param('s', $email);
$stmt->execute();

$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user || !password_verify($password, $user['password'])) {
    cx_json_response(false, 'Invalid email or password.', [], 401);
}

if (empty($user['email_verified_at'])) {
    cx_json_response(
        false,
        'Please verify your email address before logging in.',
        ['code' => 'EMAIL_NOT_VERIFIED'],
        403
    );
}

if ($user['status'] === 'Suspended') {
    cx_json_response(
        false,
        'Your account is suspended. Please contact support.',
        [],
        403
    );
}

if ($user['status'] === 'Banned') {
    cx_json_response(
        false,
        'Your account has been banned. Please contact support.',
        [],
        403
    );
}

if ($user['status'] !== 'Active') {
    cx_json_response(
        false,
        'Your account is not active. Please contact support.',
        [],
        403
    );
}

/* =========================================
   START USER SESSION
========================================= */

cx_start_user_session();

session_regenerate_id(true);

$_SESSION['user_id'] = (int)$user['user_id'];
$_SESSION['user_name'] = $user['full_name'];
$_SESSION['user_email'] = $user['email'];
$_SESSION['session_version'] = (int)$user['session_version'];
$_SESSION['user_logged_in'] = true;

/* =========================================
   HANDLE EXISTING REMEMBER TOKEN
========================================= */

$existingToken = $_COOKIE[cx_user_remember_cookie_name()] ?? '';

if (
    $existingToken !== '' &&
    preg_match('/^[a-f0-9]{64}$/i', $existingToken)
) {
    $existingHash = hash('sha256', $existingToken);

    $revokeOld = $conn->prepare(
        "UPDATE Remember_Tokens
         SET revoked_at = NOW()
         WHERE token_hash = ?
           AND revoked_at IS NULL"
    );

    $revokeOld->bind_param('s', $existingHash);
    $revokeOld->execute();
    $revokeOld->close();
}

/* =========================================
   REMEMBER ME
========================================= */

if ($rememberMe) {

    $rawToken = bin2hex(random_bytes(32));
    $tokenHash = hash('sha256', $rawToken);

    $expiresUnix = time() + (30 * 86400);
    $expiresAt = date('Y-m-d H:i:s', $expiresUnix);

    $userId = (int)$user['user_id'];

    $tokenStmt = $conn->prepare(
        "INSERT INTO Remember_Tokens
            (user_id, token_hash, expires_at)
         VALUES (?, ?, ?)"
    );

    if (!$tokenStmt) {
        error_log('Remember token prepare error: ' . $conn->error);
        cx_json_response(
            false,
            'Unable to complete login right now.',
            [],
            500
        );
    }

    $tokenStmt->bind_param(
        'iss',
        $userId,
        $tokenHash,
        $expiresAt
    );

    if (!$tokenStmt->execute()) {
        error_log('Remember token insert error: ' . $tokenStmt->error);
        $tokenStmt->close();

        cx_json_response(
            false,
            'Unable to complete login right now.',
            [],
            500
        );
    }

    $tokenStmt->close();

    cx_set_remember_cookie($rawToken, $expiresUnix);

} else {

    // Remember Me was unchecked.
    // Remove any old remember cookie.

    cx_clear_remember_cookie();
}

/* =========================================
   SECURITY ACTIVITY
========================================= */

cx_log_security_activity(
    $conn,
    (int)$user['user_id'],
    'Login',
    'Successful login',
    $rememberMe
        ? 'User logged in with Remember Me enabled.'
        : 'User logged in without Remember Me.'
);

/* =========================================
   RESPONSE
========================================= */

cx_json_response(true, 'Login successful.', [
    'user' => [
        'user_id' => (int)$user['user_id'],
        'full_name' => $user['full_name'],
        'email' => $user['email']
    ],
    'remember_me' => $rememberMe
]);

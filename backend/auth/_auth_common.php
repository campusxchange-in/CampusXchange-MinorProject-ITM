<?php
declare(strict_types=1);

/*
 * Shared JSON, CORS, database, and session helpers for CampusXchange user auth.
 * Location: CX/backend/auth/_auth_common.php
 */

require_once __DIR__ . '/../../config/app_config.php';

date_default_timezone_set('Asia/Kolkata');

$allowedOrigin = FRONTEND_URL;
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';

if ($origin === $allowedOrigin) {
    header("Access-Control-Allow-Origin: {$allowedOrigin}");
    header('Access-Control-Allow-Credentials: true');
    header('Vary: Origin');
}
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');
header('Content-Type: application/json; charset=utf-8');

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
    http_response_code(204);
    exit;
}

function cx_json_response(bool $success, string $message, array $extra = [], int $status = 200): never
{
    http_response_code($status);
    echo json_encode(
        array_merge(['success' => $success, 'message' => $message], $extra),
        JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
    );
    exit;
}

function cx_read_json(): array
{
    $raw = file_get_contents('php://input');
    if ($raw === false || trim($raw) === '') {
        return [];
    }

    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

function cx_require_method(string $method): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== strtoupper($method)) {
        cx_json_response(false, 'Method not allowed.', [], 405);
    }
}

function cx_start_user_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    $https = (
        (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
        (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https')
    );

    session_name('CX_USER_SESSION');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => $https,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
}

function cx_db()
{
    static $connection = null;

    if ($connection instanceof mysqli) {
        return $connection;
    }

    require __DIR__ . '/../../server/database/db_connect.php';

    if (!isset($conn) || !($conn instanceof mysqli)) {
        cx_json_response(false, 'Database connection failed.', [], 500);
    }

    $conn->set_charset('utf8mb4');
    $conn->query("SET time_zone = '+05:30'");
    $connection = $conn;
    return $connection;
}

function cx_user_remember_cookie_name(): string
{
    return 'cx_user_remember';
}

function cx_set_remember_cookie(string $token, int $expiresAt): void
{
    $https = (
        (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
        (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https')
    );

    setcookie(cx_user_remember_cookie_name(), $token, [
        'expires' => $expiresAt,
        'path' => '/',
        'domain' => '',
        'secure' => $https,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
}

function cx_clear_remember_cookie(): void
{
    cx_set_remember_cookie('', time() - 3600);
}

function cx_log_security_activity(mysqli $conn, int $userId, string $type, string $title, string $description = ''): void
{
    $stmt = $conn->prepare(
        "INSERT INTO Login_Security_Activity (user_id, activity_type, title, description)
         VALUES (?, ?, ?, ?)"
    );
    if ($stmt) {
        $stmt->bind_param('isss', $userId, $type, $title, $description);
        $stmt->execute();
        $stmt->close();
    }
}

<?php
declare(strict_types=1);

/*
 * Shared helpers for every logged-in user endpoint.
 * Location: CX/backend/user/_user_common.php
 *
 * Including this file:
 *  - applies CORS + JSON headers (via auth/_auth_common.php)
 *  - blocks the request with 401 JSON unless a valid user session exists
 *  - gives you $conn (mysqli) and $userId (int)
 */

require_once __DIR__ . '/../middleware/user_auth_check.php';
// user_auth_check.php already loaded _auth_common.php and set $conn, $userId.

/* Absolute URL for a stored upload, built from PHP_BASE_URL (app_config.php) */
function cx_upload_url(?string $path, string $folder = 'products'): ?string
{
    if ($path === null || $path === '') {
        return null;
    }
    if (preg_match('#^https?://#i', $path)) {
        return $path;
    }
    $clean = preg_replace('#^/+#', '', $path);
    $clean = preg_replace('#^uploads/#', '', $clean);
    if (strpos($clean, '/') === false) {
        $clean = $folder . '/' . $clean;
    }
    return rtrim(PHP_BASE_URL, '/') . '/uploads/' . $clean;
}

function cx_int($value, int $default = 0): int
{
    return is_numeric($value) ? (int)$value : $default;
}

/* Run a prepared SELECT and return all rows. $types like 'iis'. */
function cx_fetch_all(mysqli $conn, string $sql, string $types = '', array $params = []): array
{
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        error_log('SQL prepare failed: ' . $conn->error);
        cx_json_response(false, 'Server error.', [], 500);
    }
    if ($types !== '') {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $rows;
}

function cx_fetch_one(mysqli $conn, string $sql, string $types = '', array $params = []): ?array
{
    $rows = cx_fetch_all($conn, $sql, $types, $params);
    return $rows[0] ?? null;
}

/* Run INSERT/UPDATE/DELETE. Returns affected rows. */
function cx_exec(mysqli $conn, string $sql, string $types = '', array $params = []): int
{
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        error_log('SQL prepare failed: ' . $conn->error);
        cx_json_response(false, 'Server error.', [], 500);
    }
    if ($types !== '') {
        $stmt->bind_param($types, ...$params);
    }
    if (!$stmt->execute()) {
        error_log('SQL execute failed: ' . $stmt->error);
        $stmt->close();
        cx_json_response(false, 'Server error.', [], 500);
    }
    $affected = $stmt->affected_rows;
    $stmt->close();
    return $affected;
}

/* Is this user approved by admin? (User_Verification.verification_status) */
function cx_user_is_verified(mysqli $conn, int $userId): bool
{
    $row = cx_fetch_one(
        $conn,
        "SELECT verification_status FROM User_Verification
         WHERE user_id = ? AND deleted_at IS NULL LIMIT 1",
        'i',
        [$userId]
    );
    return $row && $row['verification_status'] === 'Approved';
}

function cx_require_verified(mysqli $conn, int $userId): void
{
    if (!cx_user_is_verified($conn, $userId)) {
        cx_json_response(
            false,
            'Your profile must be verified by an admin before you can do this.',
            ['code' => 'NOT_VERIFIED'],
            403
        );
    }
}

/* Insert a row in Notifications for a user. */
function cx_notify(
    mysqli $conn,
    int $toUserId,
    string $type,
    string $title,
    string $message,
    ?string $refType = null,
    ?int $refId = null
): void {
    cx_exec(
        $conn,
        "INSERT INTO Notifications
            (user_id, notification_type, title, message, reference_type, reference_id)
         VALUES (?, ?, ?, ?, ?, ?)",
        'issssi',
        [$toUserId, $type, $title, $message, $refType, $refId]
    );
}

/* ---------- file upload helper ----------
 * Saves one uploaded file ($_FILES entry) into CX/uploads/<folder>/ under a
 * random name. Returns the stored file name, or throws RuntimeException. */
function cx_save_upload(array $file, string $folder, array $allowedMime, int $maxBytes = 5242880): string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        throw new RuntimeException('File upload failed. Please try again.');
    }
    if (($file['size'] ?? 0) > $maxBytes) {
        throw new RuntimeException('Each file must be smaller than ' . round($maxBytes / 1048576) . 'MB.');
    }
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    if (!isset($allowedMime[$mime])) {
        throw new RuntimeException('Unsupported file type.');
    }
    $dir = __DIR__ . '/../../uploads/' . $folder;
    if (!is_dir($dir) && !mkdir($dir, 0755, true)) {
        throw new RuntimeException('Upload folder is not writable.');
    }
    $name = bin2hex(random_bytes(12)) . '.' . $allowedMime[$mime];
    if (!move_uploaded_file($file['tmp_name'], $dir . '/' . $name)) {
        throw new RuntimeException('Could not save the uploaded file.');
    }
    return $name;
}

/* Normalise $_FILES['x'] (single or multiple) into a list of file arrays. */
function cx_files_list(string $key): array
{
    if (empty($_FILES[$key])) {
        return [];
    }
    $f = $_FILES[$key];
    if (!is_array($f['name'])) {
        return $f['error'] === UPLOAD_ERR_NO_FILE ? [] : [$f];
    }
    $out = [];
    foreach ($f['name'] as $i => $_) {
        if ($f['error'][$i] === UPLOAD_ERR_NO_FILE) {
            continue;
        }
        $out[] = [
            'name' => $f['name'][$i], 'type' => $f['type'][$i], 'tmp_name' => $f['tmp_name'][$i],
            'error' => $f['error'][$i], 'size' => $f['size'][$i],
        ];
    }
    return $out;
}

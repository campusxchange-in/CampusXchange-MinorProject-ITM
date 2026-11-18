<?php
declare(strict_types=1);

/*
 * Shared helpers for PUBLIC endpoints (no login needed).
 * Location: CX/backend/public/_public_common.php
 *
 * Used by the landing page, login/signup pages and the admin login page,
 * which are all visible before anybody is signed in. Only harmless
 * aggregate numbers and public product cards are returned from here.
 */

require_once __DIR__ . '/../auth/_auth_common.php';   // CORS + JSON + cx_db()

header('Cache-Control: no-store');

/* Absolute URL for a stored upload, built from PHP_BASE_URL (app_config.php) */
function cx_public_upload_url(?string $path, string $folder = 'products'): ?string
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

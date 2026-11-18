<?php

/* =====================================================
   MAIN ADMIN ACCESS CHECK
===================================================== */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$logged_in_admin_id = (int)($_SESSION['admin_id'] ?? 0);

if ($logged_in_admin_id <= 0) {
    header("Location: " . FRONTEND_URL . "/admin-login");
    exit;
}

if (!isset($conn) || !($conn instanceof mysqli)) {
    die("Database connection unavailable.");
}

/* Get current admin from database */

$stmt = $conn->prepare("
    SELECT admin_id, admin_role, status
    FROM Admins
    WHERE admin_id = ?
      AND deleted_at IS NULL
    LIMIT 1
");

$stmt->bind_param("i", $logged_in_admin_id);
$stmt->execute();

$result = $stmt->get_result();
$current_admin = $result->fetch_assoc();

$stmt->close();

/* Verify admin account */

if (
    !$current_admin ||
    $current_admin['admin_role'] !== 'Main Admin' ||
    $current_admin['status'] !== 'Active'
) {
    http_response_code(403);
    exit("Access denied. Main Admin privileges are required.");
}
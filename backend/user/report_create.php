<?php
/* POST CX/backend/user/report_create.php  (multipart/form-data)
 * fields: product_id, report_category, reason, description
 * files:  evidence[] (screenshots, required, max 5)
 * Creates a Product report; admins review it in the admin panel. */
require_once __DIR__ . '/_user_common.php';
cx_require_method('POST');

$productId = cx_int($_POST['product_id'] ?? 0);
$category = (string)($_POST['report_category'] ?? '');
$reason = mb_substr(trim((string)($_POST['reason'] ?? '')), 0, 255);
$description = mb_substr(trim((string)($_POST['description'] ?? '')), 0, 3000);

$allowed = ['Scam/Fraud', 'Fake/Misleading Listing', 'Prohibited Product', 'Spam', 'Inappropriate Behavior', 'Other'];
if ($productId <= 0) cx_json_response(false, 'Invalid product.', [], 422);
if (!in_array($category, $allowed, true)) cx_json_response(false, 'Choose a report category.', [], 422);
if ($reason === '') cx_json_response(false, 'Please give a short reason.', [], 422);

$files = cx_files_list('evidence');
if (count($files) < 1) cx_json_response(false, 'Please attach at least one screenshot as evidence.', [], 422);
if (count($files) > 5) cx_json_response(false, 'You can attach up to 5 screenshots.', [], 422);

$p = cx_fetch_one($conn, "SELECT product_id, seller_id, title FROM Products WHERE product_id = ? AND deleted_at IS NULL LIMIT 1", 'i', [$productId]);
if (!$p) cx_json_response(false, 'Product not found.', [], 404);
if ((int)$p['seller_id'] === $userId) cx_json_response(false, 'You cannot report your own product.', [], 422);

$dup = cx_fetch_one($conn,
    "SELECT report_id FROM Reports
     WHERE reporter_id = ? AND reported_product_id = ? AND status IN ('Pending','Under Review') AND deleted_at IS NULL LIMIT 1",
    'ii', [$userId, $productId]);
if ($dup) cx_json_response(false, 'You have already reported this product. Our team is reviewing it.', [], 409);

$types = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
$saved = [];
try {
    foreach ($files as $f) {
        $saved[] = ['name' => cx_save_upload($f, 'reports', $types), 'orig' => mb_substr((string)$f['name'], 0, 255), 'size' => (int)$f['size']];
    }
} catch (RuntimeException $e) {
    cx_json_response(false, $e->getMessage(), [], 422);
}

$conn->begin_transaction();
try {
    cx_exec($conn,
        "INSERT INTO Reports (reporter_id, reported_user_id, reported_product_id, report_type, report_category, reason, description)
         VALUES (?, ?, ?, 'Product', ?, ?, ?)",
        'iiisss', [$userId, (int)$p['seller_id'], $productId, $category, $reason, $description !== '' ? $description : null]);
    $reportId = (int)$conn->insert_id;

    foreach ($saved as $s) {
        cx_exec($conn,
            "INSERT INTO Report_Evidence (report_id, file_path, file_name, file_type, file_size) VALUES (?, ?, ?, 'image', ?)",
            'issi', [$reportId, $s['name'], $s['orig'], $s['size']]);
    }

    $admins = cx_fetch_all($conn, "SELECT admin_id FROM Admins WHERE status = 'Active' AND deleted_at IS NULL");
    foreach ($admins as $a) {
        cx_exec($conn,
            "INSERT INTO Notifications (admin_id, notification_type, title, message, reference_type, reference_id)
             VALUES (?, 'Report', 'New product report', ?, 'Report', ?)",
            'isi', [(int)$a['admin_id'], 'A product "' . $p['title'] . '" was reported.', $reportId]);
    }
    $conn->commit();
} catch (Throwable $e) {
    $conn->rollback();
    error_log('report_create failed: ' . $e->getMessage());
    cx_json_response(false, 'Could not submit your report. Please try again.', [], 500);
}

cx_json_response(true, 'Report submitted. Our team will review it.', ['report_id' => $reportId]);

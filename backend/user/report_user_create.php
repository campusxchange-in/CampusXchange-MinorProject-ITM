<?php
/* POST CX/backend/user/report_user_create.php  (multipart/form-data)
 * fields: chat_id, report_category, reason, description
 * files:  evidence[]  (screenshots: at least 1, at most 2)
 *
 * Reports the OTHER person of a chat the logged-in user takes part in.
 * The reported user is taken from the chat on the server, never from the browser,
 * so nobody can report an arbitrary account. Creates a Reports row with
 * report_type = 'User' (admins already review these) + Report_Evidence rows. */
require_once __DIR__ . '/_user_common.php';
cx_require_method('POST');

$chatId = cx_int($_POST['chat_id'] ?? 0);
$category = (string)($_POST['report_category'] ?? '');
$reason = mb_substr(trim((string)($_POST['reason'] ?? '')), 0, 255);
$description = mb_substr(trim((string)($_POST['description'] ?? '')), 0, 3000);

$allowed = ['Harassment', 'Scam/Fraud', 'Inappropriate Behavior', 'Spam', 'Other'];
if ($chatId <= 0) cx_json_response(false, 'Invalid conversation.', [], 422);
if (!in_array($category, $allowed, true)) cx_json_response(false, 'Choose a report category.', [], 422);
if ($reason === '') cx_json_response(false, 'Please give a short reason.', [], 422);

$files = cx_files_list('evidence');
if (count($files) < 1) cx_json_response(false, 'Please attach at least one screenshot as evidence.', [], 422);
if (count($files) > 2) cx_json_response(false, 'You can attach up to 2 screenshots.', [], 422);

$chat = cx_fetch_one($conn,
    "SELECT c.chat_id, c.buyer_id, c.seller_id, p.title
     FROM Chats c INNER JOIN Products p ON p.product_id = c.product_id
     WHERE c.chat_id = ? AND (c.buyer_id = ? OR c.seller_id = ?) AND c.deleted_at IS NULL LIMIT 1",
    'iii', [$chatId, $userId, $userId]);
if (!$chat) cx_json_response(false, 'Conversation not found.', [], 404);

$reportedId = ((int)$chat['buyer_id'] === $userId) ? (int)$chat['seller_id'] : (int)$chat['buyer_id'];
if ($reportedId === $userId) cx_json_response(false, 'You cannot report yourself.', [], 422);

$other = cx_fetch_one($conn, "SELECT full_name FROM Users WHERE user_id = ? LIMIT 1", 'i', [$reportedId]);

$dup = cx_fetch_one($conn,
    "SELECT report_id FROM Reports
     WHERE reporter_id = ? AND reported_user_id = ? AND report_type = 'User'
       AND status IN ('Pending','Under Review') AND deleted_at IS NULL LIMIT 1",
    'ii', [$userId, $reportedId]);
if ($dup) cx_json_response(false, 'You have already reported this user. Our team is reviewing it.', [], 409);

$types = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
$saved = [];
try {
    foreach ($files as $f) {
        $saved[] = ['name' => cx_save_upload($f, 'reports', $types), 'orig' => mb_substr((string)$f['name'], 0, 255), 'size' => (int)$f['size']];
    }
} catch (RuntimeException $e) {
    cx_json_response(false, $e->getMessage(), [], 422);
}

/* give the admin the context of where this happened */
$context = 'Reported from chat #' . $chatId . ' about "' . $chat['title'] . '".';
$fullDescription = $description !== '' ? $description . "\n\n" . $context : $context;

$conn->begin_transaction();
try {
    cx_exec($conn,
        "INSERT INTO Reports (reporter_id, reported_user_id, report_type, report_category, reason, description)
         VALUES (?, ?, 'User', ?, ?, ?)",
        'iisss', [$userId, $reportedId, $category, $reason, $fullDescription]);
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
             VALUES (?, 'Report', 'New user report', ?, 'Report', ?)",
            'isi', [(int)$a['admin_id'], 'User "' . ($other['full_name'] ?? 'Unknown') . '" was reported.', $reportId]);
    }
    $conn->commit();
} catch (Throwable $e) {
    $conn->rollback();
    error_log('report_user_create failed: ' . $e->getMessage());
    cx_json_response(false, 'Could not submit your report. Please try again.', [], 500);
}

cx_json_response(true, 'Report submitted. Our team will review it.', ['report_id' => $reportId]);

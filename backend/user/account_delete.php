<?php
/* POST CX/backend/user/account_delete.php   body: { confirm: "DELETE" }
 * SOFT delete: nothing is physically removed (chats, messages, transactions and
 * reports must stay for disputes/audit). The account can no longer log in. */
require_once __DIR__ . '/_user_common.php';
cx_require_method('POST');

$d = cx_read_json();
if (($d['confirm'] ?? '') !== 'DELETE') {
    cx_json_response(false, 'Type DELETE in capital letters to confirm.', [], 422);
}

$open = cx_fetch_one(
    $conn,
    "SELECT COUNT(*) AS c FROM Transactions
     WHERE (buyer_id = ? OR seller_id = ?) AND transaction_status = 'Disputed' AND deleted_at IS NULL",
    'ii',
    [$userId, $userId]
);
if ((int)$open['c'] > 0) {
    cx_json_response(false, 'You have a disputed order under review. Please wait until it is resolved before deleting your account.', [], 409);
}

$conn->begin_transaction();
try {
    /* listings stop being public but stay in the database */
    cx_exec($conn,
        "UPDATE Products SET deleted_at = NOW(), status = IF(status = 'Active', 'Inactive', status)
         WHERE seller_id = ? AND deleted_at IS NULL", 'i', [$userId]);
    cx_exec($conn, "UPDATE Wishlist SET deleted_at = NOW() WHERE user_id = ? AND deleted_at IS NULL", 'i', [$userId]);
    cx_exec($conn, "UPDATE Notifications SET deleted_at = NOW() WHERE user_id = ? AND deleted_at IS NULL", 'i', [$userId]);
    cx_exec($conn, "UPDATE Remember_Tokens SET revoked_at = NOW() WHERE user_id = ? AND revoked_at IS NULL", 'i', [$userId]);
    cx_exec($conn, "UPDATE User_Verification SET deleted_at = NOW() WHERE user_id = ? AND deleted_at IS NULL", 'i', [$userId]);
    cx_exec($conn, "UPDATE Users SET deleted_at = NOW(), session_version = session_version + 1 WHERE user_id = ?", 'i', [$userId]);
    $conn->commit();
} catch (Throwable $e) {
    $conn->rollback();
    error_log('account_delete failed: ' . $e->getMessage());
    cx_json_response(false, 'Could not delete your account. Please try again.', [], 500);
}

cx_clear_remember_cookie();
$_SESSION = [];
session_destroy();

cx_json_response(true, 'Your account has been deleted.');

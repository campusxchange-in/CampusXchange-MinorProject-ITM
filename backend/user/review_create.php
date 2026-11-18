<?php
/* POST CX/backend/user/review_create.php   body: { transaction_id, rating (1-5), text }
 * Only the BUYER of a Completed transaction may review, once per transaction. */
require_once __DIR__ . '/_user_common.php';
cx_require_method('POST');

$d = cx_read_json();
$transactionId = cx_int($d['transaction_id'] ?? 0);
$rating = cx_int($d['rating'] ?? 0);
$text = trim((string)($d['text'] ?? ''));

if ($transactionId <= 0) cx_json_response(false, 'Invalid order.', [], 422);
if ($rating < 1 || $rating > 5) cx_json_response(false, 'Choose a rating from 1 to 5.', [], 422);
if (mb_strlen($text) > 1000) cx_json_response(false, 'Review is too long (max 1000 characters).', [], 422);

$t = cx_fetch_one(
    $conn,
    "SELECT transaction_id, product_id, seller_id, buyer_id, transaction_status
     FROM Transactions WHERE transaction_id = ? AND deleted_at IS NULL LIMIT 1",
    'i',
    [$transactionId]
);
if (!$t || (int)$t['buyer_id'] !== $userId) {
    cx_json_response(false, 'Only the buyer of a completed exchange can review.', [], 403);
}
if ($t['transaction_status'] !== 'Completed') {
    cx_json_response(false, 'You can review only after the exchange is completed.', [], 422);
}

$exists = cx_fetch_one($conn, "SELECT seller_review_id FROM Seller_Reviews WHERE transaction_id = ? LIMIT 1", 'i', [$transactionId]);
if ($exists) cx_json_response(false, 'You have already reviewed this exchange.', [], 409);

$textParam = $text !== '' ? $text : null;
$conn->begin_transaction();
try {
    cx_exec($conn,
        "INSERT INTO Seller_Reviews (seller_id, reviewer_id, transaction_id, rating, review_text) VALUES (?, ?, ?, ?, ?)",
        'iiiis', [(int)$t['seller_id'], $userId, $transactionId, $rating, $textParam]);
    cx_exec($conn,
        "INSERT INTO Product_Reviews (product_id, reviewer_id, transaction_id, rating, review_text) VALUES (?, ?, ?, ?, ?)",
        'iiiis', [(int)$t['product_id'], $userId, $transactionId, $rating, $textParam]);

    cx_notify($conn, (int)$t['seller_id'], 'Review', 'New review received',
        ($_SESSION['user_name'] ?? 'A buyer') . ' rated you ' . $rating . '/5.', 'Review', $transactionId);
    $conn->commit();
} catch (Throwable $e) {
    $conn->rollback();
    error_log('review_create failed: ' . $e->getMessage());
    cx_json_response(false, 'Could not save your review.', [], 500);
}

cx_json_response(true, 'Thanks for your review!');

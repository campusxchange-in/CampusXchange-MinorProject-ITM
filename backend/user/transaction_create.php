<?php
/* POST CX/backend/user/transaction_create.php   body: { chat_id, selling_price? }
 * The SELLER picks the buyer of a chat: creates a Completed transaction,
 * marks the product Sold and notifies the buyer (review becomes available)
 * and the other interested buyers. */
require_once __DIR__ . '/_user_common.php';
cx_require_method('POST');

$d = cx_read_json();
$chatId = cx_int($d['chat_id'] ?? 0);
if ($chatId <= 0) cx_json_response(false, 'Invalid chat.', [], 422);

$chat = cx_fetch_one(
    $conn,
    "SELECT c.chat_id, c.product_id, c.buyer_id, c.seller_id,
            p.title, p.price, p.status
     FROM Chats c INNER JOIN Products p ON p.product_id = c.product_id
     WHERE c.chat_id = ? AND c.deleted_at IS NULL LIMIT 1",
    'i',
    [$chatId]
);
if (!$chat || (int)$chat['seller_id'] !== $userId) {
    cx_json_response(false, 'Only the seller can mark an item as sold.', [], 403);
}
if ($chat['status'] !== 'Active') {
    cx_json_response(false, 'Only active listings can be marked as sold.', [], 422);
}

$price = is_numeric($d['selling_price'] ?? null) ? round((float)$d['selling_price'], 2) : (float)$chat['price'];
if ($price <= 0) cx_json_response(false, 'Enter a valid selling price.', [], 422);

$productId = (int)$chat['product_id'];
$buyerId = (int)$chat['buyer_id'];

$conn->begin_transaction();
try {
    /* lock the product row so two chats cannot both sell it */
    $lock = cx_fetch_one($conn, "SELECT status FROM Products WHERE product_id = ? FOR UPDATE", 'i', [$productId]);
    if (!$lock || $lock['status'] !== 'Active') {
        $conn->rollback();
        cx_json_response(false, 'This item is no longer available.', [], 409);
    }

    cx_exec(
        $conn,
        "INSERT INTO Transactions (product_id, seller_id, buyer_id, selling_price, transaction_status)
         VALUES (?, ?, ?, ?, 'Completed')",
        'iiid',
        [$productId, $userId, $buyerId, $price]
    );
    $transactionId = (int)$conn->insert_id;

    cx_exec($conn, "UPDATE Products SET status = 'Sold' WHERE product_id = ?", 'i', [$productId]);

    cx_notify($conn, $buyerId, 'Transaction', 'Exchange completed',
        'The seller marked "' . $chat['title'] . '" as sold to you. You can now review the seller in My Orders.',
        'Transaction', $transactionId);

    $others = cx_fetch_all(
        $conn,
        "SELECT DISTINCT buyer_id FROM Chats
         WHERE product_id = ? AND buyer_id <> ? AND deleted_at IS NULL",
        'ii',
        [$productId, $buyerId]
    );
    foreach ($others as $o) {
        cx_notify($conn, (int)$o['buyer_id'], 'Product', 'Item no longer available',
            '"' . $chat['title'] . '" has been sold to another buyer.', 'Product', $productId);
    }

    $conn->commit();
} catch (Throwable $e) {
    $conn->rollback();
    error_log('transaction_create failed: ' . $e->getMessage());
    cx_json_response(false, 'Could not complete the sale. Please try again.', [], 500);
}

cx_json_response(true, 'Marked as sold.', ['transaction_id' => $transactionId]);

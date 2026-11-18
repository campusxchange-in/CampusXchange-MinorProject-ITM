<?php
/* POST CX/backend/user/chat_start.php
 * body: { "product_id": 12, "message": "Hi ..." }
 * Creates (or re-opens) the product chat between this buyer and the seller,
 * stores the first message, notifies the seller. Returns { chat_id }. */
require_once __DIR__ . '/_user_common.php';
cx_require_method('POST');

$data = cx_read_json();
$productId = cx_int($data['product_id'] ?? 0);
$message = trim((string)($data['message'] ?? ''));

if ($productId <= 0) {
    cx_json_response(false, 'Invalid product.', [], 422);
}
if ($message === '') {
    cx_json_response(false, 'Please write a message to the seller.', [], 422);
}
if (mb_strlen($message) > 2000) {
    cx_json_response(false, 'Message is too long (max 2000 characters).', [], 422);
}

cx_require_verified($conn, $userId);

$product = cx_fetch_one(
    $conn,
    "SELECT product_id, seller_id, title, status FROM Products
     WHERE product_id = ? AND deleted_at IS NULL LIMIT 1",
    'i',
    [$productId]
);
if (!$product || $product['status'] !== 'Active') {
    cx_json_response(false, 'This product is no longer available.', [], 404);
}

$sellerId = (int)$product['seller_id'];
if ($sellerId === $userId) {
    cx_json_response(false, 'You cannot message yourself about your own product.', [], 422);
}

$conn->begin_transaction();
try {
    $chat = cx_fetch_one(
        $conn,
        "SELECT chat_id, deleted_at FROM Chats
         WHERE product_id = ? AND buyer_id = ? AND seller_id = ? LIMIT 1",
        'iii',
        [$productId, $userId, $sellerId]
    );

    if ($chat) {
        $chatId = (int)$chat['chat_id'];
        if ($chat['deleted_at'] !== null) {
            cx_exec($conn, "UPDATE Chats SET deleted_at = NULL WHERE chat_id = ?", 'i', [$chatId]);
        }
    } else {
        cx_exec(
            $conn,
            "INSERT INTO Chats (product_id, buyer_id, seller_id) VALUES (?, ?, ?)",
            'iii',
            [$productId, $userId, $sellerId]
        );
        $chatId = (int)$conn->insert_id;
    }

    cx_exec(
        $conn,
        "INSERT INTO Messages (chat_id, sender_id, message_text, message_type)
         VALUES (?, ?, ?, 'Text')",
        'iis',
        [$chatId, $userId, $message]
    );
    cx_exec($conn, "UPDATE Chats SET updated_at = NOW() WHERE chat_id = ?", 'i', [$chatId]);

    cx_notify(
        $conn,
        $sellerId,
        'Message',
        'New enquiry on your listing',
        ($_SESSION['user_name'] ?? 'A buyer') . ' messaged you about "' . $product['title'] . '".',
        'Chat',
        $chatId
    );

    $conn->commit();
} catch (Throwable $e) {
    $conn->rollback();
    error_log('chat_start failed: ' . $e->getMessage());
    cx_json_response(false, 'Could not start the chat. Please try again.', [], 500);
}

cx_json_response(true, 'Chat started.', ['chat_id' => $chatId]);

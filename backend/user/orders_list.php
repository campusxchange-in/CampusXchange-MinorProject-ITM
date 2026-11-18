<?php
/* GET CX/backend/user/orders_list.php
 * The logged-in user's purchases (Transactions where they are the buyer). */
require_once __DIR__ . '/_user_common.php';
cx_require_method('GET');

$rows = cx_fetch_all(
    $conn,
    "SELECT t.transaction_id, t.product_id, t.seller_id, t.selling_price,
            t.transaction_status, t.transaction_date, t.notes,
            p.title, c.category_name, u.full_name AS seller_name,
            (SELECT pi.image_path FROM Product_Images pi
              WHERE pi.product_id = p.product_id AND pi.deleted_at IS NULL
              ORDER BY pi.is_primary DESC, pi.image_id ASC LIMIT 1) AS image_path,
            (SELECT ch.chat_id FROM Chats ch
              WHERE ch.product_id = t.product_id AND ch.buyer_id = t.buyer_id
                AND ch.seller_id = t.seller_id AND ch.deleted_at IS NULL LIMIT 1) AS chat_id,
            (SELECT sr.rating FROM Seller_Reviews sr
              WHERE sr.transaction_id = t.transaction_id AND sr.deleted_at IS NULL LIMIT 1) AS my_rating
     FROM Transactions t
     INNER JOIN Products p ON p.product_id = t.product_id
     INNER JOIN Users u ON u.user_id = t.seller_id
     LEFT JOIN Categories c ON c.category_id = p.category_id
     WHERE t.buyer_id = ? AND t.deleted_at IS NULL
     ORDER BY t.transaction_date DESC, t.transaction_id DESC",
    'i',
    [$userId]
);

$orders = array_map(static fn(array $r): array => [
    'id' => (int)$r['transaction_id'],
    'order_no' => 'CXO' . str_pad((string)$r['transaction_id'], 5, '0', STR_PAD_LEFT),
    'product_id' => (int)$r['product_id'],
    'name' => $r['title'],
    'seller' => $r['seller_name'],
    'seller_id' => (int)$r['seller_id'],
    'category' => $r['category_name'] ?? 'Other',
    'price' => (float)$r['selling_price'],
    'status' => $r['transaction_status'],
    'date' => $r['transaction_date'],
    'image' => cx_upload_url($r['image_path'], 'products'),
    'chat_id' => $r['chat_id'] !== null ? (int)$r['chat_id'] : null,
    'my_rating' => $r['my_rating'] !== null ? (int)$r['my_rating'] : null,
], $rows);

cx_json_response(true, 'OK', ['orders' => $orders]);

<?php
/* GET CX/backend/user/dashboard.php
 * Activity counts + the user's 3 most recent orders (as buyer). */
require_once __DIR__ . '/_user_common.php';
cx_require_method('GET');

$wish = cx_fetch_one($conn,
    "SELECT COUNT(*) AS c FROM Wishlist w
     INNER JOIN Products p ON p.product_id = w.product_id AND p.deleted_at IS NULL
     WHERE w.user_id = ? AND w.deleted_at IS NULL", 'i', [$userId]);

$orders = cx_fetch_one($conn,
    "SELECT COUNT(*) AS c FROM Transactions
     WHERE buyer_id = ? AND deleted_at IS NULL", 'i', [$userId]);

$listings = cx_fetch_one($conn,
    "SELECT COUNT(*) AS c FROM Products
     WHERE seller_id = ? AND status = 'Active' AND deleted_at IS NULL", 'i', [$userId]);

$recent = cx_fetch_all($conn,
    "SELECT t.transaction_id, t.selling_price, t.transaction_status, t.transaction_date,
            p.product_id, p.title,
            (SELECT pi.image_path FROM Product_Images pi
              WHERE pi.product_id = p.product_id AND pi.deleted_at IS NULL
              ORDER BY pi.is_primary DESC, pi.image_id ASC LIMIT 1) AS image_path
     FROM Transactions t
     INNER JOIN Products p ON p.product_id = t.product_id
     WHERE t.buyer_id = ? AND t.deleted_at IS NULL
     ORDER BY t.transaction_date DESC, t.transaction_id DESC
     LIMIT 3", 'i', [$userId]);

$recentOrders = array_map(static fn(array $r): array => [
    'id' => (int)$r['transaction_id'],
    'product_id' => (int)$r['product_id'],
    'name' => $r['title'],
    'price' => (float)$r['selling_price'],
    'status' => $r['transaction_status'],
    'date' => $r['transaction_date'],
    'image' => cx_upload_url($r['image_path'], 'products'),
], $recent);

cx_json_response(true, 'OK', [
    'stats' => [
        'wishlist' => (int)$wish['c'],
        'orders' => (int)$orders['c'],
        'active_listings' => (int)$listings['c'],
    ],
    'recent_orders' => $recentOrders,
]);

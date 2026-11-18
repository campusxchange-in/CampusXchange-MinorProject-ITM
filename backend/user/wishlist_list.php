<?php
/* GET CX/backend/user/wishlist_list.php
 * The logged-in user's saved products (newest saved first). */
require_once __DIR__ . '/_user_common.php';
cx_require_method('GET');

$rows = cx_fetch_all(
    $conn,
    "SELECT w.wishlist_id, w.created_at AS saved_at,
            p.product_id, p.title, p.price, p.product_condition, p.status,
            c.category_name,
            u.full_name AS seller_name, sp.location AS seller_location,
            (SELECT pi.image_path FROM Product_Images pi
              WHERE pi.product_id = p.product_id AND pi.deleted_at IS NULL
              ORDER BY pi.is_primary DESC, pi.image_id ASC LIMIT 1) AS image_path
     FROM Wishlist w
     INNER JOIN Products p ON p.product_id = w.product_id AND p.deleted_at IS NULL
     INNER JOIN Users u ON u.user_id = p.seller_id
     LEFT JOIN User_Profiles sp ON sp.user_id = p.seller_id
     LEFT JOIN Categories c ON c.category_id = p.category_id
     WHERE w.user_id = ? AND w.deleted_at IS NULL
     ORDER BY w.created_at DESC, w.wishlist_id DESC",
    'i',
    [$userId]
);

$items = array_map(static fn(array $r): array => [
    'id' => (int)$r['product_id'],
    'wishlist_id' => (int)$r['wishlist_id'],
    'name' => $r['title'],
    'category' => $r['category_name'] ?? 'Other',
    'price' => (float)$r['price'],
    'condition' => $r['product_condition'],
    'status' => $r['status'],
    'seller' => $r['seller_name'],
    'location' => $r['seller_location'] ?: 'Campus',
    'image' => cx_upload_url($r['image_path'], 'products'),
    'saved_at' => $r['saved_at'],
], $rows);

cx_json_response(true, 'OK', ['items' => $items, 'count' => count($items)]);

<?php
/* GET CX/backend/user/my_listings.php
 * All products created by the logged-in user (not soft-deleted). */
require_once __DIR__ . '/_user_common.php';
cx_require_method('GET');

$rows = cx_fetch_all(
    $conn,
    "SELECT p.product_id, p.title, p.description, p.price, p.product_condition, p.status,
            p.category_id, c.category_name, p.invoice_document, p.removal_reason,
            p.created_at, p.updated_at,
            (SELECT pi.image_path FROM Product_Images pi
              WHERE pi.product_id = p.product_id AND pi.deleted_at IS NULL
              ORDER BY pi.is_primary DESC, pi.image_id ASC LIMIT 1) AS image_path
     FROM Products p
     LEFT JOIN Categories c ON c.category_id = p.category_id
     WHERE p.seller_id = ? AND p.deleted_at IS NULL
     ORDER BY p.created_at DESC, p.product_id DESC",
    'i',
    [$userId]
);

$listings = array_map(static fn(array $r): array => [
    'id' => (int)$r['product_id'],
    'name' => $r['title'] ?? '',
    'description' => $r['description'] ?? '',
    'price' => (float)$r['price'],
    'condition' => $r['product_condition'],
    'status' => $r['status'],
    'category' => $r['category_name'] ?? 'Uncategorised',
    'category_id' => $r['category_id'] !== null ? (int)$r['category_id'] : null,
    'removal_reason' => $r['removal_reason'],
    'image' => cx_upload_url($r['image_path'], 'products'),
    'created_at' => $r['created_at'],
], $rows);

cx_json_response(true, 'OK', ['listings' => $listings]);

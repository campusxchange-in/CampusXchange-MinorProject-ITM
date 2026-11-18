<?php
/* POST CX/backend/user/listing_delete.php   body: { product_id }
 * Soft delete only (chats, wishlists and transactions keep pointing at the row).
 * Sold listings are kept for order history and cannot be deleted. */
require_once __DIR__ . '/_user_common.php';
cx_require_method('POST');

$d = cx_read_json();
$productId = cx_int($d['product_id'] ?? 0);
if ($productId <= 0) cx_json_response(false, 'Invalid product.', [], 422);

$p = cx_fetch_one(
    $conn,
    "SELECT product_id, status FROM Products
     WHERE product_id = ? AND seller_id = ? AND deleted_at IS NULL LIMIT 1",
    'ii',
    [$productId, $userId]
);
if (!$p) cx_json_response(false, 'Listing not found.', [], 404);
if ($p['status'] === 'Sold') {
    cx_json_response(false, 'Sold listings are kept for order history and cannot be deleted.', [], 403);
}

cx_exec($conn, "UPDATE Products SET deleted_at = NOW(), status = IF(status = 'Active', 'Inactive', status) WHERE product_id = ?", 'i', [$productId]);
cx_json_response(true, 'Listing deleted.');

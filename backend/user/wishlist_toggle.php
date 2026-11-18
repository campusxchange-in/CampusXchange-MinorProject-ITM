<?php
/* POST CX/backend/user/wishlist_toggle.php   body: { "product_id": 12 }
 * Adds or removes (soft delete) a wishlist row. Returns { wishlisted: bool }. */
require_once __DIR__ . '/_user_common.php';
cx_require_method('POST');

$data = cx_read_json();
$productId = cx_int($data['product_id'] ?? 0);
if ($productId <= 0) {
    cx_json_response(false, 'Invalid product.', [], 422);
}

$product = cx_fetch_one(
    $conn,
    "SELECT product_id, seller_id, status FROM Products
     WHERE product_id = ? AND deleted_at IS NULL LIMIT 1",
    'i',
    [$productId]
);
if (!$product) {
    cx_json_response(false, 'Product not found.', [], 404);
}
if ((int)$product['seller_id'] === $userId) {
    cx_json_response(false, 'You cannot wishlist your own product.', [], 422);
}

$row = cx_fetch_one(
    $conn,
    "SELECT wishlist_id, deleted_at FROM Wishlist WHERE user_id = ? AND product_id = ? LIMIT 1",
    'ii',
    [$userId, $productId]
);

if ($row && $row['deleted_at'] === null) {
    cx_exec($conn, "UPDATE Wishlist SET deleted_at = NOW() WHERE wishlist_id = ?", 'i', [(int)$row['wishlist_id']]);
    $wishlisted = false;
} elseif ($row) {
    if ($product['status'] !== 'Active') {
        cx_json_response(false, 'This product is no longer available.', [], 422);
    }
    cx_exec($conn, "UPDATE Wishlist SET deleted_at = NULL, created_at = NOW() WHERE wishlist_id = ?", 'i', [(int)$row['wishlist_id']]);
    $wishlisted = true;
} else {
    if ($product['status'] !== 'Active') {
        cx_json_response(false, 'This product is no longer available.', [], 422);
    }
    cx_exec($conn, "INSERT INTO Wishlist (user_id, product_id) VALUES (?, ?)", 'ii', [$userId, $productId]);
    $wishlisted = true;
}

cx_json_response(true, $wishlisted ? 'Added to wishlist.' : 'Removed from wishlist.', ['wishlisted' => $wishlisted]);

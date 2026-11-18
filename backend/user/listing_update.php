<?php
/* POST CX/backend/user/listing_update.php
 * body: { product_id, title, price, category_id, description }
 * Only the owner may edit; sold / admin-removed listings are locked. */
require_once __DIR__ . '/_user_common.php';
cx_require_method('POST');

$d = cx_read_json();
$productId = cx_int($d['product_id'] ?? 0);
$title = trim((string)($d['title'] ?? ''));
$description = trim((string)($d['description'] ?? ''));
$categoryId = cx_int($d['category_id'] ?? 0);
$price = is_numeric($d['price'] ?? null) ? round((float)$d['price'], 2) : -1;

if ($productId <= 0) cx_json_response(false, 'Invalid product.', [], 422);
if ($title === '' || mb_strlen($title) > 150) cx_json_response(false, 'Enter a product name (max 150 characters).', [], 422);
if ($price <= 0 || $price > 99999999) cx_json_response(false, 'Enter a valid price.', [], 422);
if (mb_strlen($description) > 5000) cx_json_response(false, 'Description is too long.', [], 422);

$p = cx_fetch_one(
    $conn,
    "SELECT product_id, status FROM Products
     WHERE product_id = ? AND seller_id = ? AND deleted_at IS NULL LIMIT 1",
    'ii',
    [$productId, $userId]
);
if (!$p) cx_json_response(false, 'Listing not found.', [], 404);
if (in_array($p['status'], ['Sold', 'Removed'], true)) {
    cx_json_response(false, 'This listing can no longer be edited.', [], 403);
}

$cat = cx_fetch_one(
    $conn,
    "SELECT category_id FROM Categories WHERE category_id = ? AND status = 'Active' AND deleted_at IS NULL LIMIT 1",
    'i',
    [$categoryId]
);
if (!$cat) cx_json_response(false, 'Choose a valid category.', [], 422);

cx_exec(
    $conn,
    "UPDATE Products SET title = ?, description = ?, price = ?, category_id = ? WHERE product_id = ?",
    'ssdii',
    [$title, $description, $price, $categoryId, $productId]
);

cx_json_response(true, 'Listing updated.');

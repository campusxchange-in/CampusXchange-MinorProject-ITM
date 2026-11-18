<?php
/* POST CX/backend/user/listing_status.php   body: { product_id, action }
 * action: "publish" (Draft -> Active) | "hide" (Active -> Inactive) | "activate" (Inactive -> Active)
 * Sold and admin-removed listings cannot be changed by the seller. */
require_once __DIR__ . '/_user_common.php';
cx_require_method('POST');

$d = cx_read_json();
$productId = cx_int($d['product_id'] ?? 0);
$action = (string)($d['action'] ?? '');

if ($productId <= 0 || !in_array($action, ['publish', 'hide', 'activate'], true)) {
    cx_json_response(false, 'Invalid request.', [], 422);
}

$p = cx_fetch_one(
    $conn,
    "SELECT p.product_id, p.title, p.price, p.category_id, p.product_condition,
            p.status, p.invoice_document, c.invoice_required,
            (SELECT COUNT(*) FROM Product_Images pi
              WHERE pi.product_id = p.product_id AND pi.deleted_at IS NULL) AS image_count
     FROM Products p
     LEFT JOIN Categories c ON c.category_id = p.category_id
     WHERE p.product_id = ? AND p.seller_id = ? AND p.deleted_at IS NULL LIMIT 1",
    'ii',
    [$productId, $userId]
);
if (!$p) cx_json_response(false, 'Listing not found.', [], 404);

if (in_array($p['status'], ['Sold', 'Removed'], true)) {
    cx_json_response(false, 'This listing can no longer be changed.', [], 403);
}

if ($action === 'hide') {
    if ($p['status'] !== 'Active') cx_json_response(false, 'Only active listings can be hidden.', [], 422);
    $new = 'Inactive';
} else {
    $expected = ($action === 'publish') ? 'Draft' : 'Inactive';
    if ($p['status'] !== $expected) cx_json_response(false, 'This listing cannot be published from its current state.', [], 422);

    cx_require_verified($conn, $userId);

    if (
        trim((string)$p['title']) === '' || (float)$p['price'] <= 0 ||
        empty($p['category_id']) || empty($p['product_condition']) || (int)$p['image_count'] < 1
    ) {
        cx_json_response(false, 'Complete the title, price, category, condition and add at least one image first.', [], 422);
    }
    if ((int)$p['invoice_required'] === 1 && empty($p['invoice_document'])) {
        cx_json_response(false, 'This category requires an invoice document.', [], 422);
    }
    $new = 'Active';
}

cx_exec($conn, "UPDATE Products SET status = ? WHERE product_id = ?", 'si', [$new, $productId]);
cx_json_response(true, 'Listing status updated.', ['status' => $new]);

<?php
/* GET CX/backend/user/product_details.php?product_id=12
 * Full product + images + seller summary + seller reviews. */
require_once __DIR__ . '/_user_common.php';
cx_require_method('GET');

$productId = cx_int($_GET['product_id'] ?? 0);
if ($productId <= 0) {
    cx_json_response(false, 'Invalid product.', [], 422);
}

$p = cx_fetch_one(
    $conn,
    "SELECT p.product_id, p.seller_id, p.title, p.description, p.price,
            p.product_condition, p.status, p.created_at,
            c.category_id, c.category_name,
            u.full_name AS seller_name, u.created_at AS seller_since,
            sp.location AS seller_location, sp.profile_picture AS seller_picture
     FROM Products p
     INNER JOIN Users u ON u.user_id = p.seller_id AND u.deleted_at IS NULL
     LEFT JOIN User_Profiles sp ON sp.user_id = p.seller_id
     LEFT JOIN Categories c ON c.category_id = p.category_id
     WHERE p.product_id = ? AND p.deleted_at IS NULL
     LIMIT 1",
    'i',
    [$productId]
);

if (!$p) {
    cx_json_response(false, 'Product not found.', [], 404);
}

$isOwn = ((int)$p['seller_id'] === $userId);

/* Non-active products are visible only to the seller or a past buyer. */
if ($p['status'] !== 'Active' && !$isOwn) {
    $bought = cx_fetch_one(
        $conn,
        "SELECT transaction_id FROM Transactions
         WHERE product_id = ? AND buyer_id = ? AND deleted_at IS NULL LIMIT 1",
        'ii',
        [$productId, $userId]
    );
    if (!$bought) {
        cx_json_response(false, 'This listing is not available.', [], 404);
    }
}

$images = cx_fetch_all(
    $conn,
    "SELECT image_id, image_path, is_primary FROM Product_Images
     WHERE product_id = ? AND deleted_at IS NULL
     ORDER BY is_primary DESC, image_id ASC",
    'i',
    [$productId]
);

$sellerId = (int)$p['seller_id'];

$stats = cx_fetch_one(
    $conn,
    "SELECT
        (SELECT COUNT(*) FROM Transactions
          WHERE seller_id = ? AND transaction_status = 'Completed' AND deleted_at IS NULL) AS deals,
        (SELECT ROUND(AVG(rating), 1) FROM Seller_Reviews
          WHERE seller_id = ? AND status = 'Visible' AND deleted_at IS NULL) AS rating,
        (SELECT COUNT(*) FROM Seller_Reviews
          WHERE seller_id = ? AND status = 'Visible' AND deleted_at IS NULL) AS review_count",
    'iii',
    [$sellerId, $sellerId, $sellerId]
);

$reviews = cx_fetch_all(
    $conn,
    "SELECT sr.seller_review_id, sr.rating, sr.review_text, sr.created_at, u.full_name AS reviewer
     FROM Seller_Reviews sr
     INNER JOIN Users u ON u.user_id = sr.reviewer_id
     WHERE sr.seller_id = ? AND sr.status = 'Visible' AND sr.deleted_at IS NULL
     ORDER BY sr.created_at DESC
     LIMIT 10",
    'i',
    [$sellerId]
);

$wish = cx_fetch_one(
    $conn,
    "SELECT wishlist_id FROM Wishlist WHERE user_id = ? AND product_id = ? AND deleted_at IS NULL LIMIT 1",
    'ii',
    [$userId, $productId]
);

$chat = cx_fetch_one(
    $conn,
    "SELECT chat_id FROM Chats
     WHERE product_id = ? AND buyer_id = ? AND seller_id = ? AND deleted_at IS NULL LIMIT 1",
    'iii',
    [$productId, $userId, $sellerId]
);

cx_json_response(true, 'OK', [
    'product' => [
        'id' => (int)$p['product_id'],
        'name' => $p['title'],
        'description' => $p['description'],
        'price' => (float)$p['price'],
        'condition' => $p['product_condition'],
        'status' => $p['status'],
        'category' => $p['category_name'] ?? 'Other',
        'location' => $p['seller_location'] ?: 'Campus',
        'posted_at' => $p['created_at'],
        'image' => cx_upload_url($images[0]['image_path'] ?? null, 'products'),
        'images' => array_map(
            static fn(array $i): string => cx_upload_url($i['image_path'], 'products'),
            $images
        ),
        'wishlisted' => (bool)$wish,
        'is_own' => $isOwn,
        'chat_id' => $chat ? (int)$chat['chat_id'] : null,
    ],
    'seller' => [
        'id' => $sellerId,
        'name' => $p['seller_name'],
        'profile_picture' => cx_upload_url($p['seller_picture'], 'user_profiles'),
        'rating' => $stats['rating'] !== null ? (float)$stats['rating'] : null,
        'review_count' => (int)$stats['review_count'],
        'total_deals' => (int)$stats['deals'],
        'member_since' => $p['seller_since'],
    ],
    'reviews' => array_map(static fn(array $r): array => [
        'id' => (int)$r['seller_review_id'],
        'reviewer' => $r['reviewer'],
        'rating' => (int)$r['rating'],
        'text' => $r['review_text'],
        'created_at' => $r['created_at'],
    ], $reviews),
]);

<?php
/* GET  CX/backend/public/public_featured_products.php
 * Products shown in the "Featured Products" section of the landing page.
 *
 * Rule: products currently for sale (same filter as Browse) that have an image.
 * To get a mix, the most wishlisted product of every category comes first,
 * then the second most wishlisted of every category, and so on. Newest wins ties.
 *
 * Optional query param:  limit (default 6, max 12)
 * Each product: id, name, price, category, location, image
 */
require_once __DIR__ . '/_public_common.php';
cx_require_method('GET');

$limit = max(1, min(12, is_numeric($_GET['limit'] ?? null) ? (int)$_GET['limit'] : 6));

try {
    $conn = cx_db();

    $sql = "SELECT t.product_id, t.title, t.price, t.category_name, t.seller_location, t.image_path
            FROM (
                SELECT b.*,
                       ROW_NUMBER() OVER (
                           PARTITION BY b.category_id
                           ORDER BY b.wish_count DESC, b.created_at DESC, b.product_id DESC
                       ) AS rn
                FROM (
                    SELECT p.product_id, p.title, p.price, p.created_at, p.category_id,
                           c.category_name, sp.location AS seller_location,
                           (SELECT pi.image_path FROM Product_Images pi
                             WHERE pi.product_id = p.product_id AND pi.deleted_at IS NULL
                             ORDER BY pi.is_primary DESC, pi.image_id ASC LIMIT 1) AS image_path,
                           (SELECT COUNT(*) FROM Wishlist w
                             WHERE w.product_id = p.product_id AND w.deleted_at IS NULL) AS wish_count
                    FROM Products p
                    INNER JOIN Users u ON u.user_id = p.seller_id
                    LEFT JOIN User_Profiles sp ON sp.user_id = p.seller_id
                    LEFT JOIN Categories c ON c.category_id = p.category_id
                    WHERE p.status = 'Active' AND p.deleted_at IS NULL
                      AND u.status = 'Active' AND u.deleted_at IS NULL
                ) b
                WHERE b.image_path IS NOT NULL
            ) t
            ORDER BY t.rn ASC, t.wish_count DESC, t.created_at DESC, t.product_id DESC
            LIMIT " . $limit;

    $res  = $conn->query($sql);
    $rows = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];

    $products = array_map(static function (array $r): array {
        return [
            'id'       => (int)$r['product_id'],
            'name'     => $r['title'],
            'price'    => (float)$r['price'],
            'category' => $r['category_name'] ?? 'Other',
            'location' => $r['seller_location'] ?: 'Campus',
            'image'    => cx_public_upload_url($r['image_path'], 'products'),
        ];
    }, $rows);

    cx_json_response(true, 'OK', ['products' => $products, 'count' => count($products)]);
} catch (Throwable $e) {
    error_log('public_featured_products failed: ' . $e->getMessage());
    cx_json_response(false, 'Unable to load products.', [], 500);
}

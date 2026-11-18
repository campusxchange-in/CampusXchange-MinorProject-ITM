<?php
/* GET  CX/backend/user/products_list.php
 * Active marketplace products for Browse + Dashboard.
 *
 * Optional query params:
 *   limit (default 200, max 500), offset (default 0)
 *   category_id, include_own=1 (default: own listings are hidden)
 *   search  - free text. It is split into words; EVERY word has to match at least
 *             one of: title, description, category, condition, seller name,
 *             seller location, seller college. So "macbook 2022", "blue bike" or
 *             "books" all work, in any word order, and a plural ("laptops")
 *             also finds the singular ("laptop"). Best title matches come first.
 *
 * Each product: id, name, price, category, category_id, condition, location,
 * posted_at, image, wishlisted, seller_id, seller_name
 */
require_once __DIR__ . '/_user_common.php';
cx_require_method('GET');

$limit  = max(1, min(500, cx_int($_GET['limit'] ?? 200, 200)));
$offset = max(0, cx_int($_GET['offset'] ?? 0));
$categoryId = cx_int($_GET['category_id'] ?? 0);
$search = trim((string)($_GET['search'] ?? ''));
$includeOwn = ($_GET['include_own'] ?? '') === '1';

$where = ["p.status = 'Active'", "p.deleted_at IS NULL", "u.deleted_at IS NULL", "u.status = 'Active'"];
$types = 'i';
$params = [$userId]; // for the wishlist LEFT JOIN

if (!$includeOwn) {
    $where[] = 'p.seller_id <> ?';
    $types .= 'i';
    $params[] = $userId;
}
if ($categoryId > 0) {
    $where[] = 'p.category_id = ?';
    $types .= 'i';
    $params[] = $categoryId;
}
/* ---------- free-text search ---------- */
$orderSql = 'p.created_at DESC, p.product_id DESC';
$orderTypes = '';
$orderParams = [];

if ($search !== '') {
    $search = mb_substr($search, 0, 120);
    $escape = static fn(string $t): string => addcslashes($t, '%_\\');

    /* up to 8 words; a word ending in "s" also matches its singular */
    $words = array_slice(array_values(array_filter(preg_split('/\s+/u', $search))), 0, 8);
    $fields = ['p.title', 'p.description', 'c.category_name', 'p.product_condition',
               'u.full_name', 'sp.location', 'sp.college_name'];

    foreach ($words as $word) {
        $variants = [$word];
        if (mb_strlen($word) > 3 && mb_substr($word, -1) === 's') {
            $variants[] = mb_substr($word, 0, -1);
        }

        $ors = [];
        foreach ($variants as $v) {
            foreach ($fields as $f) {
                $ors[] = "$f LIKE ?";
                $types .= 's';
                $params[] = '%' . $escape($v) . '%';
            }
        }
        $where[] = '(' . implode(' OR ', $ors) . ')';
    }

    /* relevance: whole phrase in the title, then first word in the title,
       then first word in the category, then newest */
    $orderSql = '(p.title LIKE ?) DESC, (p.title LIKE ?) DESC, (c.category_name LIKE ?) DESC, '
              . 'p.created_at DESC, p.product_id DESC';
    $orderTypes = 'sss';
    $orderParams = [
        '%' . $escape($search) . '%',
        '%' . $escape($words[0] ?? $search) . '%',
        '%' . $escape($words[0] ?? $search) . '%',
    ];
}

$sql = "SELECT p.product_id, p.title, p.price, p.product_condition, p.created_at,
               p.category_id, c.category_name,
               p.seller_id, u.full_name AS seller_name, sp.location AS seller_location,
               (SELECT pi.image_path FROM Product_Images pi
                 WHERE pi.product_id = p.product_id AND pi.deleted_at IS NULL
                 ORDER BY pi.is_primary DESC, pi.image_id ASC LIMIT 1) AS image_path,
               (w.wishlist_id IS NOT NULL) AS wishlisted
        FROM Products p
        INNER JOIN Users u ON u.user_id = p.seller_id
        LEFT JOIN User_Profiles sp ON sp.user_id = p.seller_id
        LEFT JOIN Categories c ON c.category_id = p.category_id
        LEFT JOIN Wishlist w ON w.product_id = p.product_id
                            AND w.user_id = ? AND w.deleted_at IS NULL
        WHERE " . implode(' AND ', $where) . "
        ORDER BY " . $orderSql . "
        LIMIT ? OFFSET ?";

$types .= $orderTypes . 'ii';
array_push($params, ...$orderParams);
$params[] = $limit;
$params[] = $offset;

$rows = cx_fetch_all($conn, $sql, $types, $params);

$products = array_map(static function (array $r): array {
    return [
        'id' => (int)$r['product_id'],
        'name' => $r['title'],
        'price' => (float)$r['price'],
        'category' => $r['category_name'] ?? 'Other',
        'category_id' => $r['category_id'] !== null ? (int)$r['category_id'] : null,
        'condition' => $r['product_condition'],
        'location' => $r['seller_location'] ?: 'Campus',
        'posted_at' => $r['created_at'],
        'image' => cx_upload_url($r['image_path'], 'products'),
        'wishlisted' => (bool)$r['wishlisted'],
        'seller_id' => (int)$r['seller_id'],
        'seller_name' => $r['seller_name'],
    ];
}, $rows);

cx_json_response(true, 'OK', ['products' => $products, 'count' => count($products)]);

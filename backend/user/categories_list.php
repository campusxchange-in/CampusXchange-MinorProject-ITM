<?php
/* GET  CX/backend/user/categories_list.php
 * Active categories + number of active products in each. */
require_once __DIR__ . '/_user_common.php';
cx_require_method('GET');

$rows = cx_fetch_all(
    $conn,
    "SELECT c.category_id, c.category_name, c.description, c.invoice_required,
            COUNT(p.product_id) AS product_count
     FROM Categories c
     LEFT JOIN Products p
            ON p.category_id = c.category_id
           AND p.status = 'Active'
           AND p.deleted_at IS NULL
     WHERE c.status = 'Active' AND c.deleted_at IS NULL
     GROUP BY c.category_id
     ORDER BY c.category_name"
);

foreach ($rows as &$r) {
    $r['category_id'] = (int)$r['category_id'];
    $r['invoice_required'] = (bool)$r['invoice_required'];
    $r['product_count'] = (int)$r['product_count'];
}
unset($r);

cx_json_response(true, 'OK', ['categories' => $rows]);

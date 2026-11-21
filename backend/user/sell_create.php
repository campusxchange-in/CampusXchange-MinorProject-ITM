<?php
/* POST CX/backend/user/sell_create.php   (multipart/form-data)
 * fields: title, description, price, condition, category_id,
 *         mode = "publish" | "draft"
 * extra:  product_type (Electronics only)
 * files:  images[] (max 5, jpg/png/webp), invoice (pdf/jpg/png), product_box (jpg/png/webp)
 *         invoice + product_box are required to publish when the category has invoice_required = 1
 * Creates the product + images. Publishing requires an admin-verified profile. */
require_once __DIR__ . '/_user_common.php';
cx_require_method('POST');

$mode = (($_POST['mode'] ?? 'publish') === 'draft') ? 'draft' : 'publish';
$title = trim((string)($_POST['title'] ?? ''));
$description = trim((string)($_POST['description'] ?? ''));
$condition = (string)($_POST['condition'] ?? '');
$categoryId = cx_int($_POST['category_id'] ?? 0);
$productType = trim((string)($_POST['product_type'] ?? ''));
/* Bill + original box are only asked for these product types (when the category has invoice_required = 1). */
$invoiceTypes = ['Mobile', 'Laptop', 'Tablet', 'Smartwatch', 'Headphones'];
$allTypes = array_merge($invoiceTypes, ['Fan', 'Table Lamp', 'Keyboard', 'Mouse', 'Other']);
if ($productType !== '' && !in_array($productType, $allTypes, true)) {
    cx_json_response(false, 'Invalid product type.', [], 422);
}
$price = is_numeric($_POST['price'] ?? null) ? round((float)$_POST['price'], 2) : null;

if ($title === '' || mb_strlen($title) > 150) {
    cx_json_response(false, 'Enter a product name (max 150 characters).', [], 422);
}
if (mb_strlen($description) > 5000) {
    cx_json_response(false, 'Description is too long.', [], 422);
}
if ($price !== null && ($price < 0 || $price > 99999999)) {
    cx_json_response(false, 'Enter a valid price.', [], 422);
}
if ($condition !== '' && !in_array($condition, ['New', 'Like New', 'Good', 'Used'], true)) {
    cx_json_response(false, 'Invalid condition.', [], 422);
}

$category = null;
if ($categoryId > 0) {
    $category = cx_fetch_one(
        $conn,
        "SELECT category_id, invoice_required FROM Categories
         WHERE category_id = ? AND status = 'Active' AND deleted_at IS NULL LIMIT 1",
        'i',
        [$categoryId]
    );
    if (!$category) {
        cx_json_response(false, 'Choose a valid category.', [], 422);
    }
}

$images = cx_files_list('images');
if (count($images) > 5) {
    cx_json_response(false, 'You can upload maximum 5 images.', [], 422);
}
$invoiceFiles = cx_files_list('invoice');
$boxFiles = cx_files_list('product_box');

if ($mode === 'publish') {
    cx_require_verified($conn, $userId);
    if ($description === '' || $condition === '' || !$category || $price === null || $price <= 0) {
        cx_json_response(false, 'Please fill all required fields.', [], 422);
    }
    if (count($images) < 1) {
        cx_json_response(false, 'Please upload at least one product image.', [], 422);
    }
    $needsDocs = $category && (int)$category['invoice_required'] === 1 && in_array($productType, $invoiceTypes, true);
    if ($needsDocs && !$invoiceFiles) {
        cx_json_response(false, 'This category requires your purchase bill / invoice.', [], 422);
    }
    if ($needsDocs && !$boxFiles) {
        cx_json_response(false, 'This category requires a photo of the original product box.', [], 422);
    }
}

$imgTypes = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
$docTypes = $imgTypes + ['application/pdf' => 'pdf'];
$savedImages = [];
$invoicePath = null;
$boxPath = null;

try {
    foreach ($images as $img) {
        $savedImages[] = cx_save_upload($img, 'products', $imgTypes);
    }
    if ($invoiceFiles) {
        $invoicePath = '../uploads/invoices/' . cx_save_upload($invoiceFiles[0], 'invoices', $docTypes);
    }
    if ($boxFiles) {
        $boxPath = '../uploads/product_boxes/' . cx_save_upload($boxFiles[0], 'product_boxes', $imgTypes);
    }
} catch (RuntimeException $e) {
    cx_json_response(false, $e->getMessage(), [], 422);
}

$status = ($mode === 'publish') ? 'Active' : 'Draft';
$catParam = $category ? (int)$category['category_id'] : null;
$condParam = $condition !== '' ? $condition : null;

$conn->begin_transaction();
try {
    cx_exec(
        $conn,
        "INSERT INTO Products
            (seller_id, category_id, product_type, title, description, price, product_condition, invoice_document, product_box_image, status)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
        'iisssdssss',
        [$userId, $catParam, ($productType !== '' ? $productType : null), $title, $description, $price, $condParam, $invoicePath, $boxPath, $status]
    );
    $productId = (int)$conn->insert_id;

    foreach ($savedImages as $i => $file) {
        $primary = ($i === 0) ? 1 : 0;
        cx_exec(
            $conn,
            "INSERT INTO Product_Images (product_id, image_path, is_primary) VALUES (?, ?, ?)",
            'isi',
            [$productId, $file, $primary]
        );
    }
    $conn->commit();
} catch (Throwable $e) {
    $conn->rollback();
    error_log('sell_create failed: ' . $e->getMessage());
    cx_json_response(false, 'Could not save the listing. Please try again.', [], 500);
}

cx_json_response(
    true,
    $mode === 'publish' ? 'Your item has been published successfully!' : 'Your item has been saved as draft.',
    ['product_id' => $productId, 'status' => $status]
);

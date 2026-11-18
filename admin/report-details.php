<?php

/* =====================================================
   AUTHENTICATION
===================================================== */

include 'includes/auth_check.php';


/* =====================================================
   DATABASE CONNECTION
===================================================== */

include '../server/database/db_connect.php';


/* =====================================================
   CURRENT ADMIN
===================================================== */

$admin_id = $_SESSION['admin_id'] ?? 0;

if (!$admin_id) {
    header("Location: " . FRONTEND_URL . "/admin-login");
    exit;
}


/* =====================================================
   PAGE CONFIG
===================================================== */

$page_title = 'Report Details | CampusXchange';
$page_heading = 'Report Details';
$page_description = 'Review a submitted report and take the appropriate moderation action.';
$active_page = 'reports';
$page_css = 'report-details.css';


/* =====================================================
   REPORT ID
===================================================== */

$report_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$report_id) {
    header("Location: reports.php");
    exit;
}


/* =====================================================
   FETCH REPORT
===================================================== */

$sql = "
    SELECT
        r.report_id,
        r.reporter_id,
        r.reported_user_id,
        r.reported_product_id,
        r.report_type,
        r.report_category,
        r.reason,
        r.description,
        r.status,
        r.reviewed_by,
        r.admin_notes,
        r.created_at,
        r.updated_at,
        r.resolved_at,

        reporter.full_name AS reporter_name,
        reporter.email AS reporter_email,
        reporter.phone AS reporter_phone,
        reporter.role AS reporter_role,

        reported_user.full_name AS reported_user_name,
        reported_user.email AS reported_user_email,
        reported_user.phone AS reported_user_phone,
        reported_user.role AS reported_user_role,
        reported_user.status AS reported_user_status,
        reported_user.created_at AS reported_user_created_at,

        p.title AS product_title,
        p.description AS product_description,
        p.price AS product_price,
        p.product_condition,
        p.invoice_document,
        p.status AS product_status,
        p.category_id,
        p.seller_id,
        p.created_at AS product_created_at,
        p.updated_at AS product_updated_at,
        p.removed_by_admin_id,
        p.removal_reason,
        p.removed_at,

        seller.full_name AS seller_name,
        seller.email AS seller_email,
        seller.phone AS seller_phone,
        seller.role AS seller_role,
        seller.status AS seller_status

    FROM Reports r

    INNER JOIN Users reporter
        ON reporter.user_id = r.reporter_id

    LEFT JOIN Users reported_user
        ON reported_user.user_id = r.reported_user_id

    LEFT JOIN Products p
        ON p.product_id = r.reported_product_id

    LEFT JOIN Users seller
        ON seller.user_id = p.seller_id

    WHERE r.report_id = ?
      AND r.deleted_at IS NULL

    LIMIT 1
";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("Database error.");
}

$stmt->bind_param("i", $report_id);
$stmt->execute();

$result = $stmt->get_result();
$report = $result->fetch_assoc();

$stmt->close();


/* =====================================================
   REPORT NOT FOUND
===================================================== */

if (!$report) {
    header("Location: reports.php");
    exit;
}


/* =====================================================
   DETERMINE REPORT TYPE
===================================================== */

$is_product_report = ($report['report_type'] === 'Product');
$is_user_report = ($report['report_type'] === 'User');


/* =====================================================
   BREADCRUMBS
===================================================== */

$breadcrumbs = [
    ['label' => 'Reports', 'url' => 'reports.php'],
    ['label' => 'Report #' . $report_id]
];


/* =====================================================
   PRODUCT DATA
===================================================== */

// $product_image = [];
$product_transaction = null;
$product_previous_reports = [];


/* =====================================================
   FETCH PRODUCT IMAGES
===================================================== */

if ($is_product_report && !empty($report['reported_product_id'])) {

    $product_id = (int) $report['reported_product_id'];

    $sql = "
        SELECT
            image_id,
            image_path,
            is_primary,
            created_at
        FROM Product_Images
        WHERE product_id = ?
          AND deleted_at IS NULL
        ORDER BY is_primary DESC, image_id ASC
        LIMIT 1
    ";

    $stmt = $conn->prepare($sql);

    if ($stmt) {
        $stmt->bind_param("i", $product_id);
        $stmt->execute();

        $result = $stmt->get_result();

        $primary_image = $result->fetch_assoc();

        $stmt->close();
    } else {

        $primary_image = null;

    }


    /* =================================================
       FETCH TRANSACTION
    ================================================= */

    $sql = "
        SELECT
            t.transaction_id,
            t.seller_id,
            t.buyer_id,
            t.selling_price,
            t.transaction_status,
            t.transaction_date,
            t.notes,

            buyer.full_name AS buyer_name,
            buyer.email AS buyer_email,

            seller.full_name AS seller_name,
            seller.email AS seller_email

        FROM Transactions t

        INNER JOIN Users buyer
            ON buyer.user_id = t.buyer_id

        INNER JOIN Users seller
            ON seller.user_id = t.seller_id

        WHERE t.product_id = ?
          AND t.deleted_at IS NULL

        ORDER BY t.transaction_date DESC

        LIMIT 1
    ";

    $stmt = $conn->prepare($sql);

    if ($stmt) {
        $stmt->bind_param("i", $product_id);
        $stmt->execute();

        $result = $stmt->get_result();
        $product_transaction = $result->fetch_assoc();

        $stmt->close();
    }


    /* =================================================
       PREVIOUS REPORTS FOR PRODUCT
    ================================================= */

    $sql = "
        SELECT
            r.report_id,
            r.report_category,
            r.reason,
            r.status,
            r.created_at,
            u.full_name AS reporter_name

        FROM Reports r

        INNER JOIN Users u
            ON u.user_id = r.reporter_id

        WHERE r.reported_product_id = ?
          AND r.report_type = 'Product'
          AND r.deleted_at IS NULL
          AND r.report_id <> ?

        ORDER BY r.created_at DESC
    ";

    $stmt = $conn->prepare($sql);

    if ($stmt) {
        $stmt->bind_param("ii", $product_id, $report_id);
        $stmt->execute();

        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $product_previous_reports[] = $row;
        }

        $stmt->close();
    }
}


/* =====================================================
   USER DATA
===================================================== */

$user_previous_reports = [];
$user_ban_history = [];


/* =====================================================
   FETCH PREVIOUS USER REPORTS
===================================================== */

if ($is_user_report && !empty($report['reported_user_id'])) {

    $user_id = (int) $report['reported_user_id'];


    /* ================================================
       PREVIOUS REPORTS
    ================================================= */

    $sql = "
        SELECT
            r.report_id,
            r.report_category,
            r.reason,
            r.status,
            r.created_at,
            u.full_name AS reporter_name

        FROM Reports r

        INNER JOIN Users u
            ON u.user_id = r.reporter_id

        WHERE r.reported_user_id = ?
          AND r.report_type = 'User'
          AND r.deleted_at IS NULL
          AND r.report_id <> ?

        ORDER BY r.created_at DESC
    ";

    $stmt = $conn->prepare($sql);

    if ($stmt) {
        $stmt->bind_param("ii", $user_id, $report_id);
        $stmt->execute();

        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $user_previous_reports[] = $row;
        }

        $stmt->close();
    }


    /* ================================================
       BAN HISTORY
    ================================================= */

    $sql = "
        SELECT
            ub.ban_id,
            ub.report_id,
            ub.ban_type,
            ub.reason,
            ub.description,
            ub.start_date,
            ub.end_date,
            ub.status,
            ub.revoked_at,
            ub.revocation_reason,
            a.full_name AS admin_name

        FROM User_Bans ub

        INNER JOIN Admins a
            ON a.admin_id = ub.admin_id

        WHERE ub.user_id = ?
          AND ub.deleted_at IS NULL

        ORDER BY ub.created_at DESC
    ";

    $stmt = $conn->prepare($sql);

    if ($stmt) {
        $stmt->bind_param("i", $user_id);
        $stmt->execute();

        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $user_ban_history[] = $row;
        }

        $stmt->close();
    }
}


/* =====================================================
   FETCH REPORT EVIDENCE
===================================================== */

$report_evidence = [];

$sql = "
    SELECT
        evidence_id,
        file_path,
        file_name,
        file_type,
        file_size,
        uploaded_at
    FROM Report_Evidence
    WHERE report_id = ?
      AND deleted_at IS NULL
    ORDER BY uploaded_at ASC
";

$stmt = $conn->prepare($sql);

if ($stmt) {
    $stmt->bind_param("i", $report_id);
    $stmt->execute();

    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $report_evidence[] = $row;
    }

    $stmt->close();
}


/* =====================================================
   FORMAT HELPERS
===================================================== */

function formatDateTime($date)
{
    if (!$date) {
        return '—';
    }

    return date('d M Y, h:i A', strtotime($date));
}


function formatPrice($price)
{
    if ($price === null || $price === '') {
        return '—';
    }

    return '₹' . number_format((float) $price, 2);
}


function getStatusClass($status)
{
    return strtolower(str_replace(' ', '-', $status));
}


function formatFileSize($bytes)
{
    if (!$bytes) {
        return 'Unknown size';
    }

    if ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    }

    if ($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . ' KB';
    }

    return $bytes . ' B';
}


/* =====================================================
   INCLUDE HEADER
===================================================== */

include 'includes/admin_header.php';

?>

<div class="report-details-page">

    <!-- =================================================
         REPORT HEADER
    ================================================== -->

    <div class="report-details-header">

        <div class="report-header-main">

            <div class="report-type-icon <?= $is_product_report ? 'product' : 'user' ?>">
                <i class="fa-solid <?= $is_product_report ? 'fa-box' : 'fa-user' ?>"></i>
            </div>

            <div>
                <div class="report-id-label">
                    Report #<?= (int) $report['report_id'] ?>
                </div>

                <h2>
                    <?= htmlspecialchars($report['report_category']) ?>
                </h2>

                <p>
                    Submitted <?= htmlspecialchars(formatDateTime($report['created_at'])) ?>
                </p>
            </div>

        </div>


        <div class="report-header-actions">

            <span class="report-status-badge <?= getStatusClass($report['status']) ?>">
                <?= htmlspecialchars($report['status']) ?>
            </span>

            <a href="reports.php" class="btn btn-secondary">
                <i class="fa-solid fa-arrow-left"></i>
                Back to Reports
            </a>

        </div>

    </div>


    <!-- =================================================
         REPORT INFORMATION
    ================================================== -->

    <div class="details-grid">

        <section class="details-card report-information-card">

            <div class="card-header">
                <div>
                    <h3>
                        <i class="fa-solid fa-flag"></i>
                        Report Information
                    </h3>
                    <p>Details submitted by the reporter.</p>
                </div>
            </div>


            <div class="details-list">

                <div class="detail-row">
                    <span class="detail-label">Report ID</span>
                    <span class="detail-value">
                        #<?= (int) $report['report_id'] ?>
                    </span>
                </div>

                <div class="detail-row">
                    <span class="detail-label">Report Type</span>
                    <span class="detail-value">
                        <span class="report-type-badge <?= $is_product_report ? 'product' : 'user' ?>">
                            <i class="fa-solid <?= $is_product_report ? 'fa-box' : 'fa-user' ?>"></i>
                            <?= htmlspecialchars($report['report_type']) ?>
                        </span>
                    </span>
                </div>

                <div class="detail-row">
                    <span class="detail-label">Category</span>
                    <span class="detail-value">
                        <?= htmlspecialchars($report['report_category']) ?>
                    </span>
                </div>

                <div class="detail-row">
                    <span class="detail-label">Reason</span>
                    <span class="detail-value">
                        <?= htmlspecialchars($report['reason']) ?>
                    </span>
                </div>

                <div class="detail-row detail-row-column">
                    <span class="detail-label">Description</span>

                    <div class="description-box">
                        <?php if (!empty($report['description'])): ?>

                            <?= nl2br(htmlspecialchars($report['description'])) ?>

                        <?php else: ?>

                            <span class="muted-text">
                                No additional description was provided.
                            </span>

                        <?php endif; ?>
                    </div>
                </div>

                <div class="detail-row">
                    <span class="detail-label">Submitted</span>
                    <span class="detail-value">
                        <?= htmlspecialchars(formatDateTime($report['created_at'])) ?>
                    </span>
                </div>

                <?php if ($report['resolved_at']): ?>

                    <div class="detail-row">
                        <span class="detail-label">Resolved</span>
                        <span class="detail-value">
                            <?= htmlspecialchars(formatDateTime($report['resolved_at'])) ?>
                        </span>
                    </div>

                <?php endif; ?>

                <?php if (!empty($report['admin_notes'])): ?>

                    <div class="detail-row detail-row-column">
                        <span class="detail-label">Admin Notes</span>

                        <div class="description-box admin-notes">
                            <?= nl2br(htmlspecialchars($report['admin_notes'])) ?>
                        </div>
                    </div>

                <?php endif; ?>

            </div>

        </section>


        <!-- =================================================
             REPORTER
        ================================================== -->

        <section class="details-card">

            <div class="card-header">
                <div>
                    <h3>
                        <i class="fa-solid fa-user-pen"></i>
                        Reporter
                    </h3>
                    <p>User who submitted this report.</p>
                </div>
            </div>


            <div class="person-profile">

                <div class="person-avatar">
                    <?= strtoupper(substr($report['reporter_name'], 0, 1)) ?>
                </div>

                <div class="person-main">
                    <h4>
                        <?= htmlspecialchars($report['reporter_name']) ?>
                    </h4>

                    <p>
                        <?= htmlspecialchars($report['reporter_email']) ?>
                    </p>

                    <?php if (!empty($report['reporter_role'])): ?>
                        <span class="role-badge">
                            <?= htmlspecialchars($report['reporter_role']) ?>
                        </span>
                    <?php endif; ?>
                </div>

            </div>


            <div class="person-contact">

                <?php if (!empty($report['reporter_phone'])): ?>

                    <div>
                        <i class="fa-solid fa-phone"></i>
                        <?= htmlspecialchars($report['reporter_phone']) ?>
                    </div>

                <?php endif; ?>

                <div>
                    <i class="fa-solid fa-envelope"></i>
                    <?= htmlspecialchars($report['reporter_email']) ?>
                </div>

            </div>

        </section>

    </div>


    <!-- =================================================
         PRODUCT REPORT
    ================================================== -->

    <?php if ($is_product_report): ?>

        <section class="details-card full-width-card">

            <div class="card-header">

                <div>
                    <h3>
                        <i class="fa-solid fa-box"></i>
                        Reported Product
                    </h3>

                    <p>
                        Product associated with this report.
                    </p>
                </div>

                <a
                    href="product-details.php?id=<?= (int) $report['reported_product_id'] ?>"
                    class="btn btn-secondary"
                >
                    <i class="fa-solid fa-arrow-up-right-from-square"></i>
                    View Product
                </a>

            </div>


            <div class="product-overview">

                <!-- Product Image -->

                <div class="product-images-panel">

                    <?php if (!empty($primary_image)): ?>

                        <div class="main-product-image">

                            <img
                                src="../uploads/products/<?= htmlspecialchars($primary_image['image_path']) ?>"
                                alt="<?= htmlspecialchars($report['product_title'] ?: 'Product image') ?>"
                            >

                        </div>

                    <?php else: ?>

                        <div class="no-product-image">
                            <i class="fa-solid fa-image"></i>
                            <span>No product image</span>
                        </div>

                    <?php endif; ?>

                </div>


                <!-- Product Details -->

                <div class="product-summary">

                    <div class="product-title-row">

                        <div>
                            <span class="product-id">
                                Product #<?= (int) $report['reported_product_id'] ?>
                            </span>

                            <h3>
                                <?= htmlspecialchars($report['product_title'] ?: 'Untitled Product') ?>
                            </h3>
                        </div>

                        <span class="product-status-badge <?= getStatusClass($report['product_status']) ?>">
                            <?= htmlspecialchars($report['product_status']) ?>
                        </span>

                    </div>


                    <div class="product-price">
                        <?= formatPrice($report['product_price']) ?>
                    </div>


                    <div class="product-meta-grid">

                        <div>
                            <span>Condition</span>
                            <strong>
                                <?= htmlspecialchars($report['product_condition'] ?: '—') ?>
                            </strong>
                        </div>

                        <div>
                            <span>Category</span>
                            <strong>
                                <?= htmlspecialchars($report['category_id'] ? 'Category #' . $report['category_id'] : '—') ?>
                            </strong>
                        </div>

                        <div>
                            <span>Listed</span>
                            <strong>
                                <?= htmlspecialchars(formatDateTime($report['product_created_at'])) ?>
                            </strong>
                        </div>

                        <div>
                            <span>Last Updated</span>
                            <strong>
                                <?= htmlspecialchars(formatDateTime($report['product_updated_at'])) ?>
                            </strong>
                        </div>

                        <div>
                            <span>Invoice</span>
                            <strong>
                                <?= !empty($report['invoice_document']) ? 'Available' : 'Not Available' ?>
                            </strong>
                        </div>

                    </div>


                    <div class="product-description">

                        <h4>Description</h4>

                        <?php if (!empty($report['product_description'])): ?>

                            <p>
                                <?= nl2br(htmlspecialchars($report['product_description'])) ?>
                            </p>

                        <?php else: ?>

                            <p class="muted-text">
                                No product description was provided.
                            </p>

                        <?php endif; ?>

                    </div>

                </div>

            </div>

        </section>


        <!-- =================================================
             SELLER
        ================================================== -->

        <section class="details-card">

            <div class="card-header">
                <div>
                    <h3>
                        <i class="fa-solid fa-store"></i>
                        Seller
                    </h3>
                    <p>Account associated with the reported product.</p>
                </div>

                <a
                    href="user_view.php?id=<?= (int) $report['seller_id'] ?>"
                    class="btn btn-secondary"
                >
                    <i class="fa-solid fa-user"></i>
                    View User
                </a>
            </div>


            <div class="person-profile">

                <div class="person-avatar">
                    <?= strtoupper(substr($report['seller_name'], 0, 1)) ?>
                </div>

                <div class="person-main">

                    <h4>
                        <?= htmlspecialchars($report['seller_name']) ?>
                    </h4>

                    <p>
                        <?= htmlspecialchars($report['seller_email']) ?>
                    </p>

                    <?php if (!empty($report['seller_role'])): ?>

                        <span class="role-badge">
                            <?= htmlspecialchars($report['seller_role']) ?>
                        </span>

                    <?php endif; ?>

                </div>

            </div>


            <div class="account-status-row">

                <span>Account Status</span>

                <span class="account-status-badge <?= getStatusClass($report['seller_status']) ?>">
                    <?= htmlspecialchars($report['seller_status']) ?>
                </span>

            </div>

        </section>


        <!-- =================================================
             TRANSACTION
        ================================================== -->

        <section class="details-card">

            <div class="card-header">

                <div>
                    <h3>
                        <i class="fa-solid fa-receipt"></i>
                        Transaction
                    </h3>

                    <p>
                        Transaction information associated with the product.
                    </p>
                </div>

            </div>


            <?php if ($product_transaction): ?>

                <div class="transaction-grid">

                    <div>
                        <span>Transaction ID</span>
                        <strong>
                            #<?= (int) $product_transaction['transaction_id'] ?>
                        </strong>
                    </div>

                    <div>
                        <span>Selling Price</span>
                        <strong>
                            <?= formatPrice($product_transaction['selling_price']) ?>
                        </strong>
                    </div>

                    <div>
                        <span>Status</span>
                        <strong>
                            <?= htmlspecialchars($product_transaction['transaction_status']) ?>
                        </strong>
                    </div>

                    <div>
                        <span>Date</span>
                        <strong>
                            <?= htmlspecialchars(formatDateTime($product_transaction['transaction_date'])) ?>
                        </strong>
                    </div>

                    <div>
                        <span>Buyer</span>
                        <strong>
                            <?= htmlspecialchars($product_transaction['buyer_name']) ?>
                        </strong>
                    </div>

                    <div>
                        <span>Seller</span>
                        <strong>
                            <?= htmlspecialchars($product_transaction['seller_name']) ?>
                        </strong>
                    </div>

                </div>

            <?php else: ?>

                <div class="empty-inline">
                    <i class="fa-solid fa-receipt"></i>
                    <span>No transaction is associated with this product.</span>
                </div>

            <?php endif; ?>

        </section>


        <!-- =================================================
             PREVIOUS PRODUCT REPORTS
        ================================================== -->

        <section class="details-card full-width-card">

            <div class="card-header">

                <div>
                    <h3>
                        <i class="fa-solid fa-flag"></i>
                        Previous Reports for This Product
                    </h3>

                    <p>
                        Other reports submitted against the same product.
                    </p>
                </div>

            </div>


            <?php if (!empty($product_previous_reports)): ?>

                <div class="table-wrapper">

                    <table class="details-table">

                        <thead>
                            <tr>
                                <th>Report</th>
                                <th>Reporter</th>
                                <th>Category</th>
                                <th>Reason</th>
                                <th>Status</th>
                                <th>Date</th>
                                <th></th>
                            </tr>
                        </thead>

                        <tbody>

                            <?php foreach ($product_previous_reports as $previous_report): ?>

                                <tr>

                                    <td>
                                        <strong>
                                            #<?= (int) $previous_report['report_id'] ?>
                                        </strong>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars($previous_report['reporter_name']) ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars($previous_report['report_category']) ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars($previous_report['reason']) ?>
                                    </td>

                                    <td>
                                        <span class="report-status-badge <?= getStatusClass($previous_report['status']) ?>">
                                            <?= htmlspecialchars($previous_report['status']) ?>
                                        </span>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars(formatDateTime($previous_report['created_at'])) ?>
                                    </td>

                                    <td>
                                        <a
                                            href="report-details.php?id=<?= (int) $previous_report['report_id'] ?>"
                                            class="table-action"
                                        >
                                            View
                                        </a>
                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        </tbody>

                    </table>

                </div>

            <?php else: ?>

                <div class="empty-inline">
                    <i class="fa-solid fa-circle-check"></i>
                    <span>No previous reports were found for this product.</span>
                </div>

            <?php endif; ?>

        </section>

    <?php endif; ?>


    <!-- =================================================
         USER REPORT
    ================================================== -->

    <?php if ($is_user_report): ?>

        <section class="details-card full-width-card">

            <div class="card-header">

                <div>
                    <h3>
                        <i class="fa-solid fa-user"></i>
                        Reported User
                    </h3>

                    <p>
                        Account associated with this report.
                    </p>
                </div>

                <a
                    href="user_view.php?id=<?= (int) $report['reported_user_id'] ?>"
                    class="btn btn-secondary"
                >
                    <i class="fa-solid fa-arrow-up-right-from-square"></i>
                    View User
                </a>

            </div>


            <div class="user-profile-large">

                <div class="large-avatar">
                    <?= strtoupper(substr($report['reported_user_name'], 0, 1)) ?>
                </div>

                <div class="user-profile-main">

                    <div class="user-name-row">

                        <div>
                            <span class="user-id">
                                User #<?= (int) $report['reported_user_id'] ?>
                            </span>

                            <h3>
                                <?= htmlspecialchars($report['reported_user_name']) ?>
                            </h3>
                        </div>

                        <span class="account-status-badge <?= getStatusClass($report['reported_user_status']) ?>">
                            <?= htmlspecialchars($report['reported_user_status']) ?>
                        </span>

                    </div>


                    <div class="user-meta-grid">

                        <div>
                            <span>Email</span>
                            <strong>
                                <?= htmlspecialchars($report['reported_user_email']) ?>
                            </strong>
                        </div>

                        <div>
                            <span>Phone</span>
                            <strong>
                                <?= htmlspecialchars($report['reported_user_phone'] ?: '—') ?>
                            </strong>
                        </div>

                        <div>
                            <span>Role</span>
                            <strong>
                                <?= htmlspecialchars($report['reported_user_role'] ?: '—') ?>
                            </strong>
                        </div>

                        <div>
                            <span>Account Created</span>
                            <strong>
                                <?= htmlspecialchars(formatDateTime($report['reported_user_created_at'])) ?>
                            </strong>
                        </div>

                    </div>

                </div>

            </div>

        </section>


        <!-- =================================================
             PREVIOUS USER REPORTS
        ================================================== -->

        <section class="details-card full-width-card">

            <div class="card-header">

                <div>
                    <h3>
                        <i class="fa-solid fa-flag"></i>
                        Previous Reports Against This User
                    </h3>

                    <p>
                        Other reports submitted against this account.
                    </p>
                </div>

            </div>


            <?php if (!empty($user_previous_reports)): ?>

                <div class="table-wrapper">

                    <table class="details-table">

                        <thead>
                            <tr>
                                <th>Report</th>
                                <th>Reporter</th>
                                <th>Category</th>
                                <th>Reason</th>
                                <th>Status</th>
                                <th>Date</th>
                                <th></th>
                            </tr>
                        </thead>

                        <tbody>

                            <?php foreach ($user_previous_reports as $previous_report): ?>

                                <tr>

                                    <td>
                                        <strong>
                                            #<?= (int) $previous_report['report_id'] ?>
                                        </strong>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars($previous_report['reporter_name']) ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars($previous_report['report_category']) ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars($previous_report['reason']) ?>
                                    </td>

                                    <td>
                                        <span class="report-status-badge <?= getStatusClass($previous_report['status']) ?>">
                                            <?= htmlspecialchars($previous_report['status']) ?>
                                        </span>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars(formatDateTime($previous_report['created_at'])) ?>
                                    </td>

                                    <td>
                                        <a
                                            href="report-details.php?id=<?= (int) $previous_report['report_id'] ?>"
                                            class="table-action"
                                        >
                                            View
                                        </a>
                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        </tbody>

                    </table>

                </div>

            <?php else: ?>

                <div class="empty-inline">
                    <i class="fa-solid fa-circle-check"></i>
                    <span>No previous reports were found against this user.</span>
                </div>

            <?php endif; ?>

        </section>


        <!-- =================================================
             BAN HISTORY
        ================================================== -->

        <section class="details-card full-width-card">

            <div class="card-header">

                <div>
                    <h3>
                        <i class="fa-solid fa-gavel"></i>
                        Moderation History
                    </h3>

                    <p>
                        Previous warnings and bans issued to this user.
                    </p>
                </div>

            </div>


            <?php if (!empty($user_ban_history)): ?>

                <div class="table-wrapper">

                    <table class="details-table">

                        <thead>
                            <tr>
                                <th>Type</th>
                                <th>Reason</th>
                                <th>Status</th>
                                <th>Start</th>
                                <th>End</th>
                                <th>Admin</th>
                            </tr>
                        </thead>

                        <tbody>

                            <?php foreach ($user_ban_history as $ban): ?>

                                <tr>

                                    <td>
                                        <span class="ban-type-badge <?= strtolower($ban['ban_type']) ?>">
                                            <?= htmlspecialchars($ban['ban_type']) ?>
                                        </span>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars($ban['reason']) ?>
                                    </td>

                                    <td>
                                        <span class="ban-status-badge <?= getStatusClass($ban['status']) ?>">
                                            <?= htmlspecialchars($ban['status']) ?>
                                        </span>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars(formatDateTime($ban['start_date'])) ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars(formatDateTime($ban['end_date'])) ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars($ban['admin_name']) ?>
                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        </tbody>

                    </table>

                </div>

            <?php else: ?>

                <div class="empty-inline">
                    <i class="fa-solid fa-circle-check"></i>
                    <span>No previous moderation actions were found for this user.</span>
                </div>

            <?php endif; ?>

        </section>

    <?php endif; ?>


    <!-- =================================================
         REPORT EVIDENCE
    ================================================== -->

    <section class="details-card full-width-card">

        <div class="card-header">

            <div>
                <h3>
                    <i class="fa-solid fa-paperclip"></i>
                    Evidence
                </h3>

                <p>
                    Files submitted with this report.
                </p>
            </div>

        </div>


        <?php if (!empty($report_evidence)): ?>

            <div class="evidence-grid">

                <?php foreach ($report_evidence as $evidence): ?>

                    <a
                        href="../uploads/reports/<?= htmlspecialchars($evidence['file_path']) ?>"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="evidence-item"
                    >

                        <div class="evidence-icon">
                            <i class="fa-solid fa-file"></i>
                        </div>

                        <div class="evidence-info">

                            <strong>
                                <?= htmlspecialchars($evidence['file_name']) ?>
                            </strong>

                            <span>
                                <?= htmlspecialchars($evidence['file_type'] ?: 'File') ?>
                                ·
                                <?= htmlspecialchars(formatFileSize($evidence['file_size'])) ?>
                            </span>

                        </div>

                        <i class="fa-solid fa-arrow-up-right-from-square evidence-open"></i>

                    </a>

                <?php endforeach; ?>

            </div>

        <?php else: ?>

            <div class="empty-inline">
                <i class="fa-solid fa-paperclip"></i>
                <span>No evidence was submitted with this report.</span>
            </div>

        <?php endif; ?>

    </section>


    <!-- =================================================
         EXISTING MODERATION RESULT
    ================================================== -->

    <?php if (
        $report['status'] === 'Resolved' ||
        $report['status'] === 'Rejected'
    ): ?>

        <section class="details-card moderation-result-card">

            <div class="moderation-result-icon <?= $report['status'] === 'Resolved' ? 'resolved' : 'rejected' ?>">
                <i class="fa-solid <?= $report['status'] === 'Resolved' ? 'fa-check' : 'fa-xmark' ?>"></i>
            </div>

            <div>

                <h3>
                    Report <?= htmlspecialchars($report['status']) ?>
                </h3>

                <p>
                    This report has already been processed and does not require another moderation action.
                </p>

                <?php if (!empty($report['admin_notes'])): ?>

                    <div class="result-notes">
                        <strong>Admin Notes</strong>
                        <p>
                            <?= nl2br(htmlspecialchars($report['admin_notes'])) ?>
                        </p>
                    </div>

                <?php endif; ?>

            </div>

        </section>

    <?php endif; ?>


    <!-- =================================================
         MODERATION ACTIONS
    ================================================== -->

    <?php if (
        $report['status'] === 'Pending' ||
        $report['status'] === 'Under Review'
    ): ?>

        <section class="details-card moderation-actions-card">

            <div class="card-header">

                <div>
                    <h3>
                        <i class="fa-solid fa-shield-halved"></i>
                        Moderation Actions
                    </h3>

                    <p>
                        Review the evidence and choose the appropriate action.
                    </p>
                </div>

            </div>


            <div class="moderation-warning">

                <i class="fa-solid fa-circle-info"></i>

                <p>
                    Moderation actions affect the reported account or product.
                    Make sure the submitted report and available evidence have been reviewed before taking action.
                </p>

            </div>

<?php if ($report['status'] === 'Pending'): ?>

    <!-- Start Review -->

    <form
        action="report-action.php"
        method="POST"
        class="moderation-review-form"
    >

        <input
            type="hidden"
            name="action"
            value="start_review"
        >

        <input
            type="hidden"
            name="report_id"
            value="<?= (int) $report_id ?>"
        >

        <div class="review-action-content">

            <div class="review-action-icon">
                <i class="fa-solid fa-magnifying-glass"></i>
            </div>

            <div>

                <h4>Start Review</h4>

                <p>
                    Mark this report as under review before taking a moderation action.
                </p>

            </div>

        </div>

        <button
            type="submit"
            class="btn btn-primary under-review-btn"
        >
            <i class="fa-solid fa-magnifying-glass"></i>
            Start Review
        </button>

    </form>

<?php endif; ?>

            <div class="moderation-action-grid">
        
                <!-- Reject Report -->

                <form
                    action="report-action.php"
                    method="POST"
                    class="moderation-action-form"
                >

                    <input
                        type="hidden"
                        name="action"
                        value="reject_report"
                    >

                    <input
                        type="hidden"
                        name="report_id"
                        value="<?= (int) $report_id ?>"
                    >

                    <div class="action-icon reject">
                        <i class="fa-solid fa-xmark"></i>
                    </div>

                    <div class="action-content">

                        <h4>Reject Report</h4>

                        <p>
                            Mark the report as invalid or unsupported without taking moderation action against the target.
                        </p>

                        <textarea
                            name="admin_notes"
                            placeholder="Reason for rejecting this report..."
                            required
                        ></textarea>

                        <button
                            type="button"
                            class="btn btn-danger moderation-confirm-button"
                            data-action="reject"
                        >
                            <i class="fa-solid fa-xmark"></i>
                            Reject Report
                        </button>

                    </div>

                </form>


                <?php if ($is_product_report): ?>

                    <!-- Remove Product -->

                    <form
                        action="report-action.php"
                        method="POST"
                        class="moderation-action-form danger-action"
                    >

                        <input
                            type="hidden"
                            name="action"
                            value="remove_product"
                        >

                        <input
                            type="hidden"
                            name="report_id"
                            value="<?= (int) $report_id ?>"
                        >

                        <input
                            type="hidden"
                            name="product_id"
                            value="<?= (int) $report['reported_product_id'] ?>"
                        >

                        <div class="action-icon danger">
                            <i class="fa-solid fa-box-archive"></i>
                        </div>

                        <div class="action-content">

                            <h4>Remove Product</h4>

                            <p>
                                Remove the product from the public marketplace and resolve this report.
                            </p>

                            <textarea
                                name="removal_reason"
                                placeholder="Reason for removing this product..."
                                required
                            ></textarea>

                            <button
                                type="button"
                                class="btn btn-danger moderation-confirm-button"
                                data-action="remove-product"
                            >
                                <i class="fa-solid fa-box-archive"></i>
                                Remove Product
                            </button>

                        </div>

                    </form>

                <?php endif; ?>


                <?php if ($is_user_report): ?>

                    <!-- Warning -->

                    <form
                        action="report-action.php"
                        method="POST"
                        class="moderation-action-form"
                    >

                        <input
                            type="hidden"
                            name="action"
                            value="issue_warning"
                        >

                        <input
                            type="hidden"
                            name="report_id"
                            value="<?= (int) $report_id ?>"
                        >

                        <input
                            type="hidden"
                            name="user_id"
                            value="<?= (int) $report['reported_user_id'] ?>"
                        >

                        <div class="action-icon warning">
                            <i class="fa-solid fa-triangle-exclamation"></i>
                        </div>

                        <div class="action-content">

                            <h4>Issue Warning</h4>

                            <p>
                                Record a formal warning without changing the user's account status.
                            </p>

                            <input
                                type="text"
                                name="reason"
                                placeholder="Warning reason..."
                                required
                            >

                            <textarea
                                name="description"
                                placeholder="Additional details..."
                            ></textarea>

                            <button
                                type="button"
                                class="btn btn-warning moderation-confirm-button"
                                data-action="warning"
                            >
                                <i class="fa-solid fa-triangle-exclamation"></i>
                                Issue Warning
                            </button>

                        </div>

                    </form>


                    <!-- Temporary Ban -->

                    <form
                        action="report-action.php"
                        method="POST"
                        class="moderation-action-form"
                    >

                        <input
                            type="hidden"
                            name="action"
                            value="temporary_ban"
                        >

                        <input
                            type="hidden"
                            name="report_id"
                            value="<?= (int) $report_id ?>"
                        >

                        <input
                            type="hidden"
                            name="user_id"
                            value="<?= (int) $report['reported_user_id'] ?>"
                        >

                        <div class="action-icon warning">
                            <i class="fa-solid fa-clock"></i>
                        </div>

                        <div class="action-content">

                            <h4>Temporary Ban</h4>

                            <p>
                                Suspend the user's account for a defined period.
                            </p>

                            <input
                                type="text"
                                name="reason"
                                placeholder="Ban reason..."
                                required
                            >

                            <div class="duration-field">

                                <input
                                    type="number"
                                    name="duration_days"
                                    min="1"
                                    max="365"
                                    placeholder="Days"
                                    required
                                >

                                <span>days</span>

                            </div>

                            <textarea
                                name="description"
                                placeholder="Additional details..."
                            ></textarea>

                            <button
                                type="button"
                                class="btn btn-warning moderation-confirm-button"
                                data-action="temporary-ban"
                            >
                                <i class="fa-solid fa-clock"></i>
                                Temporary Ban
                            </button>

                        </div>

                    </form>


                    <!-- Permanent Ban -->

                    <form
                        action="report-action.php"
                        method="POST"
                        class="moderation-action-form danger-action"
                    >

                        <input
                            type="hidden"
                            name="action"
                            value="permanent_ban"
                        >

                        <input
                            type="hidden"
                            name="report_id"
                            value="<?= (int) $report_id ?>"
                        >

                        <input
                            type="hidden"
                            name="user_id"
                            value="<?= (int) $report['reported_user_id'] ?>"
                        >

                        <div class="action-icon danger">
                            <i class="fa-solid fa-ban"></i>
                        </div>

                        <div class="action-content">

                            <h4>Permanent Ban</h4>

                            <p>
                                Permanently ban the user's account from the platform.
                            </p>

                            <input
                                type="text"
                                name="reason"
                                placeholder="Ban reason..."
                                required
                            >

                            <textarea
                                name="description"
                                placeholder="Additional details..."
                            ></textarea>

                            <button
                                type="button"
                                class="btn btn-danger moderation-confirm-button"
                                data-action="permanent-ban"
                            >
                                <i class="fa-solid fa-ban"></i>
                                Permanent Ban
                            </button>

                        </div>

                    </form>

                <?php endif; ?>

            </div>

        </section>

    <?php endif; ?>

</div>

<!-- =====================================================
     MODERATION CONFIRMATION MODAL
===================================================== -->

<div
    class="moderation-modal"
    id="moderationModal"
    aria-hidden="true"
>

    <div class="moderation-modal-overlay"></div>

    <div
        class="moderation-modal-dialog"
        role="dialog"
        aria-modal="true"
        aria-labelledby="moderationModalTitle"
    >

        <button
            type="button"
            class="moderation-modal-close"
            id="moderationModalClose"
            aria-label="Close"
        >
            <i class="fa-solid fa-xmark"></i>
        </button>


        <div
            class="moderation-modal-icon"
            id="moderationModalIcon"
        >
            <i class="fa-solid fa-shield-halved"></i>
        </div>


        <div class="moderation-modal-content">

            <h3 id="moderationModalTitle">
                Confirm Moderation Action
            </h3>

            <p id="moderationModalMessage">
                Are you sure you want to continue?
            </p>

        </div>


        <div class="moderation-modal-actions">

            <button
                type="button"
                class="btn btn-secondary"
                id="moderationModalCancel"
            >
                Cancel
            </button>

            <button
                type="button"
                class="btn btn-danger"
                id="moderationModalConfirm"
            >
                Confirm Action
            </button>

        </div>

    </div>

</div>



<script>
document.addEventListener('DOMContentLoaded', function () {

    const modal = document.getElementById('moderationModal');
    const overlay = document.querySelector('.moderation-modal-overlay');
    const closeButton = document.getElementById('moderationModalClose');
    const cancelButton = document.getElementById('moderationModalCancel');
    const confirmButton = document.getElementById('moderationModalConfirm');

    const modalTitle = document.getElementById('moderationModalTitle');
    const modalMessage = document.getElementById('moderationModalMessage');
    const modalIcon = document.getElementById('moderationModalIcon');

    let pendingForm = null;


    /* =================================================
       MODERATION ACTION CONFIG
    ================================================= */

    const actionConfig = {

        reject: {
            title: 'Reject Report',
            message: 'Are you sure you want to reject this report? The report will be marked as rejected and no moderation action will be taken against the reported target.',
            icon: 'fa-xmark',
            type: 'danger',
            buttonText: 'Reject Report'
        },

        'remove-product': {
            title: 'Remove Product',
            message: 'Are you sure you want to remove this product? It will no longer be publicly available on the marketplace and this report will be resolved.',
            icon: 'fa-box-archive',
            type: 'danger',
            buttonText: 'Remove Product'
        },

        warning: {
            title: 'Issue Warning',
            message: 'Are you sure you want to issue a warning to this user? A moderation warning will be recorded in the user’s moderation history.',
            icon: 'fa-triangle-exclamation',
            type: 'warning',
            buttonText: 'Issue Warning'
        },

        'temporary-ban': {
            title: 'Temporary Ban',
            message: 'Are you sure you want to temporarily suspend this user? Their account status will be changed to Suspended for the selected duration.',
            icon: 'fa-clock',
            type: 'warning',
            buttonText: 'Temporary Ban'
        },

        'permanent-ban': {
            title: 'Permanent Ban',
            message: 'Are you sure you want to permanently ban this user? Their account status will be changed to Banned.',
            icon: 'fa-ban',
            type: 'danger',
            buttonText: 'Permanent Ban'
        }

    };


    /* =================================================
       OPEN MODAL
    ================================================= */

    function openModal(button) {

        const action = button.dataset.action;
        const config = actionConfig[action];

        if (!config) {
            return;
        }

        pendingForm = button.closest('form');

        if (!pendingForm) {
            return;
        }


        /* ---------------------------------------------
           Modal Content
        --------------------------------------------- */

        modalTitle.textContent = config.title;
        modalMessage.textContent = config.message;

        confirmButton.textContent = config.buttonText;


        /* ---------------------------------------------
           Icon
        --------------------------------------------- */

        modalIcon.className = 'moderation-modal-icon ' + config.type;

        modalIcon.innerHTML =
            '<i class="fa-solid ' + config.icon + '"></i>';


        /* ---------------------------------------------
           Confirm Button
        --------------------------------------------- */

        confirmButton.className = 'btn ' +
            (config.type === 'warning'
                ? 'btn-warning'
                : 'btn-danger');


        /* ---------------------------------------------
           Show Modal
        --------------------------------------------- */

        modal.classList.add('show');
        modal.setAttribute('aria-hidden', 'false');

        document.body.classList.add('modal-open');

        setTimeout(function () {
            confirmButton.focus();
        }, 50);
    }


    /* =================================================
       CLOSE MODAL
    ================================================= */

    function closeModal() {

        modal.classList.remove('show');
        modal.setAttribute('aria-hidden', 'true');

        document.body.classList.remove('modal-open');

        pendingForm = null;
    }


    /* =================================================
       ACTION BUTTONS
    ================================================= */

    document
        .querySelectorAll('.moderation-confirm-button')
        .forEach(function (button) {

            button.addEventListener('click', function () {
                openModal(this);
            });

        });


    /* =================================================
       CONFIRM ACTION
    ================================================= */

    confirmButton.addEventListener('click', function () {

        if (!pendingForm) {
            return;
        }

        const form = pendingForm;

        pendingForm = null;

        confirmButton.disabled = true;

        form.submit();

    });


    /* =================================================
       CANCEL
    ================================================= */

    cancelButton.addEventListener('click', function () {
        closeModal();
    });


    /* =================================================
       CLOSE BUTTON
    ================================================= */

    closeButton.addEventListener('click', function () {
        closeModal();
    });


    /* =================================================
       OVERLAY CLICK
    ================================================= */

    overlay.addEventListener('click', function () {
        closeModal();
    });


    /* =================================================
       ESCAPE KEY
    ================================================= */

    document.addEventListener('keydown', function (event) {

        if (
            event.key === 'Escape' &&
            modal.classList.contains('show')
        ) {
            closeModal();
        }

    });

});
</script>

<?php include 'includes/admin_footer.php'; ?>


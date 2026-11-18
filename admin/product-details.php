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
   PAGE CONFIGURATION
===================================================== */

$page_title = 'Product Details | CampusXchange';
$page_heading = 'Product Details';
$page_description = 'View product information, seller details, reports, and moderation history.';
$active_page = 'products';
$page_css = 'product-details.css';


/* =====================================================
   PRODUCT ID
===================================================== */

$product_id = filter_input(
    INPUT_GET,
    'id',
    FILTER_VALIDATE_INT
);


if (!$product_id || $product_id <= 0) {

    header('Location: products');

    exit;

}


/* =====================================================
   FETCH PRODUCT
===================================================== */

$product = null;


$sql = "
    SELECT
        p.product_id,
        p.seller_id,
        p.category_id,
        p.title,
        p.description,
        p.price,
        p.product_condition,
        p.invoice_document,
        p.status,
        p.removed_by_admin_id,
        p.removal_reason,
        p.removed_at,
        p.created_at,
        p.updated_at,

        u.full_name AS seller_name,
        u.email AS seller_email,
        u.phone AS seller_phone,
        u.role AS seller_role,
        u.status AS seller_status,

        c.category_name,
        c.invoice_required

    FROM Products p

    INNER JOIN Users u
        ON u.user_id = p.seller_id

    LEFT JOIN Categories c
        ON c.category_id = p.category_id

    WHERE p.product_id = ?
      AND p.status IN (
          'Active',
          'Inactive',
          'Sold',
          'Removed'
      )
      AND p.deleted_at IS NULL

    LIMIT 1
";


$stmt = $conn->prepare($sql);


if ($stmt) {

    $stmt->bind_param(
        'i',
        $product_id
    );

    $stmt->execute();

    $result = $stmt->get_result();

    $product = $result->fetch_assoc();

    $stmt->close();

}


if (!$product) {

    header('Location: products');

    exit;

}


/* =====================================================
   FETCH PRODUCT IMAGES
===================================================== */

$images = [];


$sql = "
    SELECT
        image_id,
        image_path,
        is_primary,
        created_at

    FROM Product_Images

    WHERE product_id = ?
      AND deleted_at IS NULL

    ORDER BY
        is_primary DESC,
        image_id ASC
        LIMIT 4
";


$stmt = $conn->prepare($sql);


if ($stmt) {

    $stmt->bind_param(
        'i',
        $product_id
    );

    $stmt->execute();

    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {

        $images[] = $row;

    }

    $stmt->close();

}


/* =====================================================
   REPORT SUMMARY
===================================================== */

$report_summary = [
    'total' => 0,
    'pending' => 0,
    'under_review' => 0,
    'resolved' => 0,
    'rejected' => 0
];


$sql = "
    SELECT
        COUNT(*) AS total,

        SUM(
            CASE
                WHEN status = 'Pending'
                THEN 1
                ELSE 0
            END
        ) AS pending,

        SUM(
            CASE
                WHEN status = 'Under Review'
                THEN 1
                ELSE 0
            END
        ) AS under_review,

        SUM(
            CASE
                WHEN status = 'Resolved'
                THEN 1
                ELSE 0
            END
        ) AS resolved,

        SUM(
            CASE
                WHEN status = 'Rejected'
                THEN 1
                ELSE 0
            END
        ) AS rejected

    FROM Reports

    WHERE reported_product_id = ?
      AND report_type = 'Product'
      AND deleted_at IS NULL
";


$stmt = $conn->prepare($sql);


if ($stmt) {

    $stmt->bind_param(
        'i',
        $product_id
    );

    $stmt->execute();

    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {

        $report_summary['total'] =
            (int) $row['total'];

        $report_summary['pending'] =
            (int) $row['pending'];

        $report_summary['under_review'] =
            (int) $row['under_review'];

        $report_summary['resolved'] =
            (int) $row['resolved'];

        $report_summary['rejected'] =
            (int) $row['rejected'];

    }

    $stmt->close();

}


/* =====================================================
   FETCH REPORTS
===================================================== */

$reports = [];


$sql = "
    SELECT
        r.report_id,
        r.reporter_id,
        r.report_category,
        r.reason,
        r.description,
        r.status,
        r.reviewed_by,
        r.admin_notes,
        r.created_at,
        r.updated_at,
        r.resolved_at,

        u.full_name AS reporter_name,
        u.email AS reporter_email,

        a.full_name AS reviewer_name

    FROM Reports r

    INNER JOIN Users u
        ON u.user_id = r.reporter_id

    LEFT JOIN Admins a
        ON a.admin_id = r.reviewed_by

    WHERE r.reported_product_id = ?
      AND r.report_type = 'Product'
      AND r.deleted_at IS NULL

    ORDER BY r.created_at DESC
";


$stmt = $conn->prepare($sql);


if ($stmt) {

    $stmt->bind_param(
        'i',
        $product_id
    );

    $stmt->execute();

    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {

        $reports[] = $row;

    }

    $stmt->close();

}


/* =====================================================
   FETCH TRANSACTION
===================================================== */

$transaction = null;


$sql = "
    SELECT
        t.transaction_id,
        t.buyer_id,
        t.seller_id,
        t.selling_price,
        t.transaction_status,
        t.transaction_date,
        t.notes,

        u.full_name AS buyer_name,
        u.email AS buyer_email

    FROM Transactions t

    INNER JOIN Users u
        ON u.user_id = t.buyer_id

    WHERE t.product_id = ?
      AND t.deleted_at IS NULL

    ORDER BY t.transaction_date DESC

    LIMIT 1
";


$stmt = $conn->prepare($sql);


if ($stmt) {

    $stmt->bind_param(
        'i',
        $product_id
    );

    $stmt->execute();

    $result = $stmt->get_result();

    $transaction = $result->fetch_assoc();

    $stmt->close();

}


/* =====================================================
   FETCH REMOVING ADMIN
===================================================== */

$removed_by_admin = null;


if (!empty($product['removed_by_admin_id'])) {

    $admin_id =
        (int) $product['removed_by_admin_id'];


    $sql = "
        SELECT
            admin_id,
            full_name,
            email,
            admin_role
        FROM Admins
        WHERE admin_id = ?
        LIMIT 1
    ";


    $stmt = $conn->prepare($sql);


    if ($stmt) {

        $stmt->bind_param(
            'i',
            $admin_id
        );

        $stmt->execute();

        $result = $stmt->get_result();

        $removed_by_admin =
            $result->fetch_assoc();

        $stmt->close();

    }

}


/* =====================================================
   BREADCRUMBS
===================================================== */

$breadcrumbs = [

    [
        'label' => 'Products',
        'url' => 'products.php'
    ],

    [
        'label' =>
            $product['title']
            ?: 'Product #' . $product_id
    ]

];


/* =====================================================
   HEADER
===================================================== */

include 'includes/admin_header.php';

?>


<!-- =====================================================
     PRODUCT OVERVIEW
===================================================== -->

<div class="product-details-layout">


    <!-- =================================================
         MAIN PRODUCT CARD
    ================================================== -->

    <div class="product-details-main">


        <!-- PRODUCT HEADER -->

        <div class="product-details-header">

            <div>

                <span class="product-id">
                    Product #<?= (int) $product['product_id'] ?>
                </span>

                <h2>

                    <?= htmlspecialchars(
                        $product['title']
                        ?: 'Untitled Product'
                    ) ?>

                </h2>

                <p>

                    Listed on
                    <?= date(
                        'd M Y, h:i A',
                        strtotime($product['created_at'])
                    ) ?>

                </p>

            </div>


            <!-- STATUS -->

            <div>

                <?php if (
                    $product['status'] === 'Active'
                ): ?>

                    <span class="status-badge status-active">
                        Active
                    </span>


                <?php elseif (
                    $product['status'] === 'Inactive'
                ): ?>

                    <span class="status-badge status-inactive">
                        User Removed
                    </span>


                <?php elseif (
                    $product['status'] === 'Sold'
                ): ?>

                    <span class="status-badge status-sold">
                        Sold
                    </span>


                <?php elseif (
                    $product['status'] === 'Removed'
                ): ?>

                    <span class="status-badge status-removed">
                        Admin Removed
                    </span>

                <?php endif; ?>

            </div>

        </div>


        <!-- =================================================
             PRODUCT IMAGES
        ================================================== -->

        <div class="product-section">

            <div class="section-heading">

                <div>

                    <h3>
                        Product Images
                    </h3>

                    <p>
                        Images uploaded with this listing.
                    </p>

                </div>

            </div>


            <?php if (!empty($images)): ?>

                <div class="product-images">

                    <?php foreach ($images as $image): ?>

                        <div class="product-image-card">

                            <img
                                src="../uploads/products/<?= htmlspecialchars(
                                    $image['image_path']
                                ) ?>"
                                alt="Product image"
                            >

                            <?php if (
                                (int) $image['is_primary'] === 1
                            ): ?>

                                <span class="primary-image-label">
                                    Primary
                                </span>

                            <?php endif; ?>

                        </div>

                    <?php endforeach; ?>

                </div>

            <?php else: ?>

                <div class="details-empty">

                    <i class="fa-solid fa-image"></i>

                    <span>
                        No product images available.
                    </span>

                </div>

            <?php endif; ?>

        </div>


        <!-- =================================================
             PRODUCT INFORMATION
        ================================================== -->

        <div class="product-section">

            <div class="section-heading">

                <div>

                    <h3>
                        Product Information
                    </h3>

                    <p>
                        Details submitted by the seller.
                    </p>

                </div>

            </div>


            <div class="details-grid">


                <div class="detail-item">

                    <span class="detail-label">
                        Title
                    </span>

                    <strong>

                        <?= htmlspecialchars(
                            $product['title']
                            ?: 'Untitled Product'
                        ) ?>

                    </strong>

                </div>


                <div class="detail-item">

                    <span class="detail-label">
                        Category
                    </span>

                    <strong>

                        <?= htmlspecialchars(
                            $product['category_name']
                            ?? 'Uncategorized'
                        ) ?>

                    </strong>

                </div>


                <div class="detail-item">

                    <span class="detail-label">
                        Price
                    </span>

                    <strong>

                        <?php if (
                            $product['price'] !== null
                        ): ?>

                            ₹<?= number_format(
                                (float) $product['price'],
                                2
                            ) ?>

                        <?php else: ?>

                            —

                        <?php endif; ?>

                    </strong>

                </div>


                <div class="detail-item">

                    <span class="detail-label">
                        Condition
                    </span>

                    <strong>

                        <?= htmlspecialchars(
                            $product['product_condition']
                            ?? '—'
                        ) ?>

                    </strong>

                </div>


                <div class="detail-item">

                    <span class="detail-label">
                        Category Invoice Requirement
                    </span>

                    <strong>

                        <?= (
                            (int) $product['invoice_required'] === 1
                        )
                            ? 'Required'
                            : 'Not Required'
                        ?>

                    </strong>

                </div>


                <div class="detail-item">

                    <span class="detail-label">
                        Invoice
                    </span>

                    <strong>

                        <?php if (
                            !empty($product['invoice_document'])
                        ): ?>

                            <a
                                href="<?= htmlspecialchars(
                                    $product['invoice_document']
                                ) ?>"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="details-link"
                            >
                                <i class="fa-solid fa-file-invoice"></i>
                                View Invoice
                            </a>

                        <?php else: ?>

                            <span class="text-muted">
                                Not Available
                            </span>

                        <?php endif; ?>

                    </strong>

                </div>


            </div>


            <!-- DESCRIPTION -->

            <div class="description-block">

                <span class="detail-label">
                    Description
                </span>

                <?php if (
                    !empty($product['description'])
                ): ?>

                    <p>

                        <?= nl2br(
                            htmlspecialchars(
                                $product['description']
                            )
                        ) ?>

                    </p>

                <?php else: ?>

                    <p class="text-muted">
                        No description provided.
                    </p>

                <?php endif; ?>

            </div>

        </div>


        <!-- =================================================
             REMOVAL INFORMATION
        ================================================== -->

        <?php if (
            $product['status'] === 'Removed'
        ): ?>

            <div class="product-section removal-section">

                <div class="section-heading">

                    <div>

                        <h3>
                            Moderation Removal
                        </h3>

                        <p>
                            Information recorded when the product
                            was removed by an administrator.
                        </p>

                    </div>

                </div>


                <div class="details-grid">


                    <div class="detail-item">

                        <span class="detail-label">
                            Removed By
                        </span>

                        <strong>

                            <?= htmlspecialchars(
                                $removed_by_admin['full_name']
                                ?? 'Unknown Admin'
                            ) ?>

                        </strong>

                    </div>


                    <div class="detail-item">

                        <span class="detail-label">
                            Removed At
                        </span>

                        <strong>

                            <?php if (
                                !empty($product['removed_at'])
                            ): ?>

                                <?= date(
                                    'd M Y, h:i A',
                                    strtotime(
                                        $product['removed_at']
                                    )
                                ) ?>

                            <?php else: ?>

                                —

                            <?php endif; ?>

                        </strong>

                    </div>


                </div>


                <div class="description-block">

                    <span class="detail-label">
                        Removal Reason
                    </span>

                    <p>

                        <?= htmlspecialchars(
                            $product['removal_reason']
                            ?: 'No reason recorded.'
                        ) ?>

                    </p>

                </div>

            </div>

        <?php endif; ?>


        <!-- =================================================
             TRANSACTION
        ================================================== -->

        <?php if ($transaction): ?>

            <div class="product-section">

                <div class="section-heading">

                    <div>

                        <h3>
                            Transaction
                        </h3>

                        <p>
                            Transaction information associated
                            with this product.
                        </p>

                    </div>

                </div>


                <div class="details-grid">


                    <div class="detail-item">

                        <span class="detail-label">
                            Transaction ID
                        </span>

                        <strong>
                            #<?= (int) $transaction['transaction_id'] ?>
                        </strong>

                    </div>


                    <div class="detail-item">

                        <span class="detail-label">
                            Buyer
                        </span>

                        <strong>

                            <?= htmlspecialchars(
                                $transaction['buyer_name']
                            ) ?>

                        </strong>

                    </div>


                    <div class="detail-item">

                        <span class="detail-label">
                            Selling Price
                        </span>

                        <strong>

                            ₹<?= number_format(
                                (float) $transaction['selling_price'],
                                2
                            ) ?>

                        </strong>

                    </div>


                    <div class="detail-item">

                        <span class="detail-label">
                            Transaction Status
                        </span>

                        <strong>

                            <?= htmlspecialchars(
                                $transaction['transaction_status']
                            ) ?>

                        </strong>

                    </div>


                    <div class="detail-item">

                        <span class="detail-label">
                            Transaction Date
                        </span>

                        <strong>

                            <?= date(
                                'd M Y, h:i A',
                                strtotime(
                                    $transaction['transaction_date']
                                )
                            ) ?>

                        </strong>

                    </div>


                </div>

            </div>

        <?php endif; ?>


    </div>


    <!-- =================================================
         SIDEBAR
    ================================================== -->

    <div class="product-details-sidebar">


        <!-- =================================================
             SELLER
        ================================================== -->

        <div class="details-card">

            <div class="details-card-header">

                <h3>
                    Seller
                </h3>

            </div>


            <div class="seller-profile">

                <div class="seller-avatar">

                    <?= htmlspecialchars(
                        strtoupper(
                            substr(
                                $product['seller_name'],
                                0,
                                1
                            )
                        )
                    ) ?>

                </div>


                <div>

                    <strong>

                        <?= htmlspecialchars(
                            $product['seller_name']
                        ) ?>

                    </strong>

                    <span>

                        <?= htmlspecialchars(
                            $product['seller_email']
                        ) ?>

                    </span>

                </div>

            </div>


            <div class="seller-details">


                <div>

                    <span>
                        User ID
                    </span>

                    <strong>
                        #<?= (int) $product['seller_id'] ?>
                    </strong>

                </div>


                <div>

                    <span>
                        Role
                    </span>

                    <strong>

                        <?= htmlspecialchars(
                            $product['seller_role']
                            ?? '—'
                        ) ?>

                    </strong>

                </div>


                <div>

                    <span>
                        Account Status
                    </span>

                    <strong>

                        <?= htmlspecialchars(
                            $product['seller_status']
                        ) ?>

                    </strong>

                </div>


            </div>


            <a
                href="user_view.php?id=<?= (int) $product['seller_id'] ?>"
                class="details-card-link"
            >

                <i class="fa-solid fa-user"></i>

                View Seller

            </a>

        </div>


        <!-- =================================================
             REPORT SUMMARY
        ================================================== -->

        <div class="details-card">

            <div class="details-card-header">

                <h3>
                    Reports
                </h3>

                <span class="report-total">

                    <?= number_format(
                        $report_summary['total']
                    ) ?>

                </span>

            </div>


            <div class="report-summary">


                <div>

                    <span>
                        Pending
                    </span>

                    <strong>
                        <?= $report_summary['pending'] ?>
                    </strong>

                </div>


                <div>

                    <span>
                        Under Review
                    </span>

                    <strong>
                        <?= $report_summary['under_review'] ?>
                    </strong>

                </div>


                <div>

                    <span>
                        Resolved
                    </span>

                    <strong>
                        <?= $report_summary['resolved'] ?>
                    </strong>

                </div>


                <div>

                    <span>
                        Rejected
                    </span>

                    <strong>
                        <?= $report_summary['rejected'] ?>
                    </strong>

                </div>


            </div>


            <?php if (!empty($reports)): ?>

                <a
                    href="reports.php?product_id=<?= (int) $product_id ?>"
                    class="details-card-link"
                >

                    <i class="fa-solid fa-flag"></i>

                    View Product Reports

                </a>

            <?php endif; ?>

        </div>


        <!-- =================================================
             PRODUCT TIMELINE
        ================================================== -->

        <div class="details-card">

            <div class="details-card-header">

                <h3>
                    Product Timeline
                </h3>

            </div>


            <div class="product-timeline">


                <div class="timeline-item">

                    <div class="timeline-icon">

                        <i class="fa-solid fa-plus"></i>

                    </div>

                    <div>

                        <strong>
                            Product Created
                        </strong>

                        <span>

                            <?= date(
                                'd M Y, h:i A',
                                strtotime(
                                    $product['created_at']
                                )
                            ) ?>

                        </span>

                    </div>

                </div>


                <div class="timeline-item">

                    <div class="timeline-icon">

                        <i class="fa-solid fa-pen"></i>

                    </div>

                    <div>

                        <strong>
                            Last Updated
                        </strong>

                        <span>

                            <?= date(
                                'd M Y, h:i A',
                                strtotime(
                                    $product['updated_at']
                                )
                            ) ?>

                        </span>

                    </div>

                </div>


                <?php if (
                    !empty($product['removed_at'])
                ): ?>

                    <div class="timeline-item">

                        <div class="timeline-icon">

                            <i class="fa-solid fa-ban"></i>

                        </div>

                        <div>

                            <strong>
                                Product Removed
                            </strong>

                            <span>

                                <?= date(
                                    'd M Y, h:i A',
                                    strtotime(
                                        $product['removed_at']
                                    )
                                ) ?>

                            </span>

                        </div>

                    </div>

                <?php endif; ?>


            </div>

        </div>


    </div>


</div>


<!-- =====================================================
     REPORT DETAILS
===================================================== -->

<?php if (!empty($reports)): ?>

    <div class="product-reports-card">

        <div class="products-card-header">

            <div>

                <h2>
                    Product Reports
                </h2>

                <p>
                    Reports submitted against this product.
                </p>

            </div>

        </div>


        <div class="table-responsive">

            <table class="admin-table">

                <thead>

                    <tr>

                        <th>
                            Report
                        </th>

                        <th>
                            Reporter
                        </th>

                        <th>
                            Category
                        </th>

                        <th>
                            Reason
                        </th>

                        <th>
                            Status
                        </th>

                        <th>
                            Submitted
                        </th>

                        <th>
                            Action
                        </th>

                    </tr>

                </thead>


                <tbody>

                <?php foreach ($reports as $report): ?>

                    <tr>


                        <!-- REPORT -->

                        <td>

                            <strong>
                                #<?= (int) $report['report_id'] ?>
                            </strong>

                        </td>


                        <!-- REPORTER -->

                        <td>

                            <div class="seller-cell">

                                <strong>

                                    <?= htmlspecialchars(
                                        $report['reporter_name']
                                    ) ?>

                                </strong>

                                <span>

                                    <?= htmlspecialchars(
                                        $report['reporter_email']
                                    ) ?>

                                </span>

                            </div>

                        </td>


                        <!-- CATEGORY -->

                        <td>

                            <?= htmlspecialchars(
                                $report['report_category']
                            ) ?>

                        </td>


                        <!-- REASON -->

                        <td>

                            <?= htmlspecialchars(
                                $report['reason']
                            ) ?>

                        </td>


                        <!-- STATUS -->

                        <td>

                            <?php if (
                                $report['status'] === 'Pending'
                            ): ?>

                                <span
                                    class="status-badge status-pending"
                                >
                                    Pending
                                </span>


                            <?php elseif (
                                $report['status'] === 'Under Review'
                            ): ?>

                                <span
                                    class="status-badge status-review"
                                >
                                    Under Review
                                </span>


                            <?php elseif (
                                $report['status'] === 'Resolved'
                            ): ?>

                                <span
                                    class="status-badge status-resolved"
                                >
                                    Resolved
                                </span>


                            <?php elseif (
                                $report['status'] === 'Rejected'
                            ): ?>

                                <span
                                    class="status-badge status-rejected"
                                >
                                    Rejected
                                </span>

                            <?php endif; ?>

                        </td>


                        <!-- SUBMITTED -->

                        <td>

                            <?= date(
                                'd M Y',
                                strtotime(
                                    $report['created_at']
                                )
                            ) ?>

                        </td>


                        <!-- ACTION -->

                        <td>

                            <a
                                href="report-details.php?id=<?= (int) $report['report_id'] ?>"
                                class="table-action-btn"
                                title="View report"
                            >

                                <i class="fa-solid fa-eye"></i>

                                <span>
                                    View
                                </span>

                            </a>

                        </td>


                    </tr>

                <?php endforeach; ?>

                </tbody>

            </table>

        </div>

    </div>

<?php endif; ?>


<?php

include 'includes/admin_footer.php';

?>
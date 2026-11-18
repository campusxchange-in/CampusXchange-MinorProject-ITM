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
   PAGE CONFIGURATION
===================================================== */

$page_title = 'Reports & Complaints | CampusXchange';

$page_heading = 'Reports & Complaints';

$page_description = 'Review and manage reports submitted against users and products.';

$active_page = 'reports';

$page_css = 'reports.css';


/* =====================================================
   CONTEXTUAL FILTERS
===================================================== */

$product_id = filter_input(
    INPUT_GET,
    'product_id',
    FILTER_VALIDATE_INT
);

$user_id = filter_input(
    INPUT_GET,
    'user_id',
    FILTER_VALIDATE_INT
);


/*
 * Only allow one contextual filter at a time.
 *
 * If both are supplied, product filter takes priority.
 */

if ($product_id && $user_id) {

    $user_id = null;

}


/* =====================================================
   PAGE HEADING BASED ON CONTEXT
===================================================== */

if ($product_id) {

    $page_heading = 'Product Reports';

    $page_description = 'Review reports submitted against this product.';

} elseif ($user_id) {

    $page_heading = 'User Reports';

    $page_description = 'Review reports submitted against this user.';

}


/* =====================================================
   BREADCRUMBS
===================================================== */

$breadcrumbs = [
    [
        'label' => 'Reports & Complaints'
    ]
];


if ($product_id) {

    $breadcrumbs[] = [
        'label' => 'Product #' . $product_id
    ];

} elseif ($user_id) {

    $breadcrumbs[] = [
        'label' => 'User #' . $user_id
    ];

}


/* =====================================================
   FETCH CONTEXT INFORMATION
===================================================== */

$context_product = null;
$context_user = null;


/* ---------------- PRODUCT CONTEXT ---------------- */

if ($product_id) {

    $sql = "
        SELECT
            p.product_id,
            p.title,
            p.status,
            u.full_name AS seller_name

        FROM Products p

        INNER JOIN Users u
            ON u.user_id = p.seller_id

        WHERE p.product_id = ?

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

        $context_product = $result->fetch_assoc();

        $stmt->close();

    }

}


/* ---------------- USER CONTEXT ---------------- */

if ($user_id) {

    $sql = "
        SELECT
            user_id,
            full_name,
            email,
            role,
            status

        FROM Users

        WHERE user_id = ?

          AND deleted_at IS NULL

        LIMIT 1
    ";

    $stmt = $conn->prepare($sql);

    if ($stmt) {

        $stmt->bind_param(
            'i',
            $user_id
        );

        $stmt->execute();

        $result = $stmt->get_result();

        $context_user = $result->fetch_assoc();

        $stmt->close();

    }

}


/* =====================================================
   REPORT STATISTICS
===================================================== */

$stats = [
    'total' => 0,
    'pending' => 0,
    'under_review' => 0,
    'resolved' => 0,
    'rejected' => 0
];


$stats_where = "
    r.deleted_at IS NULL
";


$stats_params = [];
$stats_types = '';


if ($product_id) {

    $stats_where .= "
        AND r.report_type = 'Product'
        AND r.reported_product_id = ?
    ";

    $stats_params[] = $product_id;
    $stats_types .= 'i';

} elseif ($user_id) {

    $stats_where .= "
        AND r.report_type = 'User'
        AND r.reported_user_id = ?
    ";

    $stats_params[] = $user_id;
    $stats_types .= 'i';

}


$sql = "
    SELECT
        COUNT(*) AS total,

        SUM(
            CASE
                WHEN r.status = 'Pending'
                THEN 1
                ELSE 0
            END
        ) AS pending,

        SUM(
            CASE
                WHEN r.status = 'Under Review'
                THEN 1
                ELSE 0
            END
        ) AS under_review,

        SUM(
            CASE
                WHEN r.status = 'Resolved'
                THEN 1
                ELSE 0
            END
        ) AS resolved,

        SUM(
            CASE
                WHEN r.status = 'Rejected'
                THEN 1
                ELSE 0
            END
        ) AS rejected

    FROM Reports r

    WHERE {$stats_where}
";


$stmt = $conn->prepare($sql);

if ($stmt) {

    if (!empty($stats_params)) {

        $stmt->bind_param(
            $stats_types,
            ...$stats_params
        );

    }

    $stmt->execute();

    $result = $stmt->get_result();

    $row = $result->fetch_assoc();

    if ($row) {

        $stats['total'] = (int) ($row['total'] ?? 0);

        $stats['pending'] = (int) ($row['pending'] ?? 0);

        $stats['under_review'] = (int) ($row['under_review'] ?? 0);

        $stats['resolved'] = (int) ($row['resolved'] ?? 0);

        $stats['rejected'] = (int) ($row['rejected'] ?? 0);

    }

    $stmt->close();

}


/* =====================================================
   FETCH REPORTS
===================================================== */

$reports = [];


$where = "
    r.deleted_at IS NULL
";


$params = [];
$types = '';


/* ---------------- CONTEXTUAL FILTER ---------------- */

if ($product_id) {

    $where .= "
        AND r.report_type = 'Product'
        AND r.reported_product_id = ?
    ";

    $params[] = $product_id;
    $types .= 'i';

} elseif ($user_id) {

    $where .= "
        AND r.report_type = 'User'
        AND r.reported_user_id = ?
    ";

    $params[] = $user_id;
    $types .= 'i';

}


/* =====================================================
   REPORT QUERY
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


        /* Reporter */

        reporter.full_name AS reporter_name,

        reporter.email AS reporter_email,


        /* Reported User */

        reported_user.full_name AS reported_user_name,

        reported_user.email AS reported_user_email,

        reported_user.role AS reported_user_role,

        reported_user.status AS reported_user_status,


        /* Reported Product */

        p.title AS product_title,

        p.price AS product_price,

        p.status AS product_status,

        p.seller_id AS product_seller_id,

        seller.full_name AS product_seller_name


    FROM Reports r


    /* Reporter */

    INNER JOIN Users reporter

        ON reporter.user_id = r.reporter_id


    /* Reported User */

    LEFT JOIN Users reported_user

        ON reported_user.user_id = r.reported_user_id


    /* Reported Product */

    LEFT JOIN Products p

        ON p.product_id = r.reported_product_id


    /* Product Seller */

    LEFT JOIN Users seller

        ON seller.user_id = p.seller_id


    WHERE {$where}


    ORDER BY

        CASE

            WHEN r.status = 'Pending'
                THEN 1

            WHEN r.status = 'Under Review'
                THEN 2

            WHEN r.status = 'Resolved'
                THEN 3

            WHEN r.status = 'Rejected'
                THEN 4

            ELSE 5

        END,

        r.created_at DESC
";


$stmt = $conn->prepare($sql);


if ($stmt) {

    if (!empty($params)) {

        $stmt->bind_param(
            $types,
            ...$params
        );

    }

    $stmt->execute();

    $result = $stmt->get_result();


    while ($row = $result->fetch_assoc()) {

        $reports[] = $row;

    }


    $stmt->close();

}


/* =====================================================
   LOAD ADMIN HEADER
===================================================== */

include 'includes/admin_header.php';

?>


<div class="reports-page">


    <!-- =================================================
         CONTEXT INFORMATION
    ================================================== -->

    <?php if ($product_id): ?>

        <div class="report-context-card">

            <div class="report-context-icon">
                <i class="fa-solid fa-box"></i>
            </div>

            <div class="report-context-content">

                <span class="report-context-label">
                    Product Reports
                </span>

                <?php if ($context_product): ?>

                    <h2>
                        <?= htmlspecialchars(
                            $context_product['title']
                                ?: 'Product #' . $product_id
                        ) ?>
                    </h2>

                    <p>
                        Product #<?= (int) $context_product['product_id'] ?>

                        <span class="context-divider">•</span>

                        Seller:
                        <?= htmlspecialchars(
                            $context_product['seller_name']
                        ) ?>

                        <span class="context-divider">•</span>

                        Status:
                        <?= htmlspecialchars(
                            $context_product['status']
                        ) ?>
                    </p>

                <?php else: ?>

                    <h2>
                        Product #<?= (int) $product_id ?>
                    </h2>

                    <p>
                        No active product information was found.
                    </p>

                <?php endif; ?>

            </div>

            <div class="report-context-action">

                <a
                    href="products.php"
                    class="btn btn-secondary"
                >
                    <i class="fa-solid fa-arrow-left"></i>
                    Products
                </a>

            </div>

        </div>


    <?php elseif ($user_id): ?>

        <div class="report-context-card">

            <div class="report-context-icon">
                <i class="fa-solid fa-user"></i>
            </div>

            <div class="report-context-content">

                <span class="report-context-label">
                    User Reports
                </span>

                <?php if ($context_user): ?>

                    <h2>
                        <?= htmlspecialchars(
                            $context_user['full_name']
                        ) ?>
                    </h2>

                    <p>

                        User #<?= (int) $context_user['user_id'] ?>

                        <span class="context-divider">•</span>

                        <?= htmlspecialchars(
                            $context_user['email']
                        ) ?>

                        <?php if (!empty($context_user['role'])): ?>

                            <span class="context-divider">•</span>

                            <?= htmlspecialchars(
                                $context_user['role']
                            ) ?>

                        <?php endif; ?>

                        <span class="context-divider">•</span>

                        Status:
                        <?= htmlspecialchars(
                            $context_user['status']
                        ) ?>

                    </p>

                <?php else: ?>

                    <h2>
                        User #<?= (int) $user_id ?>
                    </h2>

                    <p>
                        No active user information was found.
                    </p>

                <?php endif; ?>

            </div>

            <div class="report-context-action">

                <a
                    href="users.php"
                    class="btn btn-secondary"
                >
                    <i class="fa-solid fa-arrow-left"></i>
                    Users
                </a>

            </div>

        </div>

    <?php endif; ?>


    <!-- =================================================
         STATISTICS
    ================================================== -->

    <div class="reports-stats">


        <div class="report-stat-card">

            <div class="report-stat-icon">
                <i class="fa-solid fa-flag"></i>
            </div>

            <div class="report-stat-content">

                <span class="report-stat-label">
                    Total Reports
                </span>

                <strong>
                    <?= $stats['total'] ?>
                </strong>

            </div>

        </div>


        <div class="report-stat-card report-stat-pending">

            <div class="report-stat-icon">
                <i class="fa-solid fa-clock"></i>
            </div>

            <div class="report-stat-content">

                <span class="report-stat-label">
                    Pending
                </span>

                <strong>
                    <?= $stats['pending'] ?>
                </strong>

            </div>

        </div>


        <div class="report-stat-card report-stat-review">

            <div class="report-stat-icon">
                <i class="fa-solid fa-magnifying-glass"></i>
            </div>

            <div class="report-stat-content">

                <span class="report-stat-label">
                    Under Review
                </span>

                <strong>
                    <?= $stats['under_review'] ?>
                </strong>

            </div>

        </div>


        <div class="report-stat-card report-stat-resolved">

            <div class="report-stat-icon">
                <i class="fa-solid fa-circle-check"></i>
            </div>

            <div class="report-stat-content">

                <span class="report-stat-label">
                    Resolved
                </span>

                <strong>
                    <?= $stats['resolved'] ?>
                </strong>

            </div>

        </div>


        <div class="report-stat-card report-stat-rejected">

            <div class="report-stat-icon">
                <i class="fa-solid fa-circle-xmark"></i>
            </div>

            <div class="report-stat-content">

                <span class="report-stat-label">
                    Rejected
                </span>

                <strong>
                    <?= $stats['rejected'] ?>
                </strong>

            </div>

        </div>


    </div>


    <!-- =================================================
         FILTERS
    ================================================== -->

    <div class="reports-filter-card">

        <div class="reports-filter-header">

            <div>

                <h2>
                    Report Queue
                </h2>

                <p>
                    Search and filter submitted reports.
                </p>

            </div>

            <div
                class="reports-result-count"
                id="reportsResultCount"
            >
                <?= count($reports) ?> reports
            </div>

        </div>


        <div class="reports-filters">


            <!-- SEARCH -->

            <div class="report-filter-group report-filter-search">

                <label for="reportSearch">
                    Search
                </label>

                <div class="report-search-wrapper">

                    <i class="fa-solid fa-magnifying-glass"></i>

                    <input
                        type="text"
                        id="reportSearch"
                        class="report-filter-input"
                        placeholder="Search report, user, product, reporter..."
                        autocomplete="off"
                    >

                </div>

            </div>


            <!-- REPORT TYPE -->

            <div class="report-filter-group">

                <label for="reportTypeFilter">
                    Report Type
                </label>

                <select
                    id="reportTypeFilter"
                    class="report-filter-select"
                >

                    <option value="all">
                        All Reports
                    </option>

                    <option value="Product">
                        Product Reports
                    </option>

                    <option value="User">
                        User Reports
                    </option>

                </select>

            </div>


            <!-- STATUS -->

            <div class="report-filter-group">

                <label for="reportStatusFilter">
                    Status
                </label>

                <select
                    id="reportStatusFilter"
                    class="report-filter-select"
                >

                    <option value="all">
                        All Statuses
                    </option>

                    <option value="Pending">
                        Pending
                    </option>

                    <option value="Under Review">
                        Under Review
                    </option>

                    <option value="Resolved">
                        Resolved
                    </option>

                    <option value="Rejected">
                        Rejected
                    </option>

                </select>

            </div>


            <!-- CATEGORY -->

            <div class="report-filter-group">

                <label for="reportCategoryFilter">
                    Category
                </label>

                <select
                    id="reportCategoryFilter"
                    class="report-filter-select"
                >

                    <option value="all">
                        All Categories
                    </option>

                    <option value="Inappropriate Behavior">
                        Inappropriate Behavior
                    </option>

                    <option value="Scam/Fraud">
                        Scam/Fraud
                    </option>

                    <option value="Harassment">
                        Harassment
                    </option>

                    <option value="Spam">
                        Spam
                    </option>

                    <option value="Fake/Misleading Listing">
                        Fake/Misleading Listing
                    </option>

                    <option value="Prohibited Product">
                        Prohibited Product
                    </option>

                    <option value="Other">
                        Other
                    </option>

                </select>

            </div>


            <!-- RESET -->

            <div class="report-filter-group report-filter-reset-group">

                <label>
                    &nbsp;
                </label>

                <button
                    type="button"
                    class="report-filter-reset"
                    id="resetReportFilters"
                >
                    <i class="fa-solid fa-rotate-left"></i>
                    Reset
                </button>

            </div>


        </div>

    </div>


    <!-- =================================================
         REPORT TABLE
    ================================================== -->

    <div class="reports-table-card">

        <div class="table-responsive">

            <table class="reports-table">

                <thead>

                    <tr>

                        <th>
                            Report
                        </th>

                        <th>
                            Type
                        </th>

                        <th>
                            Reported
                        </th>

                        <th>
                            Reporter
                        </th>

                        <th>
                            Category
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


                <tbody id="reportsTableBody">


                    <?php if (!empty($reports)): ?>


                        <?php foreach ($reports as $report): ?>


                            <?php

                            $is_product_report =
                                $report['report_type'] === 'Product';


                            if ($is_product_report) {

                                $reported_name =
                                    $report['product_title']
                                    ?: 'Product #' .
                                       $report['reported_product_id'];

                                $reported_meta =
                                    'Product #' .
                                    $report['reported_product_id'];

                                if (!empty($report['product_seller_name'])) {

                                    $reported_meta .=
                                        ' • Seller: ' .
                                        $report['product_seller_name'];

                                }

                            } else {

                                $reported_name =
                                    $report['reported_user_name']
                                    ?: 'User #' .
                                       $report['reported_user_id'];

                                $reported_meta =
                                    'User #' .
                                    $report['reported_user_id'];

                                if (!empty($report['reported_user_role'])) {

                                    $reported_meta .=
                                        ' • ' .
                                        $report['reported_user_role'];

                                }

                            }


                            $search_text = strtolower(
                                implode(
                                    ' ',
                                    [
                                        $report['report_id'],
                                        $report['report_type'],
                                        $reported_name,
                                        $reported_meta,
                                        $report['reporter_name'],
                                        $report['reporter_email'],
                                        $report['report_category'],
                                        $report['reason'],
                                        $report['status']
                                    ]
                                )
                            );


                            ?>


                            <tr

                                class="report-row"

                                data-search="<?= htmlspecialchars(
                                    $search_text
                                ) ?>"

                                data-type="<?= htmlspecialchars(
                                    $report['report_type']
                                ) ?>"

                                data-status="<?= htmlspecialchars(
                                    $report['status']
                                ) ?>"

                                data-category="<?= htmlspecialchars(
                                    $report['report_category']
                                ) ?>"
                            >


                                <!-- REPORT ID -->

                                <td>

                                    <div class="report-id">

                                        #<?= (int) $report['report_id'] ?>

                                    </div>

                                </td>


                                <!-- TYPE -->

                                <td>

                                    <?php if ($is_product_report): ?>

                                        <span class="report-type-badge product">

                                            <i class="fa-solid fa-box"></i>

                                            Product

                                        </span>

                                    <?php else: ?>

                                        <span class="report-type-badge user">

                                            <i class="fa-solid fa-user"></i>

                                            User

                                        </span>

                                    <?php endif; ?>

                                </td>


                                <!-- REPORTED -->

                                <td>

                                    <div class="reported-target">

                                        <strong>

                                            <?= htmlspecialchars(
                                                $reported_name
                                            ) ?>

                                        </strong>

                                        <span>

                                            <?= htmlspecialchars(
                                                $reported_meta
                                            ) ?>

                                        </span>

                                    </div>

                                </td>


                                <!-- REPORTER -->

                                <td>

                                    <div class="reporter-info">

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

                                    <span class="report-category">

                                        <?= htmlspecialchars(
                                            $report['report_category']
                                        ) ?>

                                    </span>

                                </td>


                                <!-- STATUS -->

                                <td>

                                    <?php

                                    $status_class = strtolower(
                                        str_replace(
                                            ' ',
                                            '-',
                                            $report['status']
                                        )
                                    );

                                    ?>


                                    <span
                                        class="report-status-badge <?= htmlspecialchars(
                                            $status_class
                                        ) ?>"
                                    >

                                        <?php if ($report['status'] === 'Pending'): ?>

                                            <i class="fa-solid fa-clock"></i>

                                        <?php elseif ($report['status'] === 'Under Review'): ?>

                                            <i class="fa-solid fa-magnifying-glass"></i>

                                        <?php elseif ($report['status'] === 'Resolved'): ?>

                                            <i class="fa-solid fa-circle-check"></i>

                                        <?php else: ?>

                                            <i class="fa-solid fa-circle-xmark"></i>

                                        <?php endif; ?>


                                        <?= htmlspecialchars(
                                            $report['status']
                                        ) ?>

                                    </span>

                                </td>


                                <!-- DATE -->

                                <td>

                                    <div class="report-date">

                                        <?= date(
                                            'd M Y',
                                            strtotime(
                                                $report['created_at']
                                            )
                                        ) ?>

                                        <span>

                                            <?= date(
                                                'h:i A',
                                                strtotime(
                                                    $report['created_at']
                                                )
                                            ) ?>

                                        </span>

                                    </div>

                                </td>


                                <!-- ACTION -->

                                <td>

                                    <a
                                        href="report-details.php?id=<?= (int) $report['report_id'] ?>"
                                        class="report-view-button"
                                    >

                                        <i class="fa-solid fa-eye"></i>

                                        View

                                    </a>

                                </td>


                            </tr>


                        <?php endforeach; ?>


                    <?php endif; ?>


                </tbody>

            </table>


            <!-- EMPTY STATE -->

            <div
                class="reports-empty-state"
                id="reportsEmptyState"
                style="<?= empty($reports) ? '' : 'display:none;' ?>"
            >

                <div class="reports-empty-icon">

                    <i class="fa-solid fa-flag"></i>

                </div>

                <h3>
                    No Reports Found
                </h3>

                <p id="reportsEmptyMessage">

                    There are no reports matching the current filters.

                </p>

                <button
                    type="button"
                    class="report-filter-reset"
                    id="emptyStateReset"
                >

                    <i class="fa-solid fa-rotate-left"></i>

                    Clear Filters

                </button>

            </div>


        </div>

    </div>


</div>


<script>

/* =====================================================
   REPORT FILTERING
===================================================== */

document.addEventListener(
    'DOMContentLoaded',
    function () {

        const searchInput =
            document.getElementById('reportSearch');

        const typeFilter =
            document.getElementById('reportTypeFilter');

        const statusFilter =
            document.getElementById('reportStatusFilter');

        const categoryFilter =
            document.getElementById('reportCategoryFilter');

        const resetButton =
            document.getElementById('resetReportFilters');

        const emptyResetButton =
            document.getElementById('emptyStateReset');

        const resultCount =
            document.getElementById('reportsResultCount');

        const emptyState =
            document.getElementById('reportsEmptyState');

        const rows =
            document.querySelectorAll('.report-row');


        function filterReports() {

            const searchValue =
                searchInput.value
                    .toLowerCase()
                    .trim();


            const selectedType =
                typeFilter.value;


            const selectedStatus =
                statusFilter.value;


            const selectedCategory =
                categoryFilter.value;


            let visibleCount = 0;


            rows.forEach(
                function (row) {

                    const rowSearch =
                        row.dataset.search || '';

                    const rowType =
                        row.dataset.type || '';

                    const rowStatus =
                        row.dataset.status || '';

                    const rowCategory =
                        row.dataset.category || '';


                    const matchesSearch =
                        !searchValue ||
                        rowSearch.includes(searchValue);


                    const matchesType =
                        selectedType === 'all' ||
                        rowType === selectedType;


                    const matchesStatus =
                        selectedStatus === 'all' ||
                        rowStatus === selectedStatus;


                    const matchesCategory =
                        selectedCategory === 'all' ||
                        rowCategory === selectedCategory;


                    const visible =
                        matchesSearch &&
                        matchesType &&
                        matchesStatus &&
                        matchesCategory;


                    if (visible) {

                        row.style.display = '';

                        visibleCount++;

                    } else {

                        row.style.display = 'none';

                    }

                }
            );


            resultCount.textContent =
                visibleCount +
                (visibleCount === 1
                    ? ' report'
                    : ' reports');


            if (visibleCount === 0) {

                emptyState.style.display = '';

            } else {

                emptyState.style.display = 'none';

            }

        }


        function resetFilters() {

            searchInput.value = '';

            typeFilter.value = 'all';

            statusFilter.value = 'all';

            categoryFilter.value = 'all';

            filterReports();

        }


        searchInput.addEventListener(
            'input',
            filterReports
        );


        typeFilter.addEventListener(
            'change',
            filterReports
        );


        statusFilter.addEventListener(
            'change',
            filterReports
        );


        categoryFilter.addEventListener(
            'change',
            filterReports
        );


        resetButton.addEventListener(
            'click',
            resetFilters
        );


        emptyResetButton.addEventListener(
            'click',
            resetFilters
        );


        filterReports();

    }
);

</script>


<?php

/* =====================================================
   LOAD ADMIN FOOTER
===================================================== */

include 'includes/admin_footer.php';

?>
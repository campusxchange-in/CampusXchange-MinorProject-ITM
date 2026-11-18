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

$page_title = 'Products | CampusXchange';
$page_heading = 'Products';
$page_description = 'Monitor listed products and review their current status.';
$active_page = 'products';
$page_css = 'products.css';


/* =====================================================
   BREADCRUMBS
===================================================== */

$breadcrumbs = [
    [
        'label' => 'Products'
    ]
];


/* =====================================================
   PRODUCT STATISTICS
===================================================== */

$stats = [
    'listed' => 0,
    'inactive' => 0,
    'removed' => 0,
    'sold' => 0
];


/* -----------------------------------------------------
   Listed Products
----------------------------------------------------- */

$result = $conn->query("
    SELECT COUNT(*) AS total
    FROM Products
    WHERE status = 'Active'
      AND deleted_at IS NULL
");

if ($result) {

    $row = $result->fetch_assoc();

    $stats['listed'] = (int) $row['total'];
}


/* -----------------------------------------------------
   User Removed Products
----------------------------------------------------- */

$result = $conn->query("
    SELECT COUNT(*) AS total
    FROM Products
    WHERE status = 'Inactive'
      AND deleted_at IS NULL
");

if ($result) {

    $row = $result->fetch_assoc();

    $stats['inactive'] = (int) $row['total'];
}


/* -----------------------------------------------------
   Admin Removed Products
----------------------------------------------------- */

$result = $conn->query("
    SELECT COUNT(*) AS total
    FROM Products
    WHERE status = 'Removed'
      AND deleted_at IS NULL
");

if ($result) {

    $row = $result->fetch_assoc();

    $stats['removed'] = (int) $row['total'];
}


/* -----------------------------------------------------
   Sold Products
----------------------------------------------------- */

$result = $conn->query("
    SELECT COUNT(*) AS total
    FROM Products
    WHERE status = 'Sold'
      AND deleted_at IS NULL
");

if ($result) {

    $row = $result->fetch_assoc();

    $stats['sold'] = (int) $row['total'];
}


/* =====================================================
   FETCH PRODUCTS
===================================================== */

/*
   IMPORTANT:

   Draft products are intentionally excluded.

   Products that were deleted while still in Draft
   are permanently removed from the database.

   Admin can therefore only see products that were
   actually listed or otherwise entered the public
   marketplace lifecycle.
*/

$products = [];


$sql = "
    SELECT
        p.product_id,
        p.title,
        p.price,
        p.product_condition,
        p.invoice_document,
        p.status,
        p.created_at,
        p.updated_at,
        p.removed_by_admin_id,
        p.removal_reason,
        p.removed_at,

        u.user_id AS seller_id,
        u.full_name AS seller_name,
        u.email AS seller_email,

        c.category_id,
        c.category_name,

        (
            SELECT COUNT(*)
            FROM Reports r
            WHERE r.reported_product_id = p.product_id
              AND r.report_type = 'Product'
              AND r.deleted_at IS NULL
        ) AS report_count

    FROM Products p

    INNER JOIN Users u
        ON u.user_id = p.seller_id

    LEFT JOIN Categories c
        ON c.category_id = p.category_id

    WHERE p.status IN (
        'Active',
        'Inactive',
        'Removed',
        'Sold'
    )

    AND p.deleted_at IS NULL

    ORDER BY p.updated_at DESC
";


$result = $conn->query($sql);


if ($result) {

    while ($row = $result->fetch_assoc()) {

        $products[] = $row;

    }

}


/* =====================================================
   HEADER
===================================================== */

include 'includes/admin_header.php';

?>




    


        <!-- =================================================
             PRODUCT STATISTICS
        ================================================== -->

        <div class="product-stats">


            <!-- LISTED -->

            <div class="product-stat-card">

                <div class="product-stat-icon">

                    <i class="fa-solid fa-box"></i>

                </div>

                <div class="product-stat-content">

                    <span class="product-stat-label">
                        Listed
                    </span>

                    <strong>
                        <?= number_format($stats['listed']) ?>
                    </strong>

                </div>

            </div>


            <!-- USER REMOVED -->

            <div class="product-stat-card">

                <div class="product-stat-icon">

                    <i class="fa-solid fa-box-open"></i>

                </div>

                <div class="product-stat-content">

                    <span class="product-stat-label">
                        User Removed
                    </span>

                    <strong>
                        <?= number_format($stats['inactive']) ?>
                    </strong>

                </div>

            </div>


            <!-- ADMIN REMOVED -->

            <div class="product-stat-card">

                <div class="product-stat-icon">

                    <i class="fa-solid fa-ban"></i>

                </div>

                <div class="product-stat-content">

                    <span class="product-stat-label">
                        Admin Removed
                    </span>

                    <strong>
                        <?= number_format($stats['removed']) ?>
                    </strong>

                </div>

            </div>


            <!-- SOLD -->

            <div class="product-stat-card">

                <div class="product-stat-icon">

                    <i class="fa-solid fa-handshake"></i>

                </div>

                <div class="product-stat-content">

                    <span class="product-stat-label">
                        Sold
                    </span>

                    <strong>
                        <?= number_format($stats['sold']) ?>
                    </strong>

                </div>

            </div>


        </div>


        <!-- =================================================
             PRODUCTS CARD
        ================================================== -->

        <!-- =================================================
            PRODUCTS CARD
        ================================================== -->

        <div class="products-card">


            <!-- =================================================
                CARD HEADER
            ================================================== -->

            <div class="products-card-header">

                <div>

                    <h2>
                        Product Listings
                    </h2>

                    <p>
                        Products that have been listed on CampusXchange.
                    </p>

                </div>


                <!-- SEARCH -->

                <div class="product-search">

                    <i class="fa-solid fa-magnifying-glass"></i>

                    <input
                        type="search"
                        id="productSearch"
                        placeholder="Search products..."
                        autocomplete="off"
                    >

                </div>

            </div>


            <!-- =================================================
                FILTERS
            ================================================== -->

            <div class="products-filters">


                <!-- STATUS -->

                <div class="product-filter-group">

                    <label for="statusFilter">
                        Status
                    </label>

                    <select
                        id="statusFilter"
                        class="product-filter-select"
                    >

                        <option value="all">
                            All Statuses
                        </option>

                        <option value="Active">
                            Active
                        </option>

                        <option value="Inactive">
                            User Removed
                        </option>

                        <option value="Sold">
                            Sold
                        </option>

                        <option value="Removed">
                            Admin Removed
                        </option>

                    </select>

                </div>


                <!-- REPORTS -->

                <div class="product-filter-group">

                    <label for="reportFilter">
                        Reports
                    </label>

                    <select
                        id="reportFilter"
                        class="product-filter-select"
                    >

                        <option value="all">
                            All Products
                        </option>

                        <option value="reported">
                            Reported
                        </option>

                        <option value="not-reported">
                            Not Reported
                        </option>

                    </select>

                </div>


                <!-- INVOICE -->

                <div class="product-filter-group">

                    <label for="invoiceFilter">
                        Invoice
                    </label>

                    <select
                        id="invoiceFilter"
                        class="product-filter-select"
                    >

                        <option value="all">
                            All Products
                        </option>

                        <option value="available">
                            Invoice Available
                        </option>

                        <option value="missing">
                            No Invoice
                        </option>

                    </select>

                </div>


                <!-- RESET -->

                <button
                    type="button"
                    class="product-filter-reset"
                    id="resetProductFilters"
                >

                    <i class="fa-solid fa-rotate-left"></i>

                    Reset

                </button>


                <!-- RESULT COUNT -->

                <div
                    class="product-filter-results"
                    id="productFilterResults"
                >

                    Showing
                    <strong><?= count($products) ?></strong>
                    products

                </div>


            </div>


            <!-- =================================================
                TABLE
            ================================================== -->

            <div class="table-responsive">

                <table
                    class="admin-table"
                    id="productsTable"
                >

                    <thead>

                        <tr>

                            <th>
                                Product
                            </th>

                            <th>
                                Seller
                            </th>

                            <th>
                                Category
                            </th>

                            <th>
                                Price
                            </th>

                            <th>
                                Condition
                            </th>

                            <th>
                                Reports
                            </th>

                            <th>
                                Status
                            </th>

                            <th>
                                Updated
                            </th>

                            <th>
                                Action
                            </th>

                        </tr>

                    </thead>


                    <tbody>

                    <?php if (!empty($products)): ?>


                        <?php foreach ($products as $product): ?>

                            <?php

                            $reportCount =
                                (int) $product['report_count'];

                            $hasReports =
                                $reportCount > 0;

                            $hasInvoice =
                                !empty($product['invoice_document']);

                            ?>


                            <tr
                                data-search="<?= htmlspecialchars(
                                    strtolower(
                                        $product['title'] . ' ' .
                                        $product['seller_name'] . ' ' .
                                        $product['seller_email'] . ' ' .
                                        ($product['category_name'] ?? '') . ' ' .
                                        $product['product_id']
                                    )
                                ) ?>"

                                data-status="<?= htmlspecialchars(
                                    $product['status']
                                ) ?>"

                                data-reported="<?= $hasReports ? 'yes' : 'no' ?>"

                                data-invoice="<?= $hasInvoice ? 'yes' : 'no' ?>"
                            >


                                <!-- PRODUCT -->

                                <td>

                                    <div class="product-name-cell">

                                        <div class="product-table-icon">

                                            <i class="fa-solid fa-box"></i>

                                        </div>

                                        <div>

                                            <strong>

                                                <?= htmlspecialchars(
                                                    $product['title']
                                                    ?: 'Untitled Product'
                                                ) ?>

                                            </strong>

                                            <span>

                                                #<?= (int) $product['product_id'] ?>

                                            </span>

                                        </div>

                                    </div>

                                </td>


                                <!-- SELLER -->

                                <td>

                                    <div class="seller-cell">

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

                                </td>


                                <!-- CATEGORY -->

                                <td>

                                    <?= htmlspecialchars(
                                        $product['category_name']
                                        ?? 'Uncategorized'
                                    ) ?>

                                </td>


                                <!-- PRICE -->

                                <td>

                                    <?php if (
                                        $product['price'] !== null
                                    ): ?>

                                        ₹<?= number_format(
                                            (float) $product['price'],
                                            2
                                        ) ?>

                                    <?php else: ?>

                                        <span class="text-muted">
                                            —
                                        </span>

                                    <?php endif; ?>

                                </td>


                                <!-- CONDITION -->

                                <td>

                                    <?= htmlspecialchars(
                                        $product['product_condition']
                                        ?? '—'
                                    ) ?>

                                </td>


                                <!-- REPORTS -->

                                <td>

                                    <?php if ($hasReports): ?>

                                        <span class="report-count">

                                            <i class="fa-solid fa-flag"></i>

                                            <?= $reportCount ?>

                                        </span>

                                    <?php else: ?>

                                        <span class="text-muted">
                                            0
                                        </span>

                                    <?php endif; ?>

                                </td>


                                <!-- STATUS -->

                                <td>

                                    <?php if (
                                        $product['status'] === 'Active'
                                    ): ?>

                                        <span
                                            class="status-badge status-active"
                                        >
                                            Active
                                        </span>


                                    <?php elseif (
                                        $product['status'] === 'Inactive'
                                    ): ?>

                                        <span
                                            class="status-badge status-inactive"
                                        >
                                            User Removed
                                        </span>


                                    <?php elseif (
                                        $product['status'] === 'Removed'
                                    ): ?>

                                        <span
                                            class="status-badge status-removed"
                                        >
                                            Admin Removed
                                        </span>


                                    <?php elseif (
                                        $product['status'] === 'Sold'
                                    ): ?>

                                        <span
                                            class="status-badge status-sold"
                                        >
                                            Sold
                                        </span>

                                    <?php endif; ?>

                                </td>


                                <!-- UPDATED -->

                                <td>

                                    <span class="date-cell">

                                        <?= date(
                                            'd M Y',
                                            strtotime(
                                                $product['updated_at']
                                            )
                                        ) ?>

                                    </span>

                                </td>


                                <!-- ACTION -->

                                <td>

                                    <a
                                        href="product-details.php?id=<?= (int) $product['product_id'] ?>"
                                        class="table-action-btn"
                                        title="View product"
                                    >

                                        <i class="fa-solid fa-eye"></i>

                                        <span>
                                            View
                                        </span>

                                    </a>

                                </td>


                            </tr>


                        <?php endforeach; ?>


                        <!-- NO FILTER RESULTS -->

                        <tr
                            id="noProductResults"
                            style="display: none;"
                        >

                            <td colspan="9">

                                <div class="empty-state">

                                    <i class="fa-solid fa-filter-circle-xmark"></i>

                                    <h3>
                                        No products found
                                    </h3>

                                    <p>
                                        No products match your current search and filters.
                                    </p>

                                    <button
                                        type="button"
                                        class="product-empty-reset"
                                        id="emptyResetFilters"
                                    >
                                        Clear Filters
                                    </button>

                                </div>

                            </td>

                        </tr>


                    <?php else: ?>


                        <tr>

                            <td colspan="9">

                                <div class="empty-state">

                                    <i class="fa-solid fa-box-open"></i>

                                    <h3>
                                        No products found
                                    </h3>

                                    <p>
                                        There are currently no listed products.
                                    </p>

                                </div>

                            </td>

                        </tr>


                    <?php endif; ?>

                    </tbody>

                </table>

            </div>


        </div>




<script>

document.addEventListener('DOMContentLoaded', function () {

    const searchInput =
        document.getElementById('productSearch');

    const statusFilter =
        document.getElementById('statusFilter');

    const reportFilter =
        document.getElementById('reportFilter');

    const invoiceFilter =
        document.getElementById('invoiceFilter');

    const resetButton =
        document.getElementById('resetProductFilters');

    const emptyResetButton =
        document.getElementById('emptyResetFilters');

    const table =
        document.getElementById('productsTable');

    const resultCount =
        document.getElementById('productFilterResults');

    const noResults =
        document.getElementById('noProductResults');


    if (!table) {
        return;
    }


    const rows =
        table.querySelectorAll('tbody tr[data-search]');


    function filterProducts() {

        const searchTerm =
            searchInput
                ? searchInput.value.trim().toLowerCase()
                : '';

        const selectedStatus =
            statusFilter
                ? statusFilter.value
                : 'all';

        const selectedReport =
            reportFilter
                ? reportFilter.value
                : 'all';

        const selectedInvoice =
            invoiceFilter
                ? invoiceFilter.value
                : 'all';


        let visibleRows = 0;


        rows.forEach(function (row) {

            const searchText =
                row.dataset.search || '';

            const status =
                row.dataset.status || '';

            const reported =
                row.dataset.reported || 'no';

            const invoice =
                row.dataset.invoice || 'no';


            /* SEARCH */

            const matchesSearch =
                searchText.includes(searchTerm);


            /* STATUS */

            const matchesStatus =
                selectedStatus === 'all' ||
                status === selectedStatus;


            /* REPORT */

            const matchesReport =
                selectedReport === 'all' ||

                (
                    selectedReport === 'reported' &&
                    reported === 'yes'
                ) ||

                (
                    selectedReport === 'not-reported' &&
                    reported === 'no'
                );


            /* INVOICE */

            const matchesInvoice =
                selectedInvoice === 'all' ||

                (
                    selectedInvoice === 'available' &&
                    invoice === 'yes'
                ) ||

                (
                    selectedInvoice === 'missing' &&
                    invoice === 'no'
                );


            const matches =
                matchesSearch &&
                matchesStatus &&
                matchesReport &&
                matchesInvoice;


            row.style.display =
                matches ? '' : 'none';


            if (matches) {
                visibleRows++;
            }

        });


        /* RESULT COUNT */

        if (resultCount) {

            resultCount.innerHTML =
                'Showing <strong>' +
                visibleRows +
                '</strong> ' +
                (visibleRows === 1
                    ? 'product'
                    : 'products');

        }


        /* NO RESULTS */

        if (noResults) {

            noResults.style.display =
                visibleRows === 0 && rows.length > 0
                    ? ''
                    : 'none';

        }

    }


    function resetFilters() {

        if (searchInput) {
            searchInput.value = '';
        }

        if (statusFilter) {
            statusFilter.value = 'all';
        }

        if (reportFilter) {
            reportFilter.value = 'all';
        }

        if (invoiceFilter) {
            invoiceFilter.value = 'all';
        }

        filterProducts();

    }


    /* SEARCH */

    if (searchInput) {

        searchInput.addEventListener(
            'input',
            filterProducts
        );

    }


    /* FILTERS */

    if (statusFilter) {

        statusFilter.addEventListener(
            'change',
            filterProducts
        );

    }


    if (reportFilter) {

        reportFilter.addEventListener(
            'change',
            filterProducts
        );

    }


    if (invoiceFilter) {

        invoiceFilter.addEventListener(
            'change',
            filterProducts
        );

    }


    /* RESET */

    if (resetButton) {

        resetButton.addEventListener(
            'click',
            resetFilters
        );

    }


    if (emptyResetButton) {

        emptyResetButton.addEventListener(
            'click',
            resetFilters
        );

    }


    /* INITIAL FILTER */

    filterProducts();

});

</script>


<?php

include 'includes/admin_footer.php';

?>
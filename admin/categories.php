<?php

/* =========================================================
   AUTHENTICATION
========================================================= */

include 'includes/auth_check.php';


/* =========================================================
   DATABASE CONNECTION
========================================================= */

include '../server/database/db_connect.php';


/* =========================================================
   CURRENT ADMIN
========================================================= */

$admin_id = $_SESSION['admin_id'] ?? 0;

if (!$admin_id) {

    header("Location: " . FRONTEND_URL . "/admin-login");

    exit;
}


/* =========================================================
   PAGE CONFIGURATION
========================================================= */

$page_title = 'Categories | CampusXchange';

$page_heading = 'Categories';

$page_description =
    'Create, manage and organize product categories.';

$active_page = 'categories';

$page_css = 'categories.css';


/* =========================================================
   PAGE VARIABLES
========================================================= */

$success_message = '';

$error_message = '';


/* =========================================================
   HELPER FUNCTION
========================================================= */

function logAdminAction(
    mysqli $conn,
    int $admin_id,
    string $action_description,
    ?int $target_id = null
): void {

    $action_type = 'Manage Category';

    $target_type = 'Category';

    $ip_address =
        $_SERVER['REMOTE_ADDR'] ?? null;

    $user_agent =
        $_SERVER['HTTP_USER_AGENT'] ?? null;


    $stmt = $conn->prepare("
        INSERT INTO Admin_Logs (
            admin_id,
            action_type,
            action_description,
            target_type,
            target_id,
            ip_address,
            user_agent
        )
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");


    if (!$stmt) {

        return;
    }


    $stmt->bind_param(
        "isssiss",
        $admin_id,
        $action_type,
        $action_description,
        $target_type,
        $target_id,
        $ip_address,
        $user_agent
    );


    $stmt->execute();

    $stmt->close();
}


/* =========================================================
   HANDLE POST ACTIONS
========================================================= */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $action =
        $_POST['action'] ?? '';


    /* =====================================================
       ADD CATEGORY
    ===================================================== */

    if ($action === 'add') {

        $category_name =
            trim($_POST['category_name'] ?? '');

        $description =
            trim($_POST['description'] ?? '');

        $status =
            $_POST['status'] ?? 'Active';

        $invoice_required =
            isset($_POST['invoice_required']) ? 1 : 0;


        /* -------------------------------------------------
           VALIDATION
        ------------------------------------------------- */

        if ($category_name === '') {

            $error_message =
                'Category name is required.';

        } elseif (mb_strlen($category_name) > 100) {

            $error_message =
                'Category name cannot exceed 100 characters.';

        } elseif (
            $description !== '' &&
            mb_strlen($description) > 255
        ) {

            $error_message =
                'Description cannot exceed 255 characters.';

        } elseif (
            !in_array(
                $status,
                ['Active', 'Inactive'],
                true
            )
        ) {

            $error_message =
                'Invalid category status.';

        } else {


            /* -------------------------------------------------
               CHECK DUPLICATE CATEGORY
            ------------------------------------------------- */

            $stmt = $conn->prepare("
                SELECT category_id
                FROM Categories
                WHERE LOWER(category_name) = LOWER(?)
                LIMIT 1
            ");


            $existing_category = null;


            if ($stmt) {

                $stmt->bind_param(
                    "s",
                    $category_name
                );

                $stmt->execute();

                $result =
                    $stmt->get_result();

                $existing_category =
                    $result->fetch_assoc();

                $stmt->close();
            }


            if ($existing_category) {

                $error_message =
                    'A category with this name already exists.';

            } else {


                /* -------------------------------------------------
                   INSERT CATEGORY
                ------------------------------------------------- */

                $description_value =
                    $description !== ''
                    ? $description
                    : null;


                $stmt = $conn->prepare("
                    INSERT INTO Categories (
                        category_name,
                        description,
                        invoice_required,
                        status
                    )
                    VALUES (?, ?, ?, ?)
                ");


                if ($stmt) {

                    $stmt->bind_param(
                        "ssis",
                        $category_name,
                        $description_value,
                        $invoice_required,
                        $status
                    );


                    if ($stmt->execute()) {

                        $new_category_id =
                            $stmt->insert_id;


                        $invoice_text =
                            $invoice_required
                            ? 'Invoice required.'
                            : 'Invoice not required.';


                        logAdminAction(
                            $conn,
                            $admin_id,
                            "Created category '{$category_name}'. {$invoice_text}",
                            $new_category_id
                        );


                        $success_message =
                            'Category added successfully.';

                    } else {

                        if ($conn->errno === 1062) {

                            $error_message =
                                'A category with this name already exists.';

                        } else {

                            $error_message =
                                'Unable to add the category. Please try again.';
                        }
                    }


                    $stmt->close();

                } else {

                    $error_message =
                        'Unable to process the category request.';
                }
            }
        }
    }


    /* =====================================================
       UPDATE CATEGORY
    ===================================================== */

    elseif ($action === 'edit') {

        $category_id =
            (int) ($_POST['category_id'] ?? 0);

        $category_name =
            trim($_POST['category_name'] ?? '');

        $description =
            trim($_POST['description'] ?? '');

        $status =
            $_POST['status'] ?? 'Active';

        $invoice_required =
            isset($_POST['invoice_required']) ? 1 : 0;


        /* -------------------------------------------------
           VALIDATION
        ------------------------------------------------- */

        if ($category_id <= 0) {

            $error_message =
                'Invalid category.';

        } elseif ($category_name === '') {

            $error_message =
                'Category name is required.';

        } elseif (mb_strlen($category_name) > 100) {

            $error_message =
                'Category name cannot exceed 100 characters.';

        } elseif (
            $description !== '' &&
            mb_strlen($description) > 255
        ) {

            $error_message =
                'Description cannot exceed 255 characters.';

        } elseif (
            !in_array(
                $status,
                ['Active', 'Inactive'],
                true
            )
        ) {

            $error_message =
                'Invalid category status.';

        } else {


            /* -------------------------------------------------
               GET CURRENT CATEGORY
            ------------------------------------------------- */

            $stmt = $conn->prepare("
                SELECT
                    category_name,
                    status,
                    invoice_required
                FROM Categories
                WHERE category_id = ?
                  AND deleted_at IS NULL
                LIMIT 1
            ");


            $current_category = null;


            if ($stmt) {

                $stmt->bind_param(
                    "i",
                    $category_id
                );

                $stmt->execute();

                $result =
                    $stmt->get_result();

                $current_category =
                    $result->fetch_assoc();

                $stmt->close();
            }


            if (!$current_category) {

                $error_message =
                    'Category not found.';

            } else {


                /* -------------------------------------------------
                   CHECK DUPLICATE NAME
                ------------------------------------------------- */

                $stmt = $conn->prepare("
                    SELECT category_id
                    FROM Categories
                    WHERE LOWER(category_name) = LOWER(?)
                      AND category_id <> ?
                    LIMIT 1
                ");


                $duplicate = null;


                if ($stmt) {

                    $stmt->bind_param(
                        "si",
                        $category_name,
                        $category_id
                    );

                    $stmt->execute();

                    $result =
                        $stmt->get_result();

                    $duplicate =
                        $result->fetch_assoc();

                    $stmt->close();
                }


                if ($duplicate) {

                    $error_message =
                        'A category with this name already exists.';

                } else {


                    /* -------------------------------------------------
                       UPDATE CATEGORY
                    ------------------------------------------------- */

                    $description_value =
                        $description !== ''
                        ? $description
                        : null;


                    $stmt = $conn->prepare("
                        UPDATE Categories
                        SET
                            category_name = ?,
                            description = ?,
                            invoice_required = ?,
                            status = ?
                        WHERE category_id = ?
                          AND deleted_at IS NULL
                    ");


                    if ($stmt) {

                        $stmt->bind_param(
                            "ssisi",
                            $category_name,
                            $description_value,
                            $invoice_required,
                            $status,
                            $category_id
                        );


                        if ($stmt->execute()) {

                            $invoice_text =
                                $invoice_required
                                ? 'Invoice required.'
                                : 'Invoice not required.';


                            logAdminAction(
                                $conn,
                                $admin_id,
                                "Updated category from '{$current_category['category_name']}' to '{$category_name}'. Status set to {$status}. {$invoice_text}",
                                $category_id
                            );


                            $success_message =
                                'Category updated successfully.';

                        } else {

                            if ($conn->errno === 1062) {

                                $error_message =
                                    'A category with this name already exists.';

                            } else {

                                $error_message =
                                    'Unable to update the category.';
                            }
                        }


                        $stmt->close();

                    } else {

                        $error_message =
                            'Unable to process the category request.';
                    }
                }
            }
        }
    }


    /* =====================================================
       TOGGLE STATUS
    ===================================================== */

    elseif ($action === 'toggle_status') {

        $category_id =
            (int) ($_POST['category_id'] ?? 0);


        if ($category_id <= 0) {

            $error_message =
                'Invalid category.';

        } else {


            /* -------------------------------------------------
               GET CATEGORY
            ------------------------------------------------- */

            $stmt = $conn->prepare("
                SELECT
                    category_name,
                    status
                FROM Categories
                WHERE category_id = ?
                  AND deleted_at IS NULL
                LIMIT 1
            ");


            $category = null;


            if ($stmt) {

                $stmt->bind_param(
                    "i",
                    $category_id
                );

                $stmt->execute();

                $result =
                    $stmt->get_result();

                $category =
                    $result->fetch_assoc();

                $stmt->close();
            }


            if (!$category) {

                $error_message =
                    'Category not found.';

            } else {


                $new_status =
                    $category['status'] === 'Active'
                    ? 'Inactive'
                    : 'Active';


                $stmt = $conn->prepare("
                    UPDATE Categories
                    SET status = ?
                    WHERE category_id = ?
                      AND deleted_at IS NULL
                ");


                if ($stmt) {

                    $stmt->bind_param(
                        "si",
                        $new_status,
                        $category_id
                    );


                    if ($stmt->execute()) {

                        logAdminAction(
                            $conn,
                            $admin_id,
                            "Changed category '{$category['category_name']}' status from '{$category['status']}' to '{$new_status}'.",
                            $category_id
                        );


                        $success_message =
                            "Category status changed to {$new_status}.";

                    } else {

                        $error_message =
                            'Unable to change category status.';
                    }


                    $stmt->close();

                } else {

                    $error_message =
                        'Unable to process the status change.';
                }
            }
        }
    }


    /* =====================================================
       SOFT DELETE CATEGORY
    ===================================================== */

    elseif ($action === 'delete') {

        $category_id =
            (int) ($_POST['category_id'] ?? 0);


        if ($category_id <= 0) {

            $error_message =
                'Invalid category.';

        } else {


            /* -------------------------------------------------
               GET CATEGORY
            ------------------------------------------------- */

            $stmt = $conn->prepare("
                SELECT
                    category_name
                FROM Categories
                WHERE category_id = ?
                  AND deleted_at IS NULL
                LIMIT 1
            ");


            $category = null;


            if ($stmt) {

                $stmt->bind_param(
                    "i",
                    $category_id
                );

                $stmt->execute();

                $result =
                    $stmt->get_result();

                $category =
                    $result->fetch_assoc();

                $stmt->close();
            }


            if (!$category) {

                $error_message =
                    'Category not found.';

            } else {


                /* -------------------------------------------------
                   CHECK PRODUCT USAGE
                ------------------------------------------------- */

                $stmt = $conn->prepare("
                    SELECT COUNT(*) AS product_count
                    FROM Products
                    WHERE category_id = ?
                      AND deleted_at IS NULL
                ");


                $product_count = 0;


                if ($stmt) {

                    $stmt->bind_param(
                        "i",
                        $category_id
                    );

                    $stmt->execute();

                    $result =
                        $stmt->get_result();

                    $usage =
                        $result->fetch_assoc();

                    $product_count =
                        (int) ($usage['product_count'] ?? 0);

                    $stmt->close();
                }


                /* -------------------------------------------------
                   SOFT DELETE
                ------------------------------------------------- */

                $stmt = $conn->prepare("
                    UPDATE Categories
                    SET
                        deleted_at = NOW(),
                        status = 'Inactive'
                    WHERE category_id = ?
                      AND deleted_at IS NULL
                ");


                if ($stmt) {

                    $stmt->bind_param(
                        "i",
                        $category_id
                    );


                    if ($stmt->execute()) {

                        $log_description =
                            "Deleted category '{$category['category_name']}'.";


                        if ($product_count > 0) {

                            $log_description .=
                                " Category had {$product_count} active product(s) associated with it.";
                        }


                        logAdminAction(
                            $conn,
                            $admin_id,
                            $log_description,
                            $category_id
                        );


                        $success_message =
                            'Category deleted successfully.';

                    } else {

                        $error_message =
                            'Unable to delete the category.';
                    }


                    $stmt->close();

                } else {

                    $error_message =
                        'Unable to process the category deletion.';
                }

                
            }
        }
    }

    /* =====================================================
    RESTORE SOFT-DELETED CATEGORY
    ===================================================== */

    elseif ($action === 'restore') {

        $category_id = (int) ($_POST['category_id'] ?? 0);

        if ($category_id <= 0) {
            $error_message = 'Invalid category.';
        } else {

            $stmt = $conn->prepare("
                SELECT category_name
                FROM Categories
                WHERE category_id = ?
                AND deleted_at IS NOT NULL
                LIMIT 1
            ");

            $deleted_category = null;

            if ($stmt) {
                $stmt->bind_param("i", $category_id);
                $stmt->execute();
                $deleted_category = $stmt->get_result()->fetch_assoc();
                $stmt->close();
            }

            if (!$deleted_category) {
                $error_message = 'Deleted category not found.';
            } else {

                $stmt = $conn->prepare("
                    UPDATE Categories
                    SET deleted_at = NULL,
                        status = 'Inactive'
                    WHERE category_id = ?
                    AND deleted_at IS NOT NULL
                ");

                if ($stmt) {
                    $stmt->bind_param("i", $category_id);

                    if ($stmt->execute() && $stmt->affected_rows > 0) {

                        logAdminAction(
                            $conn,
                            $admin_id,
                            "Restored category '{$deleted_category['category_name']}'. Category restored as Inactive.",
                            $category_id
                        );

                        $success_message =
                            'Category restored successfully. It is currently Inactive; activate it when ready.';

                    } else {
                        $error_message = 'Unable to restore the category.';
                    }

                    $stmt->close();

                } else {
                    $error_message = 'Unable to process the restore request.';
                }
            }
        }
    }
}


/* =========================================================
   FETCH CATEGORIES
========================================================= */

$categories = [];

$category_view = $_GET['view'] ?? 'active';

if (!in_array($category_view, ['active', 'deleted', 'all'], true)) {
    $category_view = 'active';
}

$category_where = '';

if ($category_view === 'active') {
    $category_where = 'WHERE c.deleted_at IS NULL';

} elseif ($category_view === 'deleted') {
    $category_where = 'WHERE c.deleted_at IS NOT NULL';
}

$sql = "
    SELECT
        c.category_id,
        c.category_name,
        c.description,
        c.invoice_required,
        c.status,
        c.created_at,
        c.updated_at,
        c.deleted_at,

        COUNT(
            CASE
                WHEN p.deleted_at IS NULL
                THEN p.product_id
            END
        ) AS product_count

    FROM Categories c

    LEFT JOIN Products p
        ON p.category_id = c.category_id

    {$category_where}

    GROUP BY
        c.category_id,
        c.category_name,
        c.description,
        c.invoice_required,
        c.status,
        c.created_at,
        c.updated_at,
        c.deleted_at

    ORDER BY
        (c.deleted_at IS NOT NULL) ASC,
        c.category_name ASC
";

$stmt = $conn->prepare($sql);

if ($stmt) {
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $categories[] = $row;
    }

    $stmt->close();
}


/* =========================================================
   CATEGORY STATISTICS
========================================================= */

$total_categories =
    count($categories);

$active_categories = 0;

$inactive_categories = 0;

$total_category_products = 0;

$invoice_required_categories = 0;


foreach ($categories as $category) {

    if ($category['status'] === 'Active') {

        $active_categories++;

    } else {

        $inactive_categories++;
    }


    $total_category_products +=
        (int) $category['product_count'];


    if ((int) $category['invoice_required'] === 1) {

        $invoice_required_categories++;
    }
}

$deleted_category_count = 0;

$count_result = $conn->query("
    SELECT COUNT(*) AS total
    FROM Categories
    WHERE deleted_at IS NOT NULL
");

if ($count_result) {
    $deleted_category_count =
        (int) ($count_result->fetch_assoc()['total'] ?? 0);
}


/* =========================================================
   BREADCRUMBS
========================================================= */

$breadcrumbs = [

    [
        'label' => 'Categories',
        'url' => 'categories.php'
    ],

    [
        'label' => 'Manage Categories'
    ]

];


include 'includes/admin_header.php';

?>





<!-- =========================================================
     ALERTS
========================================================= -->

<?php if ($success_message): ?>

    <div class="category-alert success">

        <i class="fa-solid fa-circle-check"></i>

        <span>
            <?= htmlspecialchars($success_message) ?>
        </span>

    </div>

<?php endif; ?>


<?php if ($error_message): ?>

    <div class="category-alert error">

        <i class="fa-solid fa-circle-exclamation"></i>

        <span>
            <?= htmlspecialchars($error_message) ?>
        </span>

    </div>

<?php endif; ?>


<!-- =========================================================
     STATISTICS
========================================================= -->

<div class="category-stats">


    <div class="category-stat-card">

        <div class="category-stat-label">

            <i class="fa-solid fa-layer-group"></i>

            <span>
                Total Categories
            </span>

        </div>

        <div class="category-stat-value">

            <?= $total_categories ?>

        </div>

    </div>


    <div class="category-stat-card">

        <div class="category-stat-label">

            <i class="fa-solid fa-circle-check"></i>

            <span>
                Active Categories
            </span>

        </div>

        <div class="category-stat-value">

            <?= $active_categories ?>

        </div>

    </div>


    <div class="category-stat-card">

        <div class="category-stat-label">

            <i class="fa-solid fa-box"></i>

            <span>
                Products Categorized
            </span>

        </div>

        <div class="category-stat-value">

            <?= $total_category_products ?>

        </div>

    </div>


    <div class="category-stat-card">

        <div class="category-stat-label">

            <i class="fa-solid fa-file-invoice"></i>

            <span>
                Invoice Required
            </span>

        </div>

        <div class="category-stat-value">

            <?= $invoice_required_categories ?>

        </div>

    </div>


</div>


<!-- =========================================================
     TOOLBAR
========================================================= -->

<div class="category-toolbar">


    <div class="category-search">

        <i class="fa-solid fa-magnifying-glass"></i>

        <input
            type="text"
            id="categorySearch"
            placeholder="Search categories..."
            autocomplete="off"
        >

    </div>


    <button
        type="button"
        class="category-add-btn"
        onclick="openAddCategoryModal()"
    >

        <i class="fa-solid fa-plus"></i>

        <span>
            Add Category
        </span>

    </button>


</div>


<!-- =========================================================
     ACTIVE / DELETED FILTER
========================================================= -->

<div class="category-view-tabs">

    <a
        href="categories.php?view=active"
        class="category-view-tab <?= $category_view === 'active' ? 'active' : '' ?>"
    >
        <i class="fa-solid fa-layer-group"></i>
        Active Categories
    </a>

    <a
        href="categories.php?view=deleted"
        class="category-view-tab <?= $category_view === 'deleted' ? 'active' : '' ?>"
    >
        <i class="fa-solid fa-trash-can"></i>
        Deleted
        <span class="category-view-count">
            <?= $deleted_category_count ?>
        </span>
    </a>

    <a
        href="categories.php?view=all"
        class="category-view-tab <?= $category_view === 'all' ? 'active' : '' ?>"
    >
        <i class="fa-solid fa-list"></i>
        All
    </a>

</div>

<!-- =========================================================
     CATEGORY TABLE
========================================================= -->

<div class="category-table-wrapper">

    <?php if (!empty($categories)): ?>

        <table
            class="category-table"
            id="categoryTable"
        >

            <thead>

                <tr>

                    <th>
                        Category
                    </th>

                    <th>
                        Description
                    </th>

                    <th>
                        Products
                    </th>

                    <th>
                        Invoice
                    </th>

                    <th>
                        Status
                    </th>

                    <th>
                        Created
                    </th>

                    <th>
                        Deleted
                    </th>

                    <th>
                        Actions
                    </th>

                </tr>

            </thead>


            <tbody>

                <?php foreach ($categories as $category): ?>

                    <tr
                        data-category-row
                        data-category-name="<?= htmlspecialchars(
                            strtolower($category['category_name'])
                        ) ?>"
                    >


                        <!-- CATEGORY -->

                        <td>

                            <div class="category-name">

                                <?= htmlspecialchars(
                                    $category['category_name']
                                ) ?>

                            </div>

                        </td>


                        <!-- DESCRIPTION -->

                        <td>

                            <div
                                class="category-description"
                                title="<?= htmlspecialchars(
                                    $category['description'] ?? ''
                                ) ?>"
                            >

                                <?=
                                    $category['description']
                                    ? htmlspecialchars(
                                        $category['description']
                                    )
                                    : 'No description'
                                ?>

                            </div>

                        </td>


                        <!-- PRODUCTS -->

                        <td>

                            <span class="category-product-count">

                                <?= (int) $category['product_count'] ?>

                            </span>

                        </td>


                        <!-- INVOICE -->

                        <td>

                            <?php if (
                                (int) $category['invoice_required'] === 1
                            ): ?>

                                <span class="category-invoice required">

                                    <i class="fa-solid fa-file-invoice"></i>

                                    Required

                                </span>

                            <?php else: ?>

                                <span class="category-invoice optional">

                                    <i class="fa-regular fa-file"></i>

                                    Optional

                                </span>

                            <?php endif; ?>

                        </td>


                        <!-- STATUS -->

                        <td>

                            <?php if (!empty($category['deleted_at'])): ?>

                                <span class="category-status deleted">
                                    <span class="category-status-dot"></span>
                                    Deleted
                                </span>

                            <?php else: ?>

                                <span class="category-status <?= $category['status'] === 'Active'
                                    ? 'active'
                                    : 'inactive' ?>">

                                    <span class="category-status-dot"></span>

                                    <?= htmlspecialchars($category['status']) ?>

                                </span>

                            <?php endif; ?>

                        </td>


                        <td>

                            <?php if (!empty($category['deleted_at'])): ?>

                                <span class="category-deleted-date">
                                    <?= date('d M Y', strtotime($category['deleted_at'])) ?>
                                </span>

                            <?php else: ?>

                                <span class="category-not-deleted">—</span>

                            <?php endif; ?>

                        </td>


                        <!-- CREATED -->

                        <td>

                            <?= date(
                                'd M Y',
                                strtotime($category['created_at'])
                            ) ?>

                        </td>


                        <!-- ACTIONS -->

                        <td>

                            <div class="category-actions">

                                <?php if (!empty($category['deleted_at'])): ?>

                                    <button
                                        type="button"
                                        class="category-action-btn restore"
                                        title="Restore Category"
                                        onclick='openRestoreModal(
                                            <?= (int) $category["category_id"] ?>,
                                            <?= json_encode(
                                                $category["category_name"],
                                                JSON_HEX_TAG |
                                                JSON_HEX_APOS |
                                                JSON_HEX_AMP |
                                                JSON_HEX_QUOT
                                            ) ?>
                                        )'
                                    >
                                        <i class="fa-solid fa-rotate-left"></i>
                                    </button>

                                <?php else: ?>

                                    <!-- EDIT -->

                                    <button
                                        type="button"
                                        class="category-action-btn"
                                        title="Edit Category"
                                        onclick='openEditCategoryModal(
                                            <?= json_encode(
                                                $category,
                                                JSON_HEX_TAG |
                                                JSON_HEX_APOS |
                                                JSON_HEX_AMP |
                                                JSON_HEX_QUOT
                                            ) ?>
                                        )'
                                    >

                                        <i class="fa-solid fa-pen"></i>

                                    </button>


                                    <!-- STATUS -->

                                    <button
                                        type="button"
                                        class="category-action-btn"
                                        title="<?= $category['status'] === 'Active'
                                            ? 'Deactivate Category'
                                            : 'Activate Category' ?>"
                                        onclick='openStatusModal(
                                            <?= (int) $category['category_id'] ?>,
                                            <?= json_encode(
                                                $category['category_name'],
                                                JSON_HEX_TAG |
                                                JSON_HEX_APOS |
                                                JSON_HEX_AMP |
                                                JSON_HEX_QUOT
                                            ) ?>,
                                            <?= json_encode(
                                                $category['status'],
                                                JSON_HEX_TAG |
                                                JSON_HEX_APOS |
                                                JSON_HEX_AMP |
                                                JSON_HEX_QUOT
                                            ) ?>
                                        )'
                                    >

                                        <?php if (
                                            $category['status'] === 'Active'
                                        ): ?>

                                            <i class="fa-solid fa-toggle-on"></i>

                                        <?php else: ?>

                                            <i class="fa-solid fa-toggle-off"></i>

                                        <?php endif; ?>

                                    </button>


                                    <!-- DELETE -->

                                    <button
                                        type="button"
                                        class="category-action-btn delete"
                                        title="Delete Category"
                                        onclick='openDeleteModal(
                                            <?= (int) $category['category_id'] ?>,
                                            <?= json_encode(
                                                $category['category_name'],
                                                JSON_HEX_TAG |
                                                JSON_HEX_APOS |
                                                JSON_HEX_AMP |
                                                JSON_HEX_QUOT
                                            ) ?>,
                                            <?= (int) $category['product_count'] ?>
                                        )'
                                    >

                                        <i class="fa-solid fa-trash"></i>

                                    </button>

                                <?php endif; ?>

                            </div>

                        </td>


                    </tr>

                <?php endforeach; ?>

            </tbody>

        </table>


        <!-- SEARCH EMPTY -->

        <div
            class="category-empty"
            id="categorySearchEmpty"
            style="display:none;"
        >

            <i class="fa-solid fa-magnifying-glass"></i>

            <strong>
                No categories found
            </strong>

            <span>
                Try a different search term.
            </span>

        </div>


    <?php else: ?>


        <!-- NO CATEGORIES -->

        <div class="category-empty">

            <i class="fa-solid fa-layer-group"></i>

            <strong>
                No categories available
            </strong>

            <span>
                Create your first product category to get started.
            </span>

        </div>


    <?php endif; ?>

</div>


<!-- =========================================================
     ADD CATEGORY MODAL
========================================================= -->

<div
    class="category-modal"
    id="addCategoryModal"
>

    <div class="category-modal-content">


        <div class="category-modal-header">

            <h2>
                Add Category
            </h2>

            <button
                type="button"
                class="category-modal-close"
                onclick="closeCategoryModal('addCategoryModal')"
            >

                <i class="fa-solid fa-xmark"></i>

            </button>

        </div>


        <form method="POST">

            <input
                type="hidden"
                name="action"
                value="add"
            >


            <div class="category-modal-body">


                <div class="category-form-group">

                    <label for="add_category_name">
                        Category Name
                    </label>

                    <input
                        type="text"
                        id="add_category_name"
                        name="category_name"
                        maxlength="100"
                        required
                    >

                </div>


                <div class="category-form-group">

                    <label for="add_description">
                        Description
                    </label>

                    <textarea
                        id="add_description"
                        name="description"
                        maxlength="255"
                        placeholder="Enter a short category description..."
                    ></textarea>

                </div>


                <!-- INVOICE REQUIREMENT -->

                <div class="category-form-group">

                    <label>
                        Invoice Requirement
                    </label>


                    <label class="category-checkbox">

                        <input
                            type="checkbox"
                            name="invoice_required"
                            id="add_invoice_required"
                            value="1"
                        >

                        <span class="category-checkbox-box"></span>

                        <span class="category-checkbox-text">

                            Require invoice for products in this category

                        </span>

                    </label>


                    <small class="category-form-help">

                        If enabled, sellers will be required to provide
                        an invoice when listing products in this category.

                    </small>

                </div>


                <!-- STATUS -->

                <div class="category-form-group">

                    <label for="add_status">
                        Status
                    </label>

                    <select
                        id="add_status"
                        name="status"
                    >

                        <option value="Active">
                            Active
                        </option>

                        <option value="Inactive">
                            Inactive
                        </option>

                    </select>

                </div>


            </div>


            <div class="category-modal-footer">

                <button
                    type="button"
                    class="category-modal-btn cancel"
                    onclick="closeCategoryModal('addCategoryModal')"
                >
                    Cancel
                </button>

                <button
                    type="submit"
                    class="category-modal-btn submit"
                >
                    Add Category
                </button>

            </div>


        </form>

    </div>

</div>


<!-- =========================================================
     EDIT CATEGORY MODAL
========================================================= -->

<div
    class="category-modal"
    id="editCategoryModal"
>

    <div class="category-modal-content">


        <div class="category-modal-header">

            <h2>
                Edit Category
            </h2>

            <button
                type="button"
                class="category-modal-close"
                onclick="closeCategoryModal('editCategoryModal')"
            >

                <i class="fa-solid fa-xmark"></i>

            </button>

        </div>


        <form method="POST">

            <input
                type="hidden"
                name="action"
                value="edit"
            >

            <input
                type="hidden"
                name="category_id"
                id="edit_category_id"
            >


            <div class="category-modal-body">


                <div class="category-form-group">

                    <label for="edit_category_name">
                        Category Name
                    </label>

                    <input
                        type="text"
                        id="edit_category_name"
                        name="category_name"
                        maxlength="100"
                        required
                    >

                </div>


                <div class="category-form-group">

                    <label for="edit_description">
                        Description
                    </label>

                    <textarea
                        id="edit_description"
                        name="description"
                        maxlength="255"
                        placeholder="Enter a short category description..."
                    ></textarea>

                </div>


                <!-- INVOICE REQUIREMENT -->

                <div class="category-form-group">

                    <label>
                        Invoice Requirement
                    </label>


                    <label class="category-checkbox">

                        <input
                            type="checkbox"
                            name="invoice_required"
                            id="edit_invoice_required"
                            value="1"
                        >

                        <span class="category-checkbox-box"></span>

                        <span class="category-checkbox-text">

                            Require invoice for products in this category

                        </span>

                    </label>


                    <small class="category-form-help">

                        Sellers will be required to provide an invoice
                        when listing products in this category.

                    </small>

                </div>


                <!-- STATUS -->

                <div class="category-form-group">

                    <label for="edit_status">
                        Status
                    </label>

                    <select
                        id="edit_status"
                        name="status"
                    >

                        <option value="Active">
                            Active
                        </option>

                        <option value="Inactive">
                            Inactive
                        </option>

                    </select>

                </div>


            </div>


            <div class="category-modal-footer">

                <button
                    type="button"
                    class="category-modal-btn cancel"
                    onclick="closeCategoryModal('editCategoryModal')"
                >
                    Cancel
                </button>

                <button
                    type="submit"
                    class="category-modal-btn submit"
                >
                    Save Changes
                </button>

            </div>


        </form>

    </div>

</div>


<!-- =========================================================
     STATUS CONFIRMATION MODAL
========================================================= -->

<div
    class="category-modal"
    id="statusCategoryModal"
>

    <div class="category-modal-content category-confirm-modal">


        <div class="category-modal-header">

            <h2 id="statusModalTitle">
                Change Category Status
            </h2>

            <button
                type="button"
                class="category-modal-close"
                onclick="closeCategoryModal('statusCategoryModal')"
            >

                <i class="fa-solid fa-xmark"></i>

            </button>

        </div>


        <div class="category-confirm-body">

            <div class="category-confirm-icon">

                <i
                    class="fa-solid fa-toggle-on"
                    id="statusModalIcon"
                ></i>

            </div>


            <h3 id="statusModalHeading">
                Change category status?
            </h3>


            <p id="statusModalMessage">
                Are you sure you want to change this category status?
            </p>

        </div>


        <form method="POST">

            <input
                type="hidden"
                name="action"
                value="toggle_status"
            >

            <input
                type="hidden"
                name="category_id"
                id="status_category_id"
            >


            <div class="category-modal-footer">

                <button
                    type="button"
                    class="category-modal-btn cancel"
                    onclick="closeCategoryModal('statusCategoryModal')"
                >
                    Cancel
                </button>

                <button
                    type="submit"
                    class="category-modal-btn submit"
                    id="statusModalSubmit"
                >
                    Confirm
                </button>

            </div>

        </form>

    </div>

</div>


<!-- =========================================================
     DELETE CONFIRMATION MODAL
========================================================= -->

<div
    class="category-modal"
    id="deleteCategoryModal"
>

    <div class="category-modal-content category-confirm-modal">


        <div class="category-modal-header">

            <h2>
                Delete Category
            </h2>

            <button
                type="button"
                class="category-modal-close"
                onclick="closeCategoryModal('deleteCategoryModal')"
            >

                <i class="fa-solid fa-xmark"></i>

            </button>

        </div>


        <div class="category-confirm-body">

            <div class="category-confirm-icon danger">

                <i class="fa-solid fa-trash"></i>

            </div>


            <h3>
                Delete this category?
            </h3>


            <p>

                You are about to delete

                <strong id="deleteCategoryName"></strong>.

            </p>


            <div
                class="category-delete-warning"
                id="deleteProductWarning"
            >

                <i class="fa-solid fa-circle-info"></i>

                <span id="deleteProductWarningText"></span>

            </div>


            <p class="category-confirm-note">

                The category will be removed from the active category
                list. Existing products will keep their category ID.

            </p>

        </div>


        <form method="POST">

            <input
                type="hidden"
                name="action"
                value="delete"
            >

            <input
                type="hidden"
                name="category_id"
                id="delete_category_id"
            >


            <div class="category-modal-footer">

                <button
                    type="button"
                    class="category-modal-btn cancel"
                    onclick="closeCategoryModal('deleteCategoryModal')"
                >
                    Cancel
                </button>

                <button
                    type="submit"
                    class="category-modal-btn danger-submit"
                >
                    Delete Category
                </button>

            </div>

        </form>

    </div>

</div>

<!-- =========================================================
     RESTORE CONFIRMATION MODAL
========================================================= -->

<div
    class="category-modal"
    id="restoreCategoryModal"
>

    <div class="category-modal-content category-confirm-modal">

        <div class="category-modal-header">

            <h2>
                Restore Category
            </h2>

            <button
                type="button"
                class="category-modal-close"
                onclick="closeCategoryModal('restoreCategoryModal')"
            >
                <i class="fa-solid fa-xmark"></i>
            </button>

        </div>


        <div class="category-confirm-body">

            <div class="category-confirm-icon">

                <i class="fa-solid fa-rotate-left"></i>

            </div>


            <h3>
                Restore this category?
            </h3>


            <p>
                You are about to restore
                <strong id="restoreCategoryName"></strong>.
            </p>


            <p class="category-confirm-note">
                The category will return to the category list
                with its status set to Inactive. You can activate
                it separately when you are ready.
            </p>

        </div>


        <form method="POST">

            <input
                type="hidden"
                name="action"
                value="restore"
            >

            <input
                type="hidden"
                name="category_id"
                id="restore_category_id"
            >


            <div class="category-modal-footer">

                <button
                    type="button"
                    class="category-modal-btn cancel"
                    onclick="closeCategoryModal('restoreCategoryModal')"
                >
                    Cancel
                </button>


                <button
                    type="submit"
                    class="category-modal-btn submit"
                >
                    <i class="fa-solid fa-rotate-left"></i>
                    Restore Category
                </button>

            </div>

        </form>

    </div>

</div>


<!-- =========================================================
     JAVASCRIPT
========================================================= -->

<script>

/* =========================================================
   OPEN ADD MODAL
========================================================= */

function openAddCategoryModal() {

    const modal =
        document.getElementById('addCategoryModal');

    const form =
        modal.querySelector('form');

    form.reset();

    modal.classList.add('show');

    document
        .getElementById('add_category_name')
        .focus();
}


/* =========================================================
   OPEN EDIT MODAL
========================================================= */

function openEditCategoryModal(category) {

    document
        .getElementById('edit_category_id')
        .value = category.category_id;


    document
        .getElementById('edit_category_name')
        .value = category.category_name;


    document
        .getElementById('edit_description')
        .value = category.description || '';


    document
        .getElementById('edit_status')
        .value = category.status;


    document
        .getElementById('edit_invoice_required')
        .checked =
            Number(category.invoice_required) === 1;


    document
        .getElementById('editCategoryModal')
        .classList.add('show');


    document
        .getElementById('edit_category_name')
        .focus();
}


/* =========================================================
   OPEN STATUS MODAL
========================================================= */

function openStatusModal(
    categoryId,
    categoryName,
    currentStatus
) {

    const newStatus =
        currentStatus === 'Active'
        ? 'Inactive'
        : 'Active';


    document
        .getElementById('status_category_id')
        .value = categoryId;


    document
        .getElementById('statusModalHeading')
        .textContent =
            `Change "${categoryName}" to ${newStatus}?`;


    document
        .getElementById('statusModalMessage')
        .textContent =
            currentStatus === 'Active'
            ? 'Products can remain associated with this category, but the category will no longer be available as an active category.'
            : 'This will make the category available as an active category again.';


    document
        .getElementById('statusModalSubmit')
        .textContent =
            `Set ${newStatus}`;


    const icon =
        document.getElementById('statusModalIcon');


    if (newStatus === 'Inactive') {

        icon.className =
            'fa-solid fa-toggle-off';

    } else {

        icon.className =
            'fa-solid fa-toggle-on';
    }


    document
        .getElementById('statusCategoryModal')
        .classList.add('show');
}

/* =========================================================
   OPEN RESTORE MODAL
========================================================= */

function openRestoreModal(
    categoryId,
    categoryName
) {

    document
        .getElementById('restore_category_id')
        .value = categoryId;


    document
        .getElementById('restoreCategoryName')
        .textContent = `"${categoryName}"`;


    document
        .getElementById('restoreCategoryModal')
        .classList.add('show');

}

/* =========================================================
   OPEN DELETE MODAL
========================================================= */

function openDeleteModal(
    categoryId,
    categoryName,
    productCount
) {

    document
        .getElementById('delete_category_id')
        .value = categoryId;


    document
        .getElementById('deleteCategoryName')
        .textContent = `"${categoryName}"`;


    const warning =
        document.getElementById(
            'deleteProductWarning'
        );


    const warningText =
        document.getElementById(
            'deleteProductWarningText'
        );


    if (productCount > 0) {

        warning.style.display = 'flex';

        warningText.textContent =
            `This category currently has ${productCount} active product(s) associated with it. Those products will keep their existing category relationship.`;

    } else {

        warning.style.display = 'none';
    }


    document
        .getElementById('deleteCategoryModal')
        .classList.add('show');
}


/* =========================================================
   CLOSE MODAL
========================================================= */

function closeCategoryModal(modalId) {

    const modal =
        document.getElementById(modalId);


    if (modal) {

        modal.classList.remove('show');
    }
}


/* =========================================================
   CLOSE WHEN CLICKING OUTSIDE
========================================================= */

document.addEventListener(
    'click',
    function (event) {

        if (
            event.target.classList.contains(
                'category-modal'
            )
        ) {

            event.target.classList.remove('show');
        }

    }
);


/* =========================================================
   ESCAPE KEY
========================================================= */

document.addEventListener(
    'keydown',
    function (event) {

        if (event.key === 'Escape') {

            document
                .querySelectorAll(
                    '.category-modal.show'
                )
                .forEach(function (modal) {

                    modal.classList.remove('show');

                });
        }

    }
);


/* =========================================================
   CATEGORY SEARCH
========================================================= */

const categorySearch =
    document.getElementById(
        'categorySearch'
    );


if (categorySearch) {

    categorySearch.addEventListener(
        'input',
        function () {

            const searchTerm =
                this.value
                    .trim()
                    .toLowerCase();


            const rows =
                document.querySelectorAll(
                    '[data-category-row]'
                );


            let visibleRows = 0;


            rows.forEach(function (row) {

                const categoryName =
                    row.dataset.categoryName || '';


                if (
                    categoryName.includes(
                        searchTerm
                    )
                ) {

                    row.style.display = '';

                    visibleRows++;

                } else {

                    row.style.display = 'none';
                }

            });


            const searchEmpty =
                document.getElementById(
                    'categorySearchEmpty'
                );


            if (searchEmpty) {

                searchEmpty.style.display =
                    visibleRows === 0
                    ? 'block'
                    : 'none';
            }

        }
    );
}


/* =========================================================
   AUTO-HIDE ALERTS
========================================================= */

setTimeout(
    function () {

        document
            .querySelectorAll(
                '.category-alert'
            )
            .forEach(function (alert) {

                alert.style.transition =
                    'opacity 0.3s ease';

                alert.style.opacity = '0';


                setTimeout(
                    function () {

                        alert.remove();

                    },
                    300
                );

            });

    },
    4000
);

</script>


<?php

include 'includes/admin_footer.php';

?>
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

$page_title = 'Activity Logs | CampusXchange';
$icon = '<i class="fa-solid fa-clock-rotate-left" style="color: var(--primary);"></i>';
$page_heading = '[icon] Activity Logs';
$page_description = 'View and monitor administrative activity across CampusXchange.';
$active_page = 'activity-logs';
$page_css = 'activity-logs.css';


/* =====================================================
   CURRENT ADMIN
===================================================== */

$admin_id = (int) ($_SESSION['admin_id'] ?? 0);

if (!$admin_id) {

    header("Location: " . FRONTEND_URL . "/admin-login");
    exit;
}


/* =====================================================
   GET CURRENT ADMIN ROLE
===================================================== */

$current_admin = null;

$sql = "
    SELECT
        admin_id,
        full_name,
        email,
        admin_role,
        status
    FROM Admins
    WHERE admin_id = ?
      AND deleted_at IS NULL
    LIMIT 1
";

$stmt = $conn->prepare($sql);

if ($stmt) {

    $stmt->bind_param("i", $admin_id);
    $stmt->execute();

    $result = $stmt->get_result();

    $current_admin = $result->fetch_assoc();

    $stmt->close();
}


if (!$current_admin) {

    header("Location: " . FRONTEND_URL . "/admin-login");
    exit;
}


$current_admin_role = $current_admin['admin_role'];

$is_main_admin = ($current_admin_role === 'Main Admin');
$is_sub_admin = ($current_admin_role === 'Sub Admin');


/* =====================================================
   BREADCRUMBS
===================================================== */

$breadcrumbs = [
    [
        'label' => 'Activity Logs'
    ]
];


/* =====================================================
   FILTER VALUES
===================================================== */

$search = trim($_GET['search'] ?? '');

$selected_admin_id = isset($_GET['admin_id'])
    ? (int) $_GET['admin_id']
    : 0;

$selected_action_type = trim($_GET['action_type'] ?? '');

$selected_target_type = trim($_GET['target_type'] ?? '');

$date_from = trim($_GET['date_from'] ?? '');

$date_to = trim($_GET['date_to'] ?? '');


/* =====================================================
   ACTION TYPES
===================================================== */

$action_types = [
    'Login',
    'Logout',
    'Create',
    'Update',
    'Delete',
    'Restore',
    'Verify User',
    'Manage User',
    'Manage Product',
    'Review Report',
    'Issue Ban',
    'Revoke Ban',
    'Manage Category',
    'Change Settings',
    'Manage Admin',
    'Other'
];


/* =====================================================
   TARGET TYPES
===================================================== */

$target_types = [
    'User',
    'Admin',
    'Product',
    'Category',
    'Report',
    'Ban',
    'System Setting',
    'Other'
];


/* =====================================================
   FETCH ADMINS
   MAIN ADMIN ONLY
===================================================== */

$admins = [];

if ($is_main_admin) {

    $sql = "
        SELECT
            admin_id,
            full_name,
            email,
            admin_role
        FROM Admins
        WHERE deleted_at IS NULL
        ORDER BY full_name ASC
    ";

    $result = $conn->query($sql);

    if ($result) {

        while ($row = $result->fetch_assoc()) {

            $admins[] = $row;

        }
    }
}


/* =====================================================
   BUILD FILTER CONDITIONS
===================================================== */

$where = [];

$params = [];

$types = '';


/*
   Never show deleted logs.
*/

$where[] = "al.deleted_at IS NULL";


/*
   SUB ADMIN SECURITY RULE

   Sub Admin can ONLY see their own logs.

   This is enforced here at database-query level,
   even if someone manually changes the URL.
*/

if ($is_sub_admin) {

    $where[] = "al.admin_id = ?";

    $params[] = $admin_id;
    $types .= 'i';
}


/*
   MAIN ADMIN ADMIN FILTER

   Main Admin may select any admin.
*/

if ($is_main_admin && $selected_admin_id > 0) {

    $where[] = "al.admin_id = ?";

    $params[] = $selected_admin_id;
    $types .= 'i';
}


/*
   SEARCH
*/

if ($search !== '') {

    $where[] = "
        (
            al.action_description LIKE ?
            OR a.full_name LIKE ?
            OR a.email LIKE ?
            OR al.ip_address LIKE ?
            OR CAST(al.target_id AS CHAR) LIKE ?
        )
    ";

    $search_value = '%' . $search . '%';

    $params[] = $search_value;
    $params[] = $search_value;
    $params[] = $search_value;
    $params[] = $search_value;
    $params[] = $search_value;

    $types .= 'sssss';
}


/*
   ACTION TYPE
*/

if (
    $selected_action_type !== ''
    && in_array($selected_action_type, $action_types, true)
) {

    $where[] = "al.action_type = ?";

    $params[] = $selected_action_type;
    $types .= 's';
}


/*
   TARGET TYPE
*/

if (
    $selected_target_type !== ''
    && in_array($selected_target_type, $target_types, true)
) {

    $where[] = "al.target_type = ?";

    $params[] = $selected_target_type;
    $types .= 's';
}


/*
   DATE FROM
*/

if ($date_from !== '') {

    $date_from_value = DateTime::createFromFormat('Y-m-d', $date_from);

    if ($date_from_value !== false) {

        $where[] = "al.created_at >= ?";

        $params[] = $date_from . ' 00:00:00';
        $types .= 's';
    }
}


/*
   DATE TO
*/

if ($date_to !== '') {

    $date_to_value = DateTime::createFromFormat('Y-m-d', $date_to);

    if ($date_to_value !== false) {

        $where[] = "al.created_at <= ?";

        $params[] = $date_to . ' 23:59:59';
        $types .= 's';
    }
}


/* =====================================================
   WHERE CLAUSE
===================================================== */

$where_sql = implode(' AND ', $where);


/* =====================================================
   STATISTICS
===================================================== */

/*
   Statistics respect the same permission rules
   and currently selected filters.
*/

$stats = [
    'total' => 0,
    'today' => 0,
    'week' => 0,
    'month' => 0
];


/* -----------------------------------------------------
   TOTAL
----------------------------------------------------- */

$sql = "
    SELECT COUNT(*) AS total
    FROM Admin_Logs al
    INNER JOIN Admins a
        ON a.admin_id = al.admin_id
    WHERE {$where_sql}
";

$stmt = $conn->prepare($sql);

if ($stmt) {

    if (!empty($params)) {

        $stmt->bind_param($types, ...$params);

    }

    $stmt->execute();

    $result = $stmt->get_result();

    $row = $result->fetch_assoc();

    $stats['total'] = (int) ($row['total'] ?? 0);

    $stmt->close();
}


/* -----------------------------------------------------
   TODAY
----------------------------------------------------- */

$today_where = $where_sql . " AND DATE(al.created_at) = CURDATE()";

$sql = "
    SELECT COUNT(*) AS total
    FROM Admin_Logs al
    INNER JOIN Admins a
        ON a.admin_id = al.admin_id
    WHERE {$today_where}
";

$stmt = $conn->prepare($sql);

if ($stmt) {

    if (!empty($params)) {

        $stmt->bind_param($types, ...$params);

    }

    $stmt->execute();

    $result = $stmt->get_result();

    $row = $result->fetch_assoc();

    $stats['today'] = (int) ($row['total'] ?? 0);

    $stmt->close();
}


/* -----------------------------------------------------
   THIS WEEK
----------------------------------------------------- */

$week_where = $where_sql . "
    AND YEARWEEK(al.created_at, 1) = YEARWEEK(CURDATE(), 1)
";

$sql = "
    SELECT COUNT(*) AS total
    FROM Admin_Logs al
    INNER JOIN Admins a
        ON a.admin_id = al.admin_id
    WHERE {$week_where}
";

$stmt = $conn->prepare($sql);

if ($stmt) {

    if (!empty($params)) {

        $stmt->bind_param($types, ...$params);

    }

    $stmt->execute();

    $result = $stmt->get_result();

    $row = $result->fetch_assoc();

    $stats['week'] = (int) ($row['total'] ?? 0);

    $stmt->close();
}


/* -----------------------------------------------------
   THIS MONTH
----------------------------------------------------- */

$month_where = $where_sql . "
    AND YEAR(al.created_at) = YEAR(CURDATE())
    AND MONTH(al.created_at) = MONTH(CURDATE())
";

$sql = "
    SELECT COUNT(*) AS total
    FROM Admin_Logs al
    INNER JOIN Admins a
        ON a.admin_id = al.admin_id
    WHERE {$month_where}
";

$stmt = $conn->prepare($sql);

if ($stmt) {

    if (!empty($params)) {

        $stmt->bind_param($types, ...$params);

    }

    $stmt->execute();

    $result = $stmt->get_result();

    $row = $result->fetch_assoc();

    $stats['month'] = (int) ($row['total'] ?? 0);

    $stmt->close();
}


/* =====================================================
   PAGINATION
===================================================== */

$per_page = 20;

$current_page = isset($_GET['page'])
    ? max(1, (int) $_GET['page'])
    : 1;


/*
   Total filtered records
*/

$total_records = $stats['total'];

$total_pages = max(
    1,
    (int) ceil($total_records / $per_page)
);


if ($current_page > $total_pages) {

    $current_page = $total_pages;
}


$offset = ($current_page - 1) * $per_page;


/* =====================================================
   FETCH ACTIVITY LOGS
===================================================== */

$logs = [];

$sql = "
    SELECT
        al.log_id,
        al.admin_id,
        al.action_type,
        al.action_description,
        al.target_type,
        al.target_id,
        al.ip_address,
        al.user_agent,
        al.created_at,

        a.full_name AS admin_name,
        a.email AS admin_email,
        a.admin_role

    FROM Admin_Logs al

    INNER JOIN Admins a
        ON a.admin_id = al.admin_id

    WHERE {$where_sql}

    ORDER BY al.created_at DESC, al.log_id DESC

    LIMIT ? OFFSET ?
";


$stmt = $conn->prepare($sql);

if ($stmt) {

    $log_params = $params;

    $log_types = $types . 'ii';

    $log_params[] = $per_page;
    $log_params[] = $offset;

    $stmt->bind_param($log_types, ...$log_params);

    $stmt->execute();

    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {

        $logs[] = $row;

    }

    $stmt->close();
}


/* =====================================================
   HELPER FUNCTIONS
===================================================== */

function activity_log_action_class(string $action): string
{
    return match ($action) {

        'Login' => 'action-login',

        'Logout' => 'action-logout',

        'Create',
        'Restore' => 'action-create',

        'Update',
        'Manage User',
        'Manage Product',
        'Manage Category',
        'Manage Admin',
        'Change Settings' => 'action-update',

        'Delete' => 'action-delete',

        'Verify User' => 'action-verify',

        'Review Report' => 'action-review',

        'Issue Ban' => 'action-ban',

        'Revoke Ban' => 'action-revoke',

        default => 'action-other'
    };
}


function activity_log_target_label(?string $target_type, $target_id): string
{
    if (!$target_type) {
        return '—';
    }

    if ($target_id !== null && $target_id !== '') {

        return $target_type . ' #' . (int) $target_id;
    }

    return $target_type;
}


/* =====================================================
   INCLUDE HEADER
===================================================== */

include 'includes/admin_header.php';

?>

<div class="activity-logs-page">

    


    <!-- =================================================
         STATISTICS
    ================================================== -->

    <div class="stats-grid">

        <div class="stat-card">

            <div class="stat-icon">
                <i class="fa-solid fa-list"></i>
            </div>

            <div class="stat-content">

                <span class="stat-label">
                    Total Logs
                </span>

                <strong class="stat-value">
                    <?= number_format($stats['total']); ?>
                </strong>

            </div>

        </div>


        <div class="stat-card">

            <div class="stat-icon">
                <i class="fa-solid fa-calendar-day"></i>
            </div>

            <div class="stat-content">

                <span class="stat-label">
                    Today
                </span>

                <strong class="stat-value">
                    <?= number_format($stats['today']); ?>
                </strong>

            </div>

        </div>


        <div class="stat-card">

            <div class="stat-icon">
                <i class="fa-solid fa-calendar-week"></i>
            </div>

            <div class="stat-content">

                <span class="stat-label">
                    This Week
                </span>

                <strong class="stat-value">
                    <?= number_format($stats['week']); ?>
                </strong>

            </div>

        </div>


        <div class="stat-card">

            <div class="stat-icon">
                <i class="fa-solid fa-calendar"></i>
            </div>

            <div class="stat-content">

                <span class="stat-label">
                    This Month
                </span>

                <strong class="stat-value">
                    <?= number_format($stats['month']); ?>
                </strong>

            </div>

        </div>

    </div>


    <!-- =================================================
         FILTERS
    ================================================== -->

    <div class="filters-card">

        <form method="GET" action="activity-logs.php">

            <div class="filters-grid">

                <!-- Search -->

                <div class="filter-group filter-search">

                    <label for="search">
                        Search
                    </label>

                    <div class="search-input-wrapper">

                        <i class="fa-solid fa-magnifying-glass"></i>

                        <input
                            type="text"
                            id="search"
                            name="search"
                            value="<?= htmlspecialchars($search); ?>"
                            placeholder="Search activity..."
                        >

                    </div>

                </div>


                <!-- Admin -->

                <?php if ($is_main_admin): ?>

                    <div class="filter-group">

                        <label for="admin_id">
                            Admin
                        </label>

                        <select
                            id="admin_id"
                            name="admin_id"
                        >

                            <option value="0">
                                All Admins
                            </option>

                            <?php foreach ($admins as $admin): ?>

                                <option
                                    value="<?= (int) $admin['admin_id']; ?>"
                                    <?= $selected_admin_id === (int) $admin['admin_id'] ? 'selected' : ''; ?>
                                >
                                    <?= htmlspecialchars($admin['full_name']); ?>
                                    —
                                    <?= htmlspecialchars($admin['admin_role']); ?>
                                </option>

                            <?php endforeach; ?>

                        </select>

                    </div>

                <?php else: ?>

                    <div class="filter-group">

                        <label>
                            Admin
                        </label>

                        <div class="current-admin-filter">

                            <i class="fa-solid fa-user-shield"></i>

                            <span>
                                <?= htmlspecialchars($current_admin['full_name']); ?>
                            </span>

                        </div>

                    </div>

                <?php endif; ?>


                <!-- Action -->

                <div class="filter-group">

                    <label for="action_type">
                        Action
                    </label>

                    <select
                        id="action_type"
                        name="action_type"
                    >

                        <option value="">
                            All Actions
                        </option>

                        <?php foreach ($action_types as $action): ?>

                            <option
                                value="<?= htmlspecialchars($action); ?>"
                                <?= $selected_action_type === $action ? 'selected' : ''; ?>
                            >
                                <?= htmlspecialchars($action); ?>
                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>


                <!-- Target -->

                <div class="filter-group">

                    <label for="target_type">
                        Target
                    </label>

                    <select
                        id="target_type"
                        name="target_type"
                    >

                        <option value="">
                            All Targets
                        </option>

                        <?php foreach ($target_types as $target): ?>

                            <option
                                value="<?= htmlspecialchars($target); ?>"
                                <?= $selected_target_type === $target ? 'selected' : ''; ?>
                            >
                                <?= htmlspecialchars($target); ?>
                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>


                <!-- Date From -->

                <div class="filter-group">

                    <label for="date_from">
                        From
                    </label>

                    <input
                        type="date"
                        id="date_from"
                        name="date_from"
                        value="<?= htmlspecialchars($date_from); ?>"
                    >

                </div>


                <!-- Date To -->

                <div class="filter-group">

                    <label for="date_to">
                        To
                    </label>

                    <input
                        type="date"
                        id="date_to"
                        name="date_to"
                        value="<?= htmlspecialchars($date_to); ?>"
                    >

                </div>

            </div>


            <div class="filter-actions">

                <button
                    type="submit"
                    class="btn btn-primary"
                >
                    <i class="fa-solid fa-filter"></i>
                    Apply Filters
                </button>


                <a
                    href="activity-logs.php"
                    class="btn btn-secondary"
                >
                    <i class="fa-solid fa-rotate-left"></i>
                    Reset
                </a>

            </div>

        </form>

    </div>


    <!-- =================================================
         ACTIVITY TABLE
    ================================================== -->

    <div class="logs-card">

        <div class="logs-card-header">

            <div>

                <h2>
                    Administrative Activity
                </h2>

                <p>
                    <?= number_format($total_records); ?>
                    <?= $total_records === 1 ? 'log' : 'logs'; ?>
                    found
                </p>

            </div>

        </div>


        <?php if (!empty($logs)): ?>

            <div class="table-wrapper">

                <table class="activity-table">

                    <thead>

                        <tr>

                            <th>
                                Date & Time
                            </th>

                            <th>
                                Admin
                            </th>

                            <th>
                                Action
                            </th>

                            <th>
                                Description
                            </th>

                            <th>
                                Target
                            </th>

                            <th>
                                IP Address
                            </th>

                        </tr>

                    </thead>

                    <tbody>

                        <?php foreach ($logs as $log): ?>

                            <tr>

                                <!-- Date -->

                                <td>

                                    <div class="log-date">

                                        <strong>
                                            <?= date(
                                                'd M Y',
                                                strtotime($log['created_at'])
                                            ); ?>
                                        </strong>

                                        <span>
                                            <?= date(
                                                'h:i A',
                                                strtotime($log['created_at'])
                                            ); ?>
                                        </span>

                                    </div>

                                </td>


                                <!-- Admin -->

                                <td>

                                    <div class="admin-info">

                                        <div class="admin-avatar1">

                                            <i class="fa-solid fa-user-shield"></i>

                                        </div>

                                        <div>

                                            <strong>
                                                <?= htmlspecialchars($log['admin_name']); ?>
                                            </strong>

                                            <span>
                                                <?= htmlspecialchars($log['admin_role']); ?>
                                            </span>

                                        </div>

                                    </div>

                                </td>


                                <!-- Action -->

                                <td>

                                    <span
                                        class="action-badge <?= activity_log_action_class($log['action_type']); ?>"
                                    >
                                        <?= htmlspecialchars($log['action_type']); ?>
                                    </span>

                                </td>


                                <!-- Description -->

                                <td>

                                    <div class="description-cell">

                                        <?= htmlspecialchars($log['action_description']); ?>

                                    </div>

                                </td>


                                <!-- Target -->

                                <td>

                                    <span class="target-badge">

                                        <?= htmlspecialchars(
                                            activity_log_target_label(
                                                $log['target_type'],
                                                $log['target_id']
                                            )
                                        ); ?>

                                    </span>

                                </td>


                                <!-- IP -->

                                <td>

                                    <span class="ip-address">

                                        <?= $log['ip_address']
                                            ? htmlspecialchars($log['ip_address'])
                                            : '—'; ?>

                                    </span>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                    </tbody>

                </table>

            </div>


            <!-- =================================================
                 PAGINATION
            ================================================== -->

            <?php if ($total_pages > 1): ?>

                <div class="pagination">

                    <div class="pagination-info">

                        Showing

                        <?= number_format($offset + 1); ?>

                        –

                        <?= number_format(
                            min(
                                $offset + $per_page,
                                $total_records
                            )
                        ); ?>

                        of

                        <?= number_format($total_records); ?>

                    </div>


                    <div class="pagination-buttons">

                        <?php if ($current_page > 1): ?>

                            <?php

                            $prev_params = $_GET;

                            $prev_params['page'] = $current_page - 1;

                            ?>

                            <a
                                href="activity-logs.php?<?= htmlspecialchars(http_build_query($prev_params)); ?>"
                                class="pagination-btn"
                                aria-label="Previous page"
                            >
                                <i class="fa-solid fa-chevron-left"></i>
                            </a>

                        <?php endif; ?>


                        <?php

                        $start_page = max(
                            1,
                            $current_page - 2
                        );

                        $end_page = min(
                            $total_pages,
                            $current_page + 2
                        );

                        ?>


                        <?php for ($page = $start_page; $page <= $end_page; $page++): ?>

                            <?php

                            $page_params = $_GET;

                            $page_params['page'] = $page;

                            ?>

                            <a
                                href="activity-logs.php?<?= htmlspecialchars(http_build_query($page_params)); ?>"
                                class="pagination-btn <?= $page === $current_page ? 'active' : ''; ?>"
                            >
                                <?= $page; ?>
                            </a>

                        <?php endfor; ?>


                        <?php if ($current_page < $total_pages): ?>

                            <?php

                            $next_params = $_GET;

                            $next_params['page'] = $current_page + 1;

                            ?>

                            <a
                                href="activity-logs.php?<?= htmlspecialchars(http_build_query($next_params)); ?>"
                                class="pagination-btn"
                                aria-label="Next page"
                            >
                                <i class="fa-solid fa-chevron-right"></i>
                            </a>

                        <?php endif; ?>

                    </div>

                </div>

            <?php endif; ?>


        <?php else: ?>

            <!-- =================================================
                 EMPTY STATE
            ================================================== -->

            <div class="empty-state">

                <div class="empty-icon">

                    <i class="fa-solid fa-clock-rotate-left"></i>

                </div>

                <h3>
                    No Activity Logs Found
                </h3>

                <p>
                    No administrative activity matches the selected filters.
                </p>

                <?php if (
                    $search !== ''
                    || $selected_admin_id > 0
                    || $selected_action_type !== ''
                    || $selected_target_type !== ''
                    || $date_from !== ''
                    || $date_to !== ''
                ): ?>

                    <a
                        href="activity-logs.php"
                        class="btn btn-secondary"
                    >
                        <i class="fa-solid fa-rotate-left"></i>
                        Clear Filters
                    </a>

                <?php endif; ?>

            </div>

        <?php endif; ?>

    </div>

</div>


<?php

/* =====================================================
   FOOTER
===================================================== */

include 'includes/admin_footer.php';

?>
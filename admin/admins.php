
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
   MAIN ADMIN ACCESS
===================================================== */

$admin_id = (int)($_SESSION['admin_id'] ?? 0);
if (!$admin_id) {
    include_once '../config/app_config.php';
    header('Location: ' . FRONTEND_URL . '/admin-login');
    exit;
}

$stmt = $conn->prepare("SELECT admin_id, full_name, email, admin_role FROM Admins WHERE admin_id = ? AND status = 'Active' AND deleted_at IS NULL LIMIT 1");
$stmt->bind_param('i', $admin_id);
$stmt->execute();
$current_admin = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$current_admin) {
    session_destroy();
    include_once '../config/app_config.php';
    header('Location: ' . FRONTEND_URL . '/admin-login');
    exit;
}

$is_main_admin = ($current_admin['admin_role'] === 'Main Admin');


/* =====================================================
   PAGE SETTINGS
===================================================== */

$page_title = 'Admin Management | CampusXchange';
$page_heading = 'Admin Management';
$active_page = 'admins';
$page_css = 'admins.css';


if (!$is_main_admin) {
    include 'includes/admin_header.php';
    ?>
    <div class="admin-page">
        <section class="admin-restricted-card">
            <div class="admin-restricted-icon"><i class="fa-solid fa-lock"></i></div>
            <div>
                <h2>Admin management is restricted</h2>
                <p>You are not the Main Admin. Only the Main Admin can view, add, or manage admin accounts.</p>
            </div>
        </section>
    </div>
    <?php
    include 'includes/admin_footer.php';
    exit;
}


/* =====================================================
   SEARCH AND FILTERS
===================================================== */

$search = trim($_GET['search'] ?? '');
$role_filter = $_GET['role'] ?? '';
$status_filter = $_GET['status'] ?? '';

$allowed_roles = ['Main Admin', 'Sub Admin'];
$allowed_statuses = ['Active', 'Suspended', 'Banned'];

if (!in_array($role_filter, $allowed_roles, true)) {
    $role_filter = '';
}

if (!in_array($status_filter, $allowed_statuses, true)) {
    $status_filter = '';
}


/* =====================================================
   PAGINATION
===================================================== */

$per_page = 10;

$page = max(1, (int)($_GET['page'] ?? 1));

$offset = ($page - 1) * $per_page;


/* =====================================================
   SUMMARY COUNTS
===================================================== */

$summary = [
    'total' => 0,
    'active' => 0,
    'suspended' => 0,
    'banned' => 0
];

$summary_result = $conn->query("
    SELECT
        COUNT(*) AS total,
        SUM(status = 'Active') AS active,
        SUM(status = 'Suspended') AS suspended,
        SUM(status = 'Banned') AS banned
    FROM Admins
    WHERE deleted_at IS NULL
");

if ($summary_result) {
    $summary = $summary_result->fetch_assoc();
}


/* =====================================================
   BUILD FILTER QUERY
===================================================== */

$where = ["a.deleted_at IS NULL"];
$params = [];
$types = "";

if ($search !== '') {

    $where[] = "(
        a.full_name LIKE ?
        OR a.email LIKE ?
        OR CAST(a.admin_id AS CHAR) LIKE ?
    )";

    $search_value = "%" . $search . "%";

    $params[] = $search_value;
    $params[] = $search_value;
    $params[] = $search_value;

    $types .= "sss";
}

if ($role_filter !== '') {

    $where[] = "a.admin_role = ?";

    $params[] = $role_filter;
    $types .= "s";
}

if ($status_filter !== '') {

    $where[] = "a.status = ?";

    $params[] = $status_filter;
    $types .= "s";
}

$where_sql = implode(" AND ", $where);


/* =====================================================
   TOTAL FILTERED RECORDS
===================================================== */

$count_sql = "
    SELECT COUNT(*) AS total
    FROM Admins a
    WHERE $where_sql
";

$count_stmt = $conn->prepare($count_sql);

if (!empty($params)) {
    $count_stmt->bind_param($types, ...$params);
}

$count_stmt->execute();

$total_records = (int)$count_stmt
    ->get_result()
    ->fetch_assoc()['total'];

$count_stmt->close();

$total_pages = max(
    1,
    (int)ceil($total_records / $per_page)
);


/* =====================================================
   FETCH ADMINS
===================================================== */

$sql = "
    SELECT
        a.admin_id,
        a.full_name,
        a.email,
        a.email_verified_at,
        a.admin_role,
        a.status,
        a.created_at,

        p.profile_picture,
        p.designation,
        p.department

    FROM Admins a

    LEFT JOIN Admin_Profiles p
        ON p.admin_id = a.admin_id
       AND p.deleted_at IS NULL

    WHERE $where_sql

    ORDER BY a.created_at DESC

    LIMIT ? OFFSET ?
";

$stmt = $conn->prepare($sql);

$query_types = $types . "ii";

$query_params = $params;
$query_params[] = $per_page;
$query_params[] = $offset;

$stmt->bind_param(
    $query_types,
    ...$query_params
);

$stmt->execute();

$admins = $stmt->get_result();


/* =====================================================
   ESCAPE OUTPUT
===================================================== */

function admin_escape($value)
{
    return htmlspecialchars(
        (string)($value ?? ''),
        ENT_QUOTES,
        'UTF-8'
    );
}

?>

<?php include 'includes/admin_header.php'; ?>

<div class="admin-page">

    <!-- PAGE HEADER -->

    <div class="admin-page-header">

        <div>
            
        </div>

        <a href="add-admin" class="add-admin-button">
            <i class="fa-solid fa-user-plus"></i>
            Add Admin
        </a>

    </div>


    <!-- SUMMARY CARDS -->

    <div class="admin-summary-grid">

        <div class="admin-summary-card">
            <div class="summary-icon total">
                <i class="fa-solid fa-users-gear"></i>
            </div>

            <div>
                <span>Total Admins</span>

                <h3>
                    <?= (int)$summary['total'] ?>
                </h3>
            </div>
        </div>


        <div class="admin-summary-card">
            <div class="summary-icon active">
                <i class="fa-solid fa-user-check"></i>
            </div>

            <div>
                <span>Active Admins</span>

                <h3>
                    <?= (int)$summary['active'] ?>
                </h3>
            </div>
        </div>


        <div class="admin-summary-card">
            <div class="summary-icon suspended">
                <i class="fa-solid fa-user-clock"></i>
            </div>

            <div>
                <span>Suspended Admins</span>

                <h3>
                    <?= (int)$summary['suspended'] ?>
                </h3>
            </div>
        </div>


        <div class="admin-summary-card">
            <div class="summary-icon banned">
                <i class="fa-solid fa-user-slash"></i>
            </div>

            <div>
                <span>Banned Admins</span>

                <h3>
                    <?= (int)$summary['banned'] ?>
                </h3>
            </div>
        </div>

    </div>


    <!-- SEARCH AND FILTERS -->

    <div class="admin-table-card">

        <form
            method="GET"
            class="admin-filter-form"
        >

            <div class="admin-search-box">

                <i class="fa-solid fa-magnifying-glass"></i>

                <input
                    type="text"
                    name="search"
                    placeholder="Search name, email, or ID..."
                    value="<?= admin_escape($search) ?>"
                >

            </div>


            <select name="role">

                <option value="">
                    All Roles
                </option>

                <option
                    value="Main Admin"
                    <?= $role_filter === 'Main Admin' ? 'selected' : '' ?>
                >
                    Main Admin
                </option>

                <option
                    value="Sub Admin"
                    <?= $role_filter === 'Sub Admin' ? 'selected' : '' ?>
                >
                    Sub Admin
                </option>

            </select>


            <select name="status">

                <option value="">
                    All Statuses
                </option>

                <option
                    value="Active"
                    <?= $status_filter === 'Active' ? 'selected' : '' ?>
                >
                    Active
                </option>

                <option
                    value="Suspended"
                    <?= $status_filter === 'Suspended' ? 'selected' : '' ?>
                >
                    Suspended
                </option>

                <option
                    value="Banned"
                    <?= $status_filter === 'Banned' ? 'selected' : '' ?>
                >
                    Banned
                </option>

            </select>


            <button
                type="submit"
                class="admin-filter-button"
            >
                <i class="fa-solid fa-filter"></i>
                Apply
            </button>


            <a
                href="admins.php"
                class="admin-reset-button"
            >
                Reset
            </a>

        </form>


        <!-- ADMIN TABLE -->

        <div class="admin-table-wrapper">

            <table class="admin-management-table">

                <thead>

                    <tr>
                        <th>Admin</th>
                        <th>Role</th>
                        <th>Email Verification</th>
                        <th>Status</th>
                        <th>Date Joined</th>
                        <th>Actions</th>
                    </tr>

                </thead>

                <tbody>

                    <?php if ($admins->num_rows > 0): ?>

                        <?php while ($admin = $admins->fetch_assoc()): ?>

                            <tr>

                                <!-- ADMIN -->

                                <td>

                                    <div class="admin-user-cell">

                                        <?php if (!empty($admin['profile_picture'])): ?>

                                            <img
                                                src="../uploads/admin_profiles/<?= admin_escape($admin['profile_picture']) ?>"
                                                alt="Admin profile"
                                                class="admin-avatar"
                                            >

                                        <?php else: ?>

                                            <div class="admin-avatar-placeholder">
                                                <i class="fa-solid fa-user"></i>
                                            </div>

                                        <?php endif; ?>


                                        <div class="admin-user-info">

                                            <strong>
                                                <?= admin_escape($admin['full_name']) ?>
                                            </strong>

                                            <span>
                                                <?= admin_escape($admin['email']) ?>
                                            </span>

                                            <small>
                                                ID:
                                                <?= (int)$admin['admin_id'] ?>
                                            </small>

                                        </div>

                                    </div>

                                </td>


                                <!-- ROLE -->

                                <td>

                                    <span class="admin-role-badge
                                        <?= $admin['admin_role'] === 'Main Admin'
                                            ? 'main-role'
                                            : 'sub-role' ?>">

                                        <?= admin_escape($admin['admin_role']) ?>

                                    </span>

                                </td>


                                <!-- EMAIL VERIFICATION -->

                                <td>

                                    <?php if (!empty($admin['email_verified_at'])): ?>

                                        <span class="verification-status verified">
                                            <i class="fa-solid fa-circle-check"></i>
                                            Verified
                                        </span>

                                    <?php else: ?>

                                        <span class="verification-status unverified">
                                            <i class="fa-solid fa-circle-exclamation"></i>
                                            Unverified
                                        </span>

                                    <?php endif; ?>

                                </td>


                                <!-- ACCOUNT STATUS -->

                                <td>

                                    <span class="admin-status-badge
                                        <?= strtolower($admin['status']) ?>">

                                        <?= admin_escape($admin['status']) ?>

                                    </span>

                                </td>


                                <!-- DATE JOINED -->

                                <td>

                                    <?= date(
                                        'd M Y',
                                        strtotime($admin['created_at'])
                                    ) ?>

                                </td>


                                <!-- ACTIONS -->

                                <td>

                                    <div class="admin-row-actions">

                                        <a
                                            href="admin-details.php?id=<?= (int)$admin['admin_id'] ?>"
                                            class="admin-action-icon view"
                                            title="View Details"
                                        >
                                            <i class="fa-solid fa-eye"></i>
                                        </a>


                                        <?php if ($admin['admin_role'] === 'Sub Admin'): ?>

                                            <a
                                                href="manage-admin.php?id=<?= (int)$admin['admin_id'] ?>"
                                                class="admin-action-icon manage"
                                                title="Manage Admin"
                                            >
                                                <i class="fa-solid fa-user-gear"></i>
                                            </a>

                                        <?php endif; ?>

                                    </div>

                                </td>

                            </tr>

                        <?php endwhile; ?>

                    <?php else: ?>

                        <tr>

                            <td
                                colspan="6"
                                class="admin-empty-state"
                            >

                                <i class="fa-solid fa-users"></i>

                                <h3>No admins found</h3>

                                <p>
                                    Try changing your search or filters.
                                </p>

                            </td>

                        </tr>

                    <?php endif; ?>

                </tbody>

            </table>

        </div>


        <!-- PAGINATION -->

        <?php if ($total_pages > 1): ?>

            <div class="admin-pagination">

                <span>
                    Showing
                    <?= $total_records > 0 ? $offset + 1 : 0 ?>
                    -
                    <?= min($offset + $per_page, $total_records) ?>
                    of
                    <?= $total_records ?>
                </span>


                <div class="pagination-buttons">

                    <?php if ($page > 1): ?>

                        <a
                            href="?<?= http_build_query([
                                'search' => $search,
                                'role' => $role_filter,
                                'status' => $status_filter,
                                'page' => $page - 1
                            ]) ?>"
                        >
                            <i class="fa-solid fa-chevron-left"></i>
                        </a>

                    <?php endif; ?>


                    <span class="current-page">
                        <?= $page ?> / <?= $total_pages ?>
                    </span>


                    <?php if ($page < $total_pages): ?>

                        <a
                            href="?<?= http_build_query([
                                'search' => $search,
                                'role' => $role_filter,
                                'status' => $status_filter,
                                'page' => $page + 1
                            ]) ?>"
                        >
                            <i class="fa-solid fa-chevron-right"></i>
                        </a>

                    <?php endif; ?>

                </div>

            </div>

        <?php endif; ?>

    </div>

</div>

<?php include 'includes/admin_footer.php'; ?>

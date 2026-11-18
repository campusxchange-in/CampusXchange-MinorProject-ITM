<?php

$page_title = 'Users | CampusXchange';
$page_heading = 'Users';
$page_description = 'Manage registered students and faculty members.';

$active_page = 'users';
$page_css = 'users.css';


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
   BREADCRUMB
===================================================== */

$breadcrumbs = [
    [
        'label' => 'Users'
    ]
];


/* =====================================================
   FILTER VALUES
===================================================== */

$search = trim($_GET['search'] ?? '');

$role = $_GET['role'] ?? 'All';

$status = $_GET['status'] ?? 'All';

$verification = $_GET['verification'] ?? 'All';


/* =====================================================
   VALID FILTER VALUES
===================================================== */

$allowed_roles = [
    'All',
    'Student',
    'Faculty'
];


$allowed_statuses = [
    'All',
    'Pending',
    'Active',
    'Suspended',
    'Banned'
];


$allowed_verification = [
    'All',
    'Not Submitted',
    'Pending',
    'Approved',
    'Rejected'
];


if (!in_array($role, $allowed_roles, true)) {

    $role = 'All';
}


if (!in_array($status, $allowed_statuses, true)) {

    $status = 'All';
}


if (!in_array($verification, $allowed_verification, true)) {

    $verification = 'All';
}


/* =====================================================
   PAGINATION
===================================================== */

$per_page = 10;

$page = isset($_GET['page'])
    ? (int) $_GET['page']
    : 1;


if ($page < 1) {

    $page = 1;
}


/* =====================================================
   DASHBOARD STATISTICS
===================================================== */


/* -----------------------------------------------------
   TOTAL USERS
----------------------------------------------------- */

$sql = "
    SELECT COUNT(*) AS total
    FROM Users
    WHERE deleted_at IS NULL
";


$result = $conn->query($sql);

$total_users = $result
    ? (int) $result->fetch_assoc()['total']
    : 0;


/* -----------------------------------------------------
   ACTIVE USERS
----------------------------------------------------- */

$sql = "
    SELECT COUNT(*) AS total
    FROM Users
    WHERE deleted_at IS NULL
      AND status = 'Active'
";


$result = $conn->query($sql);

$active_users = $result
    ? (int) $result->fetch_assoc()['total']
    : 0;


/* -----------------------------------------------------
   PENDING USERS
----------------------------------------------------- */

$sql = "
    SELECT COUNT(*) AS total
    FROM Users
    WHERE deleted_at IS NULL
      AND status = 'Pending'
";


$result = $conn->query($sql);

$pending_users = $result
    ? (int) $result->fetch_assoc()['total']
    : 0;


/* -----------------------------------------------------
   SUSPENDED USERS
----------------------------------------------------- */

$sql = "
    SELECT COUNT(*) AS total
    FROM Users
    WHERE deleted_at IS NULL
      AND status = 'Suspended'
";


$result = $conn->query($sql);

$suspended_users = $result
    ? (int) $result->fetch_assoc()['total']
    : 0;


/* -----------------------------------------------------
   BANNED USERS
----------------------------------------------------- */

$sql = "
    SELECT COUNT(*) AS total
    FROM Users
    WHERE deleted_at IS NULL
      AND status = 'Banned'
";


$result = $conn->query($sql);

$banned_users = $result
    ? (int) $result->fetch_assoc()['total']
    : 0;


/* -----------------------------------------------------
   STUDENTS
----------------------------------------------------- */

$sql = "
    SELECT COUNT(*) AS total
    FROM Users
    WHERE deleted_at IS NULL
      AND role = 'Student'
";


$result = $conn->query($sql);

$total_students = $result
    ? (int) $result->fetch_assoc()['total']
    : 0;


/* -----------------------------------------------------
   FACULTY
----------------------------------------------------- */

$sql = "
    SELECT COUNT(*) AS total
    FROM Users
    WHERE deleted_at IS NULL
      AND role = 'Faculty'
";


$result = $conn->query($sql);

$total_faculty = $result
    ? (int) $result->fetch_assoc()['total']
    : 0;


/* =====================================================
   BUILD USER QUERY
===================================================== */

$where = [
    "u.deleted_at IS NULL"
];


$params = [];

$types = "";


/* -----------------------------------------------------
   SEARCH
----------------------------------------------------- */

if ($search !== '') {

    $where[] = "
        (
            u.full_name LIKE ?
            OR u.email LIKE ?
            OR u.phone LIKE ?
            OR uv.id_number LIKE ?
        )
    ";

    $search_value = '%' . $search . '%';

    $params[] = $search_value;
    $params[] = $search_value;
    $params[] = $search_value;
    $params[] = $search_value;

    $types .= "ssss";
}


/* -----------------------------------------------------
   ROLE FILTER
----------------------------------------------------- */

if ($role !== 'All') {

    $where[] = "u.role = ?";

    $params[] = $role;

    $types .= "s";
}


/* -----------------------------------------------------
   STATUS FILTER
----------------------------------------------------- */

if ($status !== 'All') {

    $where[] = "u.status = ?";

    $params[] = $status;

    $types .= "s";
}


/* -----------------------------------------------------
   VERIFICATION FILTER
----------------------------------------------------- */

if ($verification !== 'All') {

    if ($verification === 'Not Submitted') {

        $where[] = "
            (
                uv.verification_status IS NULL
                OR uv.verification_status = 'Not Submitted'
            )
        ";

    } else {

        $where[] = "uv.verification_status = ?";

        $params[] = $verification;

        $types .= "s";
    }
}


$where_sql = implode(" AND ", $where);


/* =====================================================
   COUNT FILTERED USERS
===================================================== */

$count_sql = "
    SELECT COUNT(*) AS total

    FROM Users u

    LEFT JOIN User_Verification uv
        ON u.user_id = uv.user_id

    WHERE $where_sql
";


$stmt = $conn->prepare($count_sql);


if (!$stmt) {

    die(
        "Failed to prepare user count query: " .
        htmlspecialchars($conn->error)
    );
}


if (!empty($params)) {

    $stmt->bind_param(
        $types,
        ...$params
    );
}


if (!$stmt->execute()) {

    die(
        "Failed to execute user count query: " .
        htmlspecialchars($stmt->error)
    );
}


$count_result = $stmt->get_result();

$total_filtered_users =
    (int) $count_result->fetch_assoc()['total'];


$stmt->close();


/* =====================================================
   CALCULATE PAGINATION
===================================================== */

$total_pages = max(
    1,
    (int) ceil(
        $total_filtered_users / $per_page
    )
);


if ($page > $total_pages) {

    $page = $total_pages;
}


$offset = ($page - 1) * $per_page;


/* =====================================================
   FETCH USERS
===================================================== */

$sql = "
    SELECT

        u.user_id,
        u.full_name,
        u.email,
        u.phone,
        u.role,
        u.status,
        u.email_verified_at,
        u.created_at,

        up.profile_picture,
        up.department,
        up.course,
        up.year_of_study,

        uv.id_type,
        uv.id_number,
        uv.verification_status,
        uv.verified_at,

        (
            SELECT COUNT(*)
            FROM Reports r
            WHERE r.reported_user_id = u.user_id
            AND r.report_type = 'User'
            AND r.status IN ('Pending', 'Under Review')
            AND r.deleted_at IS NULL
        ) AS report_count

    FROM Users u

    LEFT JOIN User_Profiles up
        ON u.user_id = up.user_id

    LEFT JOIN User_Verification uv
        ON u.user_id = uv.user_id

    WHERE $where_sql

    ORDER BY u.created_at DESC

    LIMIT ? OFFSET ?
";


$list_params = $params;

$list_types = $types . "ii";

$list_params[] = $per_page;
$list_params[] = $offset;


$stmt = $conn->prepare($sql);


if (!$stmt) {

    die(
        "Failed to prepare users query: " .
        htmlspecialchars($conn->error)
    );
}


$stmt->bind_param(
    $list_types,
    ...$list_params
);


if (!$stmt->execute()) {

    die(
        "Failed to execute users query: " .
        htmlspecialchars($stmt->error)
    );
}


$result = $stmt->get_result();

$users = [];


while ($row = $result->fetch_assoc()) {

    $users[] = $row;
}


$stmt->close();


/* =====================================================
   PAGINATION URL
===================================================== */

function build_page_url($page_number)
{
    $query = $_GET;

    $query['page'] = $page_number;

    return 'users?' . http_build_query($query);
}


include 'includes/admin_header.php';

?>


<div class="users-page">



    <!-- =================================================
         STATISTICS
    ================================================== -->

    <div class="users-stats-grid">


        <!-- TOTAL -->

        <div class="user-stat-card">

            <div class="user-stat-icon">

                <i class="fa-solid fa-users"></i>

            </div>

            <div>

                <span>
                    Total Users
                </span>

                <strong>
                    <?= number_format($total_users) ?>
                </strong>

            </div>

        </div>



        <!-- ACTIVE -->

        <div class="user-stat-card">

            <div class="user-stat-icon">

                <i class="fa-solid fa-user-check"></i>

            </div>

            <div>

                <span>
                    Active
                </span>

                <strong>
                    <?= number_format($active_users) ?>
                </strong>

            </div>

        </div>



        <!-- PENDING -->

        <div class="user-stat-card">

            <div class="user-stat-icon">

                <i class="fa-solid fa-user-clock"></i>

            </div>

            <div>

                <span>
                    Pending
                </span>

                <strong>
                    <?= number_format($pending_users) ?>
                </strong>

            </div>

        </div>



        <!-- SUSPENDED -->

        <div class="user-stat-card">

            <div class="user-stat-icon">

                <i class="fa-solid fa-user-lock"></i>

            </div>

            <div>

                <span>
                    Suspended
                </span>

                <strong>
                    <?= number_format($suspended_users) ?>
                </strong>

            </div>

        </div>



        <!-- BANNED -->

        <div class="user-stat-card">

            <div class="user-stat-icon">

                <i class="fa-solid fa-user-slash"></i>

            </div>

            <div>

                <span>
                    Banned
                </span>

                <strong>
                    <?= number_format($banned_users) ?>
                </strong>

            </div>

        </div>



        <!-- STUDENTS -->

        <div class="user-stat-card">

            <div class="user-stat-icon">

                <i class="fa-solid fa-graduation-cap"></i>

            </div>

            <div>

                <span>
                    Students
                </span>

                <strong>
                    <?= number_format($total_students) ?>
                </strong>

            </div>

        </div>



        <!-- FACULTY -->

        <div class="user-stat-card">

            <div class="user-stat-icon">

                <i class="fa-solid fa-chalkboard-user"></i>

            </div>

            <div>

                <span>
                    Faculty
                </span>

                <strong>
                    <?= number_format($total_faculty) ?>
                </strong>

            </div>

        </div>


    </div>



    <!-- =================================================
         USER MANAGEMENT CARD
    ================================================== -->

    <div class="users-card">


        <!-- =================================================
             FILTERS
        ================================================== -->

        <div class="users-toolbar">


            <form
                method="GET"
                action="users"
                class="users-filter-form"
            >


                <!-- SEARCH -->

                <div class="users-search">

                    <i class="fa-solid fa-magnifying-glass"></i>

                    <input
                        type="search"
                        name="search"
                        value="<?= htmlspecialchars($search) ?>"
                        placeholder="Search users..."
                    >

                </div>



                <!-- ROLE -->

                <div class="users-filter">

                    <select name="role">

                        <option value="All"
                            <?= $role === 'All' ? 'selected' : '' ?>
                        >
                            All Roles
                        </option>

                        <option value="Student"
                            <?= $role === 'Student' ? 'selected' : '' ?>
                        >
                            Student
                        </option>

                        <option value="Faculty"
                            <?= $role === 'Faculty' ? 'selected' : '' ?>
                        >
                            Faculty
                        </option>

                    </select>

                </div>



                <!-- STATUS -->

                <div class="users-filter">

                    <select name="status">

                        <option value="All"
                            <?= $status === 'All' ? 'selected' : '' ?>
                        >
                            All Status
                        </option>

                        <option value="Pending"
                            <?= $status === 'Pending' ? 'selected' : '' ?>
                        >
                            Pending
                        </option>

                        <option value="Active"
                            <?= $status === 'Active' ? 'selected' : '' ?>
                        >
                            Active
                        </option>

                        <option value="Suspended"
                            <?= $status === 'Suspended' ? 'selected' : '' ?>
                        >
                            Suspended
                        </option>

                        <option value="Banned"
                            <?= $status === 'Banned' ? 'selected' : '' ?>
                        >
                            Banned
                        </option>

                    </select>

                </div>



                <!-- VERIFICATION -->

                <div class="users-filter">

                    <select name="verification">

                        <option value="All"
                            <?= $verification === 'All' ? 'selected' : '' ?>
                        >
                            All Verification
                        </option>

                        <option value="Not Submitted"
                            <?= $verification === 'Not Submitted' ? 'selected' : '' ?>
                        >
                            Not Submitted
                        </option>

                        <option value="Pending"
                            <?= $verification === 'Pending' ? 'selected' : '' ?>
                        >
                            Pending
                        </option>

                        <option value="Approved"
                            <?= $verification === 'Approved' ? 'selected' : '' ?>
                        >
                            Approved
                        </option>

                        <option value="Rejected"
                            <?= $verification === 'Rejected' ? 'selected' : '' ?>
                        >
                            Rejected
                        </option>

                    </select>

                </div>



                <!-- FILTER BUTTON -->

                <button
                    type="submit"
                    class="btn-primary"
                >

                    <i class="fa-solid fa-filter"></i>

                    Filter

                </button>



                <!-- CLEAR -->

                <?php if (
                    $search !== '' ||
                    $role !== 'All' ||
                    $status !== 'All' ||
                    $verification !== 'All'
                ): ?>

                    <a
                        href="users"
                        class="btn-secondary"
                    >

                        <i class="fa-solid fa-xmark"></i>

                        Clear

                    </a>

                <?php endif; ?>


            </form>


        </div>



        <!-- =================================================
             TABLE HEADER
        ================================================== -->

        <div class="users-table-header">

            <div>

                <h3>
                    Registered Users
                </h3>

                <p>

                    <?php if ($total_filtered_users > 0): ?>

                        Showing
                        <?= number_format($offset + 1) ?>
                        -
                        <?= number_format(
                            min(
                                $offset + $per_page,
                                $total_filtered_users
                            )
                        ) ?>

                        of

                        <?= number_format(
                            $total_filtered_users
                        ) ?>

                    <?php else: ?>

                        No users found.

                    <?php endif; ?>

                </p>

            </div>

        </div>



        <!-- =================================================
             USERS TABLE
        ================================================== -->

        <div class="users-table-wrapper">

            <table class="users-table">

                <thead>

                    <tr>

                        <th>
                            User
                        </th>

                        <th>
                            Role
                        </th>

                        <th>
                            Verification
                        </th>

                        <th>
                            Status
                        </th>

                        <th>
                            Reports
                        </th>

                        <th>
                            Joined
                        </th>

                        <th>
                            Actions
                        </th>

                    </tr>

                </thead>


                <tbody>


                <?php if (!empty($users)): ?>


                    <?php foreach ($users as $user): ?>


                        <?php

                        $full_name =
                            $user['full_name'] ?? 'Unknown User';

                        $initial =
                            strtoupper(
                                substr(
                                    trim($full_name),
                                    0,
                                    1
                                )
                            );

                        $profile_picture =
                            $user['profile_picture'] ?? '';

                        $user_role =
                            $user['role'] ?? 'Not Assigned';

                        $user_status =
                            $user['status'] ?? 'Pending';

                        $verification_status =
                            $user['verification_status']
                            ?? 'Not Submitted';

                        ?>


                        <tr>


                            <!-- USER -->

                            <td>

                                <div class="user-table-profile">


                                    <?php if (
                                        !empty($profile_picture)
                                    ): ?>

                                        <img
                                            src="../uploads/user_profiles/<?= htmlspecialchars($profile_picture) ?>"
                                            alt="<?= htmlspecialchars($full_name) ?>"
                                            class="user-avatar"
                                        >

                                    <?php else: ?>

                                        <div class="user-avatar-placeholder">

                                            <?= htmlspecialchars($initial) ?>

                                        </div>

                                    <?php endif; ?>


                                    <div class="user-info">

                                        <strong>

                                            <?= htmlspecialchars(
                                                $full_name
                                            ) ?>

                                        </strong>

                                        <span>

                                            <?= htmlspecialchars(
                                                $user['email']
                                            ) ?>

                                        </span>

                                    </div>


                                </div>

                            </td>



                            <!-- ROLE -->

                            <td>

                                <?php if (
                                    $user_role === 'Student'
                                ): ?>

                                    <span class="role-badge role-student">

                                        <i class="fa-solid fa-graduation-cap"></i>

                                        Student

                                    </span>

                                <?php elseif (
                                    $user_role === 'Faculty'
                                ): ?>

                                    <span class="role-badge role-faculty">

                                        <i class="fa-solid fa-chalkboard-user"></i>

                                        Faculty

                                    </span>

                                <?php else: ?>

                                    <span class="role-badge role-pending">

                                        Not Assigned

                                    </span>

                                <?php endif; ?>

                            </td>



                            <!-- VERIFICATION -->

                            <td>

                                <?php if (
                                    $verification_status === 'Approved'
                                ): ?>

                                    <span class="verification-badge verification-approved">

                                        <i class="fa-solid fa-circle-check"></i>

                                        Approved

                                    </span>

                                <?php elseif (
                                    $verification_status === 'Pending'
                                ): ?>

                                    <span class="verification-badge verification-pending">

                                        <i class="fa-solid fa-clock"></i>

                                        Pending

                                    </span>

                                <?php elseif (
                                    $verification_status === 'Rejected'
                                ): ?>

                                    <span class="verification-badge verification-rejected">

                                        <i class="fa-solid fa-circle-xmark"></i>

                                        Rejected

                                    </span>

                                <?php else: ?>

                                    <span class="verification-badge verification-none">

                                        Not Submitted

                                    </span>

                                <?php endif; ?>

                            </td>



                            <!-- STATUS -->

                            <td>

                                <?php if (
                                    $user_status === 'Active'
                                ): ?>

                                    <span class="status-badge status-active">

                                        <i class="fa-solid fa-circle"></i>

                                        Active

                                    </span>

                                <?php elseif (
                                    $user_status === 'Pending'
                                ): ?>

                                    <span class="status-badge status-pending">

                                        <i class="fa-solid fa-circle"></i>

                                        Pending

                                    </span>

                                <?php elseif (
                                    $user_status === 'Suspended'
                                ): ?>

                                    <span class="status-badge status-suspended">

                                        <i class="fa-solid fa-circle"></i>

                                        Suspended

                                    </span>

                                <?php elseif (
                                    $user_status === 'Banned'
                                ): ?>

                                    <span class="status-badge status-banned">

                                        <i class="fa-solid fa-circle"></i>

                                        Banned

                                    </span>

                                <?php endif; ?>

                            </td>


                            <!-- REPORTS -->

                            <td>

                                <?php
                                $report_count = (int) ($user['report_count'] ?? 0);
                                ?>

                                <?php if ($report_count > 0): ?>

                                    <a
                                        href="reports?user_id=<?= (int) $user['user_id'] ?>"
                                        class="user-report-link user-report-active"
                                        title="View open reports"
                                    >

                                        <i class="fa-solid fa-flag"></i>

                                        <?= $report_count ?>

                                        <?= $report_count === 1 ? 'Open Report' : 'Open Reports' ?>

                                    </a>

                                <?php else: ?>

                                    <span class="user-report-none">

                                        <i class="fa-solid fa-circle-check"></i>

                                        No Open Reports

                                    </span>

                                <?php endif; ?>

                            </td>


                            <!-- JOINED -->

                            <td>

                                <span class="user-date">

                                    <?= !empty($user['created_at'])
                                        ? date(
                                            'd M Y',
                                            strtotime(
                                                $user['created_at']
                                            )
                                        )
                                        : '—'
                                    ?>

                                </span>

                            </td>



                            <!-- ACTIONS -->

                            <td>

                                <div class="user-actions">


                                    <a
                                        href="user_view?id=<?= (int) $user['user_id'] ?>"
                                        class="user-action-btn"
                                        title="View User"
                                    >

                                        <i class="fa-solid fa-eye"></i>

                                    </a>


                                    <a
                                        href="user_manage?id=<?= (int) $user['user_id'] ?>"
                                        class="user-action-btn"
                                        title="Manage User"
                                    >

                                        <i class="fa-solid fa-gear"></i>

                                    </a>


                                </div>

                            </td>


                        </tr>


                    <?php endforeach; ?>


                <?php else: ?>


                    <tr>

                        <td
                            colspan="6"
                            class="users-empty-state"
                        >

                            <div>

                                <i class="fa-solid fa-users-slash"></i>

                                <h3>
                                    No Users Found
                                </h3>

                                <p>
                                    No users match the selected
                                    search and filter criteria.
                                </p>

                            </div>

                        </td>

                    </tr>


                <?php endif; ?>


                </tbody>

            </table>

        </div>



        <!-- =================================================
             PAGINATION
        ================================================== -->

        <?php if ($total_pages > 1): ?>

            <div class="users-pagination">


                <!-- PREVIOUS -->

                <?php if ($page > 1): ?>

                    <a
                        href="<?= htmlspecialchars(
                            build_page_url($page - 1)
                        ) ?>"
                        class="pagination-btn"
                    >

                        <i class="fa-solid fa-chevron-left"></i>

                    </a>

                <?php else: ?>

                    <span class="pagination-btn disabled">

                        <i class="fa-solid fa-chevron-left"></i>

                    </span>

                <?php endif; ?>



                <!-- PAGE NUMBERS -->

                <div class="pagination-pages">


                    <?php

                    $start_page =
                        max(1, $page - 2);

                    $end_page =
                        min(
                            $total_pages,
                            $page + 2
                        );

                    ?>


                    <?php if ($start_page > 1): ?>

                        <a
                            href="<?= htmlspecialchars(
                                build_page_url(1)
                            ) ?>"
                            class="pagination-page"
                        >
                            1
                        </a>

                        <?php if ($start_page > 2): ?>

                            <span class="pagination-dots">
                                ...
                            </span>

                        <?php endif; ?>

                    <?php endif; ?>


                    <?php for (
                        $i = $start_page;
                        $i <= $end_page;
                        $i++
                    ): ?>

                        <?php if ($i === $page): ?>

                            <span class="pagination-page active">

                                <?= $i ?>

                            </span>

                        <?php else: ?>

                            <a
                                href="<?= htmlspecialchars(
                                    build_page_url($i)
                                ) ?>"
                                class="pagination-page"
                            >

                                <?= $i ?>

                            </a>

                        <?php endif; ?>

                    <?php endfor; ?>


                    <?php if (
                        $end_page < $total_pages
                    ): ?>

                        <?php if (
                            $end_page < $total_pages - 1
                        ): ?>

                            <span class="pagination-dots">
                                ...
                            </span>

                        <?php endif; ?>


                        <a
                            href="<?= htmlspecialchars(
                                build_page_url($total_pages)
                            ) ?>"
                            class="pagination-page"
                        >

                            <?= $total_pages ?>

                        </a>

                    <?php endif; ?>


                </div>



                <!-- NEXT -->

                <?php if ($page < $total_pages): ?>

                    <a
                        href="<?= htmlspecialchars(
                            build_page_url($page + 1)
                        ) ?>"
                        class="pagination-btn"
                    >

                        <i class="fa-solid fa-chevron-right"></i>

                    </a>

                <?php else: ?>

                    <span class="pagination-btn disabled">

                        <i class="fa-solid fa-chevron-right"></i>

                    </span>

                <?php endif; ?>


            </div>

        <?php endif; ?>


    </div>


</div>


<?php include 'includes/admin_footer.php'; ?>
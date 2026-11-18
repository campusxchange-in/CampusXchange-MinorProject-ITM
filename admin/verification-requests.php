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

$page_title = 'Verification Requests | CampusXchange';
$page_heading = 'Verification Requests';
$page_description = 'Review and manage user identity verification requests.';
$active_page = 'verification-requests';
$page_css = 'verification-requests.css';


/* =====================================================
   FILTERS
===================================================== */

$search = trim($_GET['search'] ?? '');
$status = $_GET['status'] ?? '';
$role = $_GET['role'] ?? '';


$allowed_statuses = [
    'Not Submitted',
    'Pending',
    'Approved',
    'Rejected'
];

$allowed_roles = [
    'Student',
    'Faculty'
];

if (!in_array($status, $allowed_statuses, true)) {
    $status = '';
}

if (!in_array($role, $allowed_roles, true)) {
    $role = '';
}


/* =====================================================
   WHERE CONDITIONS
===================================================== */

$where = [
    "u.deleted_at IS NULL",
    "uv.deleted_at IS NULL"
];

$params = [];
$types = '';


/* =====================================================
   SEARCH
===================================================== */

if ($search !== '') {

    $where[] = "(
        u.full_name LIKE ?
        OR u.email LIKE ?
        OR uv.id_number LIKE ?
    )";

    $search_value = '%' . $search . '%';

    $params[] = $search_value;
    $params[] = $search_value;
    $params[] = $search_value;

    $types .= 'sss';
}


/* =====================================================
   STATUS FILTER
===================================================== */

if ($status !== '') {

    $where[] = "uv.verification_status = ?";

    $params[] = $status;
    $types .= 's';
}


/* =====================================================
   ROLE FILTER
===================================================== */

if ($role !== '') {

    $where[] = "u.role = ?";

    $params[] = $role;
    $types .= 's';
}


/* =====================================================
   FETCH VERIFICATION REQUESTS
===================================================== */

$sql = "
    SELECT
        uv.verification_id,
        uv.user_id,
        uv.id_type,
        uv.id_number,
        uv.college_id_front_image,
        uv.college_id_back_image,
        uv.verification_status,
        uv.verified_at,
        uv.rejection_reason,
        uv.created_at,
        uv.updated_at,
        uv.submitted_at,

        u.full_name,
        u.email,
        u.role,
        u.status AS user_status

    FROM User_Verification uv

    INNER JOIN Users u
        ON u.user_id = uv.user_id

    WHERE " . implode(' AND ', $where) . "

    ORDER BY
        CASE uv.verification_status
            WHEN 'Pending' THEN 1
            WHEN 'Rejected' THEN 2
            WHEN 'Approved' THEN 3
            WHEN 'Not Submitted' THEN 4
            ELSE 5
        END,
        uv.created_at DESC
";


$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("Database error: " . $conn->error);
}


if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}


$stmt->execute();

$result = $stmt->get_result();


/* =====================================================
   STATISTICS
===================================================== */

$stats_sql = "
    SELECT
        COUNT(*) AS total,
        SUM(CASE WHEN uv.verification_status = 'Pending' THEN 1 ELSE 0 END) AS pending,
        SUM(CASE WHEN uv.verification_status = 'Approved' THEN 1 ELSE 0 END) AS approved,
        SUM(CASE WHEN uv.verification_status = 'Rejected' THEN 1 ELSE 0 END) AS rejected

    FROM User_Verification uv

    INNER JOIN Users u
        ON u.user_id = uv.user_id

    WHERE
        u.deleted_at IS NULL
        AND uv.deleted_at IS NULL
";

$stats_result = $conn->query($stats_sql);

$stats = $stats_result
    ? $stats_result->fetch_assoc()
    : [
        'total' => 0,
        'pending' => 0,
        'approved' => 0,
        'rejected' => 0
    ];


/* =====================================================
   BREADCRUMBS
===================================================== */

$breadcrumbs = [
    [
        'label' => 'Verification Requests'
    ]
];


/* =====================================================
   HEADER
===================================================== */

include 'includes/admin_header.php';

?>

<div class="verification-page">




    <!-- =================================================
         STATISTICS
    ================================================== -->

    <div class="verification-stats">

        <div class="stat-card">

            <div class="stat-icon total">
                <i class="fa-solid fa-id-card"></i>
            </div>

            <div class="stat-content">
                <span class="stat-label">Total Requests</span>
                <strong><?= (int) $stats['total']; ?></strong>
            </div>

        </div>


        <div class="stat-card">

            <div class="stat-icon pending">
                <i class="fa-solid fa-clock"></i>
            </div>

            <div class="stat-content">
                <span class="stat-label">Pending</span>
                <strong><?= (int) $stats['pending']; ?></strong>
            </div>

        </div>


        <div class="stat-card">

            <div class="stat-icon approved">
                <i class="fa-solid fa-circle-check"></i>
            </div>

            <div class="stat-content">
                <span class="stat-label">Approved</span>
                <strong><?= (int) $stats['approved']; ?></strong>
            </div>

        </div>


        <div class="stat-card">

            <div class="stat-icon rejected">
                <i class="fa-solid fa-circle-xmark"></i>
            </div>

            <div class="stat-content">
                <span class="stat-label">Rejected</span>
                <strong><?= (int) $stats['rejected']; ?></strong>
            </div>

        </div>

    </div>


    <!-- =================================================
         FILTERS
    ================================================== -->

    <div class="filter-card">

        <form method="GET" class="verification-filters">

            <div class="filter-group search-group">

                <label for="search">Search</label>

                <div class="search-input">

                    <i class="fa-solid fa-magnifying-glass"></i>

                    <input
                        type="text"
                        id="search"
                        name="search"
                        value="<?= htmlspecialchars($search); ?>"
                        placeholder="Name, email or ID number"
                    >

                </div>

            </div>


            <div class="filter-group">

                <label for="status">Status</label>

                <select id="status" name="status">

                    <option value="">All Statuses</option>

                    <?php foreach ($allowed_statuses as $status_option): ?>

                        <option
                            value="<?= htmlspecialchars($status_option); ?>"
                            <?= $status === $status_option ? 'selected' : ''; ?>
                        >
                            <?= htmlspecialchars($status_option); ?>
                        </option>

                    <?php endforeach; ?>

                </select>

            </div>


            <div class="filter-group">

                <label for="role">Role</label>

                <select id="role" name="role">

                    <option value="">All Roles</option>

                    <?php foreach ($allowed_roles as $role_option): ?>

                        <option
                            value="<?= htmlspecialchars($role_option); ?>"
                            <?= $role === $role_option ? 'selected' : ''; ?>
                        >
                            <?= htmlspecialchars($role_option); ?>
                        </option>

                    <?php endforeach; ?>

                </select>

            </div>


            <div class="filter-actions">

                <button type="submit" class="btn btn-primary">
                    <i class="fa-solid fa-filter"></i>
                    Apply
                </button>

                <a href="verification-requests.php" class="btn btn-secondary">
                    <i class="fa-solid fa-rotate-left"></i>
                    Reset
                </a>

            </div>

        </form>

    </div>


    <!-- =================================================
         VERIFICATION TABLE
    ================================================== -->

    <div class="table-card">

        <div class="table-header">

            <div>
                <h2>Verification Requests</h2>

                <p>
                    Review submitted identity verification documents.
                </p>
            </div>

        </div>


        <div class="table-responsive">

            <table class="verification-table">

                <thead>

                    <tr>

                        <th>Request</th>

                        <th>User</th>

                        <th>Role</th>

                        <th>ID Details</th>

                        <th>Status</th>

                        <th>Submitted</th>

                        <th>Action</th>

                    </tr>

                </thead>


                <tbody>

                <?php if ($result->num_rows > 0): ?>

                    <?php while ($verification = $result->fetch_assoc()): ?>

                        <tr>

                            <!-- Request -->

                            <td>

                                <div class="request-id">
                                    #<?= (int) $verification['verification_id']; ?>
                                </div>

                            </td>


                            <!-- User -->

                            <td>

                                <div class="user-cell">

                                    <div class="user-avatar">
                                        <?= strtoupper(substr($verification['full_name'], 0, 1)); ?>
                                    </div>

                                    <div class="user-info">

                                        <strong>
                                            <?= htmlspecialchars($verification['full_name']); ?>
                                        </strong>

                                        <span>
                                            <?= htmlspecialchars($verification['email']); ?>
                                        </span>

                                    </div>

                                </div>

                            </td>


                            <!-- Role -->

                            <td>

                                <span class="role-badge <?= strtolower($verification['role']); ?>">
                                    <?= htmlspecialchars($verification['role']); ?>
                                </span>

                            </td>


                            <!-- ID Details -->

                            <td>

                                <div class="id-details">

                                    <strong>
                                        <?= htmlspecialchars($verification['id_type'] ?? '—'); ?>
                                    </strong>

                                    <span>
                                        <?= htmlspecialchars($verification['id_number'] ?? '—'); ?>
                                    </span>

                                </div>

                            </td>


                            <!-- Status -->

                            <td>

                                <?php

                                $verification_status = $verification['verification_status'];

                                $status_class = match ($verification_status) {
                                    'Pending' => 'pending',
                                    'Approved' => 'approved',
                                    'Rejected' => 'rejected',
                                    default => 'not-submitted'
                                };

                                ?>

                                <span class="status-badge <?= $status_class; ?>">

                                    <?php if ($verification_status === 'Pending'): ?>

                                        <i class="fa-solid fa-clock"></i>

                                    <?php elseif ($verification_status === 'Approved'): ?>

                                        <i class="fa-solid fa-circle-check"></i>

                                    <?php elseif ($verification_status === 'Rejected'): ?>

                                        <i class="fa-solid fa-circle-xmark"></i>

                                    <?php else: ?>

                                        <i class="fa-solid fa-minus-circle"></i>

                                    <?php endif; ?>

                                    <?= htmlspecialchars($verification_status); ?>

                                </span>

                            </td>


                            <!-- Submitted -->

                            <td>

                                <div class="date-cell">

                                    <strong>
                                        <?= date('d M Y', strtotime($verification['submitted_at'])); ?>
                                    </strong>

                                    <span>
                                        <?= date('h:i A', strtotime($verification['submitted_at'])); ?>
                                    </span>

                                </div>

                            </td>


                            <!-- Action -->

                            <td>

                                <a
                                    href="verification-details.php?id=<?= (int) $verification['verification_id']; ?>"
                                    class="action-btn"
                                    title="View Verification"
                                >
                                    <i class="fa-solid fa-eye"></i>
                                    View
                                </a>

                            </td>

                        </tr>

                    <?php endwhile; ?>

                <?php else: ?>

                    <tr>

                        <td colspan="7">

                            <div class="empty-state">

                                <div class="empty-icon">
                                    <i class="fa-solid fa-id-card"></i>
                                </div>

                                <h3>No verification requests found</h3>

                                <p>
                                    No verification records match your current filters.
                                </p>

                            </div>

                        </td>

                    </tr>

                <?php endif; ?>

                </tbody>

            </table>

        </div>

    </div>

</div>


<?php

$stmt->close();

include 'includes/admin_footer.php';

?>
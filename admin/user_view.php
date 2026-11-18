<?php

/* =====================================================
   USER VIEW
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

$page_title = 'User Details | CampusXchange';
$page_heading = 'User Details';
$page_description = 'View detailed information about a registered user.';

$active_page = 'users';
$page_css = 'user_view.css';


/* =====================================================
   USER ID
===================================================== */

$user_id = filter_input(
    INPUT_GET,
    'id',
    FILTER_VALIDATE_INT
);

if (!$user_id || $user_id <= 0) {

    header("Location: users.php");

    exit;
}


/* =====================================================
   FETCH USER
===================================================== */

$user = null;

$sql = "
    SELECT
        u.user_id,
        u.full_name,
        u.email,
        u.pending_email,
        u.phone AS account_phone,
        u.role,
        u.email_verified_at,
        u.status,
        u.created_at,
        u.updated_at,

        up.profile_picture,
        up.phone AS profile_phone,
        up.department,
        up.course,
        up.year_of_study,
        up.semester,
        up.bio,
        up.location,
        up.profile_completion_percentage,
        up.profile_status,

        uv.id_type,
        uv.id_number,
        uv.college_id_front_image,
        uv.college_id_back_image,
        uv.verification_status,
        uv.verified_by,
        uv.verified_at,
        uv.rejection_reason,

        pres.last_seen_at

    FROM Users u

    LEFT JOIN User_Profiles up
        ON u.user_id = up.user_id
        AND u.deleted_at IS NULL

    LEFT JOIN User_Verification uv
        ON u.user_id = uv.user_id
        AND uv.deleted_at IS NULL

    LEFT JOIN User_Presence pres
        ON u.user_id = pres.user_id

    WHERE u.user_id = ?
      AND u.deleted_at IS NULL

    LIMIT 1
";


$stmt = $conn->prepare($sql);

if (!$stmt) {

    die("Database error: " . htmlspecialchars($conn->error));
}

$stmt->bind_param("i", $user_id);

$stmt->execute();

$result = $stmt->get_result();

$user = $result->fetch_assoc();

$stmt->close();


/* =====================================================
   USER NOT FOUND
===================================================== */

if (!$user) {

    header("Location: users.php");

    exit;
}


/* =====================================================
   FETCH VERIFIER ADMIN
===================================================== */

$verifier_name = null;

if (!empty($user['verified_by'])) {

    $sql = "
        SELECT full_name
        FROM Admins
        WHERE admin_id = ?
        LIMIT 1
    ";

    $stmt = $conn->prepare($sql);

    if ($stmt) {

        $stmt->bind_param(
            "i",
            $user['verified_by']
        );

        $stmt->execute();

        $result = $stmt->get_result();

        $verifier = $result->fetch_assoc();

        $verifier_name = $verifier['full_name'] ?? null;

        $stmt->close();
    }
}


/* =====================================================
   PRODUCT STATISTICS
===================================================== */

$product_stats = [
    'total' => 0,
    'active' => 0,
    'sold' => 0,
    'removed' => 0
];

$sql = "
    SELECT
        COUNT(*) AS total,
        SUM(status = 'Active') AS active,
        SUM(status = 'Sold') AS sold,
        SUM(status = 'Removed') AS removed
    FROM Products
    WHERE seller_id = ?
      AND deleted_at IS NULL
";

$stmt = $conn->prepare($sql);

if ($stmt) {

    $stmt->bind_param("i", $user_id);

    $stmt->execute();

    $result = $stmt->get_result();

    $product_stats = array_merge(
        $product_stats,
        $result->fetch_assoc() ?: []
    );

    $stmt->close();
}


/* =====================================================
   TRANSACTION STATISTICS
===================================================== */

$transaction_stats = [
    'selling' => 0,
    'buying' => 0,
    'completed' => 0,
    'cancelled' => 0,
    'disputed' => 0
];

$sql = "
    SELECT
        SUM(seller_id = ?) AS selling,
        SUM(buyer_id = ?) AS buying,
        SUM(transaction_status = 'Completed'
            AND (seller_id = ? OR buyer_id = ?)
        ) AS completed,
        SUM(transaction_status = 'Cancelled'
            AND (seller_id = ? OR buyer_id = ?)
        ) AS cancelled,
        SUM(transaction_status = 'Disputed'
            AND (seller_id = ? OR buyer_id = ?)
        ) AS disputed
    FROM Transactions
    WHERE (seller_id = ? OR buyer_id = ?)
      AND deleted_at IS NULL
";

$stmt = $conn->prepare($sql);

if ($stmt) {

    $stmt->bind_param(
        "iiiiiiiiii",
        $user_id,
        $user_id,
        $user_id,
        $user_id,
        $user_id,
        $user_id,
        $user_id,
        $user_id,
        $user_id,
        $user_id
    );

    $stmt->execute();

    $result = $stmt->get_result();

    $transaction_stats = array_merge(
        $transaction_stats,
        $result->fetch_assoc() ?: []
    );

    $stmt->close();
}


/* =====================================================
   REVIEW STATISTICS
===================================================== */

$review_stats = [
    'product_reviews' => 0,
    'seller_reviews' => 0,
    'average_seller_rating' => null
];

$sql = "
    SELECT
        (
            SELECT COUNT(*)
            FROM Product_Reviews
            WHERE reviewer_id = ?
              AND deleted_at IS NULL
        ) AS product_reviews,

        (
            SELECT COUNT(*)
            FROM Seller_Reviews
            WHERE reviewer_id = ?
              AND deleted_at IS NULL
        ) AS seller_reviews,

        (
            SELECT AVG(rating)
            FROM Seller_Reviews
            WHERE seller_id = ?
              AND status = 'Visible'
              AND deleted_at IS NULL
        ) AS average_seller_rating
";

$stmt = $conn->prepare($sql);

if ($stmt) {

    $stmt->bind_param(
        "iii",
        $user_id,
        $user_id,
        $user_id
    );

    $stmt->execute();

    $result = $stmt->get_result();

    $review_stats = array_merge(
        $review_stats,
        $result->fetch_assoc() ?: []
    );

    $stmt->close();
}


/* =====================================================
   REPORT STATISTICS
===================================================== */

$report_stats = [
    'submitted' => 0,
    'against' => 0,
    'pending_against' => 0
];

$sql = "
    SELECT
        (
            SELECT COUNT(*)
            FROM Reports
            WHERE reporter_id = ?
              AND deleted_at IS NULL
        ) AS submitted,

        (
            SELECT COUNT(*)
            FROM Reports
            WHERE reported_user_id = ?
              AND deleted_at IS NULL
        ) AS against,

        (
            SELECT COUNT(*)
            FROM Reports
            WHERE reported_user_id = ?
              AND status IN ('Pending', 'Under Review')
              AND deleted_at IS NULL
        ) AS pending_against
";

$stmt = $conn->prepare($sql);

if ($stmt) {

    $stmt->bind_param(
        "iii",
        $user_id,
        $user_id,
        $user_id
    );

    $stmt->execute();

    $result = $stmt->get_result();

    $report_stats = array_merge(
        $report_stats,
        $result->fetch_assoc() ?: []
    );

    $stmt->close();
}


/* =====================================================
   ACTIVE BAN
===================================================== */

$active_ban = null;

$sql = "
    SELECT
        ub.ban_id,
        ub.ban_type,
        ub.reason,
        ub.description,
        ub.start_date,
        ub.end_date,
        ub.status,
        a.full_name AS admin_name

    FROM User_Bans ub

    LEFT JOIN Admins a
        ON ub.admin_id = a.admin_id

    WHERE ub.user_id = ?
      AND ub.status = 'Active'
      AND ub.deleted_at IS NULL

    ORDER BY ub.created_at DESC

    LIMIT 1
";

$stmt = $conn->prepare($sql);

if ($stmt) {

    $stmt->bind_param("i", $user_id);

    $stmt->execute();

    $result = $stmt->get_result();

    $active_ban = $result->fetch_assoc();

    $stmt->close();
}


/* =====================================================
   USER PRODUCTS
===================================================== */

$products = [];

$sql = "
    SELECT
        p.product_id,
        p.title,
        p.price,
        p.product_condition,
        p.status,
        p.created_at,
        c.category_name,

        (
            SELECT pi.image_path
            FROM Product_Images pi
            WHERE pi.product_id = p.product_id
              AND pi.deleted_at IS NULL
            ORDER BY pi.is_primary DESC, pi.image_id ASC
            LIMIT 1
        ) AS product_image

    FROM Products p

    LEFT JOIN Categories c
        ON p.category_id = c.category_id
        AND c.deleted_at IS NULL

    WHERE p.seller_id = ?
      AND p.deleted_at IS NULL

    ORDER BY p.created_at DESC

    LIMIT 10
";

$stmt = $conn->prepare($sql);

if ($stmt) {

    $stmt->bind_param("i", $user_id);

    $stmt->execute();

    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {

        $products[] = $row;
    }

    $stmt->close();
}


/* =====================================================
   TRANSACTIONS
===================================================== */

$transactions = [];

$sql = "
    SELECT
        t.transaction_id,
        t.product_id,
        t.seller_id,
        t.buyer_id,
        t.selling_price,
        t.transaction_status,
        t.transaction_date,

        p.title,

        seller.full_name AS seller_name,
        buyer.full_name AS buyer_name

    FROM Transactions t

    LEFT JOIN Products p
        ON t.product_id = p.product_id

    LEFT JOIN Users seller
        ON t.seller_id = seller.user_id

    LEFT JOIN Users buyer
        ON t.buyer_id = buyer.user_id

    WHERE (t.seller_id = ? OR t.buyer_id = ?)
      AND t.deleted_at IS NULL

    ORDER BY t.transaction_date DESC

    LIMIT 10
";

$stmt = $conn->prepare($sql);

if ($stmt) {

    $stmt->bind_param(
        "ii",
        $user_id,
        $user_id
    );

    $stmt->execute();

    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {

        $transactions[] = $row;
    }

    $stmt->close();
}


/* =====================================================
   SECURITY ACTIVITY
===================================================== */

$security_activity = [];

$sql = "
    SELECT
        activity_id,
        activity_type,
        title,
        description,
        created_at

    FROM Login_Security_Activity

    WHERE user_id = ?

    ORDER BY created_at DESC

    LIMIT 10
";

$stmt = $conn->prepare($sql);

if ($stmt) {

    $stmt->bind_param("i", $user_id);

    $stmt->execute();

    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {

        $security_activity[] = $row;
    }

    $stmt->close();
}


/* =====================================================
   BAN HISTORY
===================================================== */

$ban_history = [];

$sql = "
    SELECT
        ub.ban_id,
        ub.ban_type,
        ub.reason,
        ub.start_date,
        ub.end_date,
        ub.status,
        ub.revoked_at,
        ub.revocation_reason,
        a.full_name AS admin_name

    FROM User_Bans ub

    LEFT JOIN Admins a
        ON ub.admin_id = a.admin_id

    WHERE ub.user_id = ?
      AND ub.deleted_at IS NULL

    ORDER BY ub.created_at DESC

    LIMIT 10
";

$stmt = $conn->prepare($sql);

if ($stmt) {

    $stmt->bind_param("i", $user_id);

    $stmt->execute();

    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {

        $ban_history[] = $row;
    }

    $stmt->close();
}


/* =====================================================
   HELPERS
===================================================== */

function e($value)
{
    return htmlspecialchars(
        (string)($value ?? ''),
        ENT_QUOTES,
        'UTF-8'
    );
}


function format_date($date)
{
    if (empty($date)) {
        return '—';
    }

    return date(
        'd M Y, h:i A',
        strtotime($date)
    );
}


function status_class($status)
{
    return strtolower(
        str_replace(
            ' ',
            '-',
            (string)$status
        )
    );
}


function money($amount)
{
    if ($amount === null || $amount === '') {
        return '—';
    }

    return '₹' . number_format(
        (float)$amount,
        2
    );
}


$profile_phone =
    $user['profile_phone']
    ?: $user['account_phone'];

$profile_picture =
    $user['profile_picture']
    ?? '';

$initial =
    strtoupper(
        substr(
            trim($user['full_name']),
            0,
            1
        )
    );

$verification_status =
    $user['verification_status']
    ?: 'Not Submitted';

$average_rating =
    $review_stats['average_seller_rating'] !== null
        ? number_format(
            (float)$review_stats['average_seller_rating'],
            1
        )
        : '—';


/* =====================================================
   BREADCRUMB
===================================================== */

$breadcrumbs = [
    [
        'label' => 'Users',
        'url' => 'users.php'
    ],
    [
        'label' => $user['full_name']
    ]
];


include 'includes/admin_header.php';

?>


<div class="user-view-page">


    <!-- =================================================
         BACK BUTTON
    ================================================== -->

    <div class="user-view-topbar">

        <a
            href="users.php"
            class="user-back-btn"
        >
            <i class="fas fa-arrow-left"></i>

            <span>Back to Users</span>
        </a>

    </div>


    <!-- =================================================
         USER HERO
    ================================================== -->

    <div class="user-hero-card">


        <div class="user-hero-left">

            <?php if (!empty($profile_picture)): ?>

                <img
                    src="../uploads/user_profiles/<?= e($profile_picture) ?>"
                    alt="<?= e($user['full_name']) ?>"
                    class="user-view-avatar"
                >

            <?php else: ?>

                <div class="user-view-avatar-placeholder">
                    <?= e($initial) ?>
                </div>

            <?php endif; ?>


            <div class="user-hero-info">

                <div class="user-name-row">

                    <h2>
                        <?= e($user['full_name']) ?>
                    </h2>

                    <span class="status-badge status-<?= e(status_class($user['status'])) ?>">

                        <i class="fas fa-circle"></i>

                        <?= e($user['status']) ?>

                    </span>

                </div>


                <p class="user-email">

                    <i class="fas fa-envelope"></i>

                    <?= e($user['email']) ?>

                </p>


                <div class="user-meta-row">

                    <?php if (!empty($user['role'])): ?>

                        <span>
                            <i class="fas fa-user-tag"></i>
                            <?= e($user['role']) ?>
                        </span>

                    <?php endif; ?>


                    <span>
                        <i class="fas fa-calendar-alt"></i>
                        Joined <?= format_date($user['created_at']) ?>
                    </span>


                    <?php if (!empty($user['location'])): ?>

                        <span>
                            <i class="fas fa-map-marker-alt"></i>
                            <?= e($user['location']) ?>
                        </span>

                    <?php endif; ?>

                </div>

            </div>

        </div>


        <div class="user-hero-actions">

            <a
                href="user_manage.php?id=<?= (int)$user['user_id'] ?>"
                class="user-manage-btn"
            >
                <i class="fas fa-user-cog"></i>
                Manage User
            </a>

        </div>

    </div>


    <!-- =================================================
         ACTIVE BAN NOTICE
    ================================================== -->

    <?php if ($active_ban): ?>

        <div class="user-ban-alert">

            <div class="user-ban-alert-icon">

                <i class="fas fa-ban"></i>

            </div>

            <div class="user-ban-alert-content">

                <strong>
                    Active <?= e($active_ban['ban_type']) ?> Ban
                </strong>

                <p>
                    <?= e($active_ban['reason']) ?>
                </p>

                <small>

                    Issued by
                    <?= e($active_ban['admin_name'] ?: 'Administrator') ?>

                    on
                    <?= format_date($active_ban['start_date']) ?>

                    <?php if (!empty($active_ban['end_date'])): ?>

                        · Ends
                        <?= format_date($active_ban['end_date']) ?>

                    <?php endif; ?>

                </small>

            </div>

        </div>

    <?php endif; ?>


    <!-- =================================================
         SUMMARY STATISTICS
    ================================================== -->

    <div class="user-summary-grid">


        <div class="user-summary-card">

            <div class="user-summary-icon">
                <i class="fas fa-box"></i>
            </div>

            <div>
                <span>Products</span>
                <strong>
                    <?= (int)$product_stats['total'] ?>
                </strong>
            </div>

        </div>


        <div class="user-summary-card">

            <div class="user-summary-icon">
                <i class="fas fa-exchange-alt"></i>
            </div>

            <div>
                <span>Transactions</span>
                <strong>
                    <?= (int)$transaction_stats['completed'] ?>
                </strong>
            </div>

        </div>


        <div class="user-summary-card">

            <div class="user-summary-icon">
                <i class="fas fa-star"></i>
            </div>

            <div>
                <span>Seller Rating</span>
                <strong>
                    <?= e($average_rating) ?>
                </strong>
            </div>

        </div>


        <div class="user-summary-card">

            <div class="user-summary-icon">
                <i class="fas fa-flag"></i>
            </div>

            <div>
                <span>Reports Against</span>
                <strong>
                    <?= (int)$report_stats['against'] ?>
                </strong>
            </div>

        </div>

    </div>


    <!-- =================================================
         MAIN GRID
    ================================================== -->

    <div class="user-view-grid">


        <!-- =============================================
             ACCOUNT INFORMATION
        ============================================== -->

        <section class="user-detail-card">

            <div class="user-detail-card-header">

                <div>

                    <h3>
                        Account Information
                    </h3>

                    <p>
                        Basic account details
                    </p>

                </div>

                <i class="fas fa-user"></i>

            </div>


            <div class="user-detail-list">


                <div class="user-detail-row">

                    <span>User ID</span>

                    <strong>
                        #<?= (int)$user['user_id'] ?>
                    </strong>

                </div>


                <div class="user-detail-row">

                    <span>Full Name</span>

                    <strong>
                        <?= e($user['full_name']) ?>
                    </strong>

                </div>


                <div class="user-detail-row">

                    <span>Email</span>

                    <strong>
                        <?= e($user['email']) ?>
                    </strong>

                </div>


                <?php if (!empty($user['pending_email'])): ?>

                    <div class="user-detail-row">

                        <span>Pending Email</span>

                        <strong>
                            <?= e($user['pending_email']) ?>
                        </strong>

                    </div>

                <?php endif; ?>


                <div class="user-detail-row">

                    <span>Phone</span>

                    <strong>
                        <?= e($profile_phone ?: '—') ?>
                    </strong>

                </div>


                <div class="user-detail-row">

                    <span>Role</span>

                    <strong>
                        <?= e($user['role'] ?: '—') ?>
                    </strong>

                </div>


                <div class="user-detail-row">

                    <span>Email Verification</span>

                    <?php if (!empty($user['email_verified_at'])): ?>

                        <span class="detail-status success">
                            <i class="fas fa-check-circle"></i>
                            Verified
                        </span>

                    <?php else: ?>

                        <span class="detail-status warning">
                            <i class="fas fa-clock"></i>
                            Not Verified
                        </span>

                    <?php endif; ?>

                </div>


                <div class="user-detail-row">

                    <span>Account Created</span>

                    <strong>
                        <?= format_date($user['created_at']) ?>
                    </strong>

                </div>


                <div class="user-detail-row">

                    <span>Last Updated</span>

                    <strong>
                        <?= format_date($user['updated_at']) ?>
                    </strong>

                </div>

            </div>

        </section>


        <!-- =============================================
             PROFILE INFORMATION
        ============================================== -->

        <section class="user-detail-card">

            <div class="user-detail-card-header">

                <div>

                    <h3>
                        Profile Information
                    </h3>

                    <p>
                        Additional profile details
                    </p>

                </div>

                <i class="fas fa-id-card"></i>

            </div>


            <div class="user-detail-list">


                <div class="user-detail-row">

                    <span>Department</span>

                    <strong>
                        <?= e($user['department'] ?: '—') ?>
                    </strong>

                </div>


                <div class="user-detail-row">

                    <span>Course</span>

                    <strong>
                        <?= e($user['course'] ?: '—') ?>
                    </strong>

                </div>


                <div class="user-detail-row">

                    <span>Year of Study</span>

                    <strong>
                        <?= e($user['year_of_study'] ?: '—') ?>
                    </strong>

                </div>


                <div class="user-detail-row">

                    <span>Semester</span>

                    <strong>
                        <?= e($user['semester'] ?: '—') ?>
                    </strong>

                </div>


                <div class="user-detail-row">

                    <span>Location</span>

                    <strong>
                        <?= e($user['location'] ?: '—') ?>
                    </strong>

                </div>


                <div class="user-detail-row">

                    <span>Profile Status</span>

                    <strong>
                        <?= e($user['profile_status'] ?: 'Incomplete') ?>
                    </strong>

                </div>


                <div class="user-detail-row">

                    <span>Profile Completion</span>

                    <div class="profile-completion">

                        <div class="profile-progress">

                            <div
                                class="profile-progress-bar"
                                style="width: <?= min(100, max(0, (int)$user['profile_completion_percentage'])) ?>%;"
                            ></div>

                        </div>

                        <span>
                            <?= (int)$user['profile_completion_percentage'] ?>%
                        </span>

                    </div>

                </div>


                <?php if (!empty($user['bio'])): ?>

                    <div class="user-detail-row user-bio-row">

                        <span>Bio</span>

                        <p>
                            <?= nl2br(e($user['bio'])) ?>
                        </p>

                    </div>

                <?php endif; ?>

            </div>

        </section>


        <!-- =============================================
             VERIFICATION
        ============================================== -->

        <section class="user-detail-card">

            <div class="user-detail-card-header">

                <div>

                    <h3>
                        Verification
                    </h3>

                    <p>
                        Identity verification details
                    </p>

                </div>

                <i class="fas fa-shield-alt"></i>

            </div>


            <div class="verification-status-large">

                <span class="verification-badge verification-<?= e(status_class($verification_status)) ?>">

                    <?php if ($verification_status === 'Approved'): ?>

                        <i class="fas fa-check-circle"></i>

                    <?php elseif ($verification_status === 'Pending'): ?>

                        <i class="fas fa-clock"></i>

                    <?php elseif ($verification_status === 'Rejected'): ?>

                        <i class="fas fa-times-circle"></i>

                    <?php else: ?>

                        <i class="fas fa-minus-circle"></i>

                    <?php endif; ?>

                    <?= e($verification_status) ?>

                </span>

            </div>


            <div class="user-detail-list">


                <div class="user-detail-row">

                    <span>ID Type</span>

                    <strong>
                        <?= e($user['id_type'] ?: '—') ?>
                    </strong>

                </div>


                <div class="user-detail-row">

                    <span>ID Number</span>

                    <strong>
                        <?= e($user['id_number'] ?: '—') ?>
                    </strong>

                </div>


                <?php if (!empty($user['verified_at'])): ?>

                    <div class="user-detail-row">

                        <span>Verified At</span>

                        <strong>
                            <?= format_date($user['verified_at']) ?>
                        </strong>

                    </div>

                <?php endif; ?>


                <?php if (!empty($verifier_name)): ?>

                    <div class="user-detail-row">

                        <span>Verified By</span>

                        <strong>
                            <?= e($verifier_name) ?>
                        </strong>

                    </div>

                <?php endif; ?>


                <?php if (!empty($user['rejection_reason'])): ?>

                    <div class="verification-rejection">

                        <strong>
                            Rejection Reason
                        </strong>

                        <p>
                            <?= e($user['rejection_reason']) ?>
                        </p>

                    </div>

                <?php endif; ?>


                <!-- =============================================
                    ID CARD FRONT
                ============================================== -->

                <div class="verification-document-row">

                    <strong>
                        ID Card Front
                    </strong>

                    <?php if (!empty($user['college_id_front_image'])): ?>

                        <a
                            href="../uploads/verification/<?= e(
                                ltrim($user['college_id_front_image'], '/\\')
                            ) ?>"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="verification-document-btn"
                        >

                            <i class="fas fa-file-image"></i>

                            View Front

                        </a>

                    <?php else: ?>

                        <span>
                            Not uploaded
                        </span>

                    <?php endif; ?>

                </div>


                <!-- =============================================
                    ID CARD BACK
                ============================================== -->

                <div class="verification-document-row">

                    <strong>
                        ID Card Back
                    </strong>

                    <?php if (!empty($user['college_id_back_image'])): ?>

                        <a
                            href="../uploads/verification/<?= e(
                                ltrim($user['college_id_back_image'], '/\\')
                            ) ?>"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="verification-document-btn"
                        >

                            <i class="fas fa-file-image"></i>

                            View Back

                        </a>

                    <?php else: ?>

                        <span>
                            Not uploaded
                        </span>

                    <?php endif; ?>

                </div>

            </div>

        </section>


        <!-- =============================================
             PRESENCE
        ============================================== -->

        <section class="user-detail-card">

            <div class="user-detail-card-header">

                <div>

                    <h3>
                        Activity Status
                    </h3>

                    <p>
                        Recent account activity
                    </p>

                </div>

                <i class="fas fa-clock"></i>

            </div>


            <div class="presence-box">

                <?php if (!empty($user['last_seen_at'])): ?>

                    <div class="presence-indicator"></div>

                    <div>

                        <strong>
                            Last Seen
                        </strong>

                        <span>
                            <?= format_date($user['last_seen_at']) ?>
                        </span>

                    </div>

                <?php else: ?>

                    <div class="presence-indicator offline"></div>

                    <div>

                        <strong>
                            No Activity Recorded
                        </strong>

                        <span>
                            Last seen information is unavailable.
                        </span>

                    </div>

                <?php endif; ?>

            </div>


            <div class="activity-summary">

                <div>

                    <span>Products</span>

                    <strong>
                        <?= (int)$product_stats['total'] ?>
                    </strong>

                </div>

                <div>

                    <span>Reviews Given</span>

                    <strong>
                        <?= (int)$review_stats['product_reviews'] + (int)$review_stats['seller_reviews'] ?>
                    </strong>

                </div>

                <div>

                    <span>Reports Submitted</span>

                    <strong>
                        <?= (int)$report_stats['submitted'] ?>
                    </strong>

                </div>

            </div>

        </section>

    </div>


    <!-- =================================================
         PRODUCTS
    ================================================== -->

    <section class="user-detail-card full-width-card">

        <div class="user-detail-card-header">

            <div>

                <h3>
                    Products
                </h3>

                <p>
                    Latest products listed by this user
                </p>

            </div>

            <span class="section-count">
                <?= (int)$product_stats['total'] ?>
            </span>

        </div>


        <?php if (!empty($products)): ?>

            <div class="user-products-table-wrapper">

                <table class="user-data-table">

                    <thead>

                        <tr>

                            <th>Product</th>

                            <th>Category</th>

                            <th>Price</th>

                            <th>Condition</th>

                            <th>Status</th>

                            <th>Listed</th>

                        </tr>

                    </thead>

                    <tbody>

                        <?php foreach ($products as $product): ?>

                            <tr>

                                <td>

                                    <div class="product-table-cell">

                                        <?php if (!empty($product['product_image'])): ?>

                                            <img
                                                src="../uploads/products/<?= e($product['product_image']) ?>"
                                                alt="<?= e($product['title'] ?: 'Product') ?>"
                                            >

                                        <?php else: ?>

                                            <div class="product-image-placeholder">

                                                <i class="fas fa-box"></i>

                                            </div>

                                        <?php endif; ?>


                                        <div>

                                            <strong>
                                                <?= e($product['title'] ?: 'Untitled Product') ?>
                                            </strong>

                                            <span>
                                                #<?= (int)$product['product_id'] ?>
                                            </span>

                                        </div>

                                    </div>

                                </td>


                                <td>
                                    <?= e($product['category_name'] ?: '—') ?>
                                </td>


                                <td>
                                    <?= money($product['price']) ?>
                                </td>


                                <td>
                                    <?= e($product['product_condition'] ?: '—') ?>
                                </td>


                                <td>

                                    <span class="status-badge status-<?= e(status_class($product['status'])) ?>">

                                        <?= e($product['status']) ?>

                                    </span>

                                </td>


                                <td class="muted-cell">

                                    <?= format_date($product['created_at']) ?>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                    </tbody>

                </table>

            </div>

        <?php else: ?>

            <div class="user-empty-state">

                <i class="fas fa-box-open"></i>

                <strong>
                    No products found
                </strong>

                <span>
                    This user has not listed any products.
                </span>

            </div>

        <?php endif; ?>

    </section>


    <!-- =================================================
         TRANSACTIONS
    ================================================== -->

    <section class="user-detail-card full-width-card">

        <div class="user-detail-card-header">

            <div>

                <h3>
                    Transactions
                </h3>

                <p>
                    Recent buying and selling activity
                </p>

            </div>

            <span class="section-count">
                <?= (int)$transaction_stats['completed'] ?>
            </span>

        </div>


        <?php if (!empty($transactions)): ?>

            <div class="user-data-table-wrapper">

                <table class="user-data-table">

                    <thead>

                        <tr>

                            <th>Transaction</th>

                            <th>Product</th>

                            <th>Role</th>

                            <th>Other Party</th>

                            <th>Amount</th>

                            <th>Status</th>

                            <th>Date</th>

                        </tr>

                    </thead>

                    <tbody>

                        <?php foreach ($transactions as $transaction): ?>

                            <?php

                            $is_seller =
                                (int)$transaction['seller_id'] ===
                                (int)$user['user_id'];

                            $other_party =
                                $is_seller
                                    ? $transaction['buyer_name']
                                    : $transaction['seller_name'];

                            ?>

                            <tr>

                                <td>

                                    <strong>
                                        #<?= (int)$transaction['transaction_id'] ?>
                                    </strong>

                                </td>


                                <td>
                                    <?= e($transaction['title'] ?: 'Product') ?>
                                </td>


                                <td>

                                    <span class="transaction-role">

                                        <?= $is_seller ? 'Seller' : 'Buyer' ?>

                                    </span>

                                </td>


                                <td>
                                    <?= e($other_party ?: '—') ?>
                                </td>


                                <td>
                                    <?= money($transaction['selling_price']) ?>
                                </td>


                                <td>

                                    <span class="status-badge status-<?= e(status_class($transaction['transaction_status'])) ?>">

                                        <?= e($transaction['transaction_status']) ?>

                                    </span>

                                </td>


                                <td class="muted-cell">

                                    <?= format_date($transaction['transaction_date']) ?>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                    </tbody>

                </table>

            </div>

        <?php else: ?>

            <div class="user-empty-state">

                <i class="fas fa-exchange-alt"></i>

                <strong>
                    No transactions found
                </strong>

                <span>
                    This user has no recorded transactions.
                </span>

            </div>

        <?php endif; ?>

    </section>


    <!-- =================================================
         BAN HISTORY + SECURITY ACTIVITY
    ================================================== -->

    <div class="user-view-grid">


        <!-- =============================================
             BAN HISTORY
        ============================================== -->

        <section class="user-detail-card">

            <div class="user-detail-card-header">

                <div>

                    <h3>
                        Ban History
                    </h3>

                    <p>
                        Account restriction history
                    </p>

                </div>

                <i class="fas fa-gavel"></i>

            </div>


            <?php if (!empty($ban_history)): ?>

                <div class="user-timeline">

                    <?php foreach ($ban_history as $ban): ?>

                        <div class="user-timeline-item">

                            <div class="timeline-icon">

                                <i class="fas fa-ban"></i>

                            </div>


                            <div class="timeline-content">

                                <div class="timeline-title-row">

                                    <strong>
                                        <?= e($ban['ban_type']) ?> Ban
                                    </strong>

                                    <span class="status-badge status-<?= e(status_class($ban['status'])) ?>">

                                        <?= e($ban['status']) ?>

                                    </span>

                                </div>


                                <p>
                                    <?= e($ban['reason']) ?>
                                </p>


                                <small>

                                    <?= format_date($ban['start_date']) ?>

                                    · By
                                    <?= e($ban['admin_name'] ?: 'Administrator') ?>

                                </small>

                            </div>

                        </div>

                    <?php endforeach; ?>

                </div>

            <?php else: ?>

                <div class="user-empty-state compact">

                    <i class="fas fa-shield-check"></i>

                    <strong>
                        No ban history
                    </strong>

                    <span>
                        No bans have been recorded for this user.
                    </span>

                </div>

            <?php endif; ?>

        </section>


        <!-- =============================================
             SECURITY ACTIVITY
        ============================================== -->

        <section class="user-detail-card">

            <div class="user-detail-card-header">

                <div>

                    <h3>
                        Security Activity
                    </h3>

                    <p>
                        Recent login and security events
                    </p>

                </div>

                <i class="fas fa-lock"></i>

            </div>


            <?php if (!empty($security_activity)): ?>

                <div class="user-timeline">

                    <?php foreach ($security_activity as $activity): ?>

                        <div class="user-timeline-item">

                            <div class="timeline-icon">

                                <i class="fas fa-shield-alt"></i>

                            </div>


                            <div class="timeline-content">

                                <strong>
                                    <?= e($activity['title']) ?>
                                </strong>


                                <?php if (!empty($activity['description'])): ?>

                                    <p>
                                        <?= e($activity['description']) ?>
                                    </p>

                                <?php endif; ?>


                                <small>

                                    <?= e($activity['activity_type']) ?>

                                    ·

                                    <?= format_date($activity['created_at']) ?>

                                </small>

                            </div>

                        </div>

                    <?php endforeach; ?>

                </div>

            <?php else: ?>

                <div class="user-empty-state compact">

                    <i class="fas fa-shield-alt"></i>

                    <strong>
                        No security activity
                    </strong>

                    <span>
                        No security events have been recorded.
                    </span>

                </div>

            <?php endif; ?>

        </section>

    </div>


</div>


<?php

include 'includes/admin_footer.php';

?>
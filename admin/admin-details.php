
<?php

/* =====================================================
   AUTHENTICATION AND DATABASE
===================================================== */

include 'includes/auth_check.php';
include '../server/database/db_connect.php';
include 'includes/main_admin_check.php';


/* =====================================================
   PAGE SETTINGS
===================================================== */

$page_title = 'Admin Details | CampusXchange';
$page_heading = 'Admin Details';
$active_page = 'admins';
$page_css = 'admin-details.css';


/* =====================================================
   GET ADMIN ID
===================================================== */

$admin_id = (int)($_GET['id'] ?? 0);

if ($admin_id <= 0) {
    header("Location: admins.php");
    exit;
}


/* =====================================================
   FETCH ADMIN
===================================================== */

$stmt = $conn->prepare("
    SELECT
        a.admin_id,
        a.full_name,
        a.email,
        a.pending_email,
        a.email_verified_at,
        a.admin_role,
        a.status,
        a.created_at,
        a.updated_at,

        p.profile_picture,
        p.phone,
        p.designation,
        p.department,
        p.address,
        p.city,
        p.state,
        p.postal_code,
        p.bio

    FROM Admins a

    LEFT JOIN Admin_Profiles p
        ON p.admin_id = a.admin_id
       AND p.deleted_at IS NULL

    WHERE a.admin_id = ?
      AND a.deleted_at IS NULL

    LIMIT 1
");

$stmt->bind_param("i", $admin_id);
$stmt->execute();

$admin = $stmt->get_result()->fetch_assoc();

$stmt->close();


/* =====================================================
   VALIDATE
===================================================== */

if (!$admin) {
    header("Location: admins.php");
    exit;
}


function details_escape($value)
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

    <!-- BREADCRUMB -->

    <div class="admin-details-breadcrumb">

        <a href="admins.php">
            Admin Management
        </a>

        <i class="fa-solid fa-chevron-right"></i>

        <span>
            Admin Details
        </span>

    </div>


    <!-- PROFILE HEADER -->

    <div class="admin-details-header">

        <div class="admin-details-profile">

            <?php if (!empty($admin['profile_picture'])): ?>

                <img
                    src="../uploads/admin_profiles/<?= details_escape($admin['profile_picture']) ?>"
                    alt="Admin profile"
                    class="admin-details-avatar"
                >

            <?php else: ?>

                <div class="admin-details-avatar-placeholder">
                    <i class="fa-solid fa-user"></i>
                </div>

            <?php endif; ?>


            <div>

                <h2>
                    <?= details_escape($admin['full_name']) ?>
                </h2>

                <p>
                    <?= details_escape($admin['email']) ?>
                </p>

                <div class="admin-details-badges">

                    <span class="admin-role-badge">
                        <?= details_escape($admin['admin_role']) ?>
                    </span>

                    <span class="admin-status-badge
                        <?= strtolower($admin['status']) ?>">

                        <?= details_escape($admin['status']) ?>

                    </span>

                </div>

            </div>

        </div>


        <div class="admin-details-actions">

            <a
                href="admins.php"
                class="admin-details-back"
            >
                <i class="fa-solid fa-arrow-left"></i>
                Back
            </a>


            <?php if ($admin['admin_role'] === 'Sub Admin'): ?>

                <a
                    href="manage-admin.php?id=<?= (int)$admin['admin_id'] ?>"
                    class="admin-details-manage"
                >
                    <i class="fa-solid fa-user-gear"></i>
                    Manage Admin
                </a>

            <?php endif; ?>

        </div>

    </div>


    <!-- DETAILS GRID -->

    <div class="admin-details-grid">

        <!-- ACCOUNT INFORMATION -->

        <div class="admin-details-card">

            <div class="admin-details-card-header">

                <i class="fa-solid fa-user-shield"></i>

                <h3>Account Information</h3>

            </div>


            <div class="admin-details-info-row">

                <span>Admin ID</span>

                <strong>
                    #<?= (int)$admin['admin_id'] ?>
                </strong>

            </div>


            <div class="admin-details-info-row">

                <span>Full Name</span>

                <strong>
                    <?= details_escape($admin['full_name']) ?>
                </strong>

            </div>


            <div class="admin-details-info-row">

                <span>Email</span>

                <strong>
                    <?= details_escape($admin['email']) ?>
                </strong>

            </div>


            <div class="admin-details-info-row">

                <span>Email Verification</span>

                <?php if ($admin['email_verified_at']): ?>

                    <strong class="verified-text">
                        Verified
                    </strong>

                <?php else: ?>

                    <strong class="unverified-text">
                        Unverified
                    </strong>

                <?php endif; ?>

            </div>


            <?php if (!empty($admin['pending_email'])): ?>

                <div class="admin-details-info-row">

                    <span>Pending Email</span>

                    <strong>
                        <?= details_escape($admin['pending_email']) ?>
                    </strong>

                </div>

            <?php endif; ?>


            <div class="admin-details-info-row">

                <span>Role</span>

                <strong>
                    <?= details_escape($admin['admin_role']) ?>
                </strong>

            </div>


            <div class="admin-details-info-row">

                <span>Account Status</span>

                <strong>
                    <?= details_escape($admin['status']) ?>
                </strong>

            </div>


            <div class="admin-details-info-row">

                <span>Created At</span>

                <strong>
                    <?= date(
                        'd M Y, h:i A',
                        strtotime($admin['created_at'])
                    ) ?>
                </strong>

            </div>


            <div class="admin-details-info-row">

                <span>Last Updated</span>

                <strong>
                    <?= date(
                        'd M Y, h:i A',
                        strtotime($admin['updated_at'])
                    ) ?>
                </strong>

            </div>

        </div>


        <!-- PROFILE INFORMATION -->

        <div class="admin-details-card">

            <div class="admin-details-card-header">

                <i class="fa-solid fa-id-card"></i>

                <h3>Profile Information</h3>

            </div>


            <div class="admin-details-info-row">

                <span>Phone</span>

                <strong>
                    <?= details_escape($admin['phone'] ?: 'Not provided') ?>
                </strong>

            </div>


            <div class="admin-details-info-row">

                <span>Designation</span>

                <strong>
                    <?= details_escape($admin['designation'] ?: 'Not provided') ?>
                </strong>

            </div>


            <div class="admin-details-info-row">

                <span>Department</span>

                <strong>
                    <?= details_escape($admin['department'] ?: 'Not provided') ?>
                </strong>

            </div>


            <div class="admin-details-info-row">

                <span>City</span>

                <strong>
                    <?= details_escape($admin['city'] ?: 'Not provided') ?>
                </strong>

            </div>


            <div class="admin-details-info-row">

                <span>State</span>

                <strong>
                    <?= details_escape($admin['state'] ?: 'Not provided') ?>
                </strong>

            </div>


            <div class="admin-details-info-row">

                <span>Postal Code</span>

                <strong>
                    <?= details_escape($admin['postal_code'] ?: 'Not provided') ?>
                </strong>

            </div>


            <div class="admin-details-bio">

                <span>Bio</span>

                <p>
                    <?= nl2br(
                        details_escape(
                            $admin['bio'] ?: 'No bio provided.'
                        )
                    ) ?>
                </p>

            </div>

        </div>

    </div>

</div>

<?php include 'includes/admin_footer.php'; ?>

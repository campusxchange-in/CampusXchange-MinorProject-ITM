
<?php

/* =====================================================
   SIDEBAR BADGE COUNTS
===================================================== */

$sidebar_verification_count = 0;
$sidebar_reports_count = 0;
$sidebar_notifications_count = 0;

$sidebar_admin_id = (int)($_SESSION['admin_id'] ?? 0);


/* =====================================================
   PENDING VERIFICATION REQUESTS
===================================================== */

if (isset($conn) && $conn instanceof mysqli) {

    $verification_count_stmt = $conn->prepare("
        SELECT COUNT(*)
        FROM User_Verification
        WHERE verification_status = 'Pending'
          AND deleted_at IS NULL
    ");

    if ($verification_count_stmt) {

        $verification_count_stmt->execute();

        $verification_count_stmt->bind_result(
            $sidebar_verification_count
        );

        $verification_count_stmt->fetch();
        $verification_count_stmt->close();

    }


    /* =================================================
       PENDING REPORTS
    ================================================= */

    $reports_count_stmt = $conn->prepare("
        SELECT COUNT(*)
        FROM Reports
        WHERE status = 'Pending'
          AND deleted_at IS NULL
    ");

    if ($reports_count_stmt) {

        $reports_count_stmt->execute();

        $reports_count_stmt->bind_result(
            $sidebar_reports_count
        );

        $reports_count_stmt->fetch();
        $reports_count_stmt->close();

    }


    /* =================================================
       UNREAD ADMIN NOTIFICATIONS
    ================================================= */

    if ($sidebar_admin_id > 0) {

        $notifications_count_stmt = $conn->prepare("
            SELECT COUNT(*)
            FROM Notifications
            WHERE admin_id = ?
              AND user_id IS NULL
              AND is_read = FALSE
              AND deleted_at IS NULL
        ");

        if ($notifications_count_stmt) {

            $notifications_count_stmt->bind_param(
                'i',
                $sidebar_admin_id
            );

            $notifications_count_stmt->execute();

            $notifications_count_stmt->bind_result(
                $sidebar_notifications_count
            );

            $notifications_count_stmt->fetch();
            $notifications_count_stmt->close();

        }

    }

}


/* =====================================================
   FORMAT BADGE COUNTS
===================================================== */

$sidebar_verification_count =
    (int)$sidebar_verification_count;

$sidebar_reports_count =
    (int)$sidebar_reports_count;

$sidebar_notifications_count =
    (int)$sidebar_notifications_count;

?>


<aside class="admin-sidebar">

    <button
        type="button"
        class="sidebar-collapse-btn"
        id="sidebarCollapseBtn"
        title="Collapse sidebar"
    >
        <i class="fa-solid fa-chevron-left"></i>
    </button>

    <!-- =====================================================
         MAIN
    ====================================================== -->

    <div class="sidebar-section">

        <div class="sidebar-title">
            MAIN
        </div>


        <!-- DASHBOARD -->

        <a
            href="dashboard"
            class="sidebar-link <?= ($active_page === 'dashboard') ? 'active' : '' ?>"
        >

            <span class="sidebar-icon">
                <i class="fa-solid fa-gauge"></i>
            </span>

            <span>
                Dashboard
            </span>

        </a>

    </div>


    <!-- =====================================================
         MANAGEMENT
    ====================================================== -->

    <div class="sidebar-section">

        <div class="sidebar-title">
            MANAGEMENT
        </div>


        <!-- USERS -->

        <a
            href="users"
            class="sidebar-link <?= ($active_page === 'users') ? 'active' : '' ?>"
        >

            <span class="sidebar-icon">
                <i class="fa-solid fa-users"></i>
            </span>

            <span>
                Users
            </span>

        </a>

        <!-- USERS -->

        <a
            href="deleted_users"
            class="sidebar-link <?= ($active_page === 'deletedusers') ? 'active' : '' ?>"
        >

            <span class="sidebar-icon">
                <i class="fa-solid fa-trash"></i>
            </span>

            <span>
                Deleted Users
            </span>

        </a>


        <!-- PRODUCTS -->

        <a
            href="products"
            class="sidebar-link <?= ($active_page === 'products') ? 'active' : '' ?>"
        >

            <span class="sidebar-icon">
                <i class="fa-solid fa-box"></i>
            </span>

            <span>
                Products
            </span>

        </a>


        <!-- CATEGORIES -->

        <a
            href="categories"
            class="sidebar-link <?= ($active_page === 'categories') ? 'active' : '' ?>"
        >

            <span class="sidebar-icon">
                <i class="fa-solid fa-layer-group"></i>
            </span>

            <span>
                Categories
            </span>

        </a>


        <!-- REVIEWS -->

        <a
            href="reviews"
            class="sidebar-link <?= ($active_page === 'reviews') ? 'active' : '' ?>"
        >

            <span class="sidebar-icon">
                <i class="fa-solid fa-star"></i>
            </span>

            <span>
                Reviews
            </span>

        </a>

    </div>


    <!-- =====================================================
         MODERATION
    ====================================================== -->

    <div class="sidebar-section">

        <div class="sidebar-title">
            MODERATION
        </div>


        <!-- VERIFICATION REQUESTS -->

        <a
            href="verification-requests"
            class="sidebar-link <?= ($active_page === 'verification-requests') ? 'active' : '' ?>"
        >

            <span class="sidebar-icon">
                <i class="fa-solid fa-circle-check"></i>
            </span>

            <span>
                Verification Requests
            </span>

            <?php if ($sidebar_verification_count > 0): ?>
                <span class="sidebar-badge">
                    <?= $sidebar_verification_count > 99
                        ? '99+'
                        : $sidebar_verification_count ?>
                </span>
            <?php endif; ?>

        </a>


        <!-- REPORTS & COMPLAINTS -->

        <a
            href="reports"
            class="sidebar-link <?= ($active_page === 'reports') ? 'active' : '' ?>"
        >

            <span class="sidebar-icon">
                <i class="fa-solid fa-flag"></i>
            </span>

            <span>
                Reports & Complaints
            </span>

            <?php if ($sidebar_reports_count > 0): ?>
                <span class="sidebar-badge">
                    <?= $sidebar_reports_count > 99
                        ? '99+'
                        : $sidebar_reports_count ?>
                </span>
            <?php endif; ?>

        </a>


        <!-- ACTIVITY LOGS -->

        <a
            href="activity-logs"
            class="sidebar-link <?= ($active_page === 'activity-logs') ? 'active' : '' ?>"
        >

            <span class="sidebar-icon">
                <i class="fa-solid fa-clock-rotate-left"></i>
            </span>

            <span>
                Activity Logs
            </span>

        </a>

    </div>


    <!-- =====================================================
         CONTENT
    ====================================================== -->

    <div class="sidebar-section">

        <div class="sidebar-title">
            CONTENT
        </div>


        <!-- CONTENT MANAGEMENT -->

        <!-- <a
            href="content"
            class="sidebar-link <?= ($active_page === 'content') ? 'active' : '' ?>"
        >

            <span class="sidebar-icon">
                <i class="fa-solid fa-file-lines"></i>
            </span>

            <span>
                Content Management
            </span>

        </a> -->


        <!-- BANNERS -->

        <!-- <a
            href="banners"
            class="sidebar-link <?= ($active_page === 'banners') ? 'active' : '' ?>"
        >

            <span class="sidebar-icon">
                <i class="fa-solid fa-images"></i>
            </span>

            <span>
                Banners
            </span>

        </a> -->


        <!-- NOTIFICATIONS -->

        <a
            href="notifications"
            class="sidebar-link <?= ($active_page === 'notifications') ? 'active' : '' ?>"
        >

            <span class="sidebar-icon">
                <i class="fa-solid fa-bell"></i>
            </span>

            <span>
                Notifications
            </span>

            <?php if ($sidebar_notifications_count > 0): ?>
                <span class="sidebar-badge">
                    <?= $sidebar_notifications_count > 99
                        ? '99+'
                        : $sidebar_notifications_count ?>
                </span>
            <?php endif; ?>

        </a>

    </div>


    <!-- =====================================================
         INSIGHTS
    ====================================================== -->

    <div class="sidebar-section">

        <div class="sidebar-title">
            INSIGHTS
        </div>


        <!-- ANALYTICS -->

        <a
            href="analytics"
            class="sidebar-link <?= ($active_page === 'analytics') ? 'active' : '' ?>"
        >

            <span class="sidebar-icon">
                <i class="fa-solid fa-chart-line"></i>
            </span>

            <span>
                Analytics
            </span>

        </a>

    </div>


    <!-- =====================================================
         SYSTEM
    ====================================================== -->

    <div class="sidebar-section">

        <div class="sidebar-title">
            SYSTEM
        </div>


        <!-- ADMINS -->

        <a
            href="admins"
            class="sidebar-link <?= ($active_page === 'admins') ? 'active' : '' ?>"
        >

            <span class="sidebar-icon">
                <i class="fa-solid fa-users-gear"></i>
            </span>

            <span>
                Admin Management
            </span>

        </a>

        
        <!-- SETTINGS -->

        <a
            href="settings"
            class="sidebar-link <?= ($active_page === 'settings') ? 'active' : '' ?>"
        >

            <span class="sidebar-icon">
                <i class="fa-solid fa-gear"></i>
            </span>

            <span>
                Settings
            </span>

        </a>
        

    </div>


</aside>
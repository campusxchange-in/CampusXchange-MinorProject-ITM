<?php

/* =====================================================
   NOTIFICATION HELPER
===================================================== */

include_once __DIR__ . '/../../server/helpers/notification_helper.php';


/* =====================================================
   CURRENT ADMIN FOR HEADER NOTIFICATIONS
===================================================== */

$notification_admin_id = (int)($_SESSION['admin_id'] ?? 0);


/* =====================================================
   HEADER NOTIFICATIONS
===================================================== */

$header_notifications = get_admin_notifications(
    $conn,
    $notification_admin_id,
    7
);

$header_unread_count = get_admin_unread_count(
    $conn,
    $notification_admin_id
);

$header_total_count = get_admin_notification_count(
    $conn,
    $notification_admin_id
);

$header_notifications = array_slice(
    $header_notifications,
    0,
    6
);

?>




<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

    <title>
        <?= htmlspecialchars($page_title ?? 'CampusXchange Admin') ?>
    </title>

    <link rel="stylesheet" href="assets/css/admin.css?v=<?= filemtime('assets/css/admin.css') ?>">
    <?php if (!empty($page_css)): ?>
        <link rel="stylesheet" href="assets/css/<?= htmlspecialchars($page_css) ?>?v=<?= filemtime('assets/css/' . $page_css)?>">
    <?php endif; ?>

</head>

<body>


    <!-- =========================================
         TOP NAVBAR
    ========================================== -->

    <header class="admin-navbar">


        <!-- SIDEBAR TOGGLE -->

        <button
            type="button"
            class="sidebar-toggle"
            id="sidebarToggle"
            aria-label="Toggle sidebar"
        >

            <i class="fa-solid fa-bars"></i>

        </button>


        <!-- BRAND -->

        <div class="navbar-brand">

            <div class="brand-logo">
                CX
            </div>

            <span class="brand-name">
                CampusXchange
            </span>

        </div>


        <!-- =========================================
             NAVBAR RIGHT
        ========================================== -->

        <div class="navbar-right">


            <!-- SEARCH -->

            <!-- <div class="navbar-search">

                <i class="fa-solid fa-magnifying-glass search-icon"></i>

                <input
                    type="text"
                    placeholder="Search..."
                >

            </div> -->


            <!-- =====================================
                 NOTIFICATION
            ====================================== -->

            <div class="notification-wrapper">

                <button
                    type="button"
                    class="navbar-icon-btn notification-trigger"
                    id="notificationTrigger"
                    aria-label="Notifications"
                    aria-expanded="false"
                    aria-controls="notificationModal"
                >
                    <i class="fa-solid fa-bell"></i>

                    <?php if ($header_unread_count > 0): ?>
                        <span class="notification-dot"></span>
                    <?php endif; ?>
                </button>


                <!-- =================================================
                    NOTIFICATION DROPDOWN
                ================================================== -->

                <div
                    class="notification-modal"
                    id="notificationModal"
                    aria-hidden="true"
                >

                    <div class="notification-modal-header">

                        <div>
                            <h3>Notifications</h3>

                            <?php if ($header_unread_count > 0): ?>
                                <span class="notification-unread-count">
                                    <?= $header_unread_count ?> unread
                                </span>
                            <?php endif; ?>
                        </div>

                        <button
                            type="button"
                            class="notification-close"
                            id="notificationClose"
                            aria-label="Close notifications"
                        >
                            <i class="fa-solid fa-xmark"></i>
                        </button>

                    </div>


                    <div class="notification-modal-body">

                        <?php if (empty($header_notifications)): ?>

                            <div class="notification-empty">

                                <div class="notification-empty-icon">
                                    <i class="fa-regular fa-bell-slash"></i>
                                </div>

                                <h4>No notifications</h4>

                                <p>
                                    You're all caught up.
                                </p>

                            </div>

                        <?php else: ?>

                            <?php foreach ($header_notifications as $notification): ?>

                                <?php
                                $notification_id = (int)$notification['notification_id'];

                                $notification_type =
                                    $notification['notification_type'] ?? 'System';

                                $notification_icon =
                                    get_notification_icon($notification_type);

                                $is_unread =
                                    !(bool)$notification['is_read'];

                                $notification_url =
                                    get_notification_redirect_url($notification);
                                ?>

                                <form
                                    method="POST"
                                    action="notification_action.php"
                                    class="notification-form"
                                >

                                    <input
                                        type="hidden"
                                        name="notification_id"
                                        value="<?= $notification_id ?>"
                                    >

                                    <input
                                        type="hidden"
                                        name="action"
                                        value="notification_click"
                                    >

                                    <button
                                        type="submit"
                                        class="notification-item <?= $is_unread ? 'unread' : 'read' ?>"
                                    >

                                        <span class="notification-item-icon">
                                            <i class="<?= htmlspecialchars($notification_icon) ?>"></i>
                                        </span>


                                        <span class="notification-item-content">

                                            <span class="notification-item-top">

                                                <span class="notification-item-type">
                                                    <?= htmlspecialchars($notification_type) ?>
                                                </span>

                                                <?php if ($is_unread): ?>
                                                    <span class="notification-item-unread-dot"></span>
                                                <?php endif; ?>

                                            </span>


                                            <span class="notification-item-title">
                                                <?= htmlspecialchars($notification['title']) ?>
                                            </span>


                                            <span class="notification-item-message">
                                                <?= htmlspecialchars($notification['message']) ?>
                                            </span>


                                            <span class="notification-item-time">
                                                <?= htmlspecialchars(
                                                    date(
                                                        'd M Y, h:i A',
                                                        strtotime($notification['created_at'])
                                                    )
                                                ) ?>
                                            </span>

                                        </span>

                                    </button>

                                </form>

                            <?php endforeach; ?>

                        <?php endif; ?>

                    </div>


                    <?php if ($header_total_count > 6): ?>

                        <div class="notification-modal-footer">

                            <a href="notifications">
                                View All Notifications
                                <i class="fa-solid fa-arrow-right"></i>
                            </a>

                        </div>

                    <?php endif; ?>

                </div>

            </div>


            <!-- =====================================
                 MESSAGE
            ====================================== -->

            <!-- <button
                type="button"
                class="navbar-icon-btn"
                aria-label="Messages"
            >

                <i class="fa-regular fa-envelope"></i>

            </button> -->


            <!-- =========================================
                 ADMIN PROFILE
            ========================================== -->

            <div class="admin-profile-wrapper">


                <button
                    type="button"
                    class="admin-profile"
                    id="adminProfileButton"
                >


                    <!-- AVATAR -->

                    <div class="admin-avatar">

                        <?= strtoupper(
                            substr($admin['full_name'] ?? 'A', 0, 1)
                        ) ?>

                    </div>


                    <!-- ADMIN INFORMATION -->

                    <div class="admin-profile-info">

                        <span class="admin-profile-name">

                            <?= htmlspecialchars(
                                explode(' ', trim($admin['full_name'] ?? 'Admin'))[0]
                            ) ?>

                        </span>

                        <span class="admin-profile-role">

                            <?= htmlspecialchars(
                                $admin['admin_role'] ?? 'Admin'
                            ) ?>

                        </span>

                    </div>


                    <!-- DROPDOWN ARROW -->

                    <span class="profile-arrow">

                        <i class="fa-solid fa-chevron-down"></i>

                    </span>

                </button>


                <!-- =====================================
                     ADMIN DROPDOWN
                ====================================== -->

                <div
                    class="admin-dropdown"
                    id="adminDropdown"
                >


                    <!-- DROPDOWN HEADER -->

                    <div class="dropdown-header">

                        <strong>

                            <?= htmlspecialchars(
                                $admin['full_name'] ?? 'Admin'
                            ) ?>

                        </strong>

                        <span>

                            <?= htmlspecialchars(
                                $admin['email'] ?? ''
                            ) ?>

                        </span>

                    </div>


                    <!-- PROFILE -->

                    <a
                        href="profile"
                        class="dropdown-item"
                    >

                        <i class="fa-solid fa-user"></i>

                        <span>
                            Profile
                        </span>

                    </a>


                    <!-- THEME -->

                    <button
                        type="button"
                        class="dropdown-item"
                        id="themeToggle"
                    >

                        <i class="fa-solid fa-moon"></i>

                        <span>
                            Dark Mode
                        </span>

                    </button>


                    <!-- SETTINGS -->

                    <a
                        href="settings"
                        class="dropdown-item"
                    >

                        <i class="fa-solid fa-gear"></i>

                        <span>
                            Settings
                        </span>

                    </a>


                    <!-- LOGOUT -->

                    <a
                        href="logout"
                        class="dropdown-item logout"
                    >

                        <i class="fa-solid fa-right-from-bracket"></i>

                        <span>
                            Logout
                        </span>

                    </a>


                </div>

            </div>

        </div>

    </header>


    <!-- =========================================
         BELOW NAVBAR
    ========================================== -->

    <div class="admin-layout">

        <!-- =========================================
            SIDEBAR
        ========================================== -->

        <?php include 'admin_sidebar.php'; ?>

        <!-- =========================================
            MAIN CONTENT
        ========================================== -->

        <main class="admin-content">

            <div class="admin-content-inner">

                <!-- =========================================
                    PAGE HEADER
                ========================================= -->

                <?php if (($active_page ?? '') !== 'dashboard'): ?>

                    <div class="page-header">

                        <div>

                            <h1>
                                <?= str_replace(
                                    '[icon]',
                                    $icon ?? '',
                                    htmlspecialchars($page_heading ?? 'Admin Panel')
                                ) ?>
                    
                            </h1>

                            <?php if (!empty($page_description)): ?>

                                <p>
                                    <?= htmlspecialchars($page_description) ?>
                                </p>

                            <?php endif; ?>

                        </div>

                    </div>

                <?php endif; ?>

                
                <!-- =========================================================
                    BREADCRUMB
                ========================================================= -->
                <?php if (($active_page ?? '') !== 'dashboard'): ?>

                    <?php include 'breadcrumb.php'; ?>

                <?php endif; ?>
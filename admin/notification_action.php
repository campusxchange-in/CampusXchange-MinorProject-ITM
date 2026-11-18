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
   NOTIFICATION HELPER
===================================================== */

include_once __DIR__ . '/../server/helpers/notification_helper.php';


/* =====================================================
   CURRENT ADMIN
===================================================== */

$admin_id = $_SESSION['admin_id'] ?? 0;

if (!$admin_id) {

    header("Location: " . FRONTEND_URL . "/admin-login");
    exit;

}


/* =====================================================
   REQUEST METHOD
===================================================== */

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    header("Location: notifications");
    exit;

}


/* =====================================================
   REQUEST DATA
===================================================== */

$notification_id = (int)(
    $_POST['notification_id'] ?? 0
);

$action = trim(
    $_POST['action'] ?? ''
);


/* =====================================================
   VALIDATE ACTION
===================================================== */

$allowed_actions = [
    'notification_click',
    'mark_read'
];


if (
    $notification_id <= 0 ||
    !in_array($action, $allowed_actions, true)
) {

    header("Location: notifications");
    exit;

}


/* =====================================================
   GET NOTIFICATION
===================================================== */

$notification = get_admin_notification(
    $conn,
    $admin_id,
    $notification_id
);


/*
 * If the notification does not belong to the
 * currently logged-in admin, stop here.
 */

if (!$notification) {

    header("Location: notifications");
    exit;

}


/* =====================================================
   MARK NOTIFICATION AS READ
===================================================== */

mark_admin_notification_read(
    $conn,
    $admin_id,
    $notification_id
);


/* =====================================================
   HANDLE NOTIFICATION CLICK
===================================================== */

if ($action === 'notification_click') {

    $redirect_url = get_notification_redirect_url(
        $notification
    );


    /*
     * Redirect URL is generated server-side from
     * the notification's database reference.
     *
     * No URL is trusted from POST data.
     */

    header(
        "Location: " . $redirect_url
    );

    exit;

}


/* =====================================================
   MARK READ ONLY
===================================================== */

if ($action === 'mark_read') {

    header("Location: notifications");
    exit;

}


/* =====================================================
   FALLBACK
===================================================== */

header("Location: notifications");
exit;
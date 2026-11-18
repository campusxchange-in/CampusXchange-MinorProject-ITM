<?php

include '../config/app_config.php';

session_start();


/* =====================================================
   DATABASE CONNECTION
===================================================== */

include '../server/database/db_connect.php';


/* =====================================================
   GET CURRENT ADMIN
===================================================== */

$admin_id = $_SESSION['admin_id'] ?? 0;
$admin_name = $_SESSION['admin_name'] ?? 'Admin';


/* =====================================================
   LOG ADMIN LOGOUT
===================================================== */

if ($admin_id) {

    $log_description =
        'Admin "' .
        $admin_name .
        '" logged out successfully.';


    $ip_address = $_SERVER['REMOTE_ADDR'] ?? null;

    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? null;


    $log = $conn->prepare("
        INSERT INTO Admin_Logs (
            admin_id,
            action_type,
            action_description,
            target_type,
            target_id,
            ip_address,
            user_agent,
            created_at
        )
        VALUES (
            ?,
            'Logout',
            ?,
            NULL,
            NULL,
            ?,
            ?,
            CURRENT_TIMESTAMP
        )
    ");


    if ($log) {

        $log->bind_param(
            "isss",
            $admin_id,
            $log_description,
            $ip_address,
            $user_agent
        );

        $log->execute();

        $log->close();
    }
}


/* =====================================================
   CLOSE DATABASE
===================================================== */

$conn->close();



$_SESSION = [];

session_destroy();

header("Location: " . FRONTEND_URL . "/admin-login");
exit;
?>
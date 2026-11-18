
<?php

/* =====================================================
   AUTHENTICATION
===================================================== */

include 'includes/auth_check.php';
include '../server/database/db_connect.php';
include 'includes/main_admin_check.php';


/* =====================================================
   ONLY POST REQUESTS
===================================================== */

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: admins.php");
    exit;
}


/* =====================================================
   CSRF VALIDATION
===================================================== */

$csrf_token = $_POST['csrf_token'] ?? '';

if (
    empty($_SESSION['csrf_token']) ||
    !hash_equals($_SESSION['csrf_token'], $csrf_token)
) {
    $_SESSION['admin_message'] = [
        'type' => 'error',
        'message' => 'Invalid security token. Please try again.'
    ];

    header("Location: admins.php");
    exit;
}


/* =====================================================
   INPUTS
===================================================== */

$target_admin_id = (int)($_POST['admin_id'] ?? 0);

$action = $_POST['action'] ?? '';

$reason = trim($_POST['reason'] ?? '');

$current_admin_id = (int)($_SESSION['admin_id'] ?? 0);


/* =====================================================
   VALIDATE INPUT
===================================================== */

$allowed_actions = [
    'suspend',
    'unsuspend',
    'ban',
    'unban'
];

if (
    $target_admin_id <= 0 ||
    !in_array($action, $allowed_actions, true) ||
    $reason === ''
) {
    $_SESSION['admin_message'] = [
        'type' => 'error',
        'message' => 'Invalid admin action or missing reason.'
    ];

    header("Location: admins.php");
    exit;
}


/* =====================================================
   PREVENT SELF-RESTRICTION
===================================================== */

if ($target_admin_id === $current_admin_id) {

    $_SESSION['admin_message'] = [
        'type' => 'error',
        'message' => 'You cannot restrict your own admin account.'
    ];

    header("Location: manage-admin.php?id=" . $target_admin_id);
    exit;
}


/* =====================================================
   FETCH TARGET ADMIN
===================================================== */

$stmt = $conn->prepare("
    SELECT
        admin_id,
        admin_role,
        status

    FROM Admins

    WHERE admin_id = ?
      AND deleted_at IS NULL

    LIMIT 1
");

$stmt->bind_param("i", $target_admin_id);
$stmt->execute();

$target_admin = $stmt->get_result()->fetch_assoc();

$stmt->close();


/* =====================================================
   VALIDATE TARGET
===================================================== */

if (!$target_admin) {

    $_SESSION['admin_message'] = [
        'type' => 'error',
        'message' => 'Admin account not found.'
    ];

    header("Location: admins.php");
    exit;
}


/* =====================================================
   PROTECT MAIN ADMIN ACCOUNTS
===================================================== */

if ($target_admin['admin_role'] !== 'Sub Admin') {

    $_SESSION['admin_message'] = [
        'type' => 'error',
        'message' => 'Main Admin accounts cannot be restricted here.'
    ];

    header("Location: admin-details.php?id=" . $target_admin_id);
    exit;
}


/* =====================================================
   VALIDATE ACTION AGAINST CURRENT STATUS
===================================================== */

$current_status = $target_admin['status'];

$valid_transition = false;

switch ($action) {

    case 'suspend':

        $valid_transition = $current_status === 'Active';
        $new_status = 'Suspended';
        $history_action = 'Suspended';

        break;


    case 'unsuspend':

        $valid_transition = $current_status === 'Suspended';
        $new_status = 'Active';
        $history_action = 'Unsuspended';

        break;


    case 'ban':

        $valid_transition = $current_status === 'Active';
        $new_status = 'Banned';
        $history_action = 'Banned';

        break;


    case 'unban':

        $valid_transition = $current_status === 'Banned';
        $new_status = 'Active';
        $history_action = 'Unbanned';

        break;
}


if (!$valid_transition) {

    $_SESSION['admin_message'] = [
        'type' => 'error',
        'message' => 'This action is not allowed for the current account status.'
    ];

    header("Location: manage-admin.php?id=" . $target_admin_id);
    exit;
}


/* =====================================================
   DATABASE TRANSACTION
===================================================== */

$conn->begin_transaction();

try {

    /* Lock target admin row */

    $lock_stmt = $conn->prepare("
        SELECT admin_id, admin_role, status
        FROM Admins
        WHERE admin_id = ?
          AND deleted_at IS NULL
        FOR UPDATE
    ");

    $lock_stmt->bind_param("i", $target_admin_id);
    $lock_stmt->execute();

    $locked_admin = $lock_stmt
        ->get_result()
        ->fetch_assoc();

    $lock_stmt->close();


    if (
        !$locked_admin ||
        $locked_admin['admin_role'] !== 'Sub Admin' ||
        $locked_admin['status'] !== $current_status
    ) {
        throw new Exception(
            "Admin status changed. Please refresh and try again."
        );
    }


    /* Update admin status */

    $update_stmt = $conn->prepare("
        UPDATE Admins
        SET status = ?
        WHERE admin_id = ?
          AND deleted_at IS NULL
    ");

    $update_stmt->bind_param(
        "si",
        $new_status,
        $target_admin_id
    );

    $update_stmt->execute();

    $update_stmt->close();


    /* Record restriction history */

    $history_stmt = $conn->prepare("
        INSERT INTO Admin_Restrictions (
            admin_id,
            action_type,
            reason,
            performed_by
        )
        VALUES (?, ?, ?, ?)
    ");

    $history_stmt->bind_param(
        "issi",
        $target_admin_id,
        $history_action,
        $reason,
        $current_admin_id
    );

    $history_stmt->execute();

    $history_stmt->close();


    /* Commit */

    $conn->commit();


    $_SESSION['admin_message'] = [
        'type' => 'success',
        'message' => 'Admin account updated successfully.'
    ];


} catch (Throwable $e) {

    $conn->rollback();

    $_SESSION['admin_message'] = [
        'type' => 'error',
        'message' => $e->getMessage()
    ];

}


/* =====================================================
   REDIRECT
===================================================== */

header(
    "Location: manage-admin.php?id=" . $target_admin_id
);

exit;

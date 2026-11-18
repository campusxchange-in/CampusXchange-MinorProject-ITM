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
   REQUEST VALIDATION
===================================================== */

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: verification-requests.php');
    exit;
}


/* =====================================================
   CSRF VALIDATION
===================================================== */

$csrf_token = $_POST['csrf_token'] ?? '';

if (
    empty($csrf_token) ||
    empty($_SESSION['csrf_token']) ||
    !hash_equals($_SESSION['csrf_token'], $csrf_token)
) {
    $_SESSION['error_message'] = 'Invalid security token. Please try again.';
    header('Location: verification-requests.php');
    exit;
}


/* =====================================================
   CURRENT ADMIN
===================================================== */

$admin_id = $_SESSION['admin_id'] ?? 0;

if (!$admin_id) {
    header('Location: ' . FRONTEND_URL . '/admin-login');
    exit;
}


/* =====================================================
   ACTION
===================================================== */

$action = $_POST['action'] ?? '';

$allowed_actions = [
    'approve',
    'reject'
];

if (!in_array($action, $allowed_actions, true)) {
    $_SESSION['error_message'] = 'Invalid verification action.';
    header('Location: verification-requests.php');
    exit;
}


/* =====================================================
   VERIFICATION ID
===================================================== */

$verification_id = filter_input(
    INPUT_POST,
    'verification_id',
    FILTER_VALIDATE_INT
);

if (!$verification_id || $verification_id <= 0) {
    $_SESSION['error_message'] = 'Invalid verification request.';
    header('Location: verification-requests.php');
    exit;
}


/* =====================================================
   FETCH VERIFICATION REQUEST
===================================================== */

$sql = "
    SELECT
        uv.verification_id,
        uv.user_id,
        uv.verification_status,
        uv.rejection_reason,

        u.full_name,
        u.email,
        u.status AS user_status

    FROM User_Verification uv

    INNER JOIN Users u
        ON u.user_id = uv.user_id

    WHERE
        uv.verification_id = ?
        AND uv.deleted_at IS NULL
        AND u.deleted_at IS NULL

    LIMIT 1
";


$stmt = $conn->prepare($sql);

if (!$stmt) {
    $_SESSION['error_message'] = 'Unable to process the verification request.';
    header('Location: verification-requests.php');
    exit;
}


$stmt->bind_param('i', $verification_id);

$stmt->execute();

$result = $stmt->get_result();


if ($result->num_rows === 0) {

    $stmt->close();

    $_SESSION['error_message'] = 'Verification request not found.';

    header('Location: verification-requests.php');
    exit;
}


$verification = $result->fetch_assoc();

$stmt->close();


/* =====================================================
   ONLY PENDING REQUESTS CAN BE PROCESSED
===================================================== */

if ($verification['verification_status'] !== 'Pending') {

    $_SESSION['error_message'] =
        'This verification request has already been processed.';

    header(
        'Location: verification-details.php?id=' .
        $verification_id
    );

    exit;
}


/* =====================================================
   REJECTION REASON VALIDATION
===================================================== */

$rejection_reason = '';

if ($action === 'reject') {

    $rejection_reason = trim(
        $_POST['rejection_reason'] ?? ''
    );

    if ($rejection_reason === '') {

        $_SESSION['error_message'] =
            'A rejection reason is required.';

        header(
            'Location: verification-details.php?id=' .
            $verification_id
        );

        exit;
    }


    if (mb_strlen($rejection_reason) > 255) {

        $_SESSION['error_message'] =
            'The rejection reason cannot exceed 255 characters.';

        header(
            'Location: verification-details.php?id=' .
            $verification_id
        );

        exit;
    }
}


/* =====================================================
   BEGIN TRANSACTION
===================================================== */

$conn->begin_transaction();


try {

    /* =================================================
       APPROVE VERIFICATION
    ================================================== */

    if ($action === 'approve') {

        /* ---------------------------------------------
           Update verification record
        --------------------------------------------- */

        $update_verification = $conn->prepare("
            UPDATE User_Verification
            SET
                verification_status = 'Approved',
                verified_by = ?,
                verified_at = CURRENT_TIMESTAMP,
                rejection_reason = NULL,
                updated_at = CURRENT_TIMESTAMP
            WHERE
                verification_id = ?
                AND verification_status = 'Pending'
                AND deleted_at IS NULL
        ");

        if (!$update_verification) {
            throw new Exception(
                'Unable to prepare verification update.'
            );
        }


        $update_verification->bind_param(
            'ii',
            $admin_id,
            $verification_id
        );


        if (!$update_verification->execute()) {

            $update_verification->close();

            throw new Exception(
                'Unable to approve verification.'
            );
        }


        if ($update_verification->affected_rows !== 1) {

            $update_verification->close();

            throw new Exception(
                'Verification request could not be approved.'
            );
        }


        $update_verification->close();


        /* ---------------------------------------------
           Activate user if currently Pending
           
           IMPORTANT:
           We only perform Pending → Active here.
           We do NOT override Suspended or Banned.
        --------------------------------------------- */

        if ($verification['user_status'] === 'Pending') {

            $activate_user = $conn->prepare("
                UPDATE Users
                SET
                    status = 'Active',
                    updated_at = CURRENT_TIMESTAMP
                WHERE
                    user_id = ?
                    AND status = 'Pending'
                    AND deleted_at IS NULL
            ");

            if (!$activate_user) {
                throw new Exception(
                    'Unable to prepare account activation.'
                );
            }


            $activate_user->bind_param(
                'i',
                $verification['user_id']
            );


            if (!$activate_user->execute()) {

                $activate_user->close();

                throw new Exception(
                    'Unable to activate the user account.'
                );
            }


            $activate_user->close();
        }


        /* ---------------------------------------------
           Admin Log
        --------------------------------------------- */

        $log_description =
            'Approved verification request #' .
            $verification_id .
            ' for user "' .
            $verification['full_name'] .
            '".';

        if ($verification['user_status'] === 'Pending') {

            $log_description .=
                ' User account activated from Pending to Active.';
        }


        $log = $conn->prepare("
            INSERT INTO Admin_Logs (
                admin_id,
                action_type,
                target_type,
                target_id,
                action_description,
                created_at
            )
            VALUES (
                ?,
                'Verify User',
                'User',
                ?,
                ?,
                CURRENT_TIMESTAMP
            )
        ");

        if (!$log) {
            throw new Exception(
                'Unable to prepare admin log.'
            );
        }


        $log->bind_param(
            'iis',
            $admin_id,
            $verification['user_id'],
            $log_description
        );


        if (!$log->execute()) {

            $log->close();

            throw new Exception(
                'Unable to record admin activity.'
            );
        }


        $log->close();


        /* ---------------------------------------------
           User Notification
        --------------------------------------------- */

        $notification_title =
            'Verification Approved';

        $notification_message =
            'Your identity verification has been approved. ' .
            'Your CampusXchange account is now active and you ' .
            'can access the full marketplace.';


        $notification = $conn->prepare("
            INSERT INTO Notifications (
                user_id,
                notification_type,
                title,
                message,
                created_at
            )
            VALUES (
                ?,
                'Verification',
                ?,
                ?,
                CURRENT_TIMESTAMP
            )
        ");

        if (!$notification) {
            throw new Exception(
                'Unable to prepare notification.'
            );
        }


        $notification->bind_param(
            'iss',
            $verification['user_id'],
            $notification_title,
            $notification_message
        );


        if (!$notification->execute()) {

            $notification->close();

            throw new Exception(
                'Unable to send verification notification.'
            );
        }


        $notification->close();


        /* ---------------------------------------------
           Commit
        --------------------------------------------- */

        $conn->commit();


        $_SESSION['success_message'] =
            'Verification approved successfully.';


        header(
            'Location: verification-details.php?id=' .
            $verification_id
        );

        exit;
    }


    /* =================================================
       REJECT VERIFICATION
    ================================================== */

    if ($action === 'reject') {

        /* ---------------------------------------------
           Update verification record
        --------------------------------------------- */

        $update_verification = $conn->prepare("
            UPDATE User_Verification
            SET
                verification_status = 'Rejected',
                verified_by = ?,
                verified_at = CURRENT_TIMESTAMP,
                rejection_reason = ?,
                updated_at = CURRENT_TIMESTAMP
            WHERE
                verification_id = ?
                AND verification_status = 'Pending'
                AND deleted_at IS NULL
        ");

        if (!$update_verification) {
            throw new Exception(
                'Unable to prepare verification rejection.'
            );
        }


        $update_verification->bind_param(
            'isi',
            $admin_id,
            $rejection_reason,
            $verification_id
        );


        if (!$update_verification->execute()) {

            $update_verification->close();

            throw new Exception(
                'Unable to reject verification.'
            );
        }


        if ($update_verification->affected_rows !== 1) {

            $update_verification->close();

            throw new Exception(
                'Verification request could not be rejected.'
            );
        }


        $update_verification->close();


        /* ---------------------------------------------
           Keep initial account Pending
           
           Rejection does NOT activate the user.
           The user can correct the problem and
           resubmit verification.
        --------------------------------------------- */

        /* ---------------------------------------------
           Admin Log
        --------------------------------------------- */

        $log_description =
            'Rejected verification request #' .
            $verification_id .
            ' for user "' .
            $verification['full_name'] .
            '". Reason: ' .
            $rejection_reason;


        $log = $conn->prepare("
            INSERT INTO Admin_Logs (
                admin_id,
                action_type,
                target_type,
                target_id,
                action_description,
                created_at
            )
            VALUES (
                ?,
                'Verify User',
                'User',
                ?,
                ?,
                CURRENT_TIMESTAMP
            )
        ");

        if (!$log) {
            throw new Exception(
                'Unable to prepare admin log.'
            );
        }


        $log->bind_param(
            'iis',
            $admin_id,
            $verification['user_id'],
            $log_description
        );


        if (!$log->execute()) {

            $log->close();

            throw new Exception(
                'Unable to record admin activity.'
            );
        }


        $log->close();


        /* ---------------------------------------------
           User Notification
        --------------------------------------------- */

        $notification_title =
            'Verification Rejected';

        $notification_message =
            'Your identity verification was rejected. ' .
            'Reason: ' .
            $rejection_reason .
            ' Please correct the issue and resubmit your verification.';


        $notification = $conn->prepare("
            INSERT INTO Notifications (
                user_id,
                notification_type,
                title,
                message,
                created_at
            )
            VALUES (
                ?,
                'Verification',
                ?,
                ?,
                CURRENT_TIMESTAMP
            )
        ");

        if (!$notification) {
            throw new Exception(
                'Unable to prepare notification.'
            );
        }


        $notification->bind_param(
            'iss',
            $verification['user_id'],
            $notification_title,
            $notification_message
        );


        if (!$notification->execute()) {

            $notification->close();

            throw new Exception(
                'Unable to send verification notification.'
            );
        }


        $notification->close();


        /* ---------------------------------------------
           Commit
        --------------------------------------------- */

        $conn->commit();


        $_SESSION['success_message'] =
            'Verification rejected successfully.';


        header(
            'Location: verification-details.php?id=' .
            $verification_id
        );

        exit;
    }


} catch (Throwable $e) {

    /* =================================================
       ROLLBACK
    ================================================== */

    $conn->rollback();

    $_SESSION['error_message'] =
        'Verification action failed: ' .
        $e->getMessage();

    header(
        'Location: verification-details.php?id=' .
        (int) $verification_id
    );

    exit;
}

?>
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
   CURRENT ADMIN
===================================================== */

$admin_id = $_SESSION['admin_id'] ?? 0;

if (!$admin_id) {
    header("Location: " . FRONTEND_URL . "/admin-login");
    exit;
}


/* =====================================================
   PAGE SETTINGS
===================================================== */

$page_title       = 'Manage User | CampusXchange';
$page_heading     = 'Manage User';
$page_description = 'Manage account status, verification and security settings.';
$active_page      = 'users';
$page_css         = 'user_manage.css';


/* =====================================================
   GET USER ID
===================================================== */

$user_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$user_id) {
    header("Location: users");
    exit;
}


/* =====================================================
   FETCH USER
===================================================== */

$stmt = $conn->prepare("
    SELECT
        u.user_id,
        u.full_name,
        u.email,
        u.phone AS account_phone,
        u.role,
        u.password,
        u.email_verified_at,
        u.status,
        u.session_version,
        u.created_at,
        u.updated_at,
        u.deleted_at,

        up.profile_id,
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

        uv.verification_id,
        uv.id_type,
        uv.id_number,
        uv.college_id_front_image,
        uv.college_id_back_image,
        uv.verification_status,
        uv.verified_by,
        uv.verified_at,
        uv.rejection_reason,
        uv.created_at AS verification_created_at

    FROM Users u

    LEFT JOIN User_Profiles up
        ON up.user_id = u.user_id
        AND u.deleted_at IS NULL

    LEFT JOIN User_Verification uv
        ON uv.user_id = u.user_id
        AND uv.deleted_at IS NULL

    WHERE u.user_id = ?
      AND u.deleted_at IS NULL

    LIMIT 1
");

$stmt->bind_param("i", $user_id);
$stmt->execute();

$result = $stmt->get_result();
$user = $result->fetch_assoc();

$stmt->close();


/* =====================================================
   USER NOT FOUND
===================================================== */

if (!$user) {
    header("Location: users");
    exit;
}


/* =====================================================
   HELPER
===================================================== */

function redirect_with_message($user_id, $type, $message)
{
    $url = "user_manage?id=" . (int) $user_id
         . "&message_type=" . urlencode($type)
         . "&message=" . urlencode($message);

    header("Location: " . $url);
    exit;
}


/* =====================================================
   HANDLE POST ACTIONS
===================================================== */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $action = $_POST['action'] ?? '';


    /* =================================================
       CSRF
    ================================================= */

    if (
        !isset($_POST['csrf_token']) ||
        !isset($_SESSION['csrf_token']) ||
        !hash_equals(
            $_SESSION['csrf_token'],
            $_POST['csrf_token']
        )
    ) {
        redirect_with_message(
            $user_id,
            'error',
            'Invalid security token. Please try again.'
        );
    }


    /* =================================================
       PREVENT ACTIONS ON DELETED USERS
    ================================================= */

    if ($user['deleted_at'] !== null) {
        redirect_with_message(
            $user_id,
            'error',
            'This user account has already been deleted.'
        );
    }


    /* =================================================
       UPDATE ACCOUNT STATUS
    ================================================= */

    if ($action === 'update_status') {

        $new_status = $_POST['status'] ?? '';

        /*
         * Pending is intentionally NOT included.
         *
         * Pending accounts are activated through
         * identity verification approval.
         */

        $allowed_statuses = [
            'Active',
            'Suspended',
            'Banned'
        ];

        if (!in_array($new_status, $allowed_statuses, true)) {
            redirect_with_message(
                $user_id,
                'error',
                'Invalid account status.'
            );
        }


        /*
         * Pending accounts cannot be manually
         * changed from this section.
         */

        if ($user['status'] === 'Pending') {
            redirect_with_message(
                $user_id,
                'error',
                'Pending accounts are activated through identity verification approval.'
            );
        }


        if ($new_status === $user['status']) {
            redirect_with_message(
                $user_id,
                'info',
                'The account is already set to ' . $new_status . '.'
            );
        }


        $old_status = $user['status'];

        $reason = trim(
            $_POST['status_reason'] ?? ''
        );


        /* =================================================
           VALIDATE REASON
        ================================================= */

        if (
            ($new_status === 'Suspended' ||
             $new_status === 'Banned') &&
            $reason === ''
        ) {
            redirect_with_message(
                $user_id,
                'error',
                'A reason is required when suspending or banning an account.'
            );
        }


        if (mb_strlen($reason) > 255) {
            redirect_with_message(
                $user_id,
                'error',
                'Status change reason must not exceed 255 characters.'
            );
        }


        $conn->begin_transaction();

        try {

            /* =================================================
               ACTIVE
            ================================================= */

            if ($new_status === 'Active') {

                /*
                 * Revoke any active User_Bans.
                 */

                $ban_status = 'Active';

                $stmt = $conn->prepare("
                    SELECT
                        ban_id,
                        ban_type,
                        reason
                    FROM User_Bans
                    WHERE user_id = ?
                      AND status = ?
                      AND deleted_at IS NULL
                ");

                $stmt->bind_param(
                    "is",
                    $user_id,
                    $ban_status
                );

                $stmt->execute();

                $ban_result = $stmt->get_result();

                $active_bans = [];

                while ($ban = $ban_result->fetch_assoc()) {
                    $active_bans[] = $ban;
                }

                $stmt->close();


                /*
                 * Revoke all active bans.
                 */

                if (!empty($active_bans)) {

                    $revoked_status = 'Revoked';
                    $revocation_reason =
                        'Account reinstated by administrator.';

                    $stmt = $conn->prepare("
                        UPDATE User_Bans
                        SET
                            status = ?,
                            revoked_by = ?,
                            revoked_at = CURRENT_TIMESTAMP,
                            revocation_reason = ?,
                            updated_at = CURRENT_TIMESTAMP
                        WHERE user_id = ?
                          AND status = 'Active'
                          AND deleted_at IS NULL
                    ");

                    $stmt->bind_param(
                        "sisi",
                        $revoked_status,
                        $admin_id,
                        $revocation_reason,
                        $user_id
                    );

                    if (!$stmt->execute()) {
                        throw new Exception(
                            'Unable to revoke active user bans.'
                        );
                    }

                    $stmt->close();

                    /*
                    * Create a separate Revoke Ban admin log
                    * for each active ban that was revoked.
                    */

                    $action_type = 'Revoke Ban';
                    $target_type = 'Ban';

                    $ip_address =
                        $_SERVER['REMOTE_ADDR'] ?? null;

                    $user_agent =
                        $_SERVER['HTTP_USER_AGENT'] ?? null;


                    foreach ($active_bans as $ban) {

                        $ban_id = (int) $ban['ban_id'];

                        $description =
                            "Revoked {$ban['ban_type']} ban #{$ban_id} "
                            . "for user #{$user_id}. "
                            . "Account status changed from {$old_status} to Active. "
                            . "Reason: {$revocation_reason}";


                        $stmt = $conn->prepare("
                            INSERT INTO Admin_Logs
                            (
                                admin_id,
                                action_type,
                                action_description,
                                target_type,
                                target_id,
                                ip_address,
                                user_agent
                            )
                            VALUES (?, ?, ?, ?, ?, ?, ?)
                        ");

                        $stmt->bind_param(
                            "isssiss",
                            $admin_id,
                            $action_type,
                            $description,
                            $target_type,
                            $ban_id,
                            $ip_address,
                            $user_agent
                        );

                        if (!$stmt->execute()) {
                            throw new Exception(
                                'Unable to create ban revocation log.'
                            );
                        }

                        $stmt->close();
                    }
                }


                /*
                 * Restore account.
                 */

                $stmt = $conn->prepare("
                    UPDATE Users
                    SET
                        status = 'Active',
                        updated_at = CURRENT_TIMESTAMP
                    WHERE user_id = ?
                      AND deleted_at IS NULL
                ");

                $stmt->bind_param("i", $user_id);

                if (!$stmt->execute()) {
                    throw new Exception(
                        'Unable to activate the user account.'
                    );
                }

                $stmt->close();


                /*
                 * Admin log.
                 */

                $action_type = 'Manage User';

                $description =
                    "Reinstated user #{$user_id} from {$old_status} to Active.";

                $target_type = 'User';

                $ip_address =
                    $_SERVER['REMOTE_ADDR'] ?? null;

                $user_agent =
                    $_SERVER['HTTP_USER_AGENT'] ?? null;

                $stmt = $conn->prepare("
                    INSERT INTO Admin_Logs
                    (
                        admin_id,
                        action_type,
                        action_description,
                        target_type,
                        target_id,
                        ip_address,
                        user_agent
                    )
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ");

                $stmt->bind_param(
                    "isssiss",
                    $admin_id,
                    $action_type,
                    $description,
                    $target_type,
                    $user_id,
                    $ip_address,
                    $user_agent
                );

                if (!$stmt->execute()) {
                    throw new Exception(
                        'Unable to create admin log.'
                    );
                }

                $stmt->close();


                /*
                 * Notification.
                 */

                $notification_type = 'Security';
                $title = 'Account Restored';

                $notification_message =
                    'Your CampusXchange account has been restored to Active status by an administrator.';

                $reference_type = 'User';
                $reference_id = $user_id;

                $stmt = $conn->prepare("
                    INSERT INTO Notifications
                    (
                        user_id,
                        notification_type,
                        title,
                        message,
                        reference_type,
                        reference_id
                    )
                    VALUES (?, ?, ?, ?, ?, ?)
                ");

                $stmt->bind_param(
                    "issssi",
                    $user_id,
                    $notification_type,
                    $title,
                    $notification_message,
                    $reference_type,
                    $reference_id
                );

                if (!$stmt->execute()) {
                    throw new Exception(
                        'Unable to create notification.'
                    );
                }

                $stmt->close();
            }


            /* =================================================
               SUSPENDED / BANNED
            ================================================= */

            else {

                /*
                 * Prevent duplicate active bans.
                 */

                $stmt = $conn->prepare("
                    SELECT ban_id
                    FROM User_Bans
                    WHERE user_id = ?
                      AND status = 'Active'
                      AND deleted_at IS NULL
                    LIMIT 1
                ");

                $stmt->bind_param(
                    "i",
                    $user_id
                );

                $stmt->execute();

                $existing_ban =
                    $stmt->get_result()->fetch_assoc();

                $stmt->close();


                if ($existing_ban) {
                    throw new Exception(
                        'This user already has an active moderation action. Revoke it before creating another one.'
                    );
                }


                /*
                 * Ban type.
                 */

                $ban_type =
                    $new_status === 'Banned'
                        ? 'Permanent'
                        : 'Temporary';


                /*
                 * Insert User_Bans record.
                 *
                 * Temporary suspension currently has
                 * no end date because this page does not
                 * provide a duration field.
                 */

                $stmt = $conn->prepare("
                    INSERT INTO User_Bans
                    (
                        user_id,
                        report_id,
                        admin_id,
                        ban_type,
                        reason,
                        description,
                        start_date,
                        end_date,
                        status
                    )
                    VALUES (?, NULL, ?, ?, ?, NULL, CURRENT_TIMESTAMP, NULL, 'Active')
                ");

                $stmt->bind_param(
                    "iiss",
                    $user_id,
                    $admin_id,
                    $ban_type,
                    $reason
                );

                if (!$stmt->execute()) {
                    throw new Exception(
                        'Unable to create moderation record.'
                    );
                }

                $stmt->close();


                /*
                 * Update Users status.
                 *
                 * Also increment session_version so
                 * existing sessions become invalid.
                 */

                $stmt = $conn->prepare("
                    UPDATE Users
                    SET
                        status = ?,
                        session_version = session_version + 1,
                        updated_at = CURRENT_TIMESTAMP
                    WHERE user_id = ?
                      AND deleted_at IS NULL
                ");

                $stmt->bind_param(
                    "si",
                    $new_status,
                    $user_id
                );

                if (!$stmt->execute()) {
                    throw new Exception(
                        'Unable to update account status.'
                    );
                }

                $stmt->close();


                /*
                 * Admin log.
                 */

                $action_type = 'Issue Ban';

                $description =
                    "Changed user #{$user_id} from {$old_status} to {$new_status}. "
                    . "Reason: {$reason}";

                $target_type = 'User';

                $ip_address =
                    $_SERVER['REMOTE_ADDR'] ?? null;

                $user_agent =
                    $_SERVER['HTTP_USER_AGENT'] ?? null;

                $stmt = $conn->prepare("
                    INSERT INTO Admin_Logs
                    (
                        admin_id,
                        action_type,
                        action_description,
                        target_type,
                        target_id,
                        ip_address,
                        user_agent
                    )
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ");

                $stmt->bind_param(
                    "isssiss",
                    $admin_id,
                    $action_type,
                    $description,
                    $target_type,
                    $user_id,
                    $ip_address,
                    $user_agent
                );

                if (!$stmt->execute()) {
                    throw new Exception(
                        'Unable to create admin log.'
                    );
                }

                $stmt->close();


                /*
                 * Notification.
                 */

                $notification_type = 'Ban';
                $title =
                    $new_status === 'Banned'
                        ? 'Account Banned'
                        : 'Account Suspended';

                $notification_message =
                    "Your CampusXchange account has been {$new_status}. "
                    . "Reason: {$reason}";

                $reference_type = 'User';
                $reference_id = $user_id;

                $stmt = $conn->prepare("
                    INSERT INTO Notifications
                    (
                        user_id,
                        notification_type,
                        title,
                        message,
                        reference_type,
                        reference_id
                    )
                    VALUES (?, ?, ?, ?, ?, ?)
                ");

                $stmt->bind_param(
                    "issssi",
                    $user_id,
                    $notification_type,
                    $title,
                    $notification_message,
                    $reference_type,
                    $reference_id
                );

                if (!$stmt->execute()) {
                    throw new Exception(
                        'Unable to create notification.'
                    );
                }

                $stmt->close();
            }


            $conn->commit();

            redirect_with_message(
                $user_id,
                'success',
                'User account status updated successfully.'
            );

        } catch (Exception $e) {

            $conn->rollback();

            redirect_with_message(
                $user_id,
                'error',
                $e->getMessage()
            );
        }
    }


    /* =================================================
       APPROVE VERIFICATION
    ================================================= */

    elseif ($action === 'approve_verification') {

        if (!$user['verification_id']) {
            redirect_with_message(
                $user_id,
                'error',
                'No verification record exists for this user.'
            );
        }


        if (
            $user['verification_status'] !== 'Pending' &&
            $user['verification_status'] !== 'Rejected'
        ) {
            redirect_with_message(
                $user_id,
                'info',
                'This verification cannot be approved in its current state.'
            );
        }


        $conn->begin_transaction();

        try {

            /*
             * Approve verification.
             */

            /* =================================================
            RE-CHECK PROFILE COMPLETION
            ================================================= */

            $stmt = $conn->prepare("
                SELECT up.profile_completion_percentage
                FROM User_Profiles up
                INNER JOIN Users u
                    ON u.user_id = up.user_id
                WHERE up.user_id = ?
                AND u.deleted_at IS NULL
                LIMIT 1
            ");

            $stmt->bind_param(
                "i",
                $user_id
            );

            if (!$stmt->execute()) {
                throw new Exception(
                    'Unable to verify profile completion.'
                );
            }

            $profile_result = $stmt->get_result();
            $profile_data = $profile_result->fetch_assoc();

            $stmt->close();

            $profile_completion = (int) (
                $profile_data['profile_completion_percentage'] ?? 0
            );

            if ($profile_completion < 100) {
                throw new Exception(
                    'Verification cannot be approved until the user profile is 100% complete.'
                );
            }


            /* =================================================
            APPROVE VERIFICATION
            ================================================= */

            $verification_status = 'Approved';

            $stmt = $conn->prepare("
                UPDATE User_Verification
                SET
                    verification_status = ?,
                    verified_by = ?,
                    verified_at = CURRENT_TIMESTAMP,
                    rejection_reason = NULL,
                    updated_at = CURRENT_TIMESTAMP
                WHERE verification_id = ?
                AND user_id = ?
                AND deleted_at IS NULL
            ");

            $stmt->bind_param(
                "siii",
                $verification_status,
                $admin_id,
                $user['verification_id'],
                $user_id
            );

            if (!$stmt->execute()) {
                throw new Exception(
                    'Unable to approve verification.'
                );
            }

            $stmt->close();


            /*
             * Activate Pending account.
             *
             * Do NOT override Suspended/Banned.
             */

            $account_activated = false;

            if ($user['status'] === 'Pending') {

                $stmt = $conn->prepare("
                    UPDATE Users
                    SET
                        status = 'Active',
                        updated_at = CURRENT_TIMESTAMP
                    WHERE user_id = ?
                      AND status = 'Pending'
                      AND deleted_at IS NULL
                ");

                $stmt->bind_param(
                    "i",
                    $user_id
                );

                if (!$stmt->execute()) {
                    throw new Exception(
                        'Unable to activate the user account.'
                    );
                }

                if ($stmt->affected_rows === 1) {
                    $account_activated = true;
                }

                $stmt->close();
            }


            /*
             * Admin log.
             */

            $action_type = 'Verify User';

            if ($account_activated) {

                $description =
                    "Approved verification and activated account for user #{$user_id}.";

            } else {

                $description =
                    "Approved verification for user #{$user_id}.";
            }

            $target_type = 'User';

            $ip_address =
                $_SERVER['REMOTE_ADDR'] ?? null;

            $user_agent =
                $_SERVER['HTTP_USER_AGENT'] ?? null;

            $stmt = $conn->prepare("
                INSERT INTO Admin_Logs
                (
                    admin_id,
                    action_type,
                    action_description,
                    target_type,
                    target_id,
                    ip_address,
                    user_agent
                )
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");

            $stmt->bind_param(
                "isssiss",
                $admin_id,
                $action_type,
                $description,
                $target_type,
                $user_id,
                $ip_address,
                $user_agent
            );

            if (!$stmt->execute()) {
                throw new Exception(
                    'Unable to create admin log.'
                );
            }

            $stmt->close();


            /*
             * Notification.
             */

            $notification_type = 'Verification';
            $title = 'Verification Approved';

            if ($account_activated) {

                $notification_message =
                    'Your identity verification has been approved and your account is now Active. You can now access the full CampusXchange marketplace.';

            } else {

                $notification_message =
                    'Your identity verification has been approved.';
            }

            $reference_type = 'User';
            $reference_id = $user_id;

            $stmt = $conn->prepare("
                INSERT INTO Notifications
                (
                    user_id,
                    notification_type,
                    title,
                    message,
                    reference_type,
                    reference_id
                )
                VALUES (?, ?, ?, ?, ?, ?)
            ");

            $stmt->bind_param(
                "issssi",
                $user_id,
                $notification_type,
                $title,
                $notification_message,
                $reference_type,
                $reference_id
            );

            if (!$stmt->execute()) {
                throw new Exception(
                    'Unable to create notification.'
                );
            }

            $stmt->close();


            $conn->commit();

            redirect_with_message(
                $user_id,
                'success',
                $account_activated
                    ? 'Verification approved and user account activated successfully.'
                    : 'User verification approved successfully.'
            );

        } catch (Exception $e) {

            $conn->rollback();

            redirect_with_message(
                $user_id,
                'error',
                $e->getMessage()
            );
        }
    }


    /* =================================================
       REJECT VERIFICATION
    ================================================= */

    elseif ($action === 'reject_verification') {

        if (!$user['verification_id']) {
            redirect_with_message(
                $user_id,
                'error',
                'No verification record exists for this user.'
            );
        }


        if ($user['verification_status'] !== 'Pending') {
            redirect_with_message(
                $user_id,
                'info',
                'Only pending verification requests can be rejected.'
            );
        }


        $rejection_reason = trim(
            $_POST['rejection_reason'] ?? ''
        );


        if ($rejection_reason === '') {
            redirect_with_message(
                $user_id,
                'error',
                'Please provide a rejection reason.'
            );
        }


        if (mb_strlen($rejection_reason) > 255) {
            redirect_with_message(
                $user_id,
                'error',
                'Rejection reason must not exceed 255 characters.'
            );
        }


        $conn->begin_transaction();

        try {

            $verification_status = 'Rejected';

            $stmt = $conn->prepare("
                UPDATE User_Verification
                SET
                    verification_status = ?,
                    verified_by = ?,
                    verified_at = CURRENT_TIMESTAMP,
                    rejection_reason = ?,
                    updated_at = CURRENT_TIMESTAMP
                WHERE verification_id = ?
                  AND user_id = ?
                  AND verification_status = 'Pending'
                  AND deleted_at IS NULL
            ");

            $stmt->bind_param(
                "sisii",
                $verification_status,
                $admin_id,
                $rejection_reason,
                $user['verification_id'],
                $user_id
            );

            if (!$stmt->execute()) {
                throw new Exception(
                    'Unable to reject verification.'
                );
            }

            if ($stmt->affected_rows !== 1) {
                throw new Exception(
                    'The verification request could not be rejected.'
                );
            }

            $stmt->close();


            /*
             * Admin log.
             */

            $action_type = 'Verify User';

            $description =
                "Rejected verification for user #{$user_id}. "
                . "Reason: {$rejection_reason}";

            $target_type = 'User';

            $ip_address =
                $_SERVER['REMOTE_ADDR'] ?? null;

            $user_agent =
                $_SERVER['HTTP_USER_AGENT'] ?? null;

            $stmt = $conn->prepare("
                INSERT INTO Admin_Logs
                (
                    admin_id,
                    action_type,
                    action_description,
                    target_type,
                    target_id,
                    ip_address,
                    user_agent
                )
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");

            $stmt->bind_param(
                "isssiss",
                $admin_id,
                $action_type,
                $description,
                $target_type,
                $user_id,
                $ip_address,
                $user_agent
            );

            if (!$stmt->execute()) {
                throw new Exception(
                    'Unable to create admin log.'
                );
            }

            $stmt->close();


            /*
             * Notification.
             */

            $notification_type = 'Verification';
            $title = 'Verification Rejected';

            $notification_message =
                "Your identity verification was rejected. "
                . "Reason: {$rejection_reason}";

            $reference_type = 'User';
            $reference_id = $user_id;

            $stmt = $conn->prepare("
                INSERT INTO Notifications
                (
                    user_id,
                    notification_type,
                    title,
                    message,
                    reference_type,
                    reference_id
                )
                VALUES (?, ?, ?, ?, ?, ?)
            ");

            $stmt->bind_param(
                "issssi",
                $user_id,
                $notification_type,
                $title,
                $notification_message,
                $reference_type,
                $reference_id
            );

            if (!$stmt->execute()) {
                throw new Exception(
                    'Unable to create notification.'
                );
            }

            $stmt->close();


            $conn->commit();

            redirect_with_message(
                $user_id,
                'success',
                'User verification rejected successfully.'
            );

        } catch (Exception $e) {

            $conn->rollback();

            redirect_with_message(
                $user_id,
                'error',
                $e->getMessage()
            );
        }
    }


    /* =================================================
       LOGOUT ALL DEVICES
    ================================================= */

    elseif ($action === 'logout_all') {

        $conn->begin_transaction();

        try {

            /*
             * Increment session version.
             */

            $stmt = $conn->prepare("
                UPDATE Users
                SET
                    session_version = session_version + 1,
                    updated_at = CURRENT_TIMESTAMP
                WHERE user_id = ?
                  AND deleted_at IS NULL
            ");

            $stmt->bind_param(
                "i",
                $user_id
            );

            if (!$stmt->execute()) {
                throw new Exception(
                    'Unable to invalidate user sessions.'
                );
            }

            $stmt->close();


            /*
             * Revoke remember tokens.
             */

            $stmt = $conn->prepare("
                UPDATE Remember_Tokens
                SET revoked_at = CURRENT_TIMESTAMP
                WHERE user_id = ?
                  AND revoked_at IS NULL
            ");

            $stmt->bind_param(
                "i",
                $user_id
            );

            if (!$stmt->execute()) {
                throw new Exception(
                    'Unable to revoke remembered sessions.'
                );
            }

            $stmt->close();


            /*
             * Security activity.
             */

            $activity_type = 'Logout All Devices';

            $activity_title =
                'Logged Out From All Devices';

            $activity_description =
                'All active sessions were invalidated by an administrator.';

            $stmt = $conn->prepare("
                INSERT INTO Login_Security_Activity
                (
                    user_id,
                    activity_type,
                    title,
                    description
                )
                VALUES (?, ?, ?, ?)
            ");

            $stmt->bind_param(
                "isss",
                $user_id,
                $activity_type,
                $activity_title,
                $activity_description
            );

            if (!$stmt->execute()) {
                throw new Exception(
                    'Unable to create security activity.'
                );
            }

            $stmt->close();


            /*
             * Admin log.
             */

            $action_type = 'Manage User';

            $description =
                "Logged user #{$user_id} out from all devices.";

            $target_type = 'User';

            $ip_address =
                $_SERVER['REMOTE_ADDR'] ?? null;

            $user_agent =
                $_SERVER['HTTP_USER_AGENT'] ?? null;

            $stmt = $conn->prepare("
                INSERT INTO Admin_Logs
                (
                    admin_id,
                    action_type,
                    action_description,
                    target_type,
                    target_id,
                    ip_address,
                    user_agent
                )
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");

            $stmt->bind_param(
                "isssiss",
                $admin_id,
                $action_type,
                $description,
                $target_type,
                $user_id,
                $ip_address,
                $user_agent
            );

            if (!$stmt->execute()) {
                throw new Exception(
                    'Unable to create admin log.'
                );
            }

            $stmt->close();


            /*
             * Notification.
             */

            $notification_type = 'Security';
            $title = 'Security Session Reset';

            $notification_message =
                'All active sessions on your CampusXchange account have been logged out by an administrator.';

            $reference_type = 'Security';
            $reference_id = null;

            $stmt = $conn->prepare("
                INSERT INTO Notifications
                (
                    user_id,
                    notification_type,
                    title,
                    message,
                    reference_type,
                    reference_id
                )
                VALUES (?, ?, ?, ?, ?, ?)
            ");

            $stmt->bind_param(
                "issssi",
                $user_id,
                $notification_type,
                $title,
                $notification_message,
                $reference_type,
                $reference_id
            );

            if (!$stmt->execute()) {
                throw new Exception(
                    'Unable to create notification.'
                );
            }

            $stmt->close();


            $conn->commit();

            redirect_with_message(
                $user_id,
                'success',
                'User has been logged out from all devices.'
            );

        } catch (Exception $e) {

            $conn->rollback();

            redirect_with_message(
                $user_id,
                'error',
                $e->getMessage()
            );
        }
    }


    /* =================================================
       SOFT DELETE USER
    ================================================= */

    elseif ($action === 'delete_user') {

        $confirmation = trim(
            $_POST['delete_confirmation'] ?? ''
        );


        if ($confirmation !== 'DELETE') {
            redirect_with_message(
                $user_id,
                'error',
                'Please type DELETE to confirm account deletion.'
            );
        }


        $conn->begin_transaction();

        try {

            /*
             * Soft delete.
             */

            $stmt = $conn->prepare("
                UPDATE Users
                SET
                    deleted_at = CURRENT_TIMESTAMP,
                    status = 'Suspended',
                    session_version = session_version + 1,
                    updated_at = CURRENT_TIMESTAMP
                WHERE user_id = ?
                  AND deleted_at IS NULL
            ");

            $stmt->bind_param(
                "i",
                $user_id
            );

            if (!$stmt->execute()) {
                throw new Exception(
                    'Unable to delete user account.'
                );
            }

            if ($stmt->affected_rows !== 1) {
                throw new Exception(
                    'User account could not be deleted.'
                );
            }

            $stmt->close();


            /*
             * Revoke remember tokens.
             */

            $stmt = $conn->prepare("
                UPDATE Remember_Tokens
                SET revoked_at = CURRENT_TIMESTAMP
                WHERE user_id = ?
                  AND revoked_at IS NULL
            ");

            $stmt->bind_param(
                "i",
                $user_id
            );

            if (!$stmt->execute()) {
                throw new Exception(
                    'Unable to revoke user sessions.'
                );
            }

            $stmt->close();


            /*
             * Admin log.
             */

            $action_type = 'Delete';

            $description =
                "Soft deleted user account #{$user_id}.";

            $target_type = 'User';

            $ip_address =
                $_SERVER['REMOTE_ADDR'] ?? null;

            $user_agent =
                $_SERVER['HTTP_USER_AGENT'] ?? null;

            $stmt = $conn->prepare("
                INSERT INTO Admin_Logs
                (
                    admin_id,
                    action_type,
                    action_description,
                    target_type,
                    target_id,
                    ip_address,
                    user_agent
                )
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");

            $stmt->bind_param(
                "isssiss",
                $admin_id,
                $action_type,
                $description,
                $target_type,
                $user_id,
                $ip_address,
                $user_agent
            );

            if (!$stmt->execute()) {
                throw new Exception(
                    'Unable to create admin log.'
                );
            }

            $stmt->close();


            $conn->commit();

            header(
                "Location: users?message_type=success&message=" .
                urlencode('User account deleted successfully.')
            );

            exit;

        } catch (Exception $e) {

            $conn->rollback();

            redirect_with_message(
                $user_id,
                'error',
                $e->getMessage()
            );
        }
    }


    /* =================================================
       UNKNOWN ACTION
    ================================================= */

    else {

        redirect_with_message(
            $user_id,
            'error',
            'Invalid management action.'
        );
    }
}


/* =====================================================
   GENERATE CSRF TOKEN
===================================================== */

if (empty($_SESSION['csrf_token'])) {

    $_SESSION['csrf_token'] =
        bin2hex(random_bytes(32));
}

$csrf_token =
    $_SESSION['csrf_token'];


/* =====================================================
   MESSAGE
===================================================== */

$message_type =
    $_GET['message_type'] ?? '';

$message =
    $_GET['message'] ?? '';


/* =====================================================
   DISPLAY VALUES
===================================================== */

$profile_picture =
    $user['profile_picture'] ?? '';

$verification_status =
    $user['verification_status']
    ?? 'Not Submitted';

$verification_front_document =
    $user['college_id_front_image']
    ?? '';

$verification_back_document =
    $user['college_id_back_image']
    ?? '';

$profile_phone =
    $user['profile_phone']
    ?: $user['account_phone']
    ?: 'Not provided';


/* =====================================================
   BREADCRUMBS
===================================================== */

$breadcrumbs = [
    [
        'label' => 'Users',
        'url' => 'users.php'
    ],
    [
        'label' => $user['full_name'],
        'url' => 'user_view.php?id=' . $user['user_id']
    ],
    [
        'label' => 'Manage User'
    ]
];

?>

<?php include 'includes/admin_header.php'; ?>


<!-- =================================================
     PAGE HEADER
================================================== -->

<div class="page-header">

    <a
        href="user_view?id=<?= (int) $user_id ?>"
        class="manage-back-btn"
    >
        ← View User
    </a>

</div>


<!-- =================================================
     MESSAGE
================================================== -->

<?php if ($message !== ''): ?>

    <div
        class="manage-message manage-message-<?= htmlspecialchars($message_type ?: 'info') ?>"
    >

        <span class="manage-message-icon">

            <?php if ($message_type === 'success'): ?>
                ✓
            <?php elseif ($message_type === 'error'): ?>
                !
            <?php else: ?>
                i
            <?php endif; ?>

        </span>

        <span>
            <?= htmlspecialchars($message) ?>
        </span>

    </div>

<?php endif; ?>


<!-- =================================================
     USER HEADER
================================================== -->

<section class="manage-user-header">

    <div class="manage-user-identity">

        <div class="manage-user-avatar">

            <?php if ($profile_picture !== ''): ?>

                <img
                    src="../uploads/user_profiles/<?= htmlspecialchars($profile_picture) ?>"
                    alt="<?= htmlspecialchars($user['full_name']) ?>"
                >

            <?php else: ?>

                <span>
                    <?= strtoupper(
                        mb_substr(
                            $user['full_name'],
                            0,
                            1
                        )
                    ) ?>
                </span>

            <?php endif; ?>

        </div>


        <div>

            <h2>
                <?= htmlspecialchars($user['full_name']) ?>
            </h2>

            <p>
                <?= htmlspecialchars($user['email']) ?>
            </p>

            <div class="manage-user-meta">

                <span>
                    User ID #<?= (int) $user['user_id'] ?>
                </span>

                <span>•</span>

                <span>
                    <?= htmlspecialchars($user['role'] ?? 'Unknown') ?>
                </span>

            </div>

        </div>

    </div>


    <div class="manage-current-status">

        <span class="manage-status-label">
            Current Status
        </span>

        <span
            class="manage-status status-<?= strtolower(htmlspecialchars($user['status'])) ?>"
        >
            <?= htmlspecialchars($user['status']) ?>
        </span>

    </div>

</section>


<!-- =================================================
     ACCOUNT STATUS
================================================== -->

<section class="manage-card">

    <div class="manage-card-header">

        <div>

            <h2>Account Status</h2>

            <p>
                Control the current access state of this account.
            </p>

        </div>

    </div>


    <?php if ($user['status'] === 'Pending'): ?>

        <div class="manage-empty-inline">

            This account is currently pending identity verification.
            The account will automatically become Active after the
            submitted verification is approved.

        </div>

    <?php else: ?>

        <form
            method="POST"
            class="status-form"
            id="statusForm"
        >

            <input
                type="hidden"
                name="csrf_token"
                value="<?= htmlspecialchars($csrf_token) ?>"
            >

            <input
                type="hidden"
                name="action"
                value="update_status"
            >


            <div class="status-options">

                <?php

                $status_options = [
                    'Active' => [
                        'description' =>
                            'User can normally access the platform.',
                        'class' => 'active'
                    ],

                    'Suspended' => [
                        'description' =>
                            'Temporarily restrict account access.',
                        'class' => 'suspended'
                    ],

                    'Banned' => [
                        'description' =>
                            'Block the account from accessing the platform.',
                        'class' => 'banned'
                    ]
                ];

                ?>


                <?php foreach ($status_options as $status => $details): ?>

                    <label
                        class="status-option <?= $user['status'] === $status ? 'selected' : '' ?>"
                    >

                        <input
                            type="radio"
                            name="status"
                            value="<?= htmlspecialchars($status) ?>"
                            <?= $user['status'] === $status ? 'checked' : '' ?>
                        >

                        <span class="status-option-content">

                            <span class="status-option-title">

                                <span
                                    class="status-dot status-dot-<?= htmlspecialchars($details['class']) ?>"
                                ></span>

                                <?= htmlspecialchars($status) ?>

                            </span>

                            <span class="status-option-description">
                                <?= htmlspecialchars($details['description']) ?>
                            </span>

                        </span>

                    </label>

                <?php endforeach; ?>

            </div>


            <!-- =============================================
                 STATUS CHANGE REASON
            ================================================ -->

            <div class="status-reason-group">

                <label for="status_reason">

                    Reason for Status Change

                    <span
                        class="status-reason-required"
                        id="statusReasonRequired"
                    >
                        *
                    </span>

                </label>


                <textarea
                    id="status_reason"
                    name="status_reason"
                    rows="4"
                    maxlength="255"
                    placeholder="Explain why you are changing this user's account status..."
                ></textarea>


                <div class="status-reason-footer">

                    <span
                        class="form-help"
                        id="statusReasonHelp"
                    >
                        A reason is optional for activating an account.
                    </span>

                    <span
                        class="status-reason-counter"
                        id="statusReasonCounter"
                    >
                        0 / 255
                    </span>

                </div>

            </div>


            <div class="form-actions">

                <button
                    type="submit"
                    class="manage-primary-btn"
                >
                    Update Status
                </button>

            </div>

        </form>

    <?php endif; ?>

</section>


<!-- =================================================
     VERIFICATION MANAGEMENT
================================================== -->

<section class="manage-card">

    <div class="manage-card-header">

        <div>

            <h2>Identity Verification</h2>

            <p>
                Review and manage the user's submitted identity verification.
            </p>

        </div>

        <span
            class="verification-status verification-<?= strtolower(
                str_replace(
                    ' ',
                    '-',
                    $verification_status
                )
            ) ?>"
        >
            <?= htmlspecialchars($verification_status) ?>
        </span>

    </div>


    <?php if ($user['verification_id']): ?>

        <div class="verification-details">

            <div class="verification-detail">

                <span class="detail-label">
                    ID Type
                </span>

                <strong>
                    <?= htmlspecialchars(
                        $user['id_type']
                            ?: 'Not specified'
                    ) ?>
                </strong>

            </div>


            <div class="verification-detail">

                <span class="detail-label">
                    ID Number
                </span>

                <strong>
                    <?= htmlspecialchars(
                        $user['id_number']
                            ?: 'Not provided'
                    ) ?>
                </strong>

            </div>


            <div class="verification-detail">

                <span class="detail-label">
                    Submitted
                </span>

                <strong>
                    <?= htmlspecialchars(
                        $user['verification_created_at']
                            ?: 'Not available'
                    ) ?>
                </strong>

            </div>


            <?php if ($user['verified_at']): ?>

                <div class="verification-detail">

                    <span class="detail-label">
                        Reviewed At
                    </span>

                    <strong>
                        <?= htmlspecialchars(
                            $user['verified_at']
                        ) ?>
                    </strong>

                </div>

            <?php endif; ?>

        </div>


        <!-- =============================================
            ID CARD DOCUMENTS
        ============================================== -->

        <div class="verification-document">

            <div>

                <span class="detail-label">
                    ID Card Front
                </span>

                <strong>
                    <?= $verification_front_document !== ''
                        ? 'Front image uploaded'
                        : 'Front image not uploaded'
                    ?>
                </strong>

            </div>

            <?php if ($verification_front_document !== ''): ?>

                <a
                    href="../uploads/verification/<?= htmlspecialchars(
                        ltrim($verification_front_document, '/\\'),
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="document-btn"
                >

                    <i class="fa-solid fa-image"></i>
                    View Front

                </a>

            <?php endif; ?>

        </div>


        <div class="verification-document">

            <div>

                <span class="detail-label">
                    ID Card Back
                </span>

                <strong>
                    <?= $verification_back_document !== ''
                        ? 'Back image uploaded'
                        : 'Back image not uploaded'
                    ?>
                </strong>

            </div>

            <?php if ($verification_back_document !== ''): ?>

                <a
                    href="../uploads/verification/<?= htmlspecialchars(
                        ltrim($verification_back_document, '/\\'),
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="document-btn"
                >

                    <i class="fa-solid fa-image"></i>
                    View Back

                </a>

            <?php endif; ?>

        </div>


        <?php if ($user['rejection_reason']): ?>

            <div class="rejection-box">

                <strong>
                    Previous Rejection Reason
                </strong>

                <p>
                    <?= htmlspecialchars(
                        $user['rejection_reason']
                    ) ?>
                </p>

            </div>

        <?php endif; ?>


        <?php if ($verification_status === 'Pending'): ?>

            <div class="verification-actions">

                <!-- APPROVE -->

                <form
                    method="POST"
                    class="approve-verification-form"
                >

                    <input
                        type="hidden"
                        name="csrf_token"
                        value="<?= htmlspecialchars($csrf_token) ?>"
                    >

                    <input
                        type="hidden"
                        name="action"
                        value="approve_verification"
                    >

                    <button
                        type="submit"
                        class="manage-success-btn"
                    >
                        ✓ Approve Verification
                    </button>

                </form>


                <!-- REJECT -->

                <button
                    type="button"
                    class="manage-danger-outline-btn"
                    onclick="openRejectModal();"
                >
                    Reject Verification
                </button>

            </div>

        <?php elseif ($verification_status === 'Rejected'): ?>

            <div class="verification-rejected-message">

                <span>!</span>

                <div>

                    <strong>
                        Verification Rejected
                    </strong>

                    <p>
                        The submitted verification was rejected.
                        The user can resubmit their verification.
                    </p>

                </div>

            </div>

        <?php elseif ($verification_status === 'Approved'): ?>

            <div class="verification-approved-message">

                <span>✓</span>

                <div>

                    <strong>
                        Verification Approved
                    </strong>

                    <p>
                        This user's identity verification has already been approved.
                    </p>

                </div>

            </div>

        <?php endif; ?>

    <?php else: ?>

        <div class="manage-empty">

            <div class="empty-icon">
                ✓
            </div>

            <h3>
                No Verification Record
            </h3>

            <p>
                This user has not submitted identity verification.
            </p>

        </div>

    <?php endif; ?>

</section>


<!-- =================================================
     SECURITY ACTIONS
================================================== -->

<section class="manage-card">

    <div class="manage-card-header">

        <div>

            <h2>Security Actions</h2>

            <p>
                Force account sessions to expire when necessary.
            </p>

        </div>

    </div>


    <div class="security-action">

        <div class="security-action-info">

            <div class="security-action-icon">
                ⇥
            </div>

            <div>

                <h3>
                    Log Out All Devices
                </h3>

                <p>
                    Immediately invalidate all active sessions and
                    remembered login tokens for this user.
                </p>

            </div>

        </div>


        <form
            method="POST"
            class="logout-all-form"
            id="logoutAllForm"
        >

            <input
                type="hidden"
                name="csrf_token"
                value="<?= htmlspecialchars($csrf_token) ?>"
            >

            <input
                type="hidden"
                name="action"
                value="logout_all"
            >

            <button
                type="submit"
                class="manage-secondary-btn"
            >
                Log Out All
            </button>

        </form>

    </div>

</section>


<!-- =================================================
     DANGER ZONE
================================================== -->

<section class="manage-card danger-zone">

    <div class="manage-card-header">

        <div>

            <h2>
                Danger Zone
            </h2>

            <p>
                Destructive account management actions.
            </p>

        </div>

    </div>


    <div class="danger-action">

        <div>

            <h3>
                Delete User Account
            </h3>

            <p>
                Soft-delete this user account and immediately
                invalidate all active sessions.
            </p>

            <small>
                The account will not be physically removed from the database.
            </small>

        </div>


        <button
            type="button"
            class="manage-danger-btn"
            onclick="openDeleteModal();"
        >
            Delete Account
        </button>

    </div>

</section>


<!-- =====================================================
     REJECT VERIFICATION MODAL
====================================================== -->

<div
    class="manage-modal"
    id="rejectModal"
    aria-hidden="true"
>

    <div
        class="manage-modal-overlay"
        onclick="closeRejectModal()"
    ></div>


    <div class="manage-modal-box">

        <div class="manage-modal-header">

            <div>

                <h2>
                    Reject Verification
                </h2>

                <p>
                    Provide a reason for rejecting this verification.
                </p>

            </div>

            <button
                type="button"
                class="modal-close"
                onclick="closeRejectModal()"
            >
                ×
            </button>

        </div>


        <form method="POST">

            <input
                type="hidden"
                name="csrf_token"
                value="<?= htmlspecialchars($csrf_token) ?>"
            >

            <input
                type="hidden"
                name="action"
                value="reject_verification"
            >


            <div class="form-group">

                <label for="rejection_reason">
                    Rejection Reason
                </label>

                <textarea
                    id="rejection_reason"
                    name="rejection_reason"
                    rows="4"
                    maxlength="255"
                    placeholder="Explain why the submitted verification was rejected..."
                    required
                ></textarea>

                <span class="form-help">
                    Maximum 255 characters.
                </span>

            </div>


            <div class="modal-actions">

                <button
                    type="button"
                    class="manage-secondary-btn"
                    onclick="closeRejectModal()"
                >
                    Cancel
                </button>

                <button
                    type="submit"
                    class="manage-danger-btn"
                >
                    Reject Verification
                </button>

            </div>

        </form>

    </div>

</div>


<!-- =====================================================
     STATUS CONFIRMATION MODAL
====================================================== -->

<div
    class="manage-modal"
    id="statusConfirmModal"
    aria-hidden="true"
>

    <div
        class="manage-modal-overlay"
        onclick="closeStatusModal()"
    ></div>


    <div class="manage-modal-box">

        <div class="manage-modal-header">

            <div>

                <h2>
                    Confirm Status Change
                </h2>

                <p>
                    Review the status change and reason before confirming.
                </p>

            </div>

            <button
                type="button"
                class="modal-close"
                onclick="closeStatusModal()"
            >
                ×
            </button>

        </div>


        <div class="status-change-preview">

            <div class="status-change-item">

                <span class="detail-label">
                    Current Status
                </span>

                <strong id="statusModalCurrent">
                    -
                </strong>

            </div>


            <div class="status-change-arrow">
                →
            </div>


            <div class="status-change-item">

                <span class="detail-label">
                    New Status
                </span>

                <strong
                    id="statusModalNew"
                    class="status-modal-new"
                >
                    -
                </strong>

            </div>

        </div>


        <div
            class="status-modal-warning"
            id="statusModalWarning"
        ></div>


        <div
            class="status-modal-reason"
            id="statusModalReasonBox"
        >

            <span class="detail-label">
                Reason
            </span>

            <p id="statusModalReason">
                -
            </p>

        </div>


        <div class="modal-actions">

            <button
                type="button"
                class="manage-secondary-btn"
                onclick="closeStatusModal()"
            >
                Cancel
            </button>


            <button
                type="button"
                class="manage-primary-btn"
                id="confirmStatusButton"
            >
                Confirm Status Change
            </button>

        </div>

    </div>

</div>


<!-- =====================================================
     APPROVE VERIFICATION MODAL
====================================================== -->

<div
    class="manage-modal"
    id="approveVerificationModal"
    aria-hidden="true"
>

    <div
        class="manage-modal-overlay"
        onclick="closeApproveModal()"
    ></div>


    <div class="manage-modal-box">

        <div class="manage-modal-header">

            <div>

                <h2>
                    Approve Verification
                </h2>

                <p>
                    Confirm that you have reviewed the submitted
                    identity document.
                </p>

            </div>

            <button
                type="button"
                class="modal-close"
                onclick="closeApproveModal()"
            >
                ×
            </button>

        </div>


        <div class="confirmation-content">

            <div class="confirmation-icon confirmation-success">
                ✓
            </div>

            <div>

                <h3>
                    Approve this verification?
                </h3>

                <p>
                    The user's identity verification will be marked
                    as approved and the action will be recorded in
                    the admin logs.
                </p>

            </div>

        </div>


        <div class="modal-actions">

            <button
                type="button"
                class="manage-secondary-btn"
                onclick="closeApproveModal()"
            >
                Cancel
            </button>

            <button
                type="button"
                class="manage-success-btn"
                onclick="confirmApproveVerification()"
            >
                Approve Verification
            </button>

        </div>

    </div>

</div>


<!-- =====================================================
     LOGOUT ALL DEVICES MODAL
====================================================== -->

<div
    class="manage-modal"
    id="logoutAllModal"
    aria-hidden="true"
>

    <div
        class="manage-modal-overlay"
        onclick="closeLogoutModal()"
    ></div>


    <div class="manage-modal-box">

        <div class="manage-modal-header">

            <div>

                <h2>
                    Log Out All Devices
                </h2>

                <p>
                    Confirm the security session reset.
                </p>

            </div>

            <button
                type="button"
                class="modal-close"
                onclick="closeLogoutModal()"
            >
                ×
            </button>

        </div>


        <div class="confirmation-content">

            <div class="confirmation-icon confirmation-security">
                ⇥
            </div>

            <div>

                <h3>
                    Log out this user everywhere?
                </h3>

                <p>
                    All active sessions and remembered login tokens
                    for this account will be invalidated immediately.
                </p>

            </div>

        </div>


        <div class="modal-actions">

            <button
                type="button"
                class="manage-secondary-btn"
                onclick="closeLogoutModal()"
            >
                Cancel
            </button>

            <button
                type="button"
                class="manage-primary-btn"
                onclick="confirmLogoutAll()"
            >
                Log Out All Devices
            </button>

        </div>

    </div>

</div>


<!-- =====================================================
     DELETE USER MODAL
====================================================== -->

<div
    class="manage-modal"
    id="deleteModal"
    aria-hidden="true"
>

    <div
        class="manage-modal-overlay"
        onclick="closeDeleteModal()"
    ></div>


    <div class="manage-modal-box danger-modal">

        <div class="manage-modal-header">

            <div>

                <h2>
                    Delete User Account
                </h2>

                <p>
                    This action should only be performed when necessary.
                </p>

            </div>

            <button
                type="button"
                class="modal-close"
                onclick="closeDeleteModal()"
            >
                ×
            </button>

        </div>


        <div class="delete-warning">

            <strong>
                You are about to delete:
            </strong>

            <span>
                <?= htmlspecialchars($user['full_name']) ?>
            </span>

            <p>
                The account will be soft-deleted and all active
                sessions will be invalidated.
            </p>

        </div>


        <form method="POST">

            <input
                type="hidden"
                name="csrf_token"
                value="<?= htmlspecialchars($csrf_token) ?>"
            >

            <input
                type="hidden"
                name="action"
                value="delete_user"
            >


            <div class="form-group">

                <label for="delete_confirmation">

                    Type <strong>DELETE</strong> to confirm

                </label>

                <input
                    type="text"
                    id="delete_confirmation"
                    name="delete_confirmation"
                    autocomplete="off"
                    placeholder="DELETE"
                    required
                >

            </div>


            <div class="modal-actions">

                <button
                    type="button"
                    class="manage-secondary-btn"
                    onclick="closeDeleteModal()"
                >
                    Cancel
                </button>

                <button
                    type="submit"
                    class="manage-danger-btn"
                >
                    Delete Account
                </button>

            </div>

        </form>

    </div>

</div>


<script src="assets/js/user_manage.js"></script>

<?php include 'includes/admin_footer.php'; ?>
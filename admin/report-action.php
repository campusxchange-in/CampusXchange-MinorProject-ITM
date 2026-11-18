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
   HELPERS
===================================================== */

function redirectToReport($report_id, $message_type, $message)
{
    $location = 'report-details.php?id=' . (int) $report_id
        . '&' . urlencode($message_type) . '=' . urlencode($message);

    header("Location: " . $location);
    exit;
}


function logAdminAction(
    mysqli $conn,
    int $admin_id,
    string $action_type,
    string $description,
    ?string $target_type = null,
    ?int $target_id = null
): void {

    $ip_address = $_SERVER['REMOTE_ADDR'] ?? null;
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? null;

    $sql = "
        INSERT INTO Admin_Logs (
            admin_id,
            action_type,
            action_description,
            target_type,
            target_id,
            ip_address,
            user_agent
        )
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ";

    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        return;
    }

    $stmt->bind_param(
        "isssiss",
        $admin_id,
        $action_type,
        $description,
        $target_type,
        $target_id,
        $ip_address,
        $user_agent
    );

    $stmt->execute();
    $stmt->close();
}


/* =====================================================
   REQUEST METHOD
===================================================== */

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: reports.php");
    exit;
}


/* =====================================================
   INPUT
===================================================== */

$action = trim($_POST['action'] ?? '');

$report_id = filter_input(
    INPUT_POST,
    'report_id',
    FILTER_VALIDATE_INT
);

if (!$report_id || $report_id <= 0) {
    header("Location: reports.php");
    exit;
}


/* =====================================================
   FETCH REPORT
===================================================== */

$sql = "
    SELECT
        r.report_id,
        r.reporter_id,
        r.reported_user_id,
        r.reported_product_id,
        r.report_type,
        r.report_category,
        r.reason,
        r.description,
        r.status,
        r.reviewed_by,
        r.admin_notes,

        p.title AS product_title,
        p.status AS product_status,
        p.seller_id AS product_seller_id,

        u.full_name AS reported_user_name,
        u.status AS reported_user_status

    FROM Reports r

    LEFT JOIN Products p
        ON p.product_id = r.reported_product_id

    LEFT JOIN Users u
        ON u.user_id = r.reported_user_id

    WHERE r.report_id = ?
      AND r.deleted_at IS NULL

    LIMIT 1
";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    redirectToReport(
        $report_id,
        'error',
        'Unable to process the report.'
    );
}

$stmt->bind_param("i", $report_id);
$stmt->execute();

$result = $stmt->get_result();
$report = $result->fetch_assoc();

$stmt->close();


if (!$report) {
    header("Location: reports.php");
    exit;
}


/* =====================================================
   ONLY PENDING / UNDER REVIEW REPORTS CAN BE ACTIONED
===================================================== */

if (!in_array($report['status'], ['Pending', 'Under Review'], true)) {

    redirectToReport(
        $report_id,
        'error',
        'This report has already been processed.'
    );
}


/* =====================================================
   VALIDATE REPORT TYPE
===================================================== */

$report_type = $report['report_type'];

if (!in_array($report_type, ['Product', 'User'], true)) {

    redirectToReport(
        $report_id,
        'error',
        'Invalid report type.'
    );
}


/* =====================================================
   ACTION: START REVIEW
===================================================== */

if ($action === 'start_review') {

    $conn->begin_transaction();

    try {

        $sql = "
            UPDATE Reports
            SET
                status = 'Under Review',
                reviewed_by = ?
            WHERE report_id = ?
              AND status = 'Pending'
              AND deleted_at IS NULL
        ";

        $stmt = $conn->prepare($sql);

        if (!$stmt) {
            throw new Exception('Failed to prepare review update.');
        }

        $stmt->bind_param(
            "ii",
            $admin_id,
            $report_id
        );

        if (!$stmt->execute() || $stmt->affected_rows !== 1) {
            $stmt->close();

            throw new Exception(
                'This report is no longer pending or could not be placed under review.'
            );
        }

        $stmt->close();


        /* ---------------------------------------------
           ADMIN LOG
        --------------------------------------------- */

        logAdminAction(
            $conn,
            $admin_id,
            'Review Report',
            'Started reviewing report #' . $report_id . '.',
            'Report',
            $report_id
        );


        $conn->commit();

        redirectToReport(
            $report_id,
            'success',
            'Report is now under review.'
        );

    } catch (Throwable $e) {

        $conn->rollback();

        redirectToReport(
            $report_id,
            'error',
            $e->getMessage()
        );
    }
}

/* =====================================================
   ACTION: REJECT REPORT
===================================================== */

if ($action === 'reject_report') {

    $admin_notes = trim($_POST['admin_notes'] ?? '');

    if ($admin_notes === '') {

        redirectToReport(
            $report_id,
            'error',
            'Admin notes are required when rejecting a report.'
        );
    }


    $conn->begin_transaction();

    try {

        $sql = "
            UPDATE Reports
            SET
                status = 'Rejected',
                reviewed_by = ?,
                admin_notes = ?,
                resolved_at = NOW()
            WHERE report_id = ?
              AND status IN ('Pending', 'Under Review')
              AND deleted_at IS NULL
        ";

        $stmt = $conn->prepare($sql);

        if (!$stmt) {
            throw new Exception('Failed to prepare report update.');
        }

        $stmt->bind_param(
            "isi",
            $admin_id,
            $admin_notes,
            $report_id
        );

        if (!$stmt->execute() || $stmt->affected_rows !== 1) {
            $stmt->close();
            throw new Exception('Report could not be rejected.');
        }

        $stmt->close();


        /* ---------------------------------------------
           ADMIN LOG
        --------------------------------------------- */

        logAdminAction(
            $conn,
            $admin_id,
            'Review Report',
            'Rejected report #' . $report_id . '.',
            'Report',
            $report_id
        );


        $conn->commit();

        redirectToReport(
            $report_id,
            'success',
            'Report rejected successfully.'
        );

    } catch (Throwable $e) {

        $conn->rollback();

        redirectToReport(
            $report_id,
            'error',
            $e->getMessage()
        );
    }
}


/* =====================================================
   PRODUCT REPORT ACTION
===================================================== */

if ($action === 'remove_product') {

    if ($report_type !== 'Product') {

        redirectToReport(
            $report_id,
            'error',
            'This report does not concern a product.'
        );
    }


    $product_id = filter_input(
        INPUT_POST,
        'product_id',
        FILTER_VALIDATE_INT
    );

    if (!$product_id || $product_id <= 0) {

        redirectToReport(
            $report_id,
            'error',
            'Invalid product.'
        );
    }


    /*
     * Do NOT trust the submitted product_id.
     * Verify that it is actually the product
     * associated with this report.
     */

    if ((int) $report['reported_product_id'] !== $product_id) {

        redirectToReport(
            $report_id,
            'error',
            'The selected product does not match this report.'
        );
    }


    $removal_reason = trim($_POST['removal_reason'] ?? '');

    if ($removal_reason === '') {

        redirectToReport(
            $report_id,
            'error',
            'A removal reason is required.'
        );
    }


    $conn->begin_transaction();

    try {

        /* ---------------------------------------------
           LOCK PRODUCT
        --------------------------------------------- */

        $sql = "
            SELECT
                product_id,
                title,
                status,
                seller_id
            FROM Products
            WHERE product_id = ?
              AND deleted_at IS NULL
            FOR UPDATE
        ";

        $stmt = $conn->prepare($sql);

        if (!$stmt) {
            throw new Exception('Failed to prepare product query.');
        }

        $stmt->bind_param("i", $product_id);
        $stmt->execute();

        $product_result = $stmt->get_result();
        $product = $product_result->fetch_assoc();

        $stmt->close();


        if (!$product) {
            throw new Exception('Product no longer exists.');
        }


        /*
         * A product should only be removed through
         * moderation while it is publicly listed.
         */

        if ($product['status'] !== 'Active') {

            throw new Exception(
                'This product is no longer active and cannot be removed through this action.'
            );
        }


        /* ---------------------------------------------
           REMOVE PRODUCT
        --------------------------------------------- */

        $sql = "
            UPDATE Products
            SET
                status = 'Removed',
                removed_by_admin_id = ?,
                removal_reason = ?,
                removed_at = NOW()
            WHERE product_id = ?
              AND status = 'Active'
              AND deleted_at IS NULL
        ";

        $stmt = $conn->prepare($sql);

        if (!$stmt) {
            throw new Exception('Failed to prepare product update.');
        }

        $stmt->bind_param(
            "isi",
            $admin_id,
            $removal_reason,
            $product_id
        );

        if (!$stmt->execute() || $stmt->affected_rows !== 1) {
            $stmt->close();
            throw new Exception('Product could not be removed.');
        }

        $stmt->close();


        /* ---------------------------------------------
           RESOLVE REPORT
        --------------------------------------------- */

        $admin_notes = 'Product removed by admin. Reason: ' . $removal_reason;

        $sql = "
            UPDATE Reports
            SET
                status = 'Resolved',
                reviewed_by = ?,
                admin_notes = ?,
                resolved_at = NOW()
            WHERE report_id = ?
              AND status IN ('Pending', 'Under Review')
              AND deleted_at IS NULL
        ";

        $stmt = $conn->prepare($sql);

        if (!$stmt) {
            throw new Exception('Failed to prepare report update.');
        }

        $stmt->bind_param(
            "isi",
            $admin_id,
            $admin_notes,
            $report_id
        );

        if (!$stmt->execute() || $stmt->affected_rows !== 1) {
            $stmt->close();
            throw new Exception('Report could not be resolved.');
        }

        $stmt->close();


        /* ---------------------------------------------
           NOTIFY SELLER
        --------------------------------------------- */

        $seller_id = (int) $product['seller_id'];

        $notification_title = 'Product Removed';
        $notification_message =
            'Your product "' .
            ($product['title'] ?: 'Untitled Product') .
            '" has been removed by an administrator. Reason: ' .
            $removal_reason;

        $notification_type = 'Product';
        $reference_type = 'Product';

        $sql = "
            INSERT INTO Notifications (
                user_id,
                notification_type,
                title,
                message,
                reference_type,
                reference_id
            )
            VALUES (?, ?, ?, ?, ?, ?)
        ";

        $stmt = $conn->prepare($sql);

        if (!$stmt) {
            throw new Exception('Failed to prepare seller notification.');
        }

        $stmt->bind_param(
            "issssi",
            $seller_id,
            $notification_type,
            $notification_title,
            $notification_message,
            $reference_type,
            $product_id
        );

        if (!$stmt->execute()) {
            $stmt->close();
            throw new Exception('Seller notification could not be created.');
        }

        $stmt->close();


        /* ---------------------------------------------
           ADMIN LOG
        --------------------------------------------- */

        logAdminAction(
            $conn,
            $admin_id,
            'Manage Product',
            'Removed product #' . $product_id .
            ' after reviewing report #' . $report_id .
            '. Reason: ' . $removal_reason,
            'Product',
            $product_id
        );

        logAdminAction(
            $conn,
            $admin_id,
            'Review Report',
            'Resolved report #' . $report_id .
            ' by removing product #' . $product_id . '.',
            'Report',
            $report_id
        );


        $conn->commit();

        redirectToReport(
            $report_id,
            'success',
            'Product removed and report resolved successfully.'
        );

    } catch (Throwable $e) {

        $conn->rollback();

        redirectToReport(
            $report_id,
            'error',
            $e->getMessage()
        );
    }
}


/* =====================================================
   USER MODERATION
===================================================== */

if (
    $action === 'issue_warning' ||
    $action === 'temporary_ban' ||
    $action === 'permanent_ban'
) {

    if ($report_type !== 'User') {

        redirectToReport(
            $report_id,
            'error',
            'This report does not concern a user.'
        );
    }


    $user_id = filter_input(
        INPUT_POST,
        'user_id',
        FILTER_VALIDATE_INT
    );

    if (!$user_id || $user_id <= 0) {

        redirectToReport(
            $report_id,
            'error',
            'Invalid user.'
        );
    }


    /*
     * Verify that the submitted user is the
     * actual reported user.
     */

    if ((int) $report['reported_user_id'] !== $user_id) {

        redirectToReport(
            $report_id,
            'error',
            'The selected user does not match this report.'
        );
    }


    $reason = trim($_POST['reason'] ?? '');
    $description = trim($_POST['description'] ?? '');

    if ($reason === '') {

        redirectToReport(
            $report_id,
            'error',
            'A moderation reason is required.'
        );
    }


    /* =================================================
       ISSUE WARNING
    ================================================= */

    if ($action === 'issue_warning') {

        $conn->begin_transaction();

        try {

            /*
             * Warning does not suspend or ban the account.
             * It is recorded in User_Bans as moderation history.
             */

            $ban_type = 'Warning';
            $status = 'Expired';

            $sql = "
                INSERT INTO User_Bans (
                    user_id,
                    report_id,
                    admin_id,
                    ban_type,
                    reason,
                    description,
                    start_date,
                    status
                )
                VALUES (?, ?, ?, ?, ?, ?, NOW(), ?)
            ";

            $stmt = $conn->prepare($sql);

            if (!$stmt) {
                throw new Exception('Failed to prepare warning.');
            }

            $stmt->bind_param(
                "iiissss",
                $user_id,
                $report_id,
                $admin_id,
                $ban_type,
                $reason,
                $description,
                $status
            );

            if (!$stmt->execute()) {
                $stmt->close();
                throw new Exception('Warning could not be issued.');
            }

            $ban_id = $stmt->insert_id;

            $stmt->close();


            /*
             * Keep user Active for a warning.
             */

            $sql = "
                UPDATE Users
                SET status = 'Active'
                WHERE user_id = ?
            ";

            $stmt = $conn->prepare($sql);

            if (!$stmt) {
                throw new Exception('Failed to prepare user update.');
            }

            $stmt->bind_param("i", $user_id);

            if (!$stmt->execute()) {
                $stmt->close();
                throw new Exception('User status could not be updated.');
            }

            $stmt->close();


            /* -----------------------------------------
               RESOLVE REPORT
            ----------------------------------------- */

            $admin_notes =
                'Warning issued. Reason: ' . $reason;

            $sql = "
                UPDATE Reports
                SET
                    status = 'Resolved',
                    reviewed_by = ?,
                    admin_notes = ?,
                    resolved_at = NOW()
                WHERE report_id = ?
                  AND status IN ('Pending', 'Under Review')
                  AND deleted_at IS NULL
            ";

            $stmt = $conn->prepare($sql);

            if (!$stmt) {
                throw new Exception('Failed to prepare report update.');
            }

            $stmt->bind_param(
                "isi",
                $admin_id,
                $admin_notes,
                $report_id
            );

            if (!$stmt->execute() || $stmt->affected_rows !== 1) {
                $stmt->close();
                throw new Exception('Report could not be resolved.');
            }

            $stmt->close();


            /* -----------------------------------------
               NOTIFICATION
            ----------------------------------------- */

            $notification_type = 'Ban';
            $notification_title = 'Moderation Warning';
            $notification_message =
                'A moderation warning has been issued to your account. Reason: ' .
                $reason;

            $reference_type = 'Ban';

            $sql = "
                INSERT INTO Notifications (
                    user_id,
                    notification_type,
                    title,
                    message,
                    reference_type,
                    reference_id
                )
                VALUES (?, ?, ?, ?, ?, ?)
            ";

            $stmt = $conn->prepare($sql);

            if (!$stmt) {
                throw new Exception('Failed to prepare notification.');
            }

            $stmt->bind_param(
                "issssi",
                $user_id,
                $notification_type,
                $notification_title,
                $notification_message,
                $reference_type,
                $ban_id
            );

            if (!$stmt->execute()) {
                $stmt->close();
                throw new Exception('Notification could not be created.');
            }

            $stmt->close();


            /* -----------------------------------------
               ADMIN LOG
            ----------------------------------------- */

            logAdminAction(
                $conn,
                $admin_id,
                'Issue Ban',
                'Issued a warning to user #' . $user_id .
                ' after reviewing report #' . $report_id .
                '. Reason: ' . $reason,
                'User',
                $user_id
            );

            logAdminAction(
                $conn,
                $admin_id,
                'Review Report',
                'Resolved report #' . $report_id .
                ' by issuing a warning to user #' . $user_id . '.',
                'Report',
                $report_id
            );


            $conn->commit();

            redirectToReport(
                $report_id,
                'success',
                'Warning issued and report resolved successfully.'
            );

        } catch (Throwable $e) {

            $conn->rollback();

            redirectToReport(
                $report_id,
                'error',
                $e->getMessage()
            );
        }
    }


    /* =================================================
       TEMPORARY BAN
    ================================================= */

    if ($action === 'temporary_ban') {

        $duration_days = filter_input(
            INPUT_POST,
            'duration_days',
            FILTER_VALIDATE_INT
        );

        if (
            !$duration_days ||
            $duration_days < 1 ||
            $duration_days > 365
        ) {

            redirectToReport(
                $report_id,
                'error',
                'Temporary ban duration must be between 1 and 365 days.'
            );
        }


        $conn->begin_transaction();

        try {

            /* -----------------------------------------
               VERIFY USER
            ----------------------------------------- */

            $sql = "
                SELECT
                    user_id,
                    full_name,
                    status
                FROM Users
                WHERE user_id = ?
                LIMIT 1
                FOR UPDATE
            ";

            $stmt = $conn->prepare($sql);

            if (!$stmt) {
                throw new Exception('Failed to prepare user query.');
            }

            $stmt->bind_param("i", $user_id);
            $stmt->execute();

            $user_result = $stmt->get_result();
            $user = $user_result->fetch_assoc();

            $stmt->close();


            if (!$user) {
                throw new Exception('User no longer exists.');
            }


            /* -----------------------------------------
               EXPIRE PREVIOUS ACTIVE TEMPORARY BANS
            ----------------------------------------- */

            $sql = "
                UPDATE User_Bans
                SET
                    status = 'Expired'
                WHERE user_id = ?
                  AND ban_type = 'Temporary'
                  AND status = 'Active'
                  AND end_date <= NOW()
            ";

            $stmt = $conn->prepare($sql);

            if (!$stmt) {
                throw new Exception('Failed to prepare previous ban update.');
            }

            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $stmt->close();


            /* -----------------------------------------
               START / END DATE
            ----------------------------------------- */

            $start_date = date('Y-m-d H:i:s');
            $end_date = date(
                'Y-m-d H:i:s',
                strtotime('+' . $duration_days . ' days')
            );

            $ban_type = 'Temporary';
            $ban_status = 'Active';

            /* -----------------------------------------
               CREATE BAN
            ----------------------------------------- */

            $sql = "
                INSERT INTO User_Bans (
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
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ";

            $stmt = $conn->prepare($sql);

            if (!$stmt) {
                throw new Exception('Failed to prepare temporary ban.');
            }

            $stmt->bind_param(
                "iiissssss",
                $user_id,
                $report_id,
                $admin_id,
                $ban_type,
                $reason,
                $description,
                $start_date,
                $end_date,
                $ban_status
            );

            if (!$stmt->execute()) {
                $stmt->close();
                throw new Exception('Temporary ban could not be issued.');
            }

            $ban_id = $stmt->insert_id;

            $stmt->close();


            /* -----------------------------------------
               SUSPEND USER
            ----------------------------------------- */

            $sql = "
                UPDATE Users
                SET status = 'Suspended'
                WHERE user_id = ?
            ";

            $stmt = $conn->prepare($sql);

            if (!$stmt) {
                throw new Exception('Failed to prepare user suspension.');
            }

            $stmt->bind_param("i", $user_id);

            if (!$stmt->execute()) {
                $stmt->close();
                throw new Exception('User could not be suspended.');
            }

            $stmt->close();


            /* -----------------------------------------
               INVALIDATE EXISTING SESSIONS
            ----------------------------------------- */

            $sql = "
                UPDATE Users
                SET session_version = session_version + 1
                WHERE user_id = ?
            ";

            $stmt = $conn->prepare($sql);

            if (!$stmt) {
                throw new Exception('Failed to invalidate user sessions.');
            }

            $stmt->bind_param("i", $user_id);

            if (!$stmt->execute()) {
                $stmt->close();
                throw new Exception('User sessions could not be invalidated.');
            }

            $stmt->close();


            /* -----------------------------------------
               RESOLVE REPORT
            ----------------------------------------- */

            $admin_notes =
                'Temporary ban issued for ' .
                $duration_days .
                ' day(s). Reason: ' .
                $reason;

            $sql = "
                UPDATE Reports
                SET
                    status = 'Resolved',
                    reviewed_by = ?,
                    admin_notes = ?,
                    resolved_at = NOW()
                WHERE report_id = ?
                  AND status IN ('Pending', 'Under Review')
                  AND deleted_at IS NULL
            ";

            $stmt = $conn->prepare($sql);

            if (!$stmt) {
                throw new Exception('Failed to prepare report resolution.');
            }

            $stmt->bind_param(
                "isi",
                $admin_id,
                $admin_notes,
                $report_id
            );

            if (!$stmt->execute() || $stmt->affected_rows !== 1) {
                $stmt->close();
                throw new Exception('Report could not be resolved.');
            }

            $stmt->close();


            /* -----------------------------------------
               NOTIFICATION
            ----------------------------------------- */

            $notification_type = 'Ban';
            $notification_title = 'Temporary Account Suspension';

            $notification_message =
                'Your account has been temporarily suspended for ' .
                $duration_days .
                ' day(s). Reason: ' .
                $reason;

            $reference_type = 'Ban';

            $sql = "
                INSERT INTO Notifications (
                    user_id,
                    notification_type,
                    title,
                    message,
                    reference_type,
                    reference_id
                )
                VALUES (?, ?, ?, ?, ?, ?)
            ";

            $stmt = $conn->prepare($sql);

            if (!$stmt) {
                throw new Exception('Failed to prepare notification.');
            }

            $stmt->bind_param(
                "issssi",
                $user_id,
                $notification_type,
                $notification_title,
                $notification_message,
                $reference_type,
                $ban_id
            );

            if (!$stmt->execute()) {
                $stmt->close();
                throw new Exception('Notification could not be created.');
            }

            $stmt->close();


            /* -----------------------------------------
               ADMIN LOG
            ----------------------------------------- */

            logAdminAction(
                $conn,
                $admin_id,
                'Issue Ban',
                'Temporarily suspended user #' . $user_id .
                ' for ' . $duration_days .
                ' day(s) after reviewing report #' . $report_id .
                '. Reason: ' . $reason,
                'User',
                $user_id
            );

            logAdminAction(
                $conn,
                $admin_id,
                'Review Report',
                'Resolved report #' . $report_id .
                ' by issuing a temporary ban to user #' . $user_id . '.',
                'Report',
                $report_id
            );


            $conn->commit();

            redirectToReport(
                $report_id,
                'success',
                'Temporary ban issued and report resolved successfully.'
            );

        } catch (Throwable $e) {

            $conn->rollback();

            redirectToReport(
                $report_id,
                'error',
                $e->getMessage()
            );
        }
    }


    /* =================================================
       PERMANENT BAN
    ================================================= */

    if ($action === 'permanent_ban') {

        $conn->begin_transaction();

        try {

            /* -----------------------------------------
               LOCK USER
            ----------------------------------------- */

            $sql = "
                SELECT
                    user_id,
                    full_name,
                    status
                FROM Users
                WHERE user_id = ?
                LIMIT 1
                FOR UPDATE
            ";

            $stmt = $conn->prepare($sql);

            if (!$stmt) {
                throw new Exception('Failed to prepare user query.');
            }

            $stmt->bind_param("i", $user_id);
            $stmt->execute();

            $user_result = $stmt->get_result();
            $user = $user_result->fetch_assoc();

            $stmt->close();


            if (!$user) {
                throw new Exception('User no longer exists.');
            }


            $ban_type = 'Permanent';
            $ban_status = 'Active';

            /* -----------------------------------------
               CREATE PERMANENT BAN
            ----------------------------------------- */

            $sql = "
                INSERT INTO User_Bans (
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
                VALUES (?, ?, ?, ?, ?, ?, NOW(), NULL, ?)
            ";

            $stmt = $conn->prepare($sql);

            if (!$stmt) {
                throw new Exception('Failed to prepare permanent ban.');
            }

            $stmt->bind_param(
                "iiissss",
                $user_id,
                $report_id,
                $admin_id,
                $ban_type,
                $reason,
                $description,
                $ban_status
            );

            if (!$stmt->execute()) {
                $stmt->close();
                throw new Exception('Permanent ban could not be issued.');
            }

            $ban_id = $stmt->insert_id;

            $stmt->close();


            /* -----------------------------------------
               BAN USER
            ----------------------------------------- */

            $sql = "
                UPDATE Users
                SET status = 'Banned'
                WHERE user_id = ?
            ";

            $stmt = $conn->prepare($sql);

            if (!$stmt) {
                throw new Exception('Failed to prepare user ban.');
            }

            $stmt->bind_param("i", $user_id);

            if (!$stmt->execute()) {
                $stmt->close();
                throw new Exception('User could not be banned.');
            }

            $stmt->close();


            /* -----------------------------------------
               INVALIDATE EXISTING SESSIONS
            ----------------------------------------- */

            $sql = "
                UPDATE Users
                SET session_version = session_version + 1
                WHERE user_id = ?
            ";

            $stmt = $conn->prepare($sql);

            if (!$stmt) {
                throw new Exception('Failed to invalidate user sessions.');
            }

            $stmt->bind_param("i", $user_id);

            if (!$stmt->execute()) {
                $stmt->close();
                throw new Exception('User sessions could not be invalidated.');
            }

            $stmt->close();


            /* -----------------------------------------
               RESOLVE REPORT
            ----------------------------------------- */

            $admin_notes =
                'Permanent ban issued. Reason: ' .
                $reason;

            $sql = "
                UPDATE Reports
                SET
                    status = 'Resolved',
                    reviewed_by = ?,
                    admin_notes = ?,
                    resolved_at = NOW()
                WHERE report_id = ?
                  AND status IN ('Pending', 'Under Review')
                  AND deleted_at IS NULL
            ";

            $stmt = $conn->prepare($sql);

            if (!$stmt) {
                throw new Exception('Failed to prepare report resolution.');
            }

            $stmt->bind_param(
                "isi",
                $admin_id,
                $admin_notes,
                $report_id
            );

            if (!$stmt->execute() || $stmt->affected_rows !== 1) {
                $stmt->close();
                throw new Exception('Report could not be resolved.');
            }

            $stmt->close();


            /* -----------------------------------------
               NOTIFICATION
            ----------------------------------------- */

            $notification_type = 'Ban';
            $notification_title = 'Account Permanently Banned';

            $notification_message =
                'Your CampusXchange account has been permanently banned. Reason: ' .
                $reason;

            $reference_type = 'Ban';

            $sql = "
                INSERT INTO Notifications (
                    user_id,
                    notification_type,
                    title,
                    message,
                    reference_type,
                    reference_id
                )
                VALUES (?, ?, ?, ?, ?, ?)
            ";

            $stmt = $conn->prepare($sql);

            if (!$stmt) {
                throw new Exception('Failed to prepare notification.');
            }

            $stmt->bind_param(
                "issssi",
                $user_id,
                $notification_type,
                $notification_title,
                $notification_message,
                $reference_type,
                $ban_id
            );

            if (!$stmt->execute()) {
                $stmt->close();
                throw new Exception('Notification could not be created.');
            }

            $stmt->close();


            /* -----------------------------------------
               ADMIN LOG
            ----------------------------------------- */

            logAdminAction(
                $conn,
                $admin_id,
                'Issue Ban',
                'Permanently banned user #' . $user_id .
                ' after reviewing report #' . $report_id .
                '. Reason: ' . $reason,
                'User',
                $user_id
            );

            logAdminAction(
                $conn,
                $admin_id,
                'Review Report',
                'Resolved report #' . $report_id .
                ' by permanently banning user #' . $user_id . '.',
                'Report',
                $report_id
            );


            $conn->commit();

            redirectToReport(
                $report_id,
                'success',
                'Permanent ban issued and report resolved successfully.'
            );

        } catch (Throwable $e) {

            $conn->rollback();

            redirectToReport(
                $report_id,
                'error',
                $e->getMessage()
            );
        }
    }
}


/* =====================================================
   UNKNOWN ACTION
===================================================== */

redirectToReport(
    $report_id,
    'error',
    'Invalid moderation action.'
);

?>
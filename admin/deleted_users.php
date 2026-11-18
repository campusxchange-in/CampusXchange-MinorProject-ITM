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

$page_title       = 'Deleted Users | CampusXchange';
$page_heading     = 'Deleted Users';
$page_description = 'View and restore soft-deleted user accounts.';
$active_page      = 'deletedusers';
$page_css         = 'deleted_users.css';


/* =====================================================
   GENERATE CSRF TOKEN
===================================================== */

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(
        random_bytes(32)
    );
}

$csrf_token = $_SESSION['csrf_token'];


/* =====================================================
   HELPER
===================================================== */

function redirect_with_message($type, $message)
{
    $url = "deleted_users"
         . "?message_type=" . urlencode($type)
         . "&message=" . urlencode($message);

    header("Location: " . $url);
    exit;
}


/* =====================================================
   HANDLE RESTORE
===================================================== */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $action = $_POST['action'] ?? '';


    /* =================================================
       CSRF CHECK
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
            'error',
            'Invalid security token. Please try again.'
        );
    }


    /* =================================================
       RESTORE USER
    ================================================= */

    if ($action === 'restore_user') {

        $user_id = filter_input(
            INPUT_POST,
            'user_id',
            FILTER_VALIDATE_INT
        );

        if (!$user_id) {

            redirect_with_message(
                'error',
                'Invalid user account.'
            );
        }


        /* =============================================
           FETCH DELETED USER
        ============================================= */

        $stmt = $conn->prepare("
            SELECT
                user_id,
                full_name,
                email,
                role,
                status,
                deleted_at
            FROM Users
            WHERE user_id = ?
            LIMIT 1
        ");

        $stmt->bind_param(
            "i",
            $user_id
        );

        $stmt->execute();

        $result = $stmt->get_result();

        $deleted_user = $result->fetch_assoc();

        $stmt->close();


        if (!$deleted_user) {

            redirect_with_message(
                'error',
                'User account could not be found.'
            );
        }


        /* =============================================
           VERIFY ACCOUNT IS ACTUALLY DELETED
        ============================================= */

        if ($deleted_user['deleted_at'] === null) {

            redirect_with_message(
                'info',
                'This user account is already active in the system.'
            );
        }


        /* =============================================
           TRANSACTION
        ============================================= */

        $conn->begin_transaction();

        try {

            /* =========================================
               RESTORE USER
            ========================================= */

            /*
             * Restored users are placed into Pending
             * rather than immediately becoming Active.
             *
             * This allows an administrator to review
             * the account again before granting access.
             */

            $restored_status = 'Banned';

            $stmt = $conn->prepare("
                UPDATE Users
                SET deleted_at = NULL,
                    status = ?,
                    session_version = session_version + 1,
                    updated_at = CURRENT_TIMESTAMP
                WHERE user_id = ?
                  AND deleted_at IS NOT NULL
            ");

            $stmt->bind_param(
                "si",
                $restored_status,
                $user_id
            );

            if (!$stmt->execute()) {

                throw new Exception(
                    'Unable to restore user account.'
                );
            }


            if ($stmt->affected_rows !== 1) {

                throw new Exception(
                    'User account could not be restored.'
                );
            }

            $stmt->close();


            /* =========================================
               KEEP REMEMBER TOKENS REVOKED
            ========================================= */

            /*
             * We intentionally do NOT restore old
             * Remember_Tokens.
             *
             * Any previous login session must remain
             * invalid after deletion.
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
                    'Unable to secure restored account sessions.'
                );
            }

            $stmt->close();


            /* =========================================
               ADMIN LOG
            ========================================= */

            $action_type = 'Restore';

            $description =
                "Restored soft-deleted user account #{$user_id} "
                . "({$deleted_user['full_name']}). "
                . "Account status set to Banned.";

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
                    'Unable to create restore admin log.'
                );
            }

            $stmt->close();


            /* =========================================
               USER NOTIFICATION
            ========================================= */

            /*
             * The account was deleted, so the user may
             * not currently be able to access it.
             *
             * We still create the notification so it
             * exists when the account becomes accessible.
             */

            $notification_type = 'Security';

            $title = 'Account Restored';

            $notification_message =
                'Your CampusXchange account has been restored '
                . 'by an administrator. Your account is currently '
                . 'banned status.';

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
                    'Unable to create restoration notification.'
                );
            }

            $stmt->close();


            /* =========================================
               COMMIT
            ========================================= */

            $conn->commit();


            redirect_with_message(
                'success',
                'User account restored successfully and placed in Banned status.'
            );


        } catch (Exception $e) {

            $conn->rollback();

            redirect_with_message(
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
            'error',
            'Invalid user management action.'
        );
    }
}


/* =====================================================
   SEARCH
===================================================== */

$search = trim(
    $_GET['search'] ?? ''
);


/* =====================================================
   FETCH DELETED USERS
===================================================== */

if ($search !== '') {

    $search_term = '%' . $search . '%';

    $stmt = $conn->prepare("
        SELECT
            user_id,
            full_name,
            email,
            role,
            status,
            created_at,
            updated_at,
            deleted_at
        FROM Users
        WHERE deleted_at IS NOT NULL
          AND (
                full_name LIKE ?
                OR email LIKE ?
                OR CAST(user_id AS CHAR) LIKE ?
              )
        ORDER BY deleted_at DESC
    ");

    $stmt->bind_param(
        "sss",
        $search_term,
        $search_term,
        $search_term
    );

} else {

    $stmt = $conn->prepare("
        SELECT
            user_id,
            full_name,
            email,
            role,
            status,
            created_at,
            updated_at,
            deleted_at
        FROM Users
        WHERE deleted_at IS NOT NULL
        ORDER BY deleted_at DESC
    ");
}


$stmt->execute();

$result = $stmt->get_result();

$deleted_users = [];

while ($row = $result->fetch_assoc()) {
    $deleted_users[] = $row;
}

$stmt->close();


/* =====================================================
   MESSAGE
===================================================== */

$message_type =
    $_GET['message_type'] ?? '';

$message =
    $_GET['message'] ?? '';


?>


<?php include 'includes/admin_header.php'; ?>


<!-- =====================================================
     PAGE HEADER
====================================================== -->

<div class="deleted-users-page-header">


    <a
        href="users"
        class="deleted-back-btn"
    >
        ← Back to Users
    </a>

</div>


<!-- =====================================================
     MESSAGE
====================================================== -->

<?php if ($message !== ''): ?>

    <div
        class="deleted-message deleted-message-<?= htmlspecialchars(
            $message_type ?: 'info'
        ) ?>"
    >

        <span class="deleted-message-icon">

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


<!-- =====================================================
     PAGE CARD
====================================================== -->

<section class="deleted-users-card">


    <!-- =================================================
         TOOLBAR
    ================================================== -->

    <div class="deleted-users-toolbar">

        <div class="deleted-users-count">

            <strong>
                <?= count($deleted_users) ?>
            </strong>

            <span>
                Deleted
                <?= count($deleted_users) === 1
                    ? 'Account'
                    : 'Accounts'
                ?>
            </span>

        </div>


        <form
            method="GET"
            class="deleted-search-form"
        >

            <div class="deleted-search-box">

                <span class="deleted-search-icon">
                    ⌕
                </span>

                <input
                    type="search"
                    name="search"
                    value="<?= htmlspecialchars($search) ?>"
                    placeholder="Search by name, email or user ID..."
                    autocomplete="off"
                >

                <?php if ($search !== ''): ?>

                    <a
                        href="deleted_users"
                        class="deleted-search-clear"
                        aria-label="Clear search"
                    >
                        ×
                    </a>

                <?php endif; ?>

            </div>

            <button
                type="submit"
                class="deleted-search-btn"
            >
                Search
            </button>

        </form>

    </div>


    <!-- =================================================
         TABLE
    ================================================== -->

    <?php if (!empty($deleted_users)): ?>

        <div class="deleted-table-wrapper">

            <table class="deleted-users-table">

                <thead>

                    <tr>

                        <th>
                            User
                        </th>

                        <th>
                            Role
                        </th>

                        <th>
                            Previous Status
                        </th>

                        <th>
                            Deleted At
                        </th>

                        <th>
                            Actions
                        </th>

                    </tr>

                </thead>


                <tbody>

                    <?php foreach ($deleted_users as $deleted_user): ?>

                        <?php
                        $initial = strtoupper(
                            mb_substr(
                                $deleted_user['full_name'],
                                0,
                                1
                            )
                        );
                        ?>

                        <tr>

                            <!-- USER -->

                            <td>

                                <div class="deleted-user-cell">

                                    <div class="deleted-user-avatar">

                                        <?= htmlspecialchars($initial) ?>

                                    </div>


                                    <div class="deleted-user-info">

                                        <strong>
                                            <?= htmlspecialchars(
                                                $deleted_user['full_name']
                                            ) ?>
                                        </strong>

                                        <span>
                                            <?= htmlspecialchars(
                                                $deleted_user['email']
                                            ) ?>
                                        </span>

                                        <small>
                                            User ID #<?= (int) $deleted_user['user_id'] ?>
                                        </small>

                                    </div>

                                </div>

                            </td>


                            <!-- ROLE -->

                            <td>

                                <span class="deleted-role">
                                    <?= htmlspecialchars(
                                        $deleted_user['role']
                                    ) ?>
                                </span>

                            </td>


                            <!-- PREVIOUS STATUS -->

                            <td>

                                <span
                                    class="deleted-status deleted-status-<?= strtolower(
                                        htmlspecialchars(
                                            $deleted_user['status']
                                        )
                                    ) ?>"
                                >
                                    <?= htmlspecialchars(
                                        $deleted_user['status']
                                    ) ?>
                                </span>

                            </td>


                            <!-- DELETED AT -->

                            <td>

                                <div class="deleted-date">

                                    <strong>
                                        <?= htmlspecialchars(
                                            date(
                                                'd M Y',
                                                strtotime(
                                                    $deleted_user['deleted_at']
                                                )
                                            )
                                        ) ?>
                                    </strong>

                                    <span>
                                        <?= htmlspecialchars(
                                            date(
                                                'h:i A',
                                                strtotime(
                                                    $deleted_user['deleted_at']
                                                )
                                            )
                                        ) ?>
                                    </span>

                                </div>

                            </td>


                            <!-- ACTION -->

                            <td>

                                <button
                                    type="button"
                                    class="deleted-restore-btn"
                                    data-user-id="<?= (int) $deleted_user['user_id'] ?>"
                                    data-user-name="<?= htmlspecialchars(
                                        $deleted_user['full_name'],
                                        ENT_QUOTES
                                    ) ?>"
                                >
                                    ↻ Restore
                                </button>

                            </td>

                        </tr>

                    <?php endforeach; ?>

                </tbody>

            </table>

        </div>


    <?php else: ?>


        <!-- =================================================
             EMPTY STATE
        ================================================== -->

        <div class="deleted-empty">

            <div class="deleted-empty-icon">
                ✓
            </div>

            <?php if ($search !== ''): ?>

                <h2>
                    No Deleted Users Found
                </h2>

                <p>
                    No deleted account matched
                    <strong>
                        <?= htmlspecialchars($search) ?>
                    </strong>.
                </p>

                <a
                    href="deleted_users"
                    class="deleted-empty-btn"
                >
                    View All Deleted Users
                </a>

            <?php else: ?>

                <h2>
                    No Deleted Users
                </h2>

                <p>
                    There are currently no soft-deleted
                    user accounts.
                </p>

                <a
                    href="users"
                    class="deleted-empty-btn"
                >
                    ← Back to Users
                </a>

            <?php endif; ?>

        </div>

    <?php endif; ?>


</section>


<!-- =====================================================
     RESTORE CONFIRMATION MODAL
====================================================== -->

<div
    class="deleted-modal"
    id="restoreModal"
    aria-hidden="true"
>

    <div
        class="deleted-modal-overlay"
        data-close-restore
    ></div>


    <div
        class="deleted-modal-box"
        role="dialog"
        aria-modal="true"
        aria-labelledby="restoreModalTitle"
    >

        <div class="deleted-modal-header">

            <div>

                <h2 id="restoreModalTitle">
                    Restore User Account
                </h2>

                <p>
                    Confirm account restoration.
                </p>

            </div>


            <button
                type="button"
                class="deleted-modal-close"
                data-close-restore
                aria-label="Close"
            >
                ×
            </button>

        </div>


        <div class="restore-confirmation">

            <div class="restore-confirmation-icon">
                ↻
            </div>


            <div>

                <h3>
                    Restore this account?
                </h3>

                <p>
                    <strong id="restoreUserName">
                        User
                    </strong>
                    will be restored from soft deletion.
                </p>

            </div>

        </div>


        <div class="restore-info-box">

            <strong>
                What happens after restoration?
            </strong>

            <ul>

                <li>
                    The account will no longer be marked as deleted.
                </li>

                <li>
                    The account will be placed in
                    <strong>Pending</strong> status.
                </li>

                <li>
                    Previous login sessions will remain invalidated.
                </li>

                <li>
                    The user can be reviewed and activated by an administrator.
                </li>

            </ul>

        </div>


        <form
            method="POST"
            id="restoreForm"
        >

            <input
                type="hidden"
                name="csrf_token"
                value="<?= htmlspecialchars($csrf_token) ?>"
            >

            <input
                type="hidden"
                name="action"
                value="restore_user"
            >

            <input
                type="hidden"
                name="user_id"
                id="restoreUserId"
                value=""
            >


            <div class="deleted-modal-actions">

                <button
                    type="button"
                    class="deleted-cancel-btn"
                    data-close-restore
                >
                    Cancel
                </button>

                <button
                    type="submit"
                    class="deleted-confirm-restore-btn"
                >
                    ↻ Restore Account
                </button>

            </div>

        </form>

    </div>

</div>


<script src="assets/js/deleted_users.js"></script>


<?php include 'includes/admin_footer.php'; ?>
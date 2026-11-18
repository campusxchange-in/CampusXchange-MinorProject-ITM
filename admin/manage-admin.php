
<?php

/* =====================================================
   AUTHENTICATION
===================================================== */

include 'includes/auth_check.php';
include '../server/database/db_connect.php';
include 'includes/main_admin_check.php';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}


/* =====================================================
   PAGE SETTINGS
===================================================== */

$page_title = 'Manage Admin | CampusXchange';
$page_heading = 'Manage Admin';
$active_page = 'admins';
$page_css = 'manage-admin.css';


/* =====================================================
   GET ADMIN
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
        admin_id,
        full_name,
        email,
        admin_role,
        status,
        created_at

    FROM Admins

    WHERE admin_id = ?
      AND deleted_at IS NULL

    LIMIT 1
");

$stmt->bind_param("i", $admin_id);
$stmt->execute();

$admin = $stmt->get_result()->fetch_assoc();

$stmt->close();


/* =====================================================
   VALIDATE ADMIN
===================================================== */

if (!$admin) {
    header("Location: admins.php");
    exit;
}


/* =====================================================
   PROTECT MAIN ADMINS
===================================================== */

if ($admin['admin_role'] !== 'Sub Admin') {
    header("Location: admin-details.php?id=" . $admin_id);
    exit;
}


/* =====================================================
   FETCH RESTRICTION HISTORY
===================================================== */

$history_stmt = $conn->prepare("
    SELECT
        r.action_type,
        r.reason,
        r.created_at,
        performer.full_name AS performed_by_name

    FROM Admin_Restrictions r

    INNER JOIN Admins performer
        ON performer.admin_id = r.performed_by

    WHERE r.admin_id = ?

    ORDER BY r.created_at DESC

    LIMIT 20
");

$history_stmt->bind_param("i", $admin_id);
$history_stmt->execute();

$history = $history_stmt->get_result();


function manage_escape($value)
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



    <!-- ADMIN SUMMARY -->

    <div class="manage-admin-summary">

        <div class="manage-admin-avatar">
            <i class="fa-solid fa-user"></i>
        </div>

        <div>

            <h2>
                <?= manage_escape($admin['full_name']) ?>
            </h2>

            <p>
                <?= manage_escape($admin['email']) ?>
            </p>

            <span class="admin-status-badge
                <?= strtolower($admin['status']) ?>">

                <?= manage_escape($admin['status']) ?>

            </span>

        </div>

    </div>

    <?php if (!empty($_SESSION['admin_message'])): ?>

        <div class="admin-action-message <?= htmlspecialchars(
            $_SESSION['admin_message']['type'],
            ENT_QUOTES,
            'UTF-8'
        ) ?>">

            <i class="fa-solid fa-circle-info"></i>

            <?= htmlspecialchars(
                $_SESSION['admin_message']['message'],
                ENT_QUOTES,
                'UTF-8'
            ) ?>

        </div>

        <?php unset($_SESSION['admin_message']); ?>

    <?php endif; ?>


    <!-- ACTIONS -->

    <div class="manage-admin-card">

        <div class="manage-admin-card-header">

            <i class="fa-solid fa-user-shield"></i>

            <h3>Account Actions</h3>

        </div>


        <p class="manage-admin-description">

            Restrict or restore this Sub Admin's access
            to the CampusXchange admin panel.

        </p>


        <?php if ($admin['status'] === 'Active'): ?>

            <!-- SUSPEND -->

            <form
                action="admin-action.php"
                method="POST"
                class="manage-admin-action-form"
            >

                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= htmlspecialchars(
                        $_SESSION['csrf_token'] ?? '',
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>"
                >

                <input
                    type="hidden"
                    name="admin_id"
                    value="<?= $admin_id ?>"
                >

                <input
                    type="hidden"
                    name="action"
                    value="suspend"
                >

                <h4>
                    Suspend Admin
                </h4>

                <p>
                    Temporarily disable this admin's access.
                    You can restore it later.
                </p>

                <label for="suspend_reason">
                    Suspension Reason
                </label>

                <textarea
                    name="reason"
                    id="suspend_reason"
                    rows="4"
                    required
                    placeholder="Explain why this admin is being suspended..."
                ></textarea>

                <button
                    type="submit"
                    class="manage-admin-button suspend"
                    onclick="return confirm('Are you sure you want to suspend this admin?');"
                >

                    <i class="fa-solid fa-user-clock"></i>

                    Suspend Admin

                </button>

            </form>


            <!-- BAN -->

            <form
                action="admin-action.php"
                method="POST"
                class="manage-admin-action-form"
            >

                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= htmlspecialchars(
                        $_SESSION['csrf_token'] ?? '',
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>"
                >

                <input
                    type="hidden"
                    name="admin_id"
                    value="<?= $admin_id ?>"
                >

                <input
                    type="hidden"
                    name="action"
                    value="ban"
                >

                <h4>
                    Ban Admin
                </h4>

                <p>
                    Permanently restrict this admin's access
                    until a Main Admin reverses the ban.
                </p>

                <label for="ban_reason">
                    Ban Reason
                </label>

                <textarea
                    name="reason"
                    id="ban_reason"
                    rows="4"
                    required
                    placeholder="Explain why this admin is being banned..."
                ></textarea>

                <button
                    type="submit"
                    class="manage-admin-button ban"
                    onclick="return confirm('Are you sure you want to ban this admin?');"
                >

                    <i class="fa-solid fa-user-slash"></i>

                    Ban Admin

                </button>

            </form>


        <?php elseif ($admin['status'] === 'Suspended'): ?>

            <!-- UNSUSPEND -->

            <form
                action="admin-action.php"
                method="POST"
                class="manage-admin-action-form"
            >

                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= htmlspecialchars(
                        $_SESSION['csrf_token'] ?? '',
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>"
                >

                <input
                    type="hidden"
                    name="admin_id"
                    value="<?= $admin_id ?>"
                >

                <input
                    type="hidden"
                    name="action"
                    value="unsuspend"
                >

                <label for="unsuspend_reason">
                    Reason for Restoring Access
                </label>

                <textarea
                    name="reason"
                    id="unsuspend_reason"
                    rows="3"
                    required
                    placeholder="Explain why access is being restored..."
                ></textarea>

                <button
                    type="submit"
                    class="manage-admin-button restore"
                >

                    <i class="fa-solid fa-user-check"></i>

                    Unsuspend Admin

                </button>

            </form>


        <?php elseif ($admin['status'] === 'Banned'): ?>

            <!-- UNBAN -->

            <form
                action="admin-action.php"
                method="POST"
                class="manage-admin-action-form"
            >

                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= htmlspecialchars(
                        $_SESSION['csrf_token'] ?? '',
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>"
                >

                <input
                    type="hidden"
                    name="admin_id"
                    value="<?= $admin_id ?>"
                >

                <input
                    type="hidden"
                    name="action"
                    value="unban"
                >

                <label for="unban_reason">
                    Reason for Removing Ban
                </label>

                <textarea
                    name="reason"
                    id="unban_reason"
                    rows="3"
                    required
                    placeholder="Explain why the ban is being removed..."
                ></textarea>

                <button
                    type="submit"
                    class="manage-admin-button restore"
                >

                    <i class="fa-solid fa-user-check"></i>

                    Unban Admin

                </button>

            </form>

        <?php endif; ?>

    </div>


    <!-- RESTRICTION HISTORY -->

    <div class="manage-admin-card">

        <div class="manage-admin-card-header">

            <i class="fa-solid fa-clock-rotate-left"></i>

            <h3>Restriction History</h3>

        </div>


        <?php if ($history->num_rows > 0): ?>

            <div class="manage-admin-history">

                <?php while ($record = $history->fetch_assoc()): ?>

                    <div class="manage-admin-history-item">

                        <div class="history-icon">

                            <i class="fa-solid fa-shield-halved"></i>

                        </div>

                        <div class="history-content">

                            <strong>
                                <?= manage_escape($record['action_type']) ?>
                            </strong>

                            <p>
                                <?= manage_escape($record['reason']) ?>
                            </p>

                            <small>

                                By
                                <?= manage_escape($record['performed_by_name']) ?>

                                &bull;

                                <?= date(
                                    'd M Y, h:i A',
                                    strtotime($record['created_at'])
                                ) ?>

                            </small>

                        </div>

                    </div>

                <?php endwhile; ?>

            </div>

        <?php else: ?>

            <div class="manage-admin-empty">

                <i class="fa-solid fa-clock-rotate-left"></i>

                <p>
                    No restriction history found.
                </p>

            </div>

        <?php endif; ?>

    </div>

</div>

<?php include 'includes/admin_footer.php'; ?>

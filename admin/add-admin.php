<?php

/* =====================================================
   APP CONFIGURATION / DATABASE / AUTHENTICATION
===================================================== */

require_once __DIR__ . '/../config/app_config.php';
require_once __DIR__ . '/../server/database/db_connect.php';
require_once __DIR__ . '/includes/main_admin_check.php';
require_once __DIR__ . '/../server/helpers/email_helper.php';

/* =====================================================
   PAGE SETTINGS
===================================================== */

$page_title = 'Add Admin | CampusXchange';
$page_heading = 'Add Admin';
$page_description = 'Create a Sub Admin account for CampusXchange.';
$active_page = 'admins';
$page_css = 'add-admin.css';

$main_admin_id = (int) ($_SESSION['admin_id'] ?? 0);

/* =====================================================
   CSRF
===================================================== */

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

/* =====================================================
   POST/REDIRECT/GET
   - Prevents browser refresh from resubmitting POST.
   - Uses one-time session flash data for the result.
===================================================== */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $full_name = trim($_POST['full_name'] ?? '');
    $email = strtolower(trim($_POST['email'] ?? ''));
    $posted_csrf = $_POST['csrf_token'] ?? '';

    $flash = [
        'error' => '',
        'success' => '',
        'name' => '',
        'email' => '',
        'invitation_sent' => false
    ];

    if (
        !is_string($posted_csrf) ||
        empty($_SESSION['csrf_token']) ||
        !hash_equals($_SESSION['csrf_token'], $posted_csrf)
    ) {
        $flash['error'] = 'Your session token is invalid. Please try again.';
    } elseif ($full_name === '') {
        $flash['error'] = 'Please enter the admin full name.';
    } elseif (mb_strlen($full_name) > 100) {
        $flash['error'] = 'The admin name cannot exceed 100 characters.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 150) {
        $flash['error'] = 'Please enter a valid email address (maximum 150 characters).';
    } else {

        try {

            $check_stmt = $conn->prepare("
                SELECT admin_id, email_verified_at
                FROM Admins
                WHERE email = ? OR pending_email = ?
                LIMIT 1
            ");
            $check_stmt->bind_param("ss", $email, $email);
            $check_stmt->execute();
            $existing_admin = $check_stmt->get_result()->fetch_assoc();
            $check_stmt->close();

            if ($existing_admin) {
                $flash['error'] = empty($existing_admin['email_verified_at'])
                    ? 'This email already belongs to an unverified admin account. Use the existing account or resend its verification email.'
                    : 'An admin account already exists with this email address.';
            } else {

                $new_admin_id = 0;
                $verification_token = '';

                try {
                    $conn->begin_transaction();

                    $temporary_password = bin2hex(random_bytes(32));
                    $password_hash = password_hash($temporary_password, PASSWORD_DEFAULT);

                    if ($password_hash === false) {
                        throw new RuntimeException('Could not create temporary password hash.');
                    }

                    $insert_stmt = $conn->prepare("
                        INSERT INTO Admins (
                            full_name, email, email_verified_at, password,
                            admin_role, status, created_at, updated_at
                        )
                        VALUES (?, ?, NULL, ?, 'Sub Admin', 'Active', NOW(), NOW())
                    ");
                    $insert_stmt->bind_param("sss", $full_name, $email, $password_hash);
                    $insert_stmt->execute();
                    $new_admin_id = (int) $insert_stmt->insert_id;
                    $insert_stmt->close();

                    $verification_token = bin2hex(random_bytes(32));

                    $token_stmt = $conn->prepare("
                        INSERT INTO Admin_Email_Verification_Tokens (
                            admin_id, email, token, expires_at, verified_at, created_at
                        )
                        VALUES (?, ?, ?, DATE_ADD(NOW(), INTERVAL 24 HOUR), NULL, NOW())
                    ");
                    $token_stmt->bind_param("iss", $new_admin_id, $email, $verification_token);
                    $token_stmt->execute();
                    $token_stmt->close();

                    $conn->commit();

                } catch (Throwable $e) {
                    try {
                        $conn->rollback();
                    } catch (Throwable $rollback_error) {
                        error_log('Add Admin Rollback Error: ' . $rollback_error->getMessage());
                    }
                    throw $e;
                }

                /* Send invitation only after commit. */
                $verification_link = rtrim(PHP_BASE_URL, '/')
                    . '/admin/verify-admin-email?token=' . urlencode($verification_token);

                $email_sent = false;

                try {
                    $email_sent = cx_send_verification_email(
                        $email,
                        $full_name,
                        $verification_link
                    );

                    if (!$email_sent) {
                        error_log('Admin Invitation Mailer Error: ' . cx_mailer_last_error());
                    }
                } catch (Throwable $mail_error) {
                    error_log('Admin Invitation Email Exception: ' . $mail_error->getMessage());
                }

                /* Activity log must never undo a successfully created account. */
                try {
                    $log_description = sprintf(
                        'Created a Sub Admin account for %s (%s). Invitation email %s.',
                        $full_name,
                        $email,
                        $email_sent ? 'sent successfully' : 'could not be sent'
                    );
                    $ip_address = $_SERVER['REMOTE_ADDR'] ?? null;
                    $user_agent = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500);

                    $log_stmt = $conn->prepare("
                        INSERT INTO Admin_Logs (
                            admin_id, action_type, action_description,
                            target_type, target_id, ip_address, user_agent, created_at
                        )
                        VALUES (?, 'Create', ?, 'Admin', ?, ?, ?, NOW())
                    ");
                    $log_stmt->bind_param(
                        "isiss",
                        $main_admin_id,
                        $log_description,
                        $new_admin_id,
                        $ip_address,
                        $user_agent
                    );
                    $log_stmt->execute();
                    $log_stmt->close();
                } catch (Throwable $log_error) {
                    error_log('Add Admin Activity Log Error: ' . $log_error->getMessage());
                }

                $flash['success'] = $email_sent
                    ? 'The Sub Admin account was created and the verification email was sent. The invitation link expires in 24 hours.'
                    : 'The Sub Admin account was created, but the verification email could not be sent. The account remains unverified; resend the invitation from Admin Management.';
                $flash['name'] = $full_name;
                $flash['email'] = $email;
                $flash['invitation_sent'] = $email_sent;
            }

        } catch (Throwable $e) {
            error_log('Add Admin Error: ' . $e->getMessage());
            $flash['error'] = 'The request could not be completed. Please check the details and try again.';
        }
    }

    /* Preserve entered values only for an error; successful form is cleared. */
    if (!empty($flash['error'])) {
        $flash['old_name'] = $full_name;
        $flash['old_email'] = $email;
    }

    $_SESSION['add_admin_flash'] = $flash;
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

    header('Location: add-admin', true, 303);
    exit;
}

/* =====================================================
   CONSUME ONE-TIME FLASH MESSAGE
===================================================== */

$flash = $_SESSION['add_admin_flash'] ?? [];
unset($_SESSION['add_admin_flash']);

$error_message = $flash['error'] ?? '';
$success_message = $flash['success'] ?? '';
$created_admin_name = $flash['name'] ?? '';
$created_admin_email = $flash['email'] ?? '';
$invitation_sent = !empty($flash['invitation_sent']);

$full_name = $flash['old_name'] ?? '';
$email = $flash['old_email'] ?? '';
$show_form = empty($success_message);

/* =====================================================
   HEADER
===================================================== */

/* =====================================================
   HEADER
===================================================== */

include 'includes/admin_header.php';

?>

<!-- =====================================================
     ADD ADMIN PAGE
===================================================== -->

<div class="add-admin-page">

    <!-- PAGE HEADER -->

    <div class="add-admin-header">

        <div></div>

        <a href="admins" class="back-admin-btn">
            <i class="fa-solid fa-arrow-left"></i>
            Back to Admins
        </a>

    </div>

    <!-- ERROR MESSAGE -->

    <?php if (!empty($error_message)): ?>

        <div class="admin-alert admin-alert-error">
            <i class="fa-solid fa-circle-exclamation"></i>
            <?= htmlspecialchars($error_message, ENT_QUOTES, 'UTF-8') ?>
        </div>

    <?php endif; ?>

    <!-- SUCCESS STATE -->

    <?php if (!empty($success_message)): ?>

        <div class="admin-creation-success">

            <div class="admin-creation-success-icon">
                <i class="fa-solid fa-check"></i>
            </div>

            <h2>Admin invitation processed</h2>

            <p class="admin-creation-success-message">
                <?= htmlspecialchars($success_message, ENT_QUOTES, 'UTF-8') ?>
            </p>

            <?php if (!empty($created_admin_name)): ?>
                <div class="admin-creation-summary">
                    <div>
                        <span>Admin name</span>
                        <strong><?= htmlspecialchars($created_admin_name, ENT_QUOTES, 'UTF-8') ?></strong>
                    </div>
                    <div>
                        <span>Email address</span>
                        <strong><?= htmlspecialchars($created_admin_email, ENT_QUOTES, 'UTF-8') ?></strong>
                    </div>
                    <div>
                        <span>Role</span>
                        <strong>Sub Admin</strong>
                    </div>
                    <div>
                        <span>Account status</span>
                        <strong><?= !empty($invitation_sent) ? 'Invitation sent' : 'Awaiting verification' ?></strong>
                    </div>
                </div>
            <?php endif; ?>

            <div class="admin-creation-success-actions">
                <a href="add-admin" class="submit-admin-btn">
                    <i class="fa-solid fa-user-plus"></i>
                    Add Another Admin
                </a>
                <a href="admins" class="cancel-admin-btn">
                    View Admins
                </a>
            </div>

        </div>

    <?php endif; ?>

    <!-- ADD ADMIN CARD -->

    <?php if ($show_form && empty($success_message)): ?>

        <div class="add-admin-card">

            <div class="add-admin-card-header">

                <div class="add-admin-icon">
                    <i class="fa-solid fa-user-plus"></i>
                </div>

                <div>
                    <h3>Admin Information</h3>
                    <p>Enter the details of the new Sub Admin.</p>
                </div>

            </div>

            <form
                action="add-admin"
                method="POST"
                class="add-admin-form"
            >

                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>"
                >

                <!-- FULL NAME -->

                <div class="form-group">

                    <label for="full_name">
                        Full Name
                        <span>*</span>
                    </label>

                    <input
                        type="text"
                        id="full_name"
                        name="full_name"
                        placeholder="Enter full name"
                        value="<?= htmlspecialchars($full_name, ENT_QUOTES, 'UTF-8') ?>"
                        maxlength="100"
                        required
                    >

                </div>

                <!-- EMAIL -->

                <div class="form-group">

                    <label for="email">
                        Email Address
                        <span>*</span>
                    </label>

                    <input
                        type="email"
                        id="email"
                        name="email"
                        placeholder="Enter email address"
                        value="<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?>"
                        maxlength="150"
                        required
                    >

                    <small>
                        A verification email will be sent to this address.
                    </small>

                </div>

                <!-- ROLE -->

                <div class="form-group">

                    <label>Admin Role</label>

                    <div class="role-display">
                        <i class="fa-solid fa-user-shield"></i>
                        <span>Sub Admin</span>
                        <span class="role-badge">Restricted Access</span>
                    </div>

                    <small>
                        New accounts are created as Sub Admins.
                    </small>

                </div>

                <!-- STATUS -->

                <div class="form-group">

                    <label>Account Status</label>

                    <div class="status-display">
                        <span class="status-dot"></span>
                        <span>Active</span>
                    </div>

                    <small>
                        The account will remain unverified until the
                        verification process is completed.
                    </small>

                </div>

                <!-- FORM ACTIONS -->

                <div class="add-admin-form-actions">

                    <a href="admins" class="cancel-admin-btn">
                        Cancel
                    </a>

                    <button
                        type="submit"
                        class="submit-admin-btn"
                    >
                        <i class="fa-solid fa-user-plus"></i>
                        Create Admin
                    </button>

                </div>

            </form>

        </div>

    <?php endif; ?>

</div>

<?php include 'includes/admin_footer.php'; ?>

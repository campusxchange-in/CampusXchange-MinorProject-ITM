
<?php

/* =====================================================
   DATABASE CONNECTION
===================================================== */

include '../server/database/db_connect.php';

/* =====================================================
   AUTHENTICATION
===================================================== */

include 'includes/main_admin_check.php';



/* =====================================================
   PAGE SETTINGS
===================================================== */

$page_title = 'Add Admin | CampusXchange';
$page_heading = 'Add Admin';
$page_description = 'Create a Sub Admin account for CampusXchange.';
$active_page = 'admins';
$page_css = 'add-admin.css';


/* =====================================================
   CURRENT ADMIN
===================================================== */

$main_admin_id = $_SESSION['admin_id'] ?? 0;


/* =====================================================
   CSRF TOKEN
===================================================== */

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}


/* =====================================================
   FORM VARIABLES
===================================================== */

$full_name = '';
$email = '';

$error_message = '';
$success_message = '';

$show_form = true;


/* =====================================================
   HANDLE FORM SUBMISSION
===================================================== */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $full_name = trim($_POST['full_name'] ?? '');
    $email = strtolower(trim($_POST['email'] ?? ''));

    $csrf_token = $_POST['csrf_token'] ?? '';


    /* ---------------------------------------------
       CSRF VALIDATION
    --------------------------------------------- */

    if (
        empty($_SESSION['csrf_token']) ||
        !hash_equals($_SESSION['csrf_token'], $csrf_token)
    ) {
        $error_message = 'Invalid request. Please refresh the page and try again.';
    }


    /* ---------------------------------------------
       VALIDATE NAME
    --------------------------------------------- */

    elseif ($full_name === '') {

        $error_message = 'Please enter the admin full name.';

    }


    /* ---------------------------------------------
       VALIDATE EMAIL
    --------------------------------------------- */

    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $error_message = 'Please enter a valid email address.';

    }


    /* ---------------------------------------------
       CHECK EXISTING EMAIL
    --------------------------------------------- */

    else {

        $check_stmt = $conn->prepare("
            SELECT admin_id, email_verified_at
            FROM Admins
            WHERE email = ?
               OR pending_email = ?
            LIMIT 1
        ");

        $check_stmt->bind_param("ss", $email, $email);
        $check_stmt->execute();

        $existing_admin = $check_stmt->get_result()->fetch_assoc();

        $check_stmt->close();


        if ($existing_admin) {

            if (empty($existing_admin['email_verified_at'])) {

                $error_message =
                    'This email already belongs to an unverified admin account. '
                    . 'Use the existing account or resend its verification email.';

            } else {

                $error_message =
                    'An admin account already exists with this email address.';

            }

        } else {

            /* ---------------------------------------------
               CREATE ADMIN ACCOUNT
            --------------------------------------------- */

            $conn->begin_transaction();

            try {

                /*
                 * The admin will set their own password after
                 * opening the verification link.
                 *
                 * A random password hash is stored temporarily.
                 * It is not sent to the new admin.
                 */

                $temporary_password = bin2hex(random_bytes(32));

                $password_hash = password_hash(
                    $temporary_password,
                    PASSWORD_DEFAULT
                );


                /* ---------------------------------------------
                   INSERT ADMIN
                --------------------------------------------- */

                $insert_stmt = $conn->prepare("
                    INSERT INTO Admins (
                        full_name,
                        email,
                        email_verified_at,
                        password,
                        admin_role,
                        status,
                        created_at,
                        updated_at
                    )
                    VALUES (
                        ?,
                        ?,
                        NULL,
                        ?,
                        'Sub Admin',
                        'Active',
                        NOW(),
                        NOW()
                    )
                ");

                $insert_stmt->bind_param(
                    "sss",
                    $full_name,
                    $email,
                    $password_hash
                );

                $insert_stmt->execute();

                $new_admin_id = $insert_stmt->insert_id;

                $insert_stmt->close();


                /* ---------------------------------------------
                   GENERATE VERIFICATION TOKEN
                --------------------------------------------- */

                $verification_token = bin2hex(random_bytes(32));

                $token_expiry = date(
                    'Y-m-d H:i:s',
                    strtotime('+24 hours')
                );


                /* ---------------------------------------------
                   SAVE VERIFICATION TOKEN
                --------------------------------------------- */

                $token_stmt = $conn->prepare("
                    INSERT INTO Admin_Email_Verification_Tokens (
                        admin_id,
                        email,
                        token,
                        expires_at,
                        verified_at,
                        created_at
                    )
                    VALUES (
                        ?,
                        ?,
                        ?,
                        ?,
                        NULL,
                        NOW()
                    )
                ");

                $token_stmt->bind_param(
                    "isss",
                    $new_admin_id,
                    $email,
                    $verification_token,
                    $token_expiry
                );

                $token_stmt->execute();

                $token_stmt->close();


                /* ---------------------------------------------
                   COMMIT
                --------------------------------------------- */

                $conn->commit();

                $success_message =
                    'The Sub Admin account has been created. '
                    . 'Email verification is pending.';

                $show_form = false;


                /*
                 * EMAIL INTEGRATION:
                 *
                 * Next, connect your existing reusable PHP mailer
                 * here to send the verification link.
                 *
                 * I have not added a guessed mailer function.
                 * We will use your actual mailer function and
                 * configuration when integrating the email flow.
                 */


                /* ---------------------------------------------
                   ACTIVITY LOG
                --------------------------------------------- */

                /*
                 * Add your existing activity-log helper here
                 * once its exact function signature is confirmed.
                 */


                /* ---------------------------------------------
                   RESET CSRF TOKEN
                --------------------------------------------- */

                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

            } catch (Throwable $e) {

                $conn->rollback();

                error_log(
                    'Add Admin Error: ' . $e->getMessage()
                );

                $error_message =
                    'Unable to create the admin account. Please try again.';

            }

        }

    }

}


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

        <div>

           

        </div>

        <a href="admins" class="back-admin-btn">

            <i class="fa-solid fa-arrow-left"></i>

            Back to Admins

        </a>

    </div>


    <!-- ERROR MESSAGE -->

    <?php if (!empty($error_message)): ?>

        <div class="admin-alert admin-alert-error">

            <i class="fa-solid fa-circle-exclamation"></i>

            <?= htmlspecialchars($error_message) ?>

        </div>

    <?php endif; ?>


    <!-- SUCCESS MESSAGE -->

    <?php if (!empty($success_message)): ?>

        <div class="admin-alert admin-alert-success">

            <i class="fa-solid fa-circle-check"></i>

            <?= htmlspecialchars($success_message) ?>

        </div>

    <?php endif; ?>


    <!-- ADD ADMIN CARD -->

    <?php if ($show_form): ?>

        <div class="add-admin-card">

            <div class="add-admin-card-header">

                <div class="add-admin-icon">

                    <i class="fa-solid fa-user-plus"></i>

                </div>

                <div>

                    <h3>Admin Information</h3>

                    <p>
                        Enter the details of the new Sub Admin.
                    </p>

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
                    value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>"
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
                        value="<?= htmlspecialchars($full_name) ?>"
                        maxlength="150"
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
                        value="<?= htmlspecialchars($email) ?>"
                        maxlength="255"
                        required
                    >

                    <small>
                        A verification email will be sent to this address.
                    </small>

                </div>


                <!-- ROLE -->

                <div class="form-group">

                    <label>
                        Admin Role
                    </label>

                    <div class="role-display">

                        <i class="fa-solid fa-user-shield"></i>

                        <span>Sub Admin</span>

                        <span class="role-badge">
                            Restricted Access
                        </span>

                    </div>

                    <small>
                        New accounts are created as Sub Admins.
                    </small>

                </div>


                <!-- STATUS -->

                <div class="form-group">

                    <label>
                        Account Status
                    </label>

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

                    <a
                        href="admins"
                        class="cancel-admin-btn"
                    >
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

<?php

$page_title = 'Account & Security | CampusXchange';
$page_heading = 'Account & Security';
$page_description = 'Manage your administrator account and security settings.';

$active_page = '';
$page_css = 'account_security.css';


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


/* =====================================================
   FETCH ACCOUNT INFORMATION
===================================================== */

$admin = null;

$sql = "
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
";


$stmt = $conn->prepare($sql);


if ($stmt) {

    $stmt->bind_param("i", $admin_id);

    $stmt->execute();

    $result = $stmt->get_result();

    $admin = $result->fetch_assoc();

    $stmt->close();
}


/* =====================================================
   SAFETY CHECK
===================================================== */

if (!$admin) {

    header("Location: " . FRONTEND_URL . "/admin-login");
    exit;
}


/* =====================================================
   BREADCRUMB
===================================================== */

$breadcrumbs = [
    [
        'label' => 'Profile',
        'url' => 'profile'
    ],
    [
        'label' => 'Account & Security'
    ]
];


include 'includes/admin_header.php';

include 'includes/account_alert.php';

?>

<!-- <?php if (!empty($_GET['error'])): ?>

    <div class="security-alert security-alert-error">

        <i class="fa-solid fa-circle-exclamation"></i>

        <span>
            <?= htmlspecialchars($_GET['error']) ?>
        </span>

    </div>

<?php endif; ?>


<?php if (!empty($_GET['success'])): ?>

    <div class="security-alert security-alert-success">

        <i class="fa-solid fa-circle-check"></i>

        <span>
            <?= htmlspecialchars($_GET['success']) ?>
        </span>

    </div>

<?php endif; ?> -->


<div class="account-security-page">


    <!-- =================================================
         ACCOUNT OVERVIEW
    ================================================== -->

    <div class="account-security-card">


        <div class="account-security-card-header">

            <div>

                <h2>
                    Account Information
                </h2>

                <p>
                    Basic information about your administrator account.
                </p>

            </div>

        </div>


        <div class="account-info-grid">


            <!-- EMAIL -->

            <div class="account-info-item">

                <span class="account-info-label">
                    Current Email
                </span>

                <strong>
                    <?= htmlspecialchars(
                        $admin['email'] ?? ''
                    ) ?>
                </strong>

            </div>


            <!-- ADMIN ROLE -->

            <div class="account-info-item">

                <span class="account-info-label">
                    Admin Role
                </span>

                <strong>
                    <?= htmlspecialchars(
                        $admin['admin_role'] ?? 'Admin'
                    ) ?>
                </strong>

            </div>


            <!-- ACCOUNT STATUS -->

            <div class="account-info-item">

                <span class="account-info-label">
                    Account Status
                </span>

                <span
                    class="account-status-badge
                    <?= strtolower($admin['status'] ?? '') === 'active'
                        ? 'status-active'
                        : 'status-inactive'
                    ?>"
                >

                    <i class="fa-solid fa-circle"></i>

                    <?= htmlspecialchars(
                        $admin['status'] ?? 'Unknown'
                    ) ?>

                </span>

            </div>


            <!-- JOINED DATE -->

            <div class="account-info-item">

                <span class="account-info-label">
                    Account Created
                </span>

                <strong>

                    <?= !empty($admin['created_at'])
                        ? date(
                            'd M Y',
                            strtotime($admin['created_at'])
                        )
                        : '—'
                    ?>

                </strong>

            </div>


        </div>

    </div>



    <!-- =================================================
         CHANGE EMAIL
    ================================================== -->

    <div class="account-security-card">


        <div class="security-section-header">

            <div class="security-section-icon">

                <i class="fa-solid fa-envelope"></i>

            </div>


            <div>

                <h2>
                    Change Email Address
                </h2>

                <p>
                    Update the email address associated with your
                    administrator account.
                </p>

            </div>

        </div>


        <div class="security-warning">

            <i class="fa-solid fa-shield-halved"></i>

            <div>

                <strong>
                    Email verification required
                </strong>

                <p>
                    A verification link will be sent to your new
                    email address. Your email will only be changed
                    after the new address is successfully verified.
                </p>

            </div>

        </div>


        <form
            action="account_email_update"
            method="POST"
            class="security-form"
        >


            <div class="form-group">

                <label for="current_email">
                    Current Email
                </label>

                <input
                    type="email"
                    id="current_email"
                    value="<?= htmlspecialchars(
                        $admin['email'] ?? ''
                    ) ?>"
                    readonly
                >

            </div>


            <div class="form-group">

                <label for="new_email">
                    New Email Address
                </label>

                <input
                    type="email"
                    id="new_email"
                    name="new_email"
                    placeholder="Enter new email address"
                    autocomplete="email"
                    required
                >

            </div>


            <div class="security-form-actions">

                <button
                    type="submit"
                    class="security-btn security-btn-primary"
                >

                    <i class="fa-solid fa-paper-plane"></i>

                    Send Verification Link

                </button>

            </div>


        </form>

    </div>



    <!-- =================================================
         CHANGE PASSWORD
    ================================================== -->

    <div class="account-security-card">


        <div class="security-section-header">

            <div class="security-section-icon">

                <i class="fa-solid fa-lock"></i>

            </div>


            <div>

                <h2>
                    Change Password
                </h2>

                <p>
                    Update your administrator account password.
                </p>

            </div>

        </div>


        <div class="security-info">

            <i class="fa-solid fa-circle-info"></i>

            <span>
                Use a strong password that you do not use on other
                websites or services.
            </span>

        </div>


        <form
            action="account_password_update"
            method="POST"
            class="security-form"
            id="passwordChangeForm"
        >

            <!-- CURRENT PASSWORD -->

            <div class="form-group">

                <label for="current_password">
                    Current Password
                </label>

                <div class="password-input">

                    <input
                        type="password"
                        id="current_password"
                        name="current_password"
                        autocomplete="current-password"
                    >

                    <button
                        type="button"
                        class="password-toggle"
                        data-target="current_password"
                        aria-label="Show password"
                    >
                        <i class="fa-solid fa-eye"></i>
                    </button>

                </div>

                <small
                    class="form-error"
                    id="currentPasswordError"
                ></small>

            </div>


            <!-- NEW PASSWORD -->

            <div class="form-group">

                <label for="new_password">
                    New Password
                </label>

                <div class="password-input">

                    <input
                        type="password"
                        id="new_password"
                        name="new_password"
                        autocomplete="new-password"
                        maxlength="20"
                    >

                    <button
                        type="button"
                        class="password-toggle"
                        data-target="new_password"
                        aria-label="Show password"
                    >
                        <i class="fa-solid fa-eye"></i>
                    </button>

                </div>

                <small>
                    Password must contain 8–20 characters.
                </small>

                <small
                    class="form-error"
                    id="newPasswordError"
                ></small>

            </div>


            <!-- CONFIRM PASSWORD -->

            <div class="form-group">

                <label for="confirm_password">
                    Confirm New Password
                </label>

                <div class="password-input">

                    <input
                        type="password"
                        id="confirm_password"
                        name="confirm_password"
                        autocomplete="new-password"
                        maxlength="20"
                    >

                    <button
                        type="button"
                        class="password-toggle"
                        data-target="confirm_password"
                        aria-label="Show password"
                    >
                        <i class="fa-solid fa-eye"></i>
                    </button>

                </div>

                <small
                    class="form-error"
                    id="confirmPasswordError"
                ></small>

            </div>


            <div class="security-form-actions">

                <button
                    type="submit"
                    class="security-btn security-btn-primary"
                >
                    <i class="fa-solid fa-key"></i>
                    Update Password
                </button>

            </div>

        </form>

    </div>



    <!-- =================================================
         SECURITY STATUS
    ================================================== -->

    <div class="account-security-card">


        <div class="security-section-header">

            <div class="security-section-icon">

                <i class="fa-solid fa-shield-halved"></i>

            </div>


            <div>

                <h2>
                    Security
                </h2>

                <p>
                    Important security information for your account.
                </p>

            </div>

        </div>


        <div class="security-status-list">


            <!-- EMAIL -->

            <div class="security-status-item">

                <div class="security-status-left">

                    <div class="security-status-icon">

                        <i class="fa-solid fa-envelope"></i>

                    </div>

                    <div>

                        <strong>
                            Email Address
                        </strong>

                        <span>
                            <?= htmlspecialchars(
                                $admin['email'] ?? ''
                            ) ?>
                        </span>

                    </div>

                </div>


                <span class="security-badge security-badge-success">

                    <i class="fa-solid fa-circle-check"></i>

                    Active

                </span>

            </div>



            <!-- PASSWORD -->

            <div class="security-status-item">

                <div class="security-status-left">

                    <div class="security-status-icon">

                        <i class="fa-solid fa-lock"></i>

                    </div>

                    <div>

                        <strong>
                            Password
                        </strong>

                        <span>
                            Your password is protected securely.
                        </span>

                    </div>

                </div>


                <span class="security-badge security-badge-success">

                    <i class="fa-solid fa-shield-halved"></i>

                    Protected

                </span>

            </div>


        </div>

    </div>



    <!-- =================================================
         BACK TO PROFILE
    ================================================== -->

    <div class="account-security-footer">

        <a
            href="profile"
            class="back-profile-btn"
        >

            <i class="fa-solid fa-arrow-left"></i>

            Back to Profile

        </a>

    </div>


</div>

<script src="assets/js/account_security.js"></script>


<?php include 'includes/admin_footer.php'; ?>
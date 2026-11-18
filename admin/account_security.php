<?php

$page_title = 'Account & Security | CampusXchange';
$page_heading = 'Account & Security';
$page_description = 'Manage your administrator account and security settings.';

$active_page = '';
$page_css = 'account_security.css';

include 'includes/auth_check.php';
include '../server/database/db_connect.php';

$admin_id = (int)($_SESSION['admin_id'] ?? 0);

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$admin = null;

$stmt = $conn->prepare("
    SELECT admin_id, full_name, email, pending_email, email_verified_at,
           admin_role, status, created_at
    FROM Admins
    WHERE admin_id = ? AND deleted_at IS NULL
    LIMIT 1
");

if ($stmt) {
    $stmt->bind_param("i", $admin_id);
    $stmt->execute();
    $admin = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

if (!$admin) {
    header("Location: " . FRONTEND_URL . "/admin-login");
    exit;
}

$breadcrumbs = [
    ['label' => 'Profile', 'url' => 'profile'],
    ['label' => 'Account & Security']
];

include 'includes/admin_header.php';
include 'includes/account_alert.php';

?>

<div class="account-security-page">

    <div class="account-security-card">
        <div class="account-security-card-header">
            <div>
                <h2>Account Information</h2>
                <p>Basic information about your administrator account.</p>
            </div>
        </div>

        <div class="account-info-grid">
            <div class="account-info-item">
                <span class="account-info-label">Current Email</span>
                <strong><?= htmlspecialchars($admin['email'] ?? '', ENT_QUOTES, 'UTF-8') ?></strong>
            </div>

            <div class="account-info-item">
                <span class="account-info-label">Admin Role</span>
                <strong><?= htmlspecialchars($admin['admin_role'] ?? 'Admin', ENT_QUOTES, 'UTF-8') ?></strong>
            </div>

            <div class="account-info-item">
                <span class="account-info-label">Account Status</span>
                <span class="account-status-badge <?= strtolower($admin['status'] ?? '') === 'active' ? 'status-active' : 'status-inactive' ?>">
                    <i class="fa-solid fa-circle"></i>
                    <?= htmlspecialchars($admin['status'] ?? 'Unknown', ENT_QUOTES, 'UTF-8') ?>
                </span>
            </div>

            <div class="account-info-item">
                <span class="account-info-label">Account Created</span>
                <strong>
                    <?= !empty($admin['created_at']) ? date('d M Y', strtotime($admin['created_at'])) : '—' ?>
                </strong>
            </div>
        </div>
    </div>

    <div class="account-security-card">
        <div class="security-section-header">
            <div class="security-section-icon"><i class="fa-solid fa-envelope"></i></div>
            <div>
                <h2>Change Email Address</h2>
                <p>Your primary email will only change after the new address is verified.</p>
            </div>
        </div>

        <div class="security-warning">
            <i class="fa-solid fa-shield-halved"></i>
            <div>
                <strong>Email verification required</strong>
                <p>A verification link will be sent to your new email address. Your current email remains active until verification is complete.</p>
            </div>
        </div>

        <?php if (!empty($admin['pending_email'])): ?>
            <div class="pending-email-notice">
                <div>
                    <strong><i class="fa-solid fa-clock"></i> Verification pending</strong>
                    <p>
                        A verification link was requested for
                        <strong><?= htmlspecialchars($admin['pending_email'], ENT_QUOTES, 'UTF-8') ?></strong>.
                        Your current email is still
                        <strong><?= htmlspecialchars($admin['email'], ENT_QUOTES, 'UTF-8') ?></strong>.
                    </p>
                </div>
            </div>
        <?php endif; ?>

        <form action="account_email_update" method="POST" class="security-form" id="emailChangeForm">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">

            <div class="form-group">
                <label for="current_email">Current Email</label>
                <input type="email" id="current_email" value="<?= htmlspecialchars($admin['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>" readonly>
            </div>

            <div class="form-group">
                <label for="new_email">New Email Address</label>
                <input
                    type="email"
                    id="new_email"
                    name="new_email"
                    placeholder="Enter new email address"
                    autocomplete="email"
                    maxlength="150"
                    required
                >
                <small class="form-error" id="newEmailError"></small>
            </div>

            <div class="form-group">
                <label for="email_current_password">Confirm Your Current Password</label>
                <div class="password-input">
                    <input
                        type="password"
                        id="email_current_password"
                        name="current_password"
                        autocomplete="current-password"
                        required
                    >
                    <button type="button" class="password-toggle" data-target="email_current_password" aria-label="Show password">
                        <i class="fa-solid fa-eye"></i>
                    </button>
                </div>
            </div>

            <div class="security-form-actions">
                <button type="submit" class="security-btn security-btn-primary" id="emailChangeSubmit">
                    <i class="fa-solid fa-paper-plane"></i>
                    Send Verification Link
                </button>
            </div>
        </form>
    </div>

    <div class="account-security-card">
        <div class="security-section-header">
            <div class="security-section-icon"><i class="fa-solid fa-lock"></i></div>
            <div>
                <h2>Change Password</h2>
                <p>Update your administrator account password.</p>
            </div>
        </div>

        <div class="security-info">
            <i class="fa-solid fa-circle-info"></i>
            <span>Use a strong password that you do not use on other websites or services.</span>
        </div>

        <form action="account_password_update" method="POST" class="security-form" id="passwordChangeForm">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">

            <div class="form-group">
                <label for="current_password">Current Password</label>
                <div class="password-input">
                    <input type="password" id="current_password" name="current_password" autocomplete="current-password">
                    <button type="button" class="password-toggle" data-target="current_password" aria-label="Show password">
                        <i class="fa-solid fa-eye"></i>
                    </button>
                </div>
            </div>

            <div class="form-group">
                <label for="new_password">New Password</label>
                <div class="password-input">
                    <input type="password" id="new_password" name="new_password" autocomplete="new-password" maxlength="20">
                    <button type="button" class="password-toggle" data-target="new_password" aria-label="Show password">
                        <i class="fa-solid fa-eye"></i>
                    </button>
                </div>
                <small>Password must contain 8–20 characters.</small>
            </div>

            <div class="form-group">
                <label for="confirm_password">Confirm New Password</label>
                <div class="password-input">
                    <input type="password" id="confirm_password" name="confirm_password" autocomplete="new-password" maxlength="20">
                    <button type="button" class="password-toggle" data-target="confirm_password" aria-label="Show password">
                        <i class="fa-solid fa-eye"></i>
                    </button>
                </div>
            </div>

            <div class="security-form-actions">
                <button type="submit" class="security-btn security-btn-primary">
                    <i class="fa-solid fa-key"></i> Update Password
                </button>
            </div>
        </form>
    </div>

    <div class="account-security-card">
        <div class="security-section-header">
            <div class="security-section-icon"><i class="fa-solid fa-shield-halved"></i></div>
            <div>
                <h2>Security</h2>
                <p>Important security information for your account.</p>
            </div>
        </div>

        <div class="security-status-list">
            <div class="security-status-item">
                <div class="security-status-left">
                    <div class="security-status-icon"><i class="fa-solid fa-envelope"></i></div>
                    <div>
                        <strong>Email Address</strong>
                        <span><?= htmlspecialchars($admin['email'] ?? '', ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                </div>
                <span class="security-badge security-badge-success"><i class="fa-solid fa-circle-check"></i> Active</span>
            </div>

            <div class="security-status-item">
                <div class="security-status-left">
                    <div class="security-status-icon"><i class="fa-solid fa-lock"></i></div>
                    <div>
                        <strong>Password</strong>
                        <span>Your password is protected securely.</span>
                    </div>
                </div>
                <span class="security-badge security-badge-success"><i class="fa-solid fa-shield-halved"></i> Protected</span>
            </div>
        </div>
    </div>

    <div class="account-security-footer">
        <a href="profile" class="back-profile-btn"><i class="fa-solid fa-arrow-left"></i> Back to Profile</a>
    </div>

</div>

<script src="assets/js/account_security.js"></script>
<?php include 'includes/admin_footer.php'; ?>

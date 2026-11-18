<?php
require_once __DIR__ . '/../config/app_config.php';
require_once __DIR__ . '/../server/database/db_connect.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Referrer-Policy: no-referrer');

$token = trim($_GET['token'] ?? '');
$valid_token_format = (bool) preg_match('/\A[a-f0-9]{64}\z/', $token);

$invitation = null;
$error_message = '';

if (!$valid_token_format) {
    $error_message = 'This invitation link is invalid. Please ask the Main Admin to send a new invitation.';
} else {
    try {
        $stmt = $conn->prepare("
            SELECT
                t.verification_token_id,
                t.admin_id,
                t.email,
                t.expires_at,
                t.verified_at,
                a.full_name,
                a.status,
                a.email_verified_at,
                a.deleted_at
            FROM Admin_Email_Verification_Tokens t
            INNER JOIN Admins a ON a.admin_id = t.admin_id
            WHERE t.token = ?
            LIMIT 1
        ");
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $invitation = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$invitation) {
            $error_message = 'This invitation link is invalid or has already been used.';
        } elseif (!empty($invitation['verified_at']) || !empty($invitation['email_verified_at'])) {
            $error_message = 'This email address has already been verified. You can continue to the admin login page.';
        } elseif (strtotime($invitation['expires_at']) < time()) {
            $error_message = 'This invitation link has expired. Please ask the Main Admin to resend the invitation.';
        } elseif ($invitation['status'] !== 'Active' || !empty($invitation['deleted_at'])) {
            $error_message = 'This administrator invitation is currently unavailable. Please contact the Main Admin.';
        }
    } catch (Throwable $e) {
        error_log('Admin Email Verification Page Error: ' . $e->getMessage());
        $error_message = 'We could not validate this invitation right now. Please try again later.';
        $invitation = null;
    }
}

if (empty($_SESSION['admin_verify_csrf'])) {
    $_SESSION['admin_verify_csrf'] = bin2hex(random_bytes(32));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Admin Invitation | CampusXchange</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="<?= htmlspecialchars(PHP_BASE_URL, ENT_QUOTES, 'UTF-8') ?>/admin/assets/css/admin-verification.css">
</head>
<body>
    <main class="verification-shell">
        <section class="verification-card">
            <a class="verification-brand" href="<?= htmlspecialchars(FRONTEND_URL, ENT_QUOTES, 'UTF-8') ?>">
                <span class="verification-brand-mark"><i class="fa-solid fa-graduation-cap"></i></span>
                <span>CampusXchange</span>
            </a>

            <?php if ($error_message !== ''): ?>
                <div class="verification-state verification-state-error">
                    <div class="verification-state-icon"><i class="fa-solid fa-circle-exclamation"></i></div>
                    <h1>Invitation unavailable</h1>
                    <p><?= htmlspecialchars($error_message, ENT_QUOTES, 'UTF-8') ?></p>
                    <a class="verification-primary-link" href="<?= htmlspecialchars(FRONTEND_URL, ENT_QUOTES, 'UTF-8') ?>/admin-login">
                        Go to Admin Login <i class="fa-solid fa-arrow-right"></i>
                    </a>
                </div>
            <?php else: ?>
                <div class="verification-heading">
                    <div class="verification-heading-icon"><i class="fa-solid fa-user-shield"></i></div>
                    <span class="verification-eyebrow">ADMIN INVITATION</span>
                    <h1>Set up your admin account</h1>
                    <p>Welcome to CampusXchange. Verify your email and create a secure password to activate your administrator account.</p>
                </div>

                <div class="verification-invitee">
                    <div class="verification-avatar"><i class="fa-solid fa-user"></i></div>
                    <div>
                        <span>Invitation for</span>
                        <strong><?= htmlspecialchars($invitation['full_name'], ENT_QUOTES, 'UTF-8') ?></strong>
                        <small><?= htmlspecialchars($invitation['email'], ENT_QUOTES, 'UTF-8') ?></small>
                    </div>
                    <span class="verification-role">Sub Admin</span>
                </div>

                <form
                    id="adminSetupForm"
                    class="verification-form"
                    method="POST"
                    action="<?= htmlspecialchars(PHP_BASE_URL, ENT_QUOTES, 'UTF-8') ?>/admin/set-admin-password"
                    novalidate
                >
                    <input type="hidden" name="token" value="<?= htmlspecialchars($token, ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['admin_verify_csrf'], ENT_QUOTES, 'UTF-8') ?>">

                    <div class="verification-field">
                        <label for="new_password">Create password</label>
                        <div class="verification-password-wrap">
                            <input
                                type="password"
                                id="new_password"
                                name="new_password"
                                placeholder="Create a strong password"
                                autocomplete="new-password"
                                minlength="8"
                                maxlength="72"
                                required
                            >
                            <button type="button" class="verification-password-toggle" data-target="new_password" aria-label="Show password">
                                <i class="fa-solid fa-eye"></i>
                            </button>
                        </div>
                        <small class="verification-hint">Use at least 8 characters. Maximum 72 bytes.</small>
                        <small class="verification-field-error" id="newPasswordError" aria-live="polite"></small>
                    </div>

                    <div class="verification-field">
                        <label for="confirm_password">Confirm password</label>
                        <div class="verification-password-wrap">
                            <input
                                type="password"
                                id="confirm_password"
                                name="confirm_password"
                                placeholder="Enter your password again"
                                autocomplete="new-password"
                                minlength="8"
                                maxlength="72"
                                required
                            >
                            <button type="button" class="verification-password-toggle" data-target="confirm_password" aria-label="Show password">
                                <i class="fa-solid fa-eye"></i>
                            </button>
                        </div>
                        <small class="verification-field-error" id="confirmPasswordError" aria-live="polite"></small>
                    </div>

                    <div class="verification-security-note">
                        <i class="fa-solid fa-shield-halved"></i>
                        <span>Your password is securely hashed and never stored in plain text.</span>
                    </div>

                    <button type="submit" class="verification-submit" id="setupSubmit">
                        <span>Verify email &amp; create account</span>
                        <i class="fa-solid fa-arrow-right"></i>
                    </button>
                </form>

                <p class="verification-expiry"><i class="fa-regular fa-clock"></i> This invitation expires in 24 hours.</p>
            <?php endif; ?>

            <div class="verification-footer">© <?= date('Y') ?> CampusXchange. All rights reserved.</div>
        </section>
        <p class="verification-help">Need help? Contact your CampusXchange administrator.</p>
    </main>
    <script src="<?= htmlspecialchars(PHP_BASE_URL, ENT_QUOTES, 'UTF-8') ?>/admin/assets/js/admin-verification.js"></script>
</body>
</html>

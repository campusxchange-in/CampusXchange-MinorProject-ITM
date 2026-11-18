<?php
/* =====================================================
   AUTHENTICATION + DATABASE
===================================================== */

include 'includes/auth_check.php';
include '../server/database/db_connect.php';

$admin_id = (int)($_SESSION['admin_id'] ?? 0);
if (!$admin_id) {
    include_once '../config/app_config.php';
    header('Location: ' . FRONTEND_URL . '/admin-login');
    exit;
}

/* =====================================================
   VERIFY ACTIVE ADMIN / PERMISSIONS
===================================================== */

$stmt = $conn->prepare("SELECT admin_id, full_name, email, admin_role FROM Admins WHERE admin_id = ? AND status = 'Active' AND deleted_at IS NULL LIMIT 1");
$stmt->bind_param('i', $admin_id);
$stmt->execute();
$current_admin = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$current_admin) {
    session_destroy();
    include_once '../config/app_config.php';
    header('Location: ' . FRONTEND_URL . '/admin-login');
    exit;
}

$is_main_admin = ($current_admin['admin_role'] === 'Main Admin');

/* =====================================================
   PAGE SETTINGS + CSRF
===================================================== */

$page_title = 'Settings | CampusXchange';
$page_heading = 'Settings';
$page_description = 'Manage CampusXchange platform preferences and controls.';
$active_page = 'settings';
$page_css = 'settings.css';

if (empty($_SESSION['settings_csrf_token'])) {
    $_SESSION['settings_csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['settings_csrf_token'];

/* =====================================================
   DEFAULT SETTINGS
   Insert missing defaults only; existing values are preserved.
===================================================== */

$defaults = [
    'platform_name' => ['CampusXchange', 'Text', 'Name displayed for the platform'],
    'support_email' => ['support@campusxchange.com', 'Email', 'Support contact email'],
    'verification_enabled' => ['1', 'Boolean', 'Allow user identity verification'],
    'require_id_front' => ['1', 'Boolean', 'Require the front side of the college ID'],
    'require_id_back' => ['1', 'Boolean', 'Require the back side of the college ID'],
    'verification_max_file_mb' => ['5', 'Number', 'Maximum size per verification image in MB'],
    'notify_new_reports' => ['1', 'Boolean', 'Enable admin notifications for new reports'],
    'notify_verification_requests' => ['1', 'Boolean', 'Enable admin notifications for verification requests'],
    'notify_new_users' => ['0', 'Boolean', 'Enable admin notifications for new registrations'],
];

if ($is_main_admin) {
    $insert_setting = $conn->prepare("INSERT INTO System_Settings (setting_key, setting_value, setting_type, description, updated_by) VALUES (?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE setting_key = VALUES(setting_key)");
    foreach ($defaults as $key => $setting) {
        [$value, $type, $description] = $setting;
        $insert_setting->bind_param('ssssi', $key, $value, $type, $description, $admin_id);
        $insert_setting->execute();
    }
    $insert_setting->close();
}

/* =====================================================
   HELPERS
===================================================== */

function settings_load(mysqli $conn): array
{
    $values = [];
    $result = $conn->query("SELECT setting_key, setting_value FROM System_Settings WHERE deleted_at IS NULL");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $values[$row['setting_key']] = $row['setting_value'];
        }
    }
    return $values;
}

function settings_log(mysqli $conn, int $admin_id, string $description): void
{
    $action_type = 'Change Settings';
    $target_type = 'System Setting';
    $target_id = null;
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? null;
    $user_agent = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500);

    $stmt = $conn->prepare("INSERT INTO Admin_Logs (admin_id, action_type, action_description, target_type, target_id, ip_address, user_agent) VALUES (?, ?, ?, ?, ?, ?, ?)");
    if ($stmt) {
        $stmt->bind_param('isssiss', $admin_id, $action_type, $description, $target_type, $target_id, $ip_address, $user_agent);
        $stmt->execute();
        $stmt->close();
    }
}

$settings = settings_load($conn);
$success_message = '';
$error_message = '';

/* =====================================================
   HANDLE SAVE
===================================================== */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$is_main_admin) {
        $error_message = 'Only the Main Admin can change global system settings.';
    } elseif (!hash_equals($csrf_token, (string)($_POST['csrf_token'] ?? ''))) {
        $error_message = 'Your session token is invalid. Refresh the page and try again.';
    } else {
        $platform_name = trim((string)($_POST['platform_name'] ?? ''));
        $support_email = trim((string)($_POST['support_email'] ?? ''));
        $verification_max_file_mb = filter_var($_POST['verification_max_file_mb'] ?? null, FILTER_VALIDATE_INT);

        if ($platform_name === '' || mb_strlen($platform_name) > 100) {
            $error_message = 'Platform name is required and must be 100 characters or fewer.';
        } elseif (!filter_var($support_email, FILTER_VALIDATE_EMAIL) || mb_strlen($support_email) > 150) {
            $error_message = 'Enter a valid support email address.';
        } elseif ($verification_max_file_mb === false || $verification_max_file_mb < 1 || $verification_max_file_mb > 25) {
            $error_message = 'The verification image limit must be between 1 and 25 MB.';
        } else {
            $posted_values = [
                'platform_name' => $platform_name,
                'support_email' => $support_email,
                'verification_enabled' => isset($_POST['verification_enabled']) ? '1' : '0',
                'require_id_front' => isset($_POST['require_id_front']) ? '1' : '0',
                'require_id_back' => isset($_POST['require_id_back']) ? '1' : '0',
                'verification_max_file_mb' => (string)$verification_max_file_mb,
                'notify_new_reports' => isset($_POST['notify_new_reports']) ? '1' : '0',
                'notify_verification_requests' => isset($_POST['notify_verification_requests']) ? '1' : '0',
                'notify_new_users' => isset($_POST['notify_new_users']) ? '1' : '0',
            ];

            $conn->begin_transaction();
            try {
                $update_stmt = $conn->prepare("UPDATE System_Settings SET setting_value = ?, updated_by = ? WHERE setting_key = ? AND deleted_at IS NULL");
                foreach ($posted_values as $key => $value) {
                    $update_stmt->bind_param('sis', $value, $admin_id, $key);
                    if (!$update_stmt->execute()) {
                        throw new RuntimeException('Could not update setting: ' . $key);
                    }
                }
                $update_stmt->close();

                $summary = 'Updated global settings: ' . implode(', ', array_keys($posted_values));
                settings_log($conn, $admin_id, $summary);
                $conn->commit();

                $settings = settings_load($conn);
                $success_message = 'Settings saved successfully.';
            } catch (Throwable $exception) {
                $conn->rollback();
                $error_message = 'Settings could not be saved. Please try again.';
            }
        }
    }
}

/* =====================================================
   BREADCRUMBS + HEADER
===================================================== */

$breadcrumbs = [
    ['label' => 'Settings']
];

include 'includes/admin_header.php';

function setting_checked(array $settings, string $key): string
{
    return (($settings[$key] ?? '0') === '1') ? 'checked' : '';
}
?>

<div class="settings-page">

    <div class="page-header settings-page-header">
        <div>
            
        </div>
        <span class="settings-role-chip">
            <i class="fa-solid fa-shield-halved"></i>
            <?= htmlspecialchars($current_admin['admin_role']) ?>
        </span>
    </div>

    <?php if ($success_message !== ''): ?>
        <div class="settings-alert settings-alert-success" role="status">
            <i class="fa-solid fa-circle-check"></i>
            <?= htmlspecialchars($success_message) ?>
        </div>
    <?php endif; ?>

    <?php if ($error_message !== ''): ?>
        <div class="settings-alert settings-alert-error" role="alert">
            <i class="fa-solid fa-circle-exclamation"></i>
            <?= htmlspecialchars($error_message) ?>
        </div>
    <?php endif; ?>

    <?php if (!$is_main_admin): ?>
        <section class="settings-card settings-restricted-card">
            <div class="settings-card-icon"><i class="fa-solid fa-lock"></i></div>
            <div>
                <h2>Global settings are restricted</h2>
                <p>As a Sub Admin, you can access your assigned admin workflows, but only the Main Admin can update CampusXchange-wide settings.</p>
            </div>
        </section>
    <?php else: ?>
        <form method="POST" action="" class="settings-form">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">

            <!-- <section class="settings-card">
                <div class="settings-card-heading">
                    <div class="settings-card-icon"><i class="fa-solid fa-sliders"></i></div>
                    <div>
                        <h2>General</h2>
                        <p>Basic platform identity and contact details.</p>
                    </div>
                </div>
                <div class="settings-fields settings-fields-two">
                    <div class="settings-field">
                        <label for="platform_name">Platform name</label>
                        <input type="text" id="platform_name" name="platform_name" maxlength="100" required value="<?= htmlspecialchars($settings['platform_name'] ?? 'CampusXchange') ?>">
                        <small>The display name used by the platform.</small>
                    </div>
                    <div class="settings-field">
                        <label for="support_email">Support email</label>
                        <input type="email" id="support_email" name="support_email" maxlength="150" required value="<?= htmlspecialchars($settings['support_email'] ?? 'support@campusxchange.com') ?>">
                        <small>Contact address for platform support.</small>
                    </div>
                </div>
            </section> -->

            <section class="settings-card">
                <div class="settings-card-heading">
                    <div class="settings-card-icon"><i class="fa-solid fa-id-card"></i></div>
                    <div>
                        <h2>Verification</h2>
                        <p>Control identity verification and college ID requirements.</p>
                    </div>
                </div>
                <div class="settings-switch-list">
                    <label class="settings-switch-row" for="verification_enabled">
                        <span><strong>Enable user verification</strong><small>Allow users to submit verification requests.</small></span>
                        <input type="checkbox" id="verification_enabled" name="verification_enabled" value="1" <?= setting_checked($settings, 'verification_enabled') ?>><span class="settings-switch"></span>
                    </label>
                    <label class="settings-switch-row" for="require_id_front">
                        <span><strong>Require front side of college ID</strong><small>Make the front ID image mandatory.</small></span>
                        <input type="checkbox" id="require_id_front" name="require_id_front" value="1" <?= setting_checked($settings, 'require_id_front') ?>><span class="settings-switch"></span>
                    </label>
                    <label class="settings-switch-row" for="require_id_back">
                        <span><strong>Require back side of college ID</strong><small>Make the back ID image mandatory.</small></span>
                        <input type="checkbox" id="require_id_back" name="require_id_back" value="1" <?= setting_checked($settings, 'require_id_back') ?>><span class="settings-switch"></span>
                    </label>
                </div>
                <div class="settings-fields settings-fields-one">
                    <div class="settings-field settings-file-limit">
                        <label for="verification_max_file_mb">Maximum verification image size</label>
                        <div class="settings-input-suffix">
                            <input type="number" id="verification_max_file_mb" name="verification_max_file_mb" min="1" max="25" step="1" required value="<?= htmlspecialchars($settings['verification_max_file_mb'] ?? '5') ?>">
                            <span>MB</span>
                        </div>
                        <small>Allowed range: 1–25 MB per image. This setting must also be applied in the PHP upload handler.</small>
                    </div>
                </div>
            </section>

            <section class="settings-card">
                <div class="settings-card-heading">
                    <div class="settings-card-icon"><i class="fa-solid fa-bell"></i></div>
                    <div>
                        <h2>Admin notifications</h2>
                        <p>Choose which events should create admin notifications.</p>
                    </div>
                </div>
                <div class="settings-switch-list">
                    <label class="settings-switch-row" for="notify_new_reports">
                        <span><strong>New reports</strong><small>Notify admins when a new report is submitted.</small></span>
                        <input type="checkbox" id="notify_new_reports" name="notify_new_reports" value="1" <?= setting_checked($settings, 'notify_new_reports') ?>><span class="settings-switch"></span>
                    </label>
                    <label class="settings-switch-row" for="notify_verification_requests">
                        <span><strong>Verification requests</strong><small>Notify admins when a verification request arrives.</small></span>
                        <input type="checkbox" id="notify_verification_requests" name="notify_verification_requests" value="1" <?= setting_checked($settings, 'notify_verification_requests') ?>><span class="settings-switch"></span>
                    </label>
                    <label class="settings-switch-row" for="notify_new_users">
                        <span><strong>New registrations</strong><small>Notify admins when a new account is created.</small></span>
                        <input type="checkbox" id="notify_new_users" name="notify_new_users" value="1" <?= setting_checked($settings, 'notify_new_users') ?>><span class="settings-switch"></span>
                    </label>
                </div>
            </section>

            <div class="settings-form-actions">
                <span><i class="fa-solid fa-circle-info"></i> Changes are saved to System_Settings and recorded in Activity Logs.</span>
                <button type="submit" class="settings-save-button"><i class="fa-solid fa-floppy-disk"></i> Save settings</button>
            </div>
        </form>
    <?php endif; ?>
</div>

<?php include 'includes/admin_footer.php'; ?>

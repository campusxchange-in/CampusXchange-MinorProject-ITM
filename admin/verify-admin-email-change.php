<?php

require_once __DIR__ . '/../config/app_config.php';
require_once __DIR__ . '/../server/database/db_connect.php';

$message = 'This verification link is invalid or has expired.';
$success = false;

$token = trim($_GET['token'] ?? '');

if ($token !== '' && preg_match('/^[a-f0-9]{64}$/', $token)) {
    try {
        $conn->begin_transaction();

        $stmt = $conn->prepare("
            SELECT
                t.verification_token_id,
                t.admin_id,
                t.email AS token_email,
                t.expires_at,
                t.verified_at,
                a.pending_email,
                a.email,
                a.status
            FROM Admin_Email_Verification_Tokens t
            INNER JOIN Admins a ON a.admin_id = t.admin_id
            WHERE t.token = ?
              AND a.deleted_at IS NULL
            LIMIT 1
            FOR UPDATE
        ");

        if (!$stmt) {
            throw new RuntimeException('Unable to validate the verification link.');
        }

        $stmt->bind_param('s', $token);
        $stmt->execute();
        $record = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$record) {
            $conn->rollback();
        } elseif (!empty($record['verified_at'])) {
            $conn->rollback();
            $message = 'This email address has already been verified.';
            $success = true;
        } elseif (
            strtotime($record['expires_at']) < time() ||
            $record['status'] !== 'Active' ||
            empty($record['pending_email']) ||
            strcasecmp($record['pending_email'], $record['token_email']) !== 0
        ) {
            $conn->rollback();
            $message = 'This verification link has expired or is no longer valid. Return to Account & Security and request a new link.';
        } else {
            $update = $conn->prepare("
                UPDATE Admins
                SET email = pending_email,
                    pending_email = NULL,
                    email_verified_at = CURRENT_TIMESTAMP,
                    updated_at = CURRENT_TIMESTAMP
                WHERE admin_id = ?
                  AND pending_email = ?
                  AND deleted_at IS NULL
            ");

            if (!$update) {
                throw new RuntimeException('Unable to update the email address.');
            }

            $update->bind_param('is', $record['admin_id'], $record['token_email']);
            $update->execute();
            $changed = $update->affected_rows;
            $update->close();

            if ($changed !== 1) {
                throw new RuntimeException('The pending email could not be confirmed.');
            }

            $mark = $conn->prepare("
                UPDATE Admin_Email_Verification_Tokens
                SET verified_at = CURRENT_TIMESTAMP
                WHERE verification_token_id = ?
            ");
            if (!$mark) {
                throw new RuntimeException('Unable to complete email verification.');
            }
            $mark->bind_param('i', $record['verification_token_id']);
            $mark->execute();
            $mark->close();

            // Remove any other outstanding verification links for this admin.
            $cleanup = $conn->prepare("
                DELETE FROM Admin_Email_Verification_Tokens
                WHERE admin_id = ?
                  AND verification_token_id <> ?
                  AND verified_at IS NULL
            ");
            if ($cleanup) {
                $cleanup->bind_param('ii', $record['admin_id'], $record['verification_token_id']);
                $cleanup->execute();
                $cleanup->close();
            }

            $conn->commit();
            $success = true;
            $message = 'Your administrator email address has been successfully verified and updated.';
        }
    } catch (Throwable $e) {
        try {
            $conn->rollback();
        } catch (Throwable $ignored) {
        }
        error_log('Admin email-change verification error: ' . $e->getMessage());
        $message = 'We could not complete email verification. Please try again or contact the system administrator.';
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Verification | CampusXchange</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        :root { color-scheme: light; }
        * { box-sizing: border-box; }
        body {
            margin: 0; min-height: 100vh; display: grid; place-items: center;
            padding: 24px; background: #f4f6fb; color: #172033;
            font-family: Inter, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        }
        .verification-card {
            width: 100%; max-width: 510px; padding: 42px 36px; text-align: center;
            background: #fff; border: 1px solid #e5e9f2; border-radius: 18px;
            box-shadow: 0 18px 55px rgba(20, 35, 65, .09);
        }
        .verification-icon {
            width: 76px; height: 76px; margin: 0 auto 22px; display: grid;
            place-items: center; border-radius: 50%; font-size: 30px;
            color: <?= $success ? "'#15803d'" : "'#b45309'" ?>;
            background: <?= $success ? "'#dcfce7'" : "'#fef3c7'" ?>;
        }
        h1 { margin: 0 0 12px; font-size: 25px; }
        p { margin: 0; color: #64748b; line-height: 1.7; font-size: 15px; }
        .verification-actions { margin-top: 28px; }
        .verification-actions a {
            display: inline-flex; align-items: center; justify-content: center; gap: 9px;
            padding: 12px 18px; border-radius: 9px; background: #2563eb;
            color: #fff; text-decoration: none; font-weight: 600; font-size: 14px;
        }
        @media (max-width: 480px) { .verification-card { padding: 32px 22px; } h1 { font-size: 22px; } }
    </style>
</head>
<body>
    <main class="verification-card">
        <div class="verification-icon">
            <i class="fa-solid <?= $success ? 'fa-circle-check' : 'fa-triangle-exclamation' ?>"></i>
        </div>
        <h1><?= $success ? 'Email Verified' : 'Verification Unsuccessful' ?></h1>
        <p><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></p>
        <div class="verification-actions">
            <a href="<?= htmlspecialchars(FRONTEND_URL . '/admin-login', ENT_QUOTES, 'UTF-8') ?>">
                <i class="fa-solid fa-arrow-right-to-bracket"></i> Go to Admin Login
            </a>
        </div>
    </main>
</body>
</html>

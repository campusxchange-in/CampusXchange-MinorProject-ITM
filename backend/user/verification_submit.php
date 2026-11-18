<?php
/* POST CX/backend/user/verification_submit.php
 * Allowed only when the profile is 100% complete and the status is
 * "Not Submitted" or "Rejected". Sets status = Pending and notifies admins. */
require_once __DIR__ . '/_user_common.php';
require_once __DIR__ . '/_profile_helpers.php';
cx_require_method('POST');

$c = cx_profile_refresh($conn, $userId);
$r = cx_profile_load($conn, $userId);
$vs = $r['verification_status'] ?? 'Not Submitted';

if ($vs === 'Pending') cx_json_response(false, 'Your verification request is already pending.', [], 409);
if ($vs === 'Approved') cx_json_response(false, 'Your profile is already verified.', [], 409);
if (!$c['complete']) {
    cx_json_response(false, 'Complete your profile to 100% first. Missing: ' . implode(', ', $c['missing']), ['missing' => $c['missing']], 422);
}

$conn->begin_transaction();
try {
    cx_exec($conn,
        "UPDATE User_Verification
         SET verification_status = 'Pending', submitted_at = NOW(),
             rejection_reason = NULL, verified_by = NULL, verified_at = NULL
         WHERE user_id = ? AND deleted_at IS NULL",
        'i', [$userId]);

    $admins = cx_fetch_all($conn, "SELECT admin_id FROM Admins WHERE status = 'Active' AND deleted_at IS NULL");
    foreach ($admins as $a) {
        cx_exec($conn,
            "INSERT INTO Notifications (admin_id, notification_type, title, message, reference_type, reference_id)
             VALUES (?, 'Verification', ?, ?, 'User', ?)",
            'issi',
            [(int)$a['admin_id'], 'New verification request',
             ($r['full_name'] ?? 'A user') . ' submitted a profile for verification.', $userId]);
    }

    cx_notify($conn, $userId, 'Verification', 'Verification submitted',
        'Your profile was submitted. An admin will review it soon.', 'User', $userId);

    $conn->commit();
} catch (Throwable $e) {
    $conn->rollback();
    error_log('verification_submit failed: ' . $e->getMessage());
    cx_json_response(false, 'Could not submit your request. Please try again.', [], 500);
}

cx_json_response(true, 'Submitted for verification.',
    cx_profile_payload(cx_profile_load($conn, $userId), $c));
